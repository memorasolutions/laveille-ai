<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', __('Nouvelle experience'))
@section('content')
<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.experiments.index') }}">{{ __('Experiences A/B') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Nouvelle') }}</li>
    </ol>
</nav>
<div class="page-content">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">{{ __('Nouvelle experience') }}</h4>
        <a href="{{ route('admin.experiments.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i> {{ __('Retour') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.experiments.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">{{ __('Nom') }} <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required maxlength="255">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">{{ __('Description') }}</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3" x-data="{
                    variants: {{ json_encode(old('variants', ['control', ''])) }},
                    addVariant() {
                        this.variants.push('');
                        this.$nextTick(() => lucide.createIcons());
                    },
                    removeVariant(index) {
                        if (this.variants.length > 2) {
                            this.variants.splice(index, 1);
                        }
                    }
                }">
                    <label class="form-label">{{ __('Variantes') }} <span class="text-danger">*</span></label>

                    <template x-for="(variant, index) in variants" :key="index">
                        <div class="input-group mb-2">
                            <input type="text" class="form-control"
                                   :name="`variants[${index}]`"
                                   x-model="variants[index]"
                                   :placeholder="`Variante ${index + 1}`"
                                   :aria-label="`Variante ${index + 1}`"
                                   maxlength="100" required>
                            <button type="button" class="btn btn-outline-danger btn-sm"
                                    @click="removeVariant(index)"
                                    :disabled="variants.length <= 2"
                                    :aria-label="`Supprimer la variante ${index + 1}`">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </div>
                    </template>

                    @error('variants')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    @error('variants.*')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror

                    <button type="button" class="btn btn-outline-primary btn-sm mt-2"
                            @click="addVariant()" aria-label="{{ __('Ajouter une variante') }}">
                        <i data-lucide="plus" class="me-1"></i> {{ __('Ajouter une variante') }}
                    </button>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i> {{ __('Creer l\'experience') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
