<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-bold">Dépenses immobilières</h1></x-slot>
    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 lg:px-8 lg:grid-cols-[360px_1fr]">@include('shared.flash')
        <form class="panel h-fit space-y-4" method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data">@csrf<h2 class="section-title">Ajouter une dépense</h2>
            <label class="field-label">Bien<select class="field-input" name="property_id" required>@foreach ($properties as $property)<option value="{{ $property->id }}">{{ $property->name }}</option>@endforeach</select></label>
            <label class="field-label">Catégorie<select class="field-input" name="category"><option value="repair">Réparation</option><option value="maintenance">Entretien</option><option value="utilities">Charges</option><option value="other">Autre</option></select></label>
            <label class="field-label">Description<input class="field-input" name="description" required maxlength="2000"></label>
            <div class="grid grid-cols-2 gap-2"><label class="field-label">Montant<input class="field-input" type="number" step=".01" min=".01" name="amount" required></label><label class="field-label">Devise<input class="field-input uppercase" name="currency" maxlength="3" value="USD" required></label></div>
            <label class="field-label">Date<input class="field-input" type="date" name="spent_at" value="{{ today()->toDateString() }}" required></label>
            <label class="field-label">Justificatif<input class="field-input" type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png"></label>
            <button class="action-button w-full">Enregistrer la dépense</button>
        </form>
        <section class="panel data-table-wrap"><h2 class="section-title mb-4">Historique</h2><table class="data-table"><thead><tr><th>Date</th><th>Bien</th><th>Catégorie</th><th>Description</th><th>Montant</th></tr></thead><tbody>
            @forelse ($expenses as $expense)<tr><td>{{ $expense->spent_at->format('d/m/Y') }}</td><td>{{ $expense->property->name }}</td><td>{{ $expense->category }}</td><td>{{ $expense->description }} @if ($expense->receipt_path)<a class="block text-xs font-semibold text-teal-700" href="{{ route('expenses.receipt', $expense) }}">Télécharger le justificatif</a>@endif</td><td class="font-semibold">{{ number_format((float) $expense->amount, 2) }} {{ $expense->currency }}</td></tr>@empty<tr><td colspan="5" class="empty-state">Aucune dépense enregistrée.</td></tr>@endforelse
        </tbody></table>{{ $expenses->links() }}</section>
    </div>
</x-app-layout>
