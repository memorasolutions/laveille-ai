<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="name" class="form-label">Nom <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $package->name ?? '') }}" required>
                @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="session_count" class="form-label">Nombre de séances <span class="text-danger">*</span></label>
                <input type="number" class="form-control @error('session_count') is-invalid @enderror" id="session_count" name="session_count" value="{{ old('session_count', $package->session_count ?? '') }}" required min="1">
                @error('session_count')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="price" class="form-label">Prix ($) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $package->price ?? '') }}" required min="0">
                @error('price')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="regular_price" class="form-label">Prix régulier ($)</label>
                <input type="number" step="0.01" class="form-control @error('regular_price') is-invalid @enderror" id="regular_price" name="regular_price" value="{{ old('regular_price', $package->regular_price ?? '') }}" min="0">
                @error('regular_price')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="validity_days" class="form-label">Validité (jours) <span class="text-danger">*</span></label>
                <input type="number" class="form-control @error('validity_days') is-invalid @enderror" id="validity_days" name="validity_days" value="{{ old('validity_days', $package->validity_days ?? '') }}" required min="1">
                @error('validity_days')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="sort_order" class="form-label">Ordre d'affichage</label>
                <input type="number" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', $package->sort_order ?? 0) }}" min="0">
                @error('sort_order')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $package->description ?? '') }}</textarea>
            @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $package->is_active ?? true))>
                <label class="form-check-label" for="is_active">Actif</label>
            </div>
        </div>
    </div>
</div>
