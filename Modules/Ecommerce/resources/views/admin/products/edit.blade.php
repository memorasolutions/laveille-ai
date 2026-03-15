<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Modifier le produit', 'subtitle' => 'Boutique'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ecommerce.dashboard') }}">Boutique</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ecommerce.products.index') }}">Produits</a></li>
        <li class="breadcrumb-item active" aria-current="page">Modifier</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
        <i data-lucide="edit-2" class="icon-md text-primary"></i> {{ $product->name }}
    </h4>
</div>

<form action="{{ route('admin.ecommerce.products.update', $product) }}" method="POST">
    @csrf @method('PUT')
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="inputName">Nom du produit</label>
                        <input type="text" name="name" id="inputName" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $product->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="inputSlug">Slug (URL)</label>
                        <input type="text" name="slug" id="inputSlug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $product->slug) }}" required>
                        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="5">{{ old('description', $product->description) }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title">Publication</h6>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">Actif</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_featured" id="isFeatured" value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isFeatured">En vedette</label>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-primary w-100">Mettre à jour</button>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title">Prix et SKU</h6>
                    <div class="mb-2">
                        <label class="form-label small">Prix</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">{{ config('modules.ecommerce.currency_symbol') }}</span>
                            <input type="number" step="0.01" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $product->price) }}" required>
                        </div>
                        @error('price') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Prix comparatif</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">{{ config('modules.ecommerce.currency_symbol') }}</span>
                            <input type="number" step="0.01" name="compare_price" class="form-control" value="{{ old('compare_price', $product->compare_price) }}">
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small">SKU</label>
                        <input type="text" name="sku" class="form-control form-control-sm @error('sku') is-invalid @enderror" value="{{ old('sku', $product->sku) }}">
                        @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Catégories</h6>
                    @php $productCats = $product->categories->pluck('id')->toArray(); @endphp
                    <div style="max-height: 200px; overflow-y: auto;">
                        @foreach($categories as $cat)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="categories[]" value="{{ $cat->id }}" id="cat{{ $cat->id }}" {{ in_array($cat->id, old('categories', $productCats)) ? 'checked' : '' }}>
                            <label class="form-check-label" for="cat{{ $cat->id }}">{{ $cat->name }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
    document.getElementById('inputName').addEventListener('input', function() {
        document.getElementById('inputSlug').value = this.value.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    });
</script>
@endpush

@endsection
