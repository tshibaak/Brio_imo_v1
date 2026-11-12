<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-bold">Mes factures et paiements</h1></x-slot>
    <div class="mx-auto max-w-7xl space-y-5 px-4 py-8 sm:px-6 lg:px-8">@include('shared.flash')
        @forelse ($invoices as $invoice)
            <article class="panel">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div><p class="text-xs font-bold uppercase tracking-wider text-teal-700">{{ $invoice->number }}</p><h2 class="mt-1 text-lg font-bold">{{ $invoice->contract->property->name }}</h2><p class="mt-1 text-sm text-slate-500">Période {{ $invoice->period_start->format('d/m/Y') }} — {{ $invoice->period_end->format('d/m/Y') }} · échéance {{ $invoice->due_at->format('d/m/Y') }}</p></div>
                    <div class="text-right"><p class="font-bold">{{ number_format((float) $invoice->totalAmount(), 2) }} {{ $invoice->currency }}</p><p class="mt-1 text-sm text-slate-500">Payé : {{ number_format((float) $invoice->paidAmount(), 2) }}</p><p class="font-semibold">Solde : {{ number_format((float) $invoice->balance(), 2) }}</p><span class="status-badge">{{ $invoice->status }}</span></div>
                </div>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @foreach ($invoice->payments as $payment)
                        <div class="rounded-xl bg-slate-50 p-3 text-sm"><div class="flex justify-between"><strong>{{ number_format((float) $payment->amount, 2) }} {{ $invoice->currency }}</strong><span>{{ $payment->status }}</span></div>@if ($payment->rejection_reason)<p class="mt-1 text-rose-700">{{ $payment->rejection_reason }}</p>@endif @if ($payment->receipt)<a class="mt-2 inline-block font-semibold text-teal-700" href="{{ route('receipts.download', $payment->receipt) }}">Télécharger le reçu</a>@endif</div>
                    @endforeach
                </div>
                @if ((float) $invoice->balance() > 0)
                    <form class="mt-5 grid gap-3 border-t border-slate-100 pt-4 md:grid-cols-5" method="POST" action="{{ route('tenant.payments.store', $invoice) }}" enctype="multipart/form-data">@csrf
                        <label class="field-label">Montant<input class="field-input" type="number" step="0.01" min="0.01" max="{{ $invoice->balance() }}" name="amount" required></label>
                        <label class="field-label">Moyen<select class="field-input" name="method"><option value="mobile_money">Mobile Money</option><option value="bank_transfer">Virement</option><option value="cash">Espèces</option><option value="other">Autre</option></select></label>
                        <label class="field-label">Référence<input class="field-input" name="transaction_reference" maxlength="120"></label>
                        <label class="field-label">Preuve (facultative)<input class="field-input" type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png"></label>
                        <div class="self-end"><button class="action-button w-full">Déclarer le paiement</button></div>
                    </form>
                @endif
            </article>
        @empty
            <p class="empty-state">Aucune facture disponible.</p>
        @endforelse
        {{ $invoices->links() }}
    </div>
</x-app-layout>
