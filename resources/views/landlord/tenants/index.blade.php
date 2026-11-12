<x-app-layout>
    <x-slot name="header"><div class="flex items-center justify-between"><h1 class="text-2xl font-bold">Mes locataires</h1><a class="action-button" href="{{ route('tenants.create') }}">Créer un locataire</a></div></x-slot>
    <div class="mx-auto max-w-7xl space-y-5 px-4 py-8 sm:px-6 lg:px-8">@include('shared.flash')
        <div class="panel data-table-wrap"><table class="data-table"><thead><tr><th>Locataire</th><th>E-mail</th><th>Téléphone</th><th>Contrats</th><th>Compte</th></tr></thead><tbody>
            @forelse ($tenants as $tenant)<tr><td class="font-semibold">{{ $tenant->name }}</td><td>{{ $tenant->email }}</td><td>{{ $tenant->phone ?: '—' }}</td><td>{{ $tenant->landlord_contracts_count }}</td><td>{{ $tenant->is_active ? 'Actif' : 'Désactivé' }}</td></tr>@empty<tr><td colspan="5" class="empty-state">Aucun locataire. Ajoutez un compte pour pouvoir créer un contrat.</td></tr>@endforelse
        </tbody></table>{{ $tenants->links() }}</div>
    </div>
</x-app-layout>
