<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\User;
use App\Notifications\PaymentDecisionNotification;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentReviewService
{
    public function approve(Payment $payment, User $reviewer): Receipt
    {
        return DB::transaction(function () use ($payment, $reviewer): Receipt {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($lockedPayment->invoice_id);
            $invoice->loadMissing('contract');

            abort_unless($invoice->contract->landlord_id === $reviewer->id, 403);
            if ($lockedPayment->status !== 'pending') {
                throw ValidationException::withMessages(['payment' => 'Ce paiement a déjà été traité.']);
            }

            $total = Money::sum($invoice->items()->pluck('amount'));
            $paid = Money::sum($invoice->payments()->where('status', 'approved')->pluck('amount'));
            if (Money::minorUnits($lockedPayment->amount) > $total - $paid) {
                throw ValidationException::withMessages(['payment' => 'Le montant dépasse le solde restant de la facture.']);
            }

            $lockedPayment->update([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            $receipt = Receipt::query()->firstOrCreate(
                ['payment_id' => $lockedPayment->id],
                [
                    'number' => 'REC-'.now()->format('Ymd').'-'.strtoupper(str()->random(8)),
                    'issued_at' => now(),
                ],
            );

            $newPaid = $paid + Money::minorUnits($lockedPayment->amount);
            $invoice->update([
                'status' => $newPaid >= $total ? 'paid' : 'partially_paid',
            ]);

            $invoice->contract->tenant->notify(new PaymentDecisionNotification($lockedPayment, true));

            return $receipt;
        });
    }

    public function reject(Payment $payment, User $reviewer, string $reason): void
    {
        DB::transaction(function () use ($payment, $reviewer, $reason): void {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($lockedPayment->invoice_id);
            $invoice->loadMissing('contract');

            abort_unless($invoice->contract->landlord_id === $reviewer->id, 403);
            if ($lockedPayment->status !== 'pending') {
                throw ValidationException::withMessages(['payment' => 'Ce paiement a déjà été traité.']);
            }

            $lockedPayment->update([
                'status' => 'rejected',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $invoice->contract->tenant->notify(new PaymentDecisionNotification($lockedPayment, false));
        });
    }
}
