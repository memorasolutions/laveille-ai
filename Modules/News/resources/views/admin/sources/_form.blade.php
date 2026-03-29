<div class="mb-3">
    <label for="name" class="form-label">{{ __('Nom') }} <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $source->name ?? '') }}" required>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label for="url" class="form-label">URL <span class="text-danger">*</span></label>
    <input type="url" class="form-control @error('url') is-invalid @enderror" id="url" name="url" value="{{ old('url', $source->url ?? '') }}" required>
    @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label for="category" class="form-label">{{ __('Catégorie') }}</label>
    <input type="text" class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category', $source->category ?? '') }}">
    @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label for="language" class="form-label">{{ __('Langue') }}</label>
    <select class="form-select @error('language') is-invalid @enderror" id="language" name="language">
        <option value="fr" {{ old('language', $source->language ?? 'fr') === 'fr' ? 'selected' : '' }}>{{ __('Français') }}</option>
        <option value="en" {{ old('language', $source->language ?? 'fr') === 'en' ? 'selected' : '' }}>{{ __('Anglais') }}</option>
    </select>
    @error('language')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3 form-check">
    <input type="hidden" name="active" value="0">
    <input type="checkbox" class="form-check-input" id="active" name="active" value="1" {{ old('active', $source->active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="active">{{ __('Actif') }}</label>
</div>

<div class="d-flex justify-content-end mt-4">
    <button type="submit" class="btn btn-primary me-2">{{ __('Enregistrer') }}</button>
    <a href="{{ route('admin.news.sources.index') }}" class="btn btn-outline-secondary">{{ __('Annuler') }}</a>
</div>
