<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PropertyController extends Controller
{
    public function index(Request $request): View
    {
        return view('landlord.properties.index', [
            'properties' => Property::query()
                ->where('landlord_id', $request->user()->id)
                ->withCount('contracts')
                ->latest()
                ->paginate(12),
        ]);
    }

    public function create(): View
    {
        return view('landlord.properties.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:80', Rule::unique('properties', 'reference')->where('landlord_id', $request->user()->id)],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'address' => ['required', 'string', 'max:255'],
            'property_type' => ['required', 'in:apartment,house,commercial,other'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:100'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $request->user()->properties()->create($data + ['status' => 'available']);

        return redirect()->route('properties.index')->with('success', 'Le bien a été ajouté.');
    }

    public function show(Request $request, Property $property): View
    {
        abort_unless($property->landlord_id === $request->user()->id, 404);
        $property->load(['contracts.tenant', 'contracts.invoices']);

        return view('landlord.properties.show', compact('property'));
    }

    public function edit(Request $request, Property $property): View
    {
        abort_unless($property->landlord_id === $request->user()->id, 404);

        return view('landlord.properties.edit', compact('property'));
    }

    public function update(Request $request, Property $property): RedirectResponse
    {
        abort_unless($property->landlord_id === $request->user()->id, 404);

        $data = $request->validate([
            'reference' => ['required', 'string', 'max:80', Rule::unique('properties', 'reference')->where('landlord_id', $request->user()->id)->ignore($property->id)],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'address' => ['required', 'string', 'max:255'],
            'property_type' => ['required', 'in:apartment,house,commercial,other'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:100'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $property->update($data);

        return redirect()->route('properties.index')->with('success', 'Le bien a été modifié.');
    }

    public function destroy(Request $request, Property $property): RedirectResponse
    {
        abort_unless($property->landlord_id === $request->user()->id, 404);
        abort_if($property->contracts()->whereIn('status', ['draft', 'active'])->exists(), 422, 'Un bien avec un contrat actif ou en brouillon ne peut pas être archivé.');

        $property->delete();

        return redirect()->route('properties.index')->with('success', 'Le bien a été archivé.');
    }
}
