<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(Request $request): View
    {
        return view('landlord.tenants.index', [
            'tenants' => $request->user()->tenants()->withCount([
                'contractsAsTenant as landlord_contracts_count' => fn ($query) => $query->where('landlord_id', $request->user()->id),
            ])->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('landlord.tenants.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        $landlord = $request->user();
        $tenant = DB::transaction(function () use ($data, $landlord): User {
            $user = User::query()->where('email', mb_strtolower($data['email']))->lockForUpdate()->first();
            if ($user) {
                abort_unless($user->isTenant(), 422, 'Cette adresse e-mail est déjà utilisée par un compte bailleur.');
                $landlord->tenants()->syncWithoutDetaching([$user->id]);

                return $user;
            }

            $tenant = User::query()->create([
                'name' => $data['name'],
                'email' => mb_strtolower($data['email']),
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'password' => $data['password'],
                'role' => 'tenant',
                'is_active' => true,
            ]);
            $landlord->tenants()->syncWithoutDetaching([$tenant->id]);

            return $tenant;
        });

        return redirect()->route('tenants.index')
            ->with('success', 'Compte locataire prêt : '.$tenant->name.'. Associez-le à un bien en créant un contrat.');
    }
}
