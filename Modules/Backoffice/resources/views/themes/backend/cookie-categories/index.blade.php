<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Catégories cookies'), 'subtitle' => __('Gestion')])

@section('content')

<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Cookies GDPR') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="cookie" class="icon-md text-primary"></i>{{ __('Catégories de cookies') }}</h4>
    <x-backoffice::help-modal id="helpCookieCategoriesModal" :title="__('Catégories de cookies RGPD')" icon="cookie" :buttonLabel="__('Aide')">
        @include('backoffice::themes.backend.cookie-categories._help')
    </x-backoffice::help-modal>
</div>

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
        <i data-lucide="check-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
        <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
        {{ session('error') }}
    </div>
@endif

<div class="card">
    <div class="card-header border-bottom py-3 px-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-2">
                <i data-lucide="shield" style="width:20px;height:20px;" class="text-primary"></i>
                <h5 class="mb-0 fw-semibold">{{ __('Catégories de cookies') }}</h5>
            </div>
            <a href="{{ route('admin.cookie-categories.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
                <i data-lucide="plus" style="width:16px;height:16px;"></i>
                {{ __('Créer') }}
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">{{ __('Ordre') }}</th>
                    <th>{{ __('Nom') }}</th>
                    <th>{{ __('Label') }}</th>
                    <th>{{ __('Obligatoire') }}</th>
                    <th>{{ __('Actif') }}</th>
                    <th class="pe-4">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                    <tr>
                        <td class="ps-4 text-muted">{{ $category->order }}</td>
                        <td>
                            <code class="text-primary bg-primary bg-opacity-10 px-2 py-1 rounded">{{ $category->name }}</code>
                        </td>
                        <td class="fw-medium">{{ $category->label }}</td>
                        <td>
                            @if($category->required)
                                <span class="badge bg-primary bg-opacity-10 text-primary">{{ __('Oui') }}</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ __('Non') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($category->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success">{{ __('Oui') }}</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">{{ __('Non') }}</span>
                            @endif
                        </td>
                        <td class="pe-4">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <a href="{{ route('admin.cookie-categories.edit', $category) }}"
                                    class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                                    <i data-lucide="pencil" style="width:14px;height:14px;"></i>
                                    {{ __('Modifier') }}
                                </a>
                                @unless($category->required)
                                    <form action="{{ route('admin.cookie-categories.destroy', $category) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('{{ __('Supprimer cette catégorie ?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1">
                                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                            {{ __('Supprimer') }}
                                        </button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
