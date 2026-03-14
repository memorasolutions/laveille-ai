<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', 'Formulaires')

@section('content')
<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Formulaires') }}</li>
    </ol>
</nav>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="clipboard-list" class="icon-md text-primary"></i>{{ __('Formulaires') }}</h4>
    <div class="d-flex align-items-center gap-2">
        <x-backoffice::help-modal id="helpFormBuilderFormsModal" :title="__('Formulaires personnalisés')" icon="clipboard-list" :buttonLabel="__('Aide')">
            @include('formbuilder::admin.forms._help')
        </x-backoffice::help-modal>
        <a href="{{ route('admin.formbuilder.forms.create') }}" class="btn btn-primary btn-icon-text">
            <i class="btn-icon-prepend" data-lucide="plus"></i>
            {{ __('Nouveau formulaire') }}
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Soumissions</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($forms as $form)
                        <tr>
                            <td>
                                <span class="fw-bold">{{ $form->title }}</span>
                                <br><small class="text-muted">/forms/{{ $form->slug }}</small>
                            </td>
                            <td>
                                <a href="{{ route('admin.formbuilder.forms.submissions.index', $form) }}">
                                    <span class="badge bg-primary rounded-pill">{{ $form->submissions_count ?? 0 }}</span>
                                </a>
                            </td>
                            <td>
                                @if($form->is_published)
                                    <span class="badge bg-success">Publié</span>
                                @else
                                    <span class="badge bg-secondary">Brouillon</span>
                                @endif
                            </td>
                            <td>{{ $form->created_at->format('d/m/Y') }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.formbuilder.forms.submissions.index', $form) }}" class="btn btn-sm btn-info text-white" title="Soumissions">
                                        <i data-lucide="inbox" style="width: 16px; height: 16px;"></i>
                                    </a>
                                    <a href="{{ route('admin.formbuilder.forms.edit', $form) }}" class="btn btn-sm btn-warning text-white" title="Modifier">
                                        <i data-lucide="edit" style="width: 16px; height: 16px;"></i>
                                    </a>
                                    <form action="{{ route('admin.formbuilder.forms.destroy', $form) }}" method="POST" class="d-inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-danger" onclick="if(confirm('Supprimer ce formulaire et toutes ses soumissions ?')) this.form.submit()">
                                            <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="text-center py-5 text-muted">
                                    <i data-lucide="file-text" style="width:48px;height:48px;" class="mb-3 d-block mx-auto opacity-25"></i>
                                    <h5 class="mb-1">Aucun formulaire</h5>
                                    <p class="mb-3">Créez votre premier formulaire pour collecter des données.</p>
                                    <a href="{{ route('admin.formbuilder.forms.create') }}" class="btn btn-primary btn-sm">
                                        <i data-lucide="plus" style="width:14px;height:14px;" class="me-1"></i> Créer un formulaire
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $forms->links() }}</div>
    </div>
</div>
@endsection
