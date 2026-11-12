<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-bold">Rapports financiers</h1></x-slot>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">@include('shared.flash')
        <form class="panel grid gap-4 sm:grid-cols-4" method="GET" action="{{ route('reports.index') }}">
            <label class="field-label">Du<input class="field-input" type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label>
            <label class="field-label">Au<input class="field-input" type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
            <label class="field-label">Bien<select class="field-input" name="property_id"><option value="">Tous les biens</option>@foreach ($properties as $property)<option value="{{ $property->id }}" @selected(($filters['property_id'] ?? '') == $property->id)>{{ $property->name }}</option>@endforeach</select></label>
            <div class="self-end"><button class="action-button w-full">Filtrer</button></div>
        </form>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([['Attendu', $expectedByCurrency], ['Encaissé (validé)', $receivedByCurrency], ['Solde restant', $outstandingByCurrency], ['Dépenses', $expensesByCurrency]] as [$label, $amounts])
                <article class="stat-card"><p class="text-sm text-slate-500">{{ $label }}</p>@forelse ($amounts as $currency => $amount)<p class="mt-2 text-xl font-bold">{{ number_format((float) $amount, 2) }} {{ $currency }}</p>@empty<p class="mt-2 text-xl font-bold">0.00</p>@endforelse</article>
            @endforeach
        </div>
        <section class="panel data-table-wrap"><h2 class="section-title mb-4">Factures et recettes validées</h2><table class="data-table"><thead><tr><th>Période</th><th>Bien / locataire</th><th>Échéance</th><th>Attendu</th><th>Encaissé</th><th>Solde</th></tr></thead><tbody>
            @forelse ($invoices as $invoice)<tr><td>{{ $invoice->period_start->format('d/m/Y') }} — {{ $invoice->period_end->format('d/m/Y') }}</td><td>{{ $invoice->contract->property->name }}<span class="block text-xs text-slate-500">{{ $invoice->contract->tenant->name }}</span></td><td>{{ $invoice->due_at->format('d/m/Y') }}</td><td>{{ number_format((float) $invoice->totalAmount(), 2) }} {{ $invoice->currency }}</td><td>{{ number_format((float) $invoice->paidAmount(), 2) }} {{ $invoice->currency }}</td><td class="font-semibold">{{ number_format((float) $invoice->balance(), 2) }} {{ $invoice->currency }}</td></tr>@empty<tr><td colspan="6" class="empty-state">Aucune facture sur cette période.</td></tr>@endforelse
        </tbody></table></section>
        <section class="panel data-table-wrap"><h2 class="section-title mb-4">Dépenses</h2><table class="data-table"><thead><tr><th>Date</th><th>Bien</th><th>Description</th><th>Montant</th></tr></thead><tbody>
            @forelse ($expenses as $expense)<tr><td>{{ $expense->spent_at->format('d/m/Y') }}</td><td>{{ $expense->property->name }}</td><td>{{ $expense->description }}</td><td>{{ number_format((float) $expense->amount, 2) }} {{ $expense->currency }}</td></tr>@empty<tr><td colspan="4" class="empty-state">Aucune dépense sur cette période.</td></tr>@endforelse
        </tbody></table></section>
        <p class="text-xs text-slate-500">Les revenus encaissés sont calculés uniquement à partir des paiements validés. Les devises restent séparées : aucune conversion n’est appliquée.</p>
    </div>
</x-app-layout>
