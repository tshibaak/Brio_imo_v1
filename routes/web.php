<?php

use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractDocumentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TicketController;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'active'])->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'active', 'role:landlord'])->group(function (): void {
    Route::resource('properties', PropertyController::class)->except(['destroy']);
    Route::delete('properties/{property}', [PropertyController::class, 'destroy'])->name('properties.destroy');
    Route::resource('tenants', TenantController::class)->only(['index', 'create', 'store']);
    Route::resource('contracts', ContractController::class)->only(['index', 'create', 'store']);
    Route::patch('contracts/{contract}/status', [ContractController::class, 'status'])->name('contracts.status');
    Route::post('contracts/{contract}/documents', [ContractDocumentController::class, 'store'])->name('contracts.documents.store');
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
    Route::post('payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
    Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::get('expenses/{expense}/receipt', [ExpenseController::class, 'receipt'])->name('expenses.receipt');
    Route::get('reports', ReportController::class)->name('reports.index');
    Route::patch('tickets/{ticket}/status', [TicketController::class, 'status'])->name('tickets.status');
    Route::post('tickets/{ticket}/expenses', [TicketController::class, 'expense'])->name('tickets.expenses.store');
});

Route::middleware(['auth', 'active', 'role:tenant'])->group(function (): void {
    Route::get('tenant/invoices', function (Request $request) {
        $invoices = Invoice::query()
            ->whereHas('contract', fn ($query) => $query->where('tenant_id', $request->user()->id))
            ->with(['items', 'payments', 'contract.property'])
            ->orderByDesc('due_at')->paginate(20);

        return view('tenant.invoices.index', compact('invoices'));
    })->name('tenant.invoices.index');
    Route::post('tenant/invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('tenant.payments.store');
    Route::get('tenant/tickets/create', [TicketController::class, 'create'])->name('tenant.tickets.create');
    Route::post('tenant/tickets', [TicketController::class, 'store'])->name('tenant.tickets.store');
    Route::post('tickets/{ticket}/confirm', [TicketController::class, 'confirm'])->name('tickets.confirm');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('tickets/{ticket}/comments', [TicketController::class, 'comment'])->name('tickets.comments.store');
    Route::get('ticket-attachments/{attachment}', [TicketController::class, 'attachment'])->name('tickets.attachments.download');
    Route::get('contract-documents/{document}', [ContractDocumentController::class, 'download'])->name('contracts.documents.download');
    Route::get('payment-proofs/{proof}', [PaymentController::class, 'proof'])->name('payments.proofs.download');
    Route::get('receipts/{receipt}', [PaymentController::class, 'receipt'])->name('receipts.download');
});

require __DIR__.'/auth.php';
