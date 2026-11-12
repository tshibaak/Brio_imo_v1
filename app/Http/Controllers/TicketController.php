<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\MaintenanceTicket;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Notifications\TicketUpdatedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $query = MaintenanceTicket::query()->with(['tenant', 'property']);
        $request->user()->isLandlord()
            ? $query->where('landlord_id', $request->user()->id)
            : $query->where('tenant_id', $request->user()->id);

        return view($request->user()->isLandlord() ? 'landlord.tickets.index' : 'tenant.tickets.index', [
            'tickets' => $query->latest()->paginate(20),
        ]);
    }

    public function create(Request $request): View
    {
        return view('tenant.tickets.create', [
            'contracts' => $request->user()->contractsAsTenant()->where('status', 'active')->with('property')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'contract_id' => ['required', 'integer'],
            'category' => ['required', 'in:plumbing,electrical,carpentry,security,other'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:10000'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $contract = $request->user()->contractsAsTenant()
            ->where('status', 'active')->with('property')->findOrFail($data['contract_id']);

        $ticket = DB::transaction(function () use ($request, $data, $contract): MaintenanceTicket {
            $ticket = MaintenanceTicket::query()->create([
                'landlord_id' => $contract->landlord_id,
                'tenant_id' => $request->user()->id,
                'contract_id' => $contract->id,
                'property_id' => $contract->property_id,
                'category' => $data['category'],
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => 'open',
            ]);
            $ticket->events()->create([
                'user_id' => $request->user()->id,
                'to_status' => 'open',
                'note' => 'Ticket créé',
            ]);

            foreach ($request->file('photos', []) as $photo) {
                $ticket->attachments()->create([
                    'path' => $photo->store('ticket-attachments', 'local'),
                    'original_name' => $photo->getClientOriginalName(),
                    'mime_type' => $photo->getMimeType() ?? 'image/jpeg',
                    'size' => $photo->getSize(),
                ]);
            }

            return $ticket;
        });

        $contract->landlord->notify(new TicketUpdatedNotification($ticket, 'Nouvelle demande de maintenance.'));

        return redirect()->route('tickets.index')->with('success', 'Votre demande a été transmise au bailleur.');
    }

    public function show(Request $request, MaintenanceTicket $ticket): View
    {
        $this->authorizeTicket($request->user(), $ticket);
        $ticket->load(['tenant', 'property', 'comments.user', 'events.user', 'assignments', 'attachments']);

        return view('tickets.show', compact('ticket'));
    }

    public function attachment(Request $request, TicketAttachment $attachment): StreamedResponse
    {
        $attachment->loadMissing('ticket');
        $this->authorizeTicket($request->user(), $attachment->ticket);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    public function status(Request $request, MaintenanceTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:acknowledged,in_progress,awaiting_tenant_confirmation'],
            'comment' => ['nullable', 'string', 'max:3000'],
            'responsible_name' => ['nullable', 'required_with:responsible_email,responsible_phone', 'string', 'max:160'],
            'responsible_email' => ['nullable', 'email', 'max:255'],
            'responsible_phone' => ['nullable', 'string', 'max:40'],
        ]);
        $updatedTicket = DB::transaction(function () use ($request, $ticket, $data): MaintenanceTicket {
            $lockedTicket = MaintenanceTicket::query()->lockForUpdate()->findOrFail($ticket->id);
            abort_unless($lockedTicket->landlord_id === $request->user()->id, 404);
            $allowedTransitions = [
                'open' => ['acknowledged', 'in_progress'],
                'acknowledged' => ['in_progress'],
                'in_progress' => ['awaiting_tenant_confirmation'],
            ];
            abort_unless(in_array($data['status'], $allowedTransitions[$lockedTicket->status] ?? [], true), 422, 'Cette transition de statut n’est pas autorisée.');
            $old = $lockedTicket->status;

            $lockedTicket->update(['status' => $data['status']]);
            $lockedTicket->events()->create([
                'user_id' => $request->user()->id,
                'from_status' => $old,
                'to_status' => $data['status'],
                'note' => $data['comment'] ?? null,
            ]);
            if (! empty($data['comment'])) {
                $lockedTicket->comments()->create(['user_id' => $request->user()->id, 'body' => $data['comment']]);
            }
            if (! empty($data['responsible_name'])) {
                $lockedTicket->assignments()->create([
                    'responsible_name' => $data['responsible_name'],
                    'responsible_email' => $data['responsible_email'] ?? null,
                    'responsible_phone' => $data['responsible_phone'] ?? null,
                ]);
            }

            return $lockedTicket;
        });
        $updatedTicket->tenant->notify(new TicketUpdatedNotification($updatedTicket, 'Le statut de votre demande a été modifié.'));

        return back()->with('success', 'Le ticket a été mis à jour.');
    }

    public function comment(Request $request, MaintenanceTicket $ticket): RedirectResponse
    {
        $this->authorizeTicket($request->user(), $ticket);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);
        $ticket->comments()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);

        return back()->with('success', 'Commentaire ajouté.');
    }

    public function confirm(Request $request, MaintenanceTicket $ticket): RedirectResponse
    {
        $data = $request->validate(['resolved' => ['required', 'boolean'], 'note' => ['nullable', 'string', 'max:2000']]);
        $updatedTicket = DB::transaction(function () use ($request, $ticket, $data): MaintenanceTicket {
            $lockedTicket = MaintenanceTicket::query()->lockForUpdate()->findOrFail($ticket->id);
            abort_unless($lockedTicket->tenant_id === $request->user()->id, 404);
            abort_unless($lockedTicket->status === 'awaiting_tenant_confirmation', 422, 'Ce ticket n’attend pas de confirmation.');

            $newStatus = $data['resolved'] ? 'closed' : 'in_progress';
            $old = $lockedTicket->status;
            $lockedTicket->update(['status' => $newStatus]);
            $lockedTicket->events()->create([
                'user_id' => $request->user()->id,
                'from_status' => $old,
                'to_status' => $newStatus,
                'note' => $data['note'] ?? null,
            ]);
            if (! empty($data['note'])) {
                $lockedTicket->comments()->create(['user_id' => $request->user()->id, 'body' => $data['note']]);
            }

            return $lockedTicket;
        });
        $updatedTicket->landlord->notify(new TicketUpdatedNotification($updatedTicket, $data['resolved'] ? 'Le locataire a confirmé la résolution.' : 'Le locataire a demandé la reprise de l’intervention.'));

        return back()->with('success', 'Votre réponse a été enregistrée.');
    }

    public function expense(Request $request, MaintenanceTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'in:repair,maintenance,utilities,other'],
            'description' => ['required', 'string', 'max:2000'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999'],
            'currency' => ['required', 'regex:/^[A-Z]{3}$/'],
            'spent_at' => ['required', 'date'],
        ]);
        DB::transaction(function () use ($request, $ticket, $data): void {
            $lockedTicket = MaintenanceTicket::query()->lockForUpdate()->findOrFail($ticket->id);
            abort_unless($lockedTicket->landlord_id === $request->user()->id, 404);
            abort_if(Expense::query()->where('ticket_id', $lockedTicket->id)->exists(), 422, 'Une dépense est déjà liée à ce ticket.');
            Expense::query()->create($data + [
                'landlord_id' => $lockedTicket->landlord_id,
                'property_id' => $lockedTicket->property_id,
                'contract_id' => $lockedTicket->contract_id,
                'ticket_id' => $lockedTicket->id,
            ]);
        });

        return back()->with('success', 'Dépense de réparation enregistrée.');
    }

    private function authorizeTicket(User $user, MaintenanceTicket $ticket): void
    {
        abort_unless(in_array($user->id, [$ticket->landlord_id, $ticket->tenant_id], true), 404);
    }
}
