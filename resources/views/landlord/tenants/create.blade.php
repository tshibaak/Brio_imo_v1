<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-bold">Créer un compte locataire</h1></x-slot>
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">@include('shared.flash')
        <form class="panel space-y-5" method="POST" action="{{ route('tenants.store') }}">@csrf
            <p class="text-sm text-slate-600">Si l’adresse appartient déjà à un locataire BRIO, le compte existant sera associé sans en créer un second.</p>
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="field-label">Nom complet<input class="field-input" name="name" required value="{{ old('name') }}"></label>
                <label class="field-label">E-mail<input class="field-input" type="email" name="email" required value="{{ old('email') }}"></label>
                <label class="field-label">Téléphone<input class="field-input" name="phone" value="{{ old('phone') }}"></label>
                <label class="field-label">Adresse<input class="field-input" name="address" value="{{ old('address') }}"></label>
                <label class="field-label">Mot de passe initial<input class="field-input" type="password" name="password" required minlength="12" autocomplete="new-password"></label>
                <label class="field-label">Confirmer le mot de passe<input class="field-input" type="password" name="password_confirmation" required minlength="12" autocomplete="new-password"></label>
            </div>
            <p class="text-xs text-slate-500">Transmettez le mot de passe initial au locataire par un canal privé. Il pourra le modifier depuis son profil.</p>
            <div class="flex justify-end gap-3"><a class="small-button" href="{{ route('tenants.index') }}">Annuler</a><button class="action-button">Créer le compte</button></div>
        </form>
    </div>
</x-app-layout>
