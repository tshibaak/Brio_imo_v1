<x-app-layout>
    <x-slot name="header"><div class="flex flex-wrap items-center justify-between gap-3"><h1 class="text-2xl font-bold text-slate-900">Mes biens</h1><a href="{{ route('properties.create') }}" class="action-button">Ajouter un bien</a></div></x-slot>
    <div class="mx-auto max-w-7xl space-y-5 px-4 py-8 sm:px-6 lg:px-8">
        @include('shared.flash')
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($properties as $property)
                <article class="panel">
                    <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-wider text-teal-700">{{ $property->reference }}</p><h2 class="mt-1 text-lg font-bold">{{ $property->name }}</h2></div><span class="status-badge">{{ ['available' => 'Disponible', 'occupied' => 'Occupé', 'maintenance' => 'Maintenance'][$property->status] ?? $property->status }}</span></div>
                    <p class="mt-3 text-sm text-slate-500">{{ $property->address }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ ucfirst($property->property_type) }} · {{ $property->bedrooms ?? '—' }} ch. · {{ $property->bathrooms ?? '—' }} sdb</p>
                    <p class="mt-3 text-xs text-slate-500">{{ $property->contracts_count }} contrat(s) dans l’historique</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <a class="small-button" href="{{ route('properties.show', $property) }}">Détails</a>
                        <a class="small-button" href="{{ route('properties.edit', $property) }}">Modifier</a>
                        @if ($property->contracts_count === 0)
                            <form method="POST" action="{{ route('properties.destroy', $property) }}" onsubmit="return confirm('Archiver ce bien ?')">@csrf @method('DELETE')<button class="small-button text-rose-700">Archiver</button></form>
                        @endif
                    </div>
                </article>
            @empty
                <p class="empty-state md:col-span-2 xl:col-span-3">Aucun bien enregistré. Ajoutez votre premier bien pour démarrer.</p>
            @endforelse
        </div>
        {{ $properties->links() }}
    </div>
</x-app-layout>
