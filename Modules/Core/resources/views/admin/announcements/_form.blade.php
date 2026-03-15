<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@php $a = $announcement ?? null; @endphp

<div class="mb-3">
    <label for="title" class="form-label">{{ __('Titre') }} <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('title') is-invalid @enderror"
           id="title" name="title" value="{{ old('title', $a?->title) }}" required maxlength="500">
    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label for="type" class="form-label">{{ __('Type') }} <span class="text-danger">*</span></label>
        <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
            <option value="">{{ __('Selectionner') }}</option>
            <option value="feature" {{ old('type', $a?->type) == 'feature' ? 'selected' : '' }}>{{ __('Nouveauté') }}</option>
            <option value="improvement" {{ old('type', $a?->type) == 'improvement' ? 'selected' : '' }}>{{ __('Amélioration') }}</option>
            <option value="fix" {{ old('type', $a?->type) == 'fix' ? 'selected' : '' }}>{{ __('Correctif') }}</option>
            <option value="announcement" {{ old('type', $a?->type) == 'announcement' ? 'selected' : '' }}>{{ __('Annonce') }}</option>
        </select>
        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="version" class="form-label">{{ __('Version') }}</label>
        <input type="text" class="form-control @error('version') is-invalid @enderror"
               id="version" name="version" value="{{ old('version', $a?->version) }}" placeholder="ex: 1.2.0" maxlength="20">
        @error('version') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label for="body" class="form-label">{{ __('Contenu') }} <span class="text-danger">*</span></label>
    <textarea class="form-control @error('body') is-invalid @enderror"
              id="body" name="body" rows="8" required>{{ old('body', $a?->body) }}</textarea>
    @error('body') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-4">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="is_published" name="is_published"
               value="1" {{ old('is_published', $a?->is_published) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_published">{{ __('Publier immédiatement') }}</label>
    </div>
</div>
