<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-bold">Créer un contrat</h1></x-slot>
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">@include('shared.flash')
        @if ($properties->isEmpty() || $tenants->isEmpty())
            <div class="panel"><p class="text-slate-700">Pour créer un contrat, ajoutez au moins un bien et un locataire.</p><div class="mt-4 flex gap-3"><a class="action-button" href="{{ route('properties.create') }}">Ajouter un bien</a><a class="small-button" href="{{ route('tenants.create') }}">Créer un locataire</a></div></div>
        @else
            <form class="panel space-y-5" method="POST" action="{{ route('contracts.store') }}">@csrf
                <div class="grid gap-5 sm:grid-cols-2">
                    <label class="field-label">Référence<input class="field-input" name="reference" required maxlength="80" value="{{ old('reference') }}"></label>
                    <label class="field-label">Statut initial<select class="field-input" name="status"><option value="draft">Brouillon</option><option value="active" @selected(old('status') === 'active')>Actif</option></select></label>
                    <label class="field-label">Bien<select class="field-input" name="property_id" required>@foreach ($properties as $property)<option value="{{ $property->id }}" @selected(old('property_id') == $property->id)>{{ $property->reference }} — {{ $property->name }}</option>@endforeach</select></label>
                    <label class="field-label">Locataire<select class="field-input" name="tenant_id" required>@foreach ($tenants as $tenant)<option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>{{ $tenant->name }} ({{ $tenant->email }})</option>@endforeach</select></label>
                    <label class="field-label">Début<input class="field-input" type="date" name="starts_at" required value="{{ old('starts_at', today()->toDateString()) }}"></label>
                    <label class="field-label">Fin (facultative)<input class="field-input" type="date" name="ends_at" value="{{ old('ends_at') }}"></label>
                    <label class="field-label">Périodicité<select class="field-input" name="periodicity"><option value="monthly">Mensuelle</option><option value="quarterly">Trimestrielle</option><option value="yearly">Annuelle</option></select></label>
                    <label class="field-label">Jour d’échéance<input class="field-input" type="number" name="due_day" min="1" max="31" required value="{{ old('due_day', 5) }}"><span class="text-xs font-normal text-slate-500">Ajusté au dernier jour lorsque le mois est plus court.</span></label>
                    <label class="field-label">Loyer<input class="field-input" type="number" step="0.01" min="0.01" name="rent_amount" required value="{{ old('rent_amount') }}"></label>
                    <label class="field-label">Charges<input class="field-input" type="number" step="0.01" min="0" name="charges_amount" required value="{{ old('charges_amount', 0) }}"></label>
                    <label class="field-label">Garantie locative<input class="field-input" type="number" step="0.01" min="0" name="deposit_amount" required value="{{ old('deposit_amount', 0) }}"></label>
                    <label class="field-label">Devise (code ISO 4217)<input class="field-input uppercase" name="currency" maxlength="3" pattern="[A-Z]{3}" required value="{{ old('currency', 'USD') }}"></label>
                    <label class="field-label sm:col-span-2">Conditions particulières<textarea class="field-input" name="terms" rows="4">{{ old('terms') }}</textarea></label>
                </div>
                <p class="text-xs text-slate-500">Les factures sont émises par périodes ancrées à la date de début du contrat. Un contrat actif génère les échéances déjà ouvertes.</p>
                <div class="flex justify-end gap-3"><a class="small-button" href="{{ route('contracts.index') }}">Annuler</a><button class="action-button">Créer le contrat</button></div>
            </form>
        @endif
    </div>
</x-app-layout>
