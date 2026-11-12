<x-app-layout>
    <x-slot name="header"><div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-sm text-slate-500">{{ $ticket->property->name }} · {{ ucfirst($ticket->category) }}</p><h1 class="text-2xl font-bold">{{ $ticket->title }}</h1></div><span class="status-badge">{{ str_replace('_', ' ', $ticket->status) }}</span></div></x-slot>
    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 lg:px-8 lg:grid-cols-[1fr_320px]">@include('shared.flash')
        <div class="space-y-6">
            <section class="panel"><h2 class="section-title">Description</h2><p class="mt-3 whitespace-pre-line text-slate-700">{{ $ticket->description }}</p><p class="mt-4 text-xs text-slate-500">Signalé par {{ $ticket->tenant->name }} le {{ $ticket->created_at->format('d/m/Y H:i') }}</p>
                @if ($ticket->attachments->isNotEmpty())<div class="mt-4 flex flex-wrap gap-2">@foreach ($ticket->attachments as $attachment)<a class="small-button" href="{{ route('tickets.attachments.download', $attachment) }}">{{ $attachment->original_name }}</a>@endforeach</div>@endif
                @foreach ($ticket->assignments as $assignment)<p class="mt-4 text-sm"><strong>Responsable :</strong> {{ $assignment->responsible_name }} · {{ $assignment->responsible_email }} {{ $assignment->responsible_phone }}</p>@endforeach
            </section>
            <section class="panel"><h2 class="section-title">Historique</h2><div class="mt-4 space-y-4">@forelse ($ticket->events as $event)<div class="border-l-2 border-teal-300 pl-4"><p class="font-semibold">{{ $event->from_status ? str_replace('_', ' ', $event->from_status).' → ' : '' }}{{ str_replace('_', ' ', $event->to_status) }}</p><p class="text-xs text-slate-500">{{ $event->user->name }} · {{ $event->created_at->format('d/m/Y H:i') }}</p>@if ($event->note)<p class="mt-1 text-sm">{{ $event->note }}</p>@endif</div>@empty<p class="text-sm text-slate-500">Aucun événement.</p>@endforelse</div></section>
            <section class="panel"><h2 class="section-title">Échanges</h2><div class="mt-4 space-y-3">@forelse ($ticket->comments as $comment)<div class="rounded-xl bg-slate-50 p-3"><p class="whitespace-pre-line text-sm">{{ $comment->body }}</p><p class="mt-2 text-xs text-slate-500">{{ $comment->user->name }} · {{ $comment->created_at->format('d/m/Y H:i') }}</p></div>@empty<p class="text-sm text-slate-500">Aucun commentaire.</p>@endforelse</div>
                <form class="mt-4 flex gap-2" method="POST" action="{{ route('tickets.comments.store', $ticket) }}">@csrf<textarea class="field-input" name="body" rows="2" required placeholder="Ajouter un commentaire"></textarea><button class="small-button self-end">Envoyer</button></form>
            </section>
        </div>
        <aside class="space-y-6">
            @if (Auth::user()->isLandlord() && $ticket->status !== 'closed')
                <section class="panel"><h2 class="section-title">Mettre à jour</h2><form class="mt-4 space-y-3" method="POST" action="{{ route('tickets.status', $ticket) }}">@csrf @method('PATCH')
                    <label class="field-label">Statut<select class="field-input" name="status"><option value="acknowledged">Prise en charge</option><option value="in_progress">En cours</option><option value="awaiting_tenant_confirmation">À confirmer par le locataire</option></select></label>
                    <label class="field-label">Commentaire<textarea class="field-input" name="comment" rows="3"></textarea></label>
                    <p class="border-t border-slate-100 pt-3 text-xs text-slate-500">Responsable externe (optionnel)</p>
                    <label class="field-label">Nom<input class="field-input" name="responsible_name"></label><label class="field-label">E-mail<input class="field-input" type="email" name="responsible_email"></label><label class="field-label">Téléphone<input class="field-input" name="responsible_phone"></label>
                    <button class="action-button w-full">Enregistrer</button>
                </form></section>
                <section class="panel"><h2 class="section-title">Dépense liée</h2><form class="mt-4 space-y-3" method="POST" action="{{ route('tickets.expenses.store', $ticket) }}">@csrf
                    <label class="field-label">Catégorie<select class="field-input" name="category"><option value="repair">Réparation</option><option value="maintenance">Entretien</option><option value="utilities">Charges</option><option value="other">Autre</option></select></label>
                    <label class="field-label">Description<input class="field-input" name="description" required></label><div class="grid grid-cols-2 gap-2"><label class="field-label">Montant<input class="field-input" type="number" step=".01" min=".01" name="amount" required></label><label class="field-label">Devise<input class="field-input uppercase" name="currency" maxlength="3" value="{{ $ticket->contract->currency }}" required></label></div>
                    <label class="field-label">Date<input class="field-input" type="date" name="spent_at" value="{{ today()->toDateString() }}" required></label><button class="small-button w-full">Enregistrer la dépense</button>
                </form></section>
            @elseif (Auth::user()->isTenant() && $ticket->status === 'awaiting_tenant_confirmation')
                <section class="panel"><h2 class="section-title">Le problème est-il résolu ?</h2><div class="mt-4 grid gap-2">
                    <form method="POST" action="{{ route('tickets.confirm', $ticket) }}">@csrf<input type="hidden" name="resolved" value="1"><button class="action-button w-full">Oui, clôturer</button></form>
                    <form method="POST" action="{{ route('tickets.confirm', $ticket) }}">@csrf<input type="hidden" name="resolved" value="0"><label class="field-label">Pourquoi ?<textarea class="field-input" name="note" rows="2"></textarea></label><button class="small-button mt-2 w-full">Non, reprendre l’intervention</button></form>
                </div></section>
            @endif
        </aside>
    </div>
</x-app-layout>
