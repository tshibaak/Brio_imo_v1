<x-app-layout>
    <x-slot name="header"><div class="flex items-center justify-between"><h1 class="text-2xl font-bold">Mes demandes</h1><a class="action-button" href="{{ route('tenant.tickets.create') }}">Signaler un problème</a></div></x-slot>
    <div class="mx-auto max-w-7xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">@include('shared.flash')
        @forelse ($tickets as $ticket)
            <a class="panel block transition hover:border-teal-300" href="{{ route('tickets.show', $ticket) }}">
                <div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-wider text-teal-700">{{ ucfirst($ticket->category) }} · {{ $ticket->property->name }}</p><h2 class="mt-1 font-bold">{{ $ticket->title }}</h2><p class="mt-1 text-sm text-slate-500">{{ $ticket->created_at->format('d/m/Y') }}</p></div><span class="status-badge">{{ str_replace('_', ' ', $ticket->status) }}</span></div>
            </a>
        @empty
            <p class="empty-state">Vous n’avez pas encore signalé de problème.</p>
        @endforelse
        {{ $tickets->links() }}
    </div>
</x-app-layout>
