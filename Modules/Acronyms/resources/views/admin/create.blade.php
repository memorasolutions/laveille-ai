<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Nouvel acronyme')])

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.acronyms.index') }}">{{ __('Acronymes') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Nouveau') }}</li>
        </ol>
    </nav>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">{{ __('Ajouter un acronyme') }}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.acronyms.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="acronym" class="form-label">{{ __('Acronyme') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('acronym') is-invalid @enderror" id="acronym" name="acronym" value="{{ old('acronym') }}" required maxlength="50">
                        @error('acronym') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-8 mb-3">
                        <label for="full_name" class="form-label">{{ __('Nom complet') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('full_name') is-invalid @enderror" id="full_name" name="full_name" value="{{ old('full_name') }}" required maxlength="500">
                        @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">{{ __('Description') }}</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="5">{{ old('description') }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                @include('acronyms::admin.partials.scraper-row', ['acronym' => (object) ['website_url' => '', 'logo_url' => '']])

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="acronym_category_id" class="form-label">{{ __('Catégorie') }}</label>
                        <select class="form-select @error('acronym_category_id') is-invalid @enderror" id="acronym_category_id" name="acronym_category_id">
                            <option value="">-- {{ __('Aucune') }} --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('acronym_category_id') == $category->id)>{{ $category->icon }} {{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('acronym_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="sort_order" class="form-label">{{ __('Ordre') }}</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}">
                    </div>
                    <div class="col-md-3 mb-3 d-flex align-items-end">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_published" name="is_published" value="1" @checked(old('is_published', true))>
                            <label class="form-check-label" for="is_published">{{ __('Publié') }}</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i data-lucide="plus"></i> {{ __('Créer') }}</button>
                    <a href="{{ route('admin.acronyms.index') }}" class="btn btn-outline-secondary"><i data-lucide="arrow-left"></i> {{ __('Annuler') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
