<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div class="mb-3">
    <label for="name" class="form-label">Nom *</label>
    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $service->name ?? '') }}" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="description" class="form-label">Description</label>
    <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $service->description ?? '') }}</textarea>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="duration_minutes" class="form-label">Durée (minutes) *</label>
        <input type="number" class="form-control @error('duration_minutes') is-invalid @enderror" id="duration_minutes" name="duration_minutes" value="{{ old('duration_minutes', $service->duration_minutes ?? 30) }}" min="1" required>
        @error('duration_minutes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label for="price" class="form-label">Prix ($)</label>
        <input type="number" class="form-control" id="price" name="price" value="{{ old('price', $service->price ?? '') }}" step="0.01" min="0">
    </div>
    <div class="col-md-4 mb-3">
        <label for="color" class="form-label">Couleur</label>
        <input type="color" class="form-control form-control-color" id="color" name="color" value="{{ old('color', $service->color ?? '#007bff') }}">
    </div>
</div>

<div class="mb-3">
    <div class="form-check">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $service->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Actif</label>
    </div>
</div>
