<x-app-layout>
    <x-slot name="header"><div class="flex flex-wrap items-center justify-between gap-3"><h1 class="text-2xl font-bold">Contrats de location</h1><a class="action-button" href="{{ route('contracts.create') }}">Nouveau contrat</a></div></x-slot>
    <div class="mx-auto max-w-7xl space-y-5 px-4 py-8 sm:px-6 lg:px-8">@include('shared.flash')
        <div class="space-y-4">
            @forelse ($contracts as $contract)
                <article class="panel">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div><p class="text-xs font-bold uppercase tracking-wider text-teal-700">{{ $contract->reference }}</p><h2 class="mt-1 text-lg font-bold">{{ $contract->tenant->name }} · {{ $contract->property->name }}</h2><p class="mt-1 text-sm text-slate-500">{{ $contract->starts_at->format('d/m/Y') }} — {{ $contract->ends_at?->format('d/m/Y') ?? 'Sans date de fin' }} · {{ number_format((float) $contract->rent_amount, 2) }} {{ $contract->currency }} / {{ $contract->periodicity }}</p></div>
                        <span class="status-badge">{{ $contract->status }}</span>
                    </div>
                    @if ($contract->documents->isNotEmpty())
                        <div class="mt-4 flex flex-wrap gap-2">@foreach ($contract->documents as $document)<a class="small-button" href="{{ route('contracts.documents.download', $document) }}">{{ $document->original_name }}</a>@endforeach</div>
                    @endif
                    @if (in_array($contract->status, ['draft', 'active'], true))
                        <form class="mt-4" method="POST" action="{{ route('contracts.status', $contract) }}">@csrf @method('PATCH')
                            @if ($contract->status === 'draft')
                                <input type="hidden" name="status" value="active"><button class="small-button">Activer le contrat</button>
                            @else
                                <input type="hidden" name="status" value="terminated"><button class="small-button text-rose-700" onclick="return confirm('Terminer ce contrat ?')">Terminer le contrat</button>
                            @endif
                        </form>
                    @endif
                    <form class="mt-5 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-[1fr_auto_auto]" method="POST" action="{{ route('contracts.documents.store', $contract) }}" enctype="multipart/form-data">@csrf
                        <label class="field-label">Document<input class="field-input" type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required></label>
                        <label class="field-label">Type<select class="field-input" name="category"><option value="lease">Bail légalisé</option><option value="deposit_receipt">Preuve de garantie</option><option value="inspection">État des lieux</option><option value="other">Autre</option></select></label>
                        <div class="self-end"><button class="small-button">Ajouter un document</button></div>
                    </form>
                </article>
            @empty
                <p class="empty-state">Aucun contrat. Ajoutez d’abord un bien et un locataire, puis créez le contrat.</p>
            @endforelse
        </div>
        {{ $contracts->links() }}
    </div>
</x-app-layout>
