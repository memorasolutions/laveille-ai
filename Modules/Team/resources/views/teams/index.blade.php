<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Équipes'), 'subtitle' => __('Équipes')])

@section('breadcrumbs')
<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Équipes') }}</li>
    </ol>
</nav>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Fermer') }}"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Fermer') }}"></button>
    </div>
@endif

<div class="card">
    <div class="card-header py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">
            <i data-lucide="users" class="me-2"></i>{{ __('Équipes') }} ({{ $teams->total() }})
        </h5>
        <div class="d-flex gap-2">
            <x-backoffice::help-modal id="helpTeamsModal" :title="__('Équipes multi-utilisateurs')" icon="users" :buttonLabel="__('Aide')">
                @include('team::teams._help')
            </x-backoffice::help-modal>
            @can('create_teams')
            <a href="{{ route('admin.teams.create') }}" class="btn btn-primary btn-sm">
                <i data-lucide="plus" class="me-1"></i> {{ __('Nouvelle équipe') }}
            </a>
            @endcan
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" aria-label="{{ __('Liste des équipes') }}">
                <thead>
                    <tr>
                        <th scope="col">{{ __('Nom') }}</th>
                        <th scope="col" class="d-none d-md-table-cell">{{ __('Propriétaire') }}</th>
                        <th scope="col" class="text-center d-none d-lg-table-cell" style="width:110px">{{ __('Membres') }}</th>
                        <th scope="col" class="d-none d-lg-table-cell" style="width:140px">{{ __('Créée le') }}</th>
                        <th scope="col" class="text-end" style="width:150px">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teams as $team)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i data-lucide="users" class="text-muted" style="width:16px;height:16px"></i>
                                <strong>{{ $team->name }}</strong>
                            </div>
                            @if($team->description)
                                <small class="text-muted d-block mt-1">{{ Str::limit($team->description, 80) }}</small>
                            @endif
                        </td>
                        <td class="d-none d-md-table-cell">
                            <div class="d-flex align-items-center gap-2">
                                <i data-lucide="crown" class="text-warning" style="width:14px;height:14px"></i>
                                <span>{{ $team->owner->name ?? '–' }}</span>
                            </div>
                        </td>
                        <td class="text-center d-none d-lg-table-cell">
                            <span class="badge bg-primary rounded-pill">{{ $team->members_count }}</span>
                        </td>
                        <td class="text-muted d-none d-lg-table-cell">
                            {{ format_date($team->created_at) }}
                        </td>
                        <td class="text-end">
                            @php
                                $teamActions = [
                                    ['label' => __('Voir l\'équipe'), 'icon' => 'eye', 'url' => route('admin.teams.show', $team)],
                                ];
                                if (auth()->user()?->can('update_teams')) {
                                    $teamActions[] = ['label' => __('Modifier l\'équipe'), 'icon' => 'pencil', 'url' => route('admin.teams.edit', $team)];
                                }
                                if (auth()->user()?->can('delete_teams')) {
                                    $teamActions[] = ['divider' => true];
                                    $teamActions[] = ['label' => __('Supprimer l\'équipe'), 'icon' => 'trash-2', 'url' => route('admin.teams.destroy', $team), 'method' => 'DELETE', 'confirm' => __('Supprimer l\'équipe') . ' « ' . $team->name . ' » ? ' . __('Cette action est irréversible.'), 'danger' => true];
                                }
                            @endphp
                            @include('core::components.action-menu', ['actions' => $teamActions])
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i data-lucide="users" class="mb-2 d-block mx-auto" style="width:32px;height:32px;opacity:.4"></i>
                            {{ __('Aucune équipe pour le moment.') }}
                            @can('create_teams')
                            <a href="{{ route('admin.teams.create') }}" class="d-block mt-2">{{ __('Créer la première équipe') }}</a>
                            @endcan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($teams->hasPages())
        <div class="px-4 py-3">
            {{ $teams->links() }}
        </div>
        @endif
    </div>
</div>

@endsection
