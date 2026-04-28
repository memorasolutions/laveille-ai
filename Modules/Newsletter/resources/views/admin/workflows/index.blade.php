<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin', ['title' => 'Workflows', 'subtitle' => 'Marketing'])

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="git-branch" class="icon-md text-primary"></i>{{ __('Workflows email') }}</h4>
    <x-backoffice::help-modal id="helpNewsletterWorkflowsModal" :title="__('Workflows email')" icon="git-branch" :buttonLabel="__('Aide')">
        @include('newsletter::admin.workflows._help')
    </x-backoffice::help-modal>
</div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                <h6 class="mb-0">{{ __('Workflows email') }}</h6>
                <a href="{{ route('admin.newsletter.workflows.create') }}" class="btn btn-sm btn-primary d-flex align-items-center gap-1">
                    <i data-lucide="plus"></i> {{ __('Nouveau workflow') }}
                </a>
            </div>
            <div class="card-body">
                @if($workflows->isEmpty())
                    <div class="text-center py-5">
                        <i data-lucide="git-branch" style="width:48px;height:48px" class="text-muted mb-3"></i>
                        <p class="text-muted">Aucun workflow pour l'instant.</p>
                        <a href="{{ route('admin.newsletter.workflows.create') }}" class="btn btn-sm btn-outline-primary">Créer un workflow</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Déclencheur</th>
                                    <th class="text-center">Étapes</th>
                                    <th class="text-center">Inscrits</th>
                                    <th>Statut</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($workflows as $workflow)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.newsletter.workflows.show', $workflow) }}" class="fw-medium text-decoration-none">{{ $workflow->name }}</a>
                                    </td>
                                    <td><span class="badge bg-light text-dark">{{ $workflow->trigger_type }}</span></td>
                                    <td class="text-center">{{ $workflow->steps_count }}</td>
                                    <td class="text-center">{{ $workflow->enrollments_count }}</td>
                                    <td>
                                        @switch($workflow->status)
                                            @case('active')<span class="badge bg-success">Actif</span>@break
                                            @case('paused')<span class="badge bg-warning">Pause</span>@break
                                            @case('archived')<span class="badge bg-secondary">Archivé</span>@break
                                            @default<span class="badge bg-info">Brouillon</span>
                                        @endswitch
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="{{ route('admin.newsletter.workflows.show', $workflow) }}" class="btn btn-sm btn-outline-info" title="Voir">
                                                <i data-lucide="bar-chart-3"></i>
                                            </a>
                                            <a href="{{ route('admin.newsletter.workflows.edit', $workflow) }}" class="btn btn-sm btn-outline-primary" title="Modifier">
                                                <i data-lucide="pencil"></i>
                                            </a>
                                            <form method="POST" action="{{ route('admin.newsletter.workflows.destroy', $workflow) }}" data-confirm="Supprimer ce workflow ?">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                                    <i data-lucide="trash-2"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $workflows->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
