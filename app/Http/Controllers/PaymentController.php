<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\Receipt;
use App\Services\PaymentReviewService;
use App\Notifications\PaymentDeclaredNotification;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        return view('landlord.payments.index', [
            'payments' => Payment::query()
                ->whereHas('invoice.contract', fn ($query) => $query->where('landlord_id', $request->user()->id))
                ->with(['invoice.contract.tenant', 'invoice.contract.property', 'proofs', 'receipt'])
                ->latest()->paginate(20),
        ]);
    }

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $allowedInvoice = Invoice::query()
            ->whereKey($invoice->id)
            ->whereHas('contract', fn ($query) => $query->where('tenant_id', $request->user()->id))
            ->with('contract.landlord')
            ->firstOrFail();

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999'],
            'method' => ['required', 'in:mobile_money,bank_transfer,cash,other'],
            'transaction_reference' => ['nullable', 'string', 'max:120'],
            'proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $payment = DB::transaction(function () use ($request, $allowedInvoice, $data): Payment {
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($allowedInvoice->id);
            $total = Money::sum($lockedInvoice->items()->pluck('amount'));
            $paid = Money::sum($lockedInvoice->payments()->where('status', 'approved')->pluck('amount'));
            $pending = Money::sum($lockedInvoice->payments()->where('status', 'pending')->pluck('amount'));
            abort_if(Money::minorUnits($data['amount']) > $total - $paid - $pending, 422, 'Le montant déclaré dépasse le solde non engagé.');

            $payment = $lockedInvoice->payments()->create([
                'declared_by' => $request->user()->id,
                'amount' => $data['amount'],
                'method' => $data['method'],
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'status' => 'pending',
            ]);

            if ($request->hasFile('proof')) {
                $file = $request->file('proof');
                $path = $file->store('payment-proofs', 'local');
                $payment->proofs()->create([
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                    'size' => $file->getSize(),
                ]);
            }

            return $payment;
        });

        $allowedInvoice->contract->landlord->notify(new PaymentDeclaredNotification($payment));

        return redirect()->route('tenant.invoices.index')->with('success', 'Votre déclaration de paiement a été envoyée.');
    }

    public function approve(Request $request, Payment $payment, PaymentReviewService $reviewService): RedirectResponse
    {
        $receipt = $reviewService->approve($payment, $request->user());

        return redirect()->route('payments.index')->with('success', 'Paiement validé. Reçu '.$receipt->number.' créé.');
    }

    public function reject(Request $request, Payment $payment, PaymentReviewService $reviewService): RedirectResponse
    {
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:2000']]);
        $reviewService->reject($payment, $request->user(), $data['rejection_reason']);

        return redirect()->route('payments.index')->with('success', 'Paiement rejeté et locataire notifié.');
    }

    public function proof(Request $request, PaymentProof $proof): StreamedResponse
    {
        $proof->loadMissing('payment.invoice.contract');
        $contract = $proof->payment->invoice->contract;
        abort_unless(
            $request->user()->id === $contract->landlord_id
            || $request->user()->id === $contract->tenant_id,
            404,
        );

        return Storage::disk('local')->download($proof->path, $proof->original_name);
    }

    public function receipt(Request $request, Receipt $receipt): StreamedResponse
    {
        $receipt->loadMissing('payment.invoice.contract.landlord', 'payment.invoice.contract.tenant', 'payment.invoice.contract.property');
        $payment = $receipt->payment;
        $contract = $payment->invoice->contract;
        abort_unless(
            $payment->status === 'approved'
            && in_array($request->user()->id, [$contract->landlord_id, $contract->tenant_id], true),
            404,
        );

        if (! $receipt->path || ! Storage::disk('local')->exists($receipt->path)) {
            $pdf = Pdf::loadView('receipts.pdf', compact('receipt', 'payment', 'contract'));
            $path = 'receipts/'.$receipt->number.'.pdf';
            Storage::disk('local')->put($path, $pdf->output());
            $receipt->update(['path' => $path]);
        }

        return Storage::disk('local')->download($receipt->path, $receipt->number.'.pdf');
    }
}
