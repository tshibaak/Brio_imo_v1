<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Property;
use App\Models\User;
use App\Services\InvoiceGenerationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContractController extends Controller
{
    public function index(Request $request): View
    {
        return view('landlord.contracts.index', [
            'contracts' => Contract::query()->where('landlord_id', $request->user()->id)
                ->with(['tenant', 'property'])
                ->latest()->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        return view('landlord.contracts.create', [
            'properties' => Property::query()->where('landlord_id', $request->user()->id)->orderBy('name')->get(),
            'tenants' => $request->user()->tenants()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, InvoiceGenerationService $invoiceGenerator): RedirectResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', Rule::exists('landlord_tenant', 'tenant_id')->where('landlord_id', $request->user()->id)],
            'property_id' => ['required', 'integer', Rule::exists('properties', 'id')->where('landlord_id', $request->user()->id)],
            'reference' => ['required', 'string', 'max:80', Rule::unique('contracts', 'reference')->where('landlord_id', $request->user()->id)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'periodicity' => ['required', 'in:monthly,quarterly,yearly'],
            'due_day' => ['required', 'integer', 'between:1,31'],
            'rent_amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'charges_amount' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'deposit_amount' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'currency' => ['required', 'regex:/^[A-Z]{3}$/'],
            'terms' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', 'in:draft,active'],
        ]);

        $contract = DB::transaction(function () use ($request, $data): Contract {
            $property = Property::query()->where('landlord_id', $request->user()->id)
                ->lockForUpdate()->findOrFail($data['property_id']);
            $tenant = User::query()->whereKey($data['tenant_id'])->where('role', 'tenant')->where('is_active', true)->firstOrFail();
            abort_unless($request->user()->tenants()->whereKey($tenant->id)->exists(), 404);

            if ($data['status'] === 'active') {
                $start = CarbonImmutable::parse($data['starts_at']);
                $end = isset($data['ends_at']) ? CarbonImmutable::parse($data['ends_at']) : null;
                $overlap = Contract::query()->where('property_id', $property->id)
                    ->where('status', 'active')
                    ->where(function ($query) use ($start): void {
                        $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $start);
                    })
                    ->where(function ($query) use ($end): void {
                        if ($end) {
                            $query->whereDate('starts_at', '<=', $end);
                        }
                    })
                    ->exists();
                abort_if($overlap, 422, 'Ce bien possède déjà un contrat actif sur cette période.');
            }

            $contract = $request->user()->contractsAsLandlord()->create($data);
            if ($data['status'] === 'active') {
                $property->update(['status' => 'occupied']);
            }

            return $contract;
        });

        if ($contract->status === 'active') {
            $invoiceGenerator->generateForContract($contract);
        }

        return redirect()->route('contracts.index')->with('success', 'Le contrat a été créé.');
    }

    public function status(Request $request, Contract $contract, InvoiceGenerationService $invoiceGenerator): RedirectResponse
    {
        abort_unless($contract->landlord_id === $request->user()->id, 404);
        $data = $request->validate(['status' => ['required', 'in:active,terminated']]);

        DB::transaction(function () use ($contract, $data): void {
            $locked = Contract::query()->lockForUpdate()->findOrFail($contract->id);
            $property = Property::query()->where('landlord_id', $locked->landlord_id)
                ->lockForUpdate()->findOrFail($locked->property_id);
            if ($data['status'] === 'active') {
                abort_if($locked->status === 'terminated', 422, 'Un contrat résilié ne peut pas être réactivé.');
                $overlap = Contract::query()->where('property_id', $property->id)
                    ->where('id', '!=', $locked->id)
                    ->where('status', 'active')
                    ->where(function ($query) use ($locked): void {
                        $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $locked->starts_at);
                    })
                    ->where(function ($query) use ($locked): void {
                        if ($locked->ends_at) {
                            $query->whereDate('starts_at', '<=', $locked->ends_at);
                        }
                    })->exists();
                abort_if($overlap, 422, 'Ce bien possède déjà un contrat actif sur cette période.');
                $locked->update(['status' => 'active']);
                $property->update(['status' => 'occupied']);
            } else {
                abort_if($locked->status !== 'active', 422, 'Seul un contrat actif peut être terminé.');
                $locked->update(['status' => 'terminated']);
                if (! $property->contracts()->where('status', 'active')->exists()) {
                    $property->update(['status' => 'available']);
                }
            }
        });

        if ($data['status'] === 'active') {
            $invoiceGenerator->generateForContract($contract->fresh());
        }

        return back()->with('success', 'Le statut du contrat a été mis à jour.');
    }
}
