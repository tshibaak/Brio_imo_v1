<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\MaintenanceTicket;
use App\Models\Payment;
use App\Models\Property;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if ($user->isTenant()) {
            $contracts = $user->contractsAsTenant()
                ->where('status', 'active')
                ->with('property')
                ->get();
            $invoices = Invoice::query()
                ->whereIn('contract_id', $user->contractsAsTenant()->select('id'))
                ->with(['items', 'payments'])
                ->orderBy('due_at')
                ->get();

            return view('tenant.dashboard', [
                'contracts' => $contracts,
                'invoices' => $invoices,
                'balanceByCurrency' => $invoices->groupBy('currency')->map(fn ($rows) => Money::decimal(Money::sum($rows->map(fn ($invoice) => $invoice->balance())))),
                'unreadNotifications' => $user->unreadNotifications()->count(),
            ]);
        }

        $invoices = Invoice::query()
            ->whereHas('contract', fn ($query) => $query->where('landlord_id', $user->id))
            ->with(['items', 'payments'])
            ->get();
        $receivedByCurrency = $invoices->groupBy('currency')
            ->map(fn ($rows) => Money::decimal(Money::sum($rows->map(fn ($invoice) => $invoice->paidAmount()))));

        return view('landlord.dashboard', [
            'propertyCount' => Property::query()->where('landlord_id', $user->id)->count(),
            'occupiedCount' => Property::query()->where('landlord_id', $user->id)->where('status', 'occupied')->count(),
            'availableCount' => Property::query()->where('landlord_id', $user->id)->where('status', 'available')->count(),
            'tenantCount' => $user->tenants()->count(),
            'expectedByCurrency' => $invoices->groupBy('currency')->map(fn ($rows) => Money::decimal(Money::sum($rows->map(fn ($invoice) => $invoice->totalAmount())))),
            'outstandingByCurrency' => $invoices->groupBy('currency')->map(fn ($rows) => Money::decimal(Money::sum($rows->map(fn ($invoice) => $invoice->balance())))),
            'receivedByCurrency' => $receivedByCurrency,
            'pendingPayments' => Payment::query()
                ->where('status', 'pending')
                ->whereHas('invoice.contract', fn ($query) => $query->where('landlord_id', $user->id))
                ->count(),
            'openTickets' => MaintenanceTicket::query()->where('landlord_id', $user->id)->whereNotIn('status', ['closed'])->count(),
            'expensesByCurrency' => Expense::query()->where('landlord_id', $user->id)->get()->groupBy('currency')->map(fn ($rows) => Money::decimal(Money::sum($rows->pluck('amount')))),
            'upcomingInvoices' => $invoices->where('due_at', '>=', today())->sortBy('due_at')->take(5),
        ]);
    }
}
