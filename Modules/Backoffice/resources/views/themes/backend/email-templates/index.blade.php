<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Templates email', 'subtitle' => 'Liste'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Templates email') }}</li>
    </ol>
</nav>

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
        <i data-lucide="check-circle" class="icon-sm"></i>
        {{ session('success') }}
    </div>
@endif

<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
            <i data-lucide="mail" class="text-primary icon-md"></i>
            {{ __('Templates email') }}
        </h4>
    </div>

    @if($templates->isEmpty())
        <div class="card-body d-flex flex-column align-items-center justify-content-center py-5 text-center">
            <i data-lucide="mail" style="width:48px;height:48px;opacity:0.2;" class="text-muted mb-3"></i>
            <p class="text-muted fw-medium mb-0">{{ __('Aucun template email configuré.') }}</p>
        </div>
    @else
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="fw-semibold small text-muted">{{ __('Nom') }}</th>
                            <th class="fw-semibold small text-muted">{{ __('Slug') }}</th>
                            <th class="fw-semibold small text-muted">{{ __('Module') }}</th>
                            <th class="fw-semibold small text-muted">{{ __('Sujet') }}</th>
                            <th class="fw-semibold small text-muted">{{ __('Statut') }}</th>
                            <th class="fw-semibold small text-muted">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($templates as $t)
                            <tr>
                                <td class="align-middle fw-semibold small text-body">{{ $t->name }}</td>
                                <td class="align-middle">
                                    <code class="text-primary small bg-primary bg-opacity-10 px-2 py-1 rounded">{{ $t->slug }}</code>
                                </td>
                                <td class="align-middle small text-muted">{{ $t->module ?? '-' }}</td>
                                <td class="align-middle small text-muted">{{ Str::limit($t->subject, 50) }}</td>
                                <td class="align-middle">
                                    @if($t->is_active)
                                        <span class="badge bg-success bg-opacity-10 text-success">
                                            {{ __('Actif') }}
                                        </span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger">
                                            {{ __('Inactif') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <a href="{{ route('admin.email-templates.edit', $t) }}"
                                       class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2">
                                        <i data-lucide="pencil"></i>
                                        {{ __('Modifier') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@endsection
