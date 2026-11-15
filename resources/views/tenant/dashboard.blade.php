<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-bold text-slate-900">Mon espace locataire</h1></x-slot>
    <div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
        @include('shared.flash')
        <div class="grid gap-4 md:grid-cols-3">
            <div class="stat-card"><p class="text-sm text-slate-500">Solde restant dû</p>@forelse ($balanceByCurrency as $currency => $balance)<p class="mt-2 text-2xl font-bold">{{ number_format((float) $balance, 2) }} {{ $currency }}</p>@empty<p class="mt-2 text-3xl font-bold">0.00</p>@endforelse<p class="mt-1 text-xs text-slate-500">Les devises restent séparées</p></div>
            <div class="stat-card"><p class="text-sm text-slate-500">Contrats actifs</p><p class="mt-2 text-3xl font-bold">{{ $contracts->count() }}</p><p class="mt-1 text-xs text-slate-500">Biens actuellement loués</p></div>
            <div class="stat-card"><p class="text-sm text-slate-500">Notifications non lues</p><p class="mt-2 text-3xl font-bold">{{ $unreadNotifications }}</p><p class="mt-1 text-xs text-slate-500">Paiements et interventions</p></div>
        </div>
        <section class="panel">
            <h2 class="section-title">Mes notifications</h2>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse ($notifications as $notification)
                    <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="break-words text-sm {{ $notification->read_at ? 'text-slate-500' : 'font-semibold text-slate-900' }}">{{ $notification->data['message'] ?? 'Nouvelle notification' }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $notification->created_at->format('d/m/Y H:i') }} · {{ $notification->read_at ? 'Lue' : 'Non lue' }}</p>
                        </div>
                        @unless ($notification->read_at)
                            <form method="POST" action="{{ route('tenant.notifications.read', $notification) }}">
                                @csrf
                                <button class="small-button w-full sm:w-auto">Marquer comme lue</button>
                            </form>
                        @endunless
                    </div>
                @empty
                    <p class="empty-state mt-4">Aucune notification pour le moment.</p>
                @endforelse
            </div>
            {{ $notifications->links() }}
        </section>
        <section class="panel">
            <div class="mb-5 flex items-center justify-between"><h2 class="section-title">Mes biens et contrats</h2><a class="text-sm font-semibold text-teal-700" href="{{ route('tenant.invoices.index') }}">Voir mes factures →</a></div>
            <div class="grid gap-4 md:grid-cols-2">
                @forelse ($contracts as $contract)
                    <div class="rounded-2xl border border-slate-200 p-5">
                        <div class="flex items-start justify-between"><div><p class="font-bold">{{ $contract->property->name }}</p><p class="mt-1 text-sm text-slate-500">{{ $contract->property->address }}</p></div><span class="status-badge">{{ $contract->status }}</span></div>
                        <p class="mt-4 text-sm">Contrat {{ $contract->reference }} · {{ number_format((float) $contract->rent_amount, 2) }} {{ $contract->currency }} / {{ $contract->periodicity === 'monthly' ? 'mois' : $contract->periodicity }}</p>
                        <p class="mt-1 text-xs text-slate-500">Du {{ $contract->starts_at->format('d/m/Y') }}{{ $contract->ends_at ? ' au '.$contract->ends_at->format('d/m/Y') : '' }}</p>
                    </div>
                @empty
                    <p class="empty-state md:col-span-2">Aucun contrat actif n’est encore associé à votre compte.</p>
                @endforelse
            </div>
        </section>
        <section class="panel">
            <div class="mb-5 flex items-center justify-between"><h2 class="section-title">Factures récentes</h2><a class="text-sm font-semibold text-teal-700" href="{{ route('tenant.invoices.index') }}">Tout afficher →</a></div>
            @forelse ($invoices->sortByDesc('due_at')->take(5) as $invoice)
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 py-4">
                    <div><p class="font-semibold">{{ $invoice->contract->property->name }}</p><p class="text-sm text-slate-500">{{ $invoice->number }} · échéance {{ $invoice->due_at->format('d/m/Y') }}</p></div>
                    <span class="font-bold">{{ number_format((float) $invoice->balance(), 2) }} {{ $invoice->currency }} restant</span>
                </div>
            @empty
                <p class="empty-state">Aucune facture disponible.</p>
            @endforelse
            <a class="action-button mt-5 inline-flex" href="{{ route('tenant.tickets.create') }}">Signaler un problème</a>
        </section>
    </div>
</x-app-layout>
