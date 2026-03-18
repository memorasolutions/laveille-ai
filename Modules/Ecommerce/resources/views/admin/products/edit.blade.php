<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Modifier le produit'), 'subtitle' => __('Boutique')])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ecommerce.dashboard') }}">{{ __('Boutique') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ecommerce.products.index') }}">{{ __('Produits') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Modifier') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
        <i data-lucide="edit-2" class="icon-md text-primary"></i> {{ $product->name }}
    </h4>
</div>

<form action="{{ route('admin.ecommerce.products.update', $product) }}" method="POST" enctype="multipart/form-data">
    @csrf @method('PUT')
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="inputName">{{ __('Nom du produit') }}</label>
                        <input type="text" name="name" id="inputName" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $product->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="inputSlug">{{ __('Slug (URL)') }}</label>
                        <input type="text" name="slug" id="inputSlug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $product->slug) }}" required>
                        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="5">{{ old('description', $product->description) }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            {{-- Images --}}
            <div class="card mb-3">
                <div class="card-header py-3 border-bottom">
                    <h6 class="mb-0"><i data-lucide="image" class="icon-sm me-1"></i> {{ __('Images') }}</h6>
                </div>
                <div class="card-body">
                    {{-- Featured image --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">{{ __('Image principale') }}</label>
                        <div class="row g-3 align-items-center">
                            <div class="col-auto">
                                @if($product->getFirstMediaUrl('featured_image', 'thumb'))
                                    <img src="{{ $product->getFirstMediaUrl('featured_image', 'thumb') }}" class="rounded border" style="width:120px;height:120px;object-fit:cover" alt="{{ __('Image principale') }}">
                                @else
                                    <div class="border rounded d-flex align-items-center justify-content-center text-muted" style="width:120px;height:120px">
                                        <i data-lucide="image" class="icon-lg"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="col">
                                <input type="file" name="featured_image" class="form-control @error('featured_image') is-invalid @enderror" accept="image/*">
                                @error('featured_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">{{ __('JPG, PNG ou WebP. Max 5 Mo.') }}</small>
                            </div>
                        </div>
                    </div>

                    <hr>

                    {{-- Gallery --}}
                    <div>
                        <label class="form-label fw-semibold">{{ __('Galerie') }}</label>

                        @if($product->getMedia('gallery')->count() > 0)
                        <div class="row g-2 mb-3">
                            @foreach($product->getMedia('gallery') as $media)
                            <div class="col-6 col-md-3">
                                <div class="position-relative rounded overflow-hidden border" style="height:100px">
                                    <img src="{{ $media->getUrl('thumb') }}" class="w-100 h-100" style="object-fit:cover" alt="{{ __('Image galerie') }}">
                                    <div class="position-absolute top-0 start-0 p-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="remove_gallery[]" value="{{ $media->id }}" id="rmg{{ $media->id }}">
                                            <label class="form-check-label small text-white" style="text-shadow:0 1px 2px rgba(0,0,0,.7)" for="rmg{{ $media->id }}">{{ __('Supprimer') }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        <input type="file" name="gallery[]" class="form-control @error('gallery.*') is-invalid @enderror" multiple accept="image/*">
                        @error('gallery.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        <small class="text-muted">{{ __('Sélectionnez plusieurs fichiers pour ajouter à la galerie.') }}</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title">{{ __('Publication') }}</h6>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">{{ __('Actif') }}</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_featured" id="isFeatured" value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isFeatured">{{ __('En vedette') }}</label>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-primary w-100">{{ __('Mettre à jour') }}</button>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title">{{ __('Prix et SKU') }}</h6>
                    <div class="mb-2">
                        <label class="form-label small">{{ __('Prix') }}</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">{{ config('modules.ecommerce.currency_symbol') }}</span>
                            <input type="number" step="0.01" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $product->price) }}" required>
                        </div>
                        @error('price') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">{{ __('Prix comparatif') }}</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">{{ config('modules.ecommerce.currency_symbol') }}</span>
                            <input type="number" step="0.01" name="compare_price" class="form-control" value="{{ old('compare_price', $product->compare_price) }}">
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small">{{ __('SKU') }}</label>
                        <input type="text" name="sku" class="form-control form-control-sm @error('sku') is-invalid @enderror" value="{{ old('sku', $product->sku) }}">
                        @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">{{ __('Catégories') }}</h6>
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
