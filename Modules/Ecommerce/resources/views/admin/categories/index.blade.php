<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Catégories'), 'subtitle' => __('Boutique')])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ecommerce.dashboard') }}">{{ __('Boutique') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Catégories') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
        <i data-lucide="folder-tree" class="icon-md text-primary"></i> {{ __('Catégories') }}
    </h4>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Fermer') }}"></button>
    </div>
@endif

<div class="card">
    <div class="card-header py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">{{ __('Liste des catégories') }}</h5>
        <a href="{{ route('admin.ecommerce.categories.create') }}" class="btn btn-primary btn-sm">
            <i data-lucide="plus" class="me-1"></i> {{ __('Nouvelle catégorie') }}
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">{{ __('Nom') }}</th>
                        <th>{{ __('Parent') }}</th>
                        <th>{{ __('Position') }}</th>
                        <th>{{ __('Statut') }}</th>
                        <th class="text-end pe-4">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                    <tr>
                        <td class="ps-4 fw-semibold">{{ $category->name }}</td>
                        <td>{{ $category->parent?->name ?? '-' }}</td>
                        <td>{{ $category->position }}</td>
                        <td>
                            <span class="badge {{ $category->is_active ? 'bg-success' : 'bg-danger' }}">
                                {{ $category->is_active ? __('Actif') : __('Inactif') }}
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('admin.ecommerce.categories.edit', $category) }}" class="btn btn-sm btn-light"><i data-lucide="edit-2" class="icon-sm"></i></a>
                            <form action="{{ route('admin.ecommerce.categories.destroy', $category) }}" method="POST" class="d-inline" data-confirm="{{ __('Supprimer cette catégorie ?') }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light text-danger"><i data-lucide="trash-2" class="icon-sm"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">{{ __('Aucune catégorie.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
