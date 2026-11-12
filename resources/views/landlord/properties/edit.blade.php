<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-bold text-slate-900">Modifier {{ $property->name }}</h1></x-slot>
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">@include('shared.flash')<form class="panel space-y-5" method="POST" action="{{ route('properties.update', $property) }}">@csrf @method('PUT') @include('landlord.properties.form')<div class="flex justify-end gap-3"><a class="small-button" href="{{ route('properties.index') }}">Annuler</a><button class="action-button">Enregistrer</button></div></form></div>
</x-app-layout>
