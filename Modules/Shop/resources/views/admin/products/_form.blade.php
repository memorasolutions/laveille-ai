@php $p = $product ?? null; @endphp

<div class="mb-3">
    <label for="name" class="form-label">{{ __('Nom') }} *</label>
    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $p?->name) }}" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="mb-3">
    <label for="slug" class="form-label">{{ __('Slug') }} *</label>
    <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug', $p?->slug) }}" required>
    @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="price" class="form-label">{{ __('Prix') }} ({{ config('shop.currency', 'CAD') }}) *</label>
        <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $p?->price) }}" required min="0">
        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="compare_price" class="form-label">{{ __('Ancien prix') }}</label>
        <input type="number" step="0.01" class="form-control" id="compare_price" name="compare_price" value="{{ old('compare_price', $p?->compare_price) }}" min="0">
    </div>
</div>
<div class="mb-3">
    <label for="short_description" class="form-label">{{ __('Description courte') }}</label>
    <input type="text" class="form-control" id="short_description" name="short_description" value="{{ old('short_description', $p?->short_description) }}" maxlength="255">
</div>
<div class="mb-3">
    <label for="description" class="form-label">{{ __('Description') }}</label>
    <textarea class="form-control" id="description" name="description" rows="5">{{ old('description', $p?->description) }}</textarea>
</div>
<div class="row">
    <div class="col-md-4 mb-3">
        <label for="category" class="form-label">{{ __('Categorie') }}</label>
        <input type="text" class="form-control" id="category" name="category" value="{{ old('category', $p?->category) }}">
    </div>
    <div class="col-md-4 mb-3">
        <label for="status" class="form-label">{{ __('Statut') }} *</label>
        <select class="form-select" id="status" name="status" required>
            <option value="draft" {{ old('status', $p?->status) === 'draft' ? 'selected' : '' }}>{{ __('Brouillon') }}</option>
            <option value="published" {{ old('status', $p?->status) === 'published' ? 'selected' : '' }}>{{ __('Publie') }}</option>
            <option value="archived" {{ old('status', $p?->status) === 'archived' ? 'selected' : '' }}>{{ __('Archive') }}</option>
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label for="sort_order" class="form-label">{{ __('Ordre') }}</label>
        <input type="number" class="form-control" id="sort_order" name="sort_order" value="{{ old('sort_order', $p?->sort_order ?? 0) }}">
    </div>
</div>
<div class="mb-3">
    <label for="gelato_product_id" class="form-label">{{ __('ID produit Gelato') }}</label>
    <input type="text" class="form-control" id="gelato_product_id" name="gelato_product_id" value="{{ old('gelato_product_id', $p?->gelato_product_id) }}">
</div>

<script>
document.getElementById('name')?.addEventListener('input', function() {
    const slug = this.value.toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    document.getElementById('slug').value = slug;
});
</script>
