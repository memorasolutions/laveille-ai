<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', __('Ajouter une question FAQ'))
@section('content')
<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.faqs.index') }}">{{ __('FAQ') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Ajouter') }}</li>
    </ol>
</nav>
<div class="page-content">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">{{ __('Ajouter une question') }}</h4>
        <a href="{{ route('admin.faqs.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i> {{ __('Retour') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.faqs.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="question" class="form-label">{{ __('Question') }} <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('question') is-invalid @enderror" id="question" name="question" value="{{ old('question') }}" required maxlength="500">
                    @error('question')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="answer" class="form-label">{{ __('Réponse') }} <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('answer') is-invalid @enderror" id="answer" name="answer" rows="5" required>{{ old('answer') }}</textarea>
                    @error('answer')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3" x-data="{ showNewInput: {{ old('category') && !$categories->contains(old('category')) ? 'true' : 'false' }} }">
                        <label for="category" class="form-label">{{ __('Catégorie') }}</label>

                        <select id="category" name="category"
                                class="form-select @error('category') is-invalid @enderror"
                                x-show="!showNewInput" :disabled="showNewInput"
                                x-on:change="showNewInput = ($event.target.value === '__new__')">
                            <option value="">{{ __('Sans catégorie') }}</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" @selected(old('category') == $cat)>{{ $cat }}</option>
                            @endforeach
                            <option value="__new__">{{ __('Nouvelle catégorie...') }}</option>
                        </select>

                        <div x-show="showNewInput" x-transition>
                            <div class="input-group">
                                <input type="text" name="category"
                                       class="form-control @error('category') is-invalid @enderror"
                                       :disabled="!showNewInput"
                                       value="{{ old('category') && !$categories->contains(old('category')) ? old('category') : '' }}"
                                       placeholder="{{ __('Nom de la nouvelle catégorie') }}" maxlength="100">
                                <button type="button" class="btn btn-outline-secondary"
                                        x-on:click="showNewInput = false"
                                        aria-label="{{ __('Revenir aux catégories existantes') }}">&times;</button>
                            </div>
                        </div>

                        @error('category')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" {{ old('is_published', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_published">{{ __('Publié') }}</label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i> {{ __('Créer la question') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
