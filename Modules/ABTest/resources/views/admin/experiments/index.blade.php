<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', __('Experiences A/B'))
@section('content')
<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Experiences A/B') }}</li>
    </ol>
</nav>
<div class="page-content">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="flask-conical" class="icon-md text-primary"></i>{{ __('Experiences A/B') }}</h4>
        <div class="d-flex align-items-center gap-2">
            <x-backoffice::help-modal id="helpExperimentsModal" :title="__('Qu\'est-ce qu\'un test A/B ?')" icon="flask-conical" :buttonLabel="__('Aide')">
                @include('abtest::admin.experiments._help')
            </x-backoffice::help-modal>
            <a href="{{ route('admin.experiments.create') }}" class="btn btn-primary">
                <i data-lucide="plus"></i> {{ __('Nouvelle expérience') }}
            </a>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            @if($experiments->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('Nom') }}</th>
                            <th>{{ __('Variantes') }}</th>
                            <th>{{ __('Statut') }}</th>
                            <th>{{ __('Date de creation') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($experiments as $experiment)
                        <tr>
                            <td>{{ $experiment->name }}</td>
                            <td>
                                @foreach($experiment->variants as $variant)
                                    <span class="badge bg-light text-dark border me-1">{{ $variant }}</span>
                                @endforeach
                            </td>
                            <td>
                                @php $statusClasses = ['draft' => 'secondary', 'running' => 'primary', 'completed' => 'success']; @endphp
                                <span class="badge bg-{{ $statusClasses[$experiment->status] ?? 'secondary' }}">{{ $experiment->status }}</span>
                            </td>
                            <td>{{ format_date($experiment->created_at, 'datetime') }}</td>
                            <td>
                                @include('core::components.action-menu', ['actions' => [
                                    ['label' => __('Voir'), 'icon' => 'eye', 'url' => route('admin.experiments.show', $experiment)],
                                    ['divider' => true],
                                    ['label' => __('Supprimer'), 'icon' => 'trash-2', 'url' => route('admin.experiments.destroy', $experiment), 'method' => 'DELETE', 'confirm' => __('Supprimer cette expérience ?'), 'danger' => true],
                                ]])
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3">{{ $experiments->links() }}</div>
            @else
            <div class="text-center py-5">
                <i data-lucide="flask-conical" class="icon-xl text-muted mb-3"></i>
                <h5 class="text-muted">{{ __('Aucune expérience') }}</h5>
                <p class="text-muted mb-4">{{ __('Créez votre première expérience A/B pour optimiser vos conversions.') }}</p>
                <a href="{{ route('admin.experiments.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> {{ __('Nouvelle expérience') }}</a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
