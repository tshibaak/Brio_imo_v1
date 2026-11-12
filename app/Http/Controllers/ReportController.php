<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Support\Money;

class ReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'property_id' => ['nullable', 'integer'],
        ]);
        $landlordId = $request->user()->id;
        $invoicesQuery = Invoice::query()
            ->whereHas('contract', function ($query) use ($landlordId, $filters): void {
                $query->where('landlord_id', $landlordId);
                if (! empty($filters['property_id'])) {
                    $query->where('property_id', $filters['property_id']);
                }
            })
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('period_end', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('period_start', '<=', $date))
            ->with(['items', 'payments', 'contract.property', 'contract.tenant']);
        $invoices = $invoicesQuery->orderByDesc('period_start')->get();

        $expenses = Expense::query()->where('landlord_id', $landlordId)
            ->when($filters['property_id'] ?? null, fn ($query, $id) => $query->where('property_id', $id))
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('spent_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('spent_at', '<=', $date))
            ->with('property')
            ->orderByDesc('spent_at')->get();

        return view('landlord.reports.index', [
            'filters' => $filters,
            'properties' => $request->user()->properties()->orderBy('name')->get(),
            'invoices' => $invoices,
            'expenses' => $expenses,
            'expectedByCurrency' => $invoices->groupBy('currency')->map(fn ($rows) => Money::decimal(Money::sum($rows->map(fn ($invoice) => $invoice->totalAmount())))),
            'receivedByCurrency' => $invoices->groupBy('currency')->map(fn ($rows) => Money::decimal(Money::sum($rows->map(fn ($invoice) => $invoice->paidAmount())))),
            'outstandingByCurrency' => $invoices->groupBy('currency')->map(fn ($rows) => Money::decimal(Money::sum($rows->map(fn ($invoice) => $invoice->balance())))),
            'expensesByCurrency' => $expenses->groupBy('currency')->map(fn ($rows) => Money::decimal(Money::sum($rows->pluck('amount')))),
        ]);
    }
}
