<div class="grid gap-5 sm:grid-cols-2">
    <label class="field-label">Référence<input class="field-input" name="reference" required maxlength="80" value="{{ old('reference', $property->reference ?? '') }}"></label>
    <label class="field-label">Nom du bien<input class="field-input" name="name" required maxlength="160" value="{{ old('name', $property->name ?? '') }}"></label>
    <label class="field-label sm:col-span-2">Adresse<input class="field-input" name="address" required value="{{ old('address', $property->address ?? '') }}"></label>
    <label class="field-label">Type<select class="field-input" name="property_type" required>@foreach (['apartment' => 'Appartement', 'house' => 'Maison', 'commercial' => 'Commercial', 'other' => 'Autre'] as $value => $label)<option value="{{ $value }}" @selected(old('property_type', $property->property_type ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
    <label class="field-label">Chambres<input class="field-input" type="number" min="0" name="bedrooms" value="{{ old('bedrooms', $property->bedrooms ?? '') }}"></label>
    <label class="field-label">Salles de bain<input class="field-input" type="number" min="0" name="bathrooms" value="{{ old('bathrooms', $property->bathrooms ?? '') }}"></label>
    <label class="field-label sm:col-span-2">Description<textarea class="field-input" name="description" rows="4">{{ old('description', $property->description ?? '') }}</textarea></label>
</div>
