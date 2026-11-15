<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-bold">Paiements à suivre</h1></x-slot>
    <div class="mx-auto max-w-7xl space-y-5 px-4 py-8 sm:px-6 lg:px-8">@include('shared.flash')
        @forelse ($payments as $payment)
            <article class="panel">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div><p class="text-xs font-bold uppercase tracking-wider text-teal-700">{{ $payment->invoice->number }}</p><h2 class="mt-1 text-lg font-bold">{{ $payment->invoice->contract->tenant->name }} · {{ $payment->invoice->contract->property->name }}</h2><p class="mt-1 text-sm text-slate-500">{{ $payment->created_at->format('d/m/Y H:i') }} · {{ str_replace('_', ' ', $payment->method) }}{{ $payment->transaction_reference ? ' · Réf. '.$payment->transaction_reference : '' }}</p></div>
                    <div class="text-right"><p class="text-xl font-bold">{{ number_format((float) $payment->amount, 2) }} {{ $payment->invoice->currency }}</p><span class="status-badge">{{ $payment->status }}</span></div>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    @foreach ($payment->proofs as $proof)<a class="small-button" href="{{ route('payments.proofs.download', $proof) }}">Télécharger la preuve</a>@endforeach
                    @if ($payment->receipt)<a class="small-button" href="{{ route('receipts.download', $payment->receipt) }}">Télécharger le reçu</a>@endif
                </div>
                @if ($payment->status === 'pending')
                    <div class="mt-5 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-[auto_1fr]">
                        <form method="POST" action="{{ route('payments.approve', $payment) }}">@csrf<button class="action-button">Valider le paiement</button></form>
                        <form class="flex flex-col gap-2 sm:flex-row" method="POST" action="{{ route('payments.reject', $payment) }}">@csrf<label class="sr-only" for="reason-{{ $payment->id }}">Motif du rejet</label><input id="reason-{{ $payment->id }}" class="field-input min-w-0 flex-1" name="rejection_reason" required placeholder="Motif du rejet"><button class="small-button text-rose-700">Rejeter</button></form>
                    </div>
                @elseif ($payment->status === 'rejected')
                    <p class="mt-3 text-sm text-rose-700">Motif : {{ $payment->rejection_reason }}</p>
                @endif
            </article>
        @empty
            <p class="empty-state">Aucun paiement déclaré pour le moment.</p>
        @endforelse
        {{ $payments->links() }}
    </div>
</x-app-layout>
