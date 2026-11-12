<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-bold">Signaler un problème</h1></x-slot>
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">@include('shared.flash')
        @if ($contracts->isEmpty())
            <div class="panel">Aucun contrat actif ne permet de créer une demande.</div>
        @else
            <form class="panel space-y-5" method="POST" action="{{ route('tenant.tickets.store') }}" enctype="multipart/form-data">@csrf
                <label class="field-label">Bien concerné<select class="field-input" name="contract_id" required>@foreach ($contracts as $contract)<option value="{{ $contract->id }}">{{ $contract->property->name }} · {{ $contract->reference }}</option>@endforeach</select></label>
                <div class="grid gap-5 sm:grid-cols-2"><label class="field-label">Catégorie<select class="field-input" name="category"><option value="plumbing">Plomberie</option><option value="electrical">Électricité</option><option value="carpentry">Menuiserie</option><option value="security">Sécurité</option><option value="other">Autre</option></select></label><label class="field-label">Titre<input class="field-input" name="title" maxlength="180" required></label></div>
                <label class="field-label">Description<textarea class="field-input" name="description" rows="5" required></textarea></label>
                <label class="field-label">Photos (5 maximum, 5 Mo chacune)<input class="field-input" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple></label>
                <div class="flex justify-end gap-3"><a class="small-button" href="{{ route('tickets.index') }}">Annuler</a><button class="action-button">Envoyer la demande</button></div>
            </form>
        @endif
    </div>
</x-app-layout>
