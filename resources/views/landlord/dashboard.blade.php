<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-bold text-slate-900">Tableau de bord bailleur</h1></x-slot>
    <div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
        @include('shared.flash')
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Biens', $propertyCount, $occupiedCount.' occupé(s)'],
                ['Locataires', $tenantCount, 'Comptes associés'],
                ['Paiements à vérifier', $pendingPayments, 'Déclarations en attente'],
                ['Demandes ouvertes', $openTickets, 'Interventions non clôturées'],
            ] as [$label, $value, $hint])
                <div class="stat-card"><p class="text-sm font-medium text-slate-500">{{ $label }}</p><p class="mt-2 text-2xl font-bold text-slate-900">{{ $value }}</p><p class="mt-1 text-xs text-slate-500">{{ $hint }}</p></div>
            @endforeach
        </div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([['Attendu', $expectedByCurrency], ['Encaissé', $receivedByCurrency], ['Solde restant', $outstandingByCurrency], ['Dépenses', $expensesByCurrency]] as [$label, $amounts])
                <article class="stat-card"><p class="text-sm font-medium text-slate-500">{{ $label }}</p>@forelse ($amounts as $currency => $amount)<p class="mt-2 text-xl font-bold text-slate-900">{{ number_format((float) $amount, 2) }} {{ $currency }}</p>@empty<p class="mt-2 text-xl font-bold text-slate-900">0.00</p>@endforelse</article>
            @endforeach
        </div>
        <div class="grid gap-6 lg:grid-cols-[1fr_auto]">
            <section class="panel">
                <div class="mb-5 flex items-center justify-between"><h2 class="section-title">Prochaines échéances</h2><a class="text-sm font-semibold text-teal-700" href="{{ route('reports.index') }}">Voir les rapports →</a></div>
                @forelse ($upcomingInvoices as $invoice)
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 py-4">
                        <div><p class="font-semibold">{{ $invoice->contract->tenant->name }} · {{ $invoice->contract->property->name }}</p><p class="text-sm text-slate-500">{{ $invoice->number }} · échéance {{ $invoice->due_at->format('d/m/Y') }}</p></div>
                        <strong>{{ number_format((float) $invoice->balance(), 2) }} {{ $invoice->currency }}</strong>
                    </div>
                @empty
                    <p class="empty-state">Aucune échéance à venir. Créez un contrat actif pour commencer.</p>
                @endforelse
            </section>
            <section class="panel lg:w-72">
                <h2 class="section-title">Actions rapides</h2>
                <div class="mt-4 grid gap-2">
                    <a class="action-link" href="{{ route('properties.create') }}">＋ Ajouter un bien</a>
                    <a class="action-link" href="{{ route('tenants.create') }}">＋ Créer un locataire</a>
                    <a class="action-link" href="{{ route('contracts.create') }}">＋ Nouveau contrat</a>
                    <a class="action-link" href="{{ route('payments.index') }}">Vérifier les paiements</a>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
