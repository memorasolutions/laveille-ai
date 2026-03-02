<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Étapes onboarding', 'subtitle' => 'Gestion'])

@section('breadcrumbs')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item active" aria-current="page">Onboarding</li>
    </ol>
</nav>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
        <i data-lucide="check-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
        {{ session('success') }}
    </div>
@endif

<div class="card">
    <div class="card-header border-bottom py-3 px-4">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="clipboard-list" style="width:20px;height:20px;" class="text-primary"></i>
            <h5 class="mb-0 fw-semibold">Étapes onboarding</h5>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4" style="width:80px;">Ordre</th>
                    <th>Slug</th>
                    <th>Titre</th>
                    <th>Description</th>
                    <th>{{ __('Actif') }}</th>
                    <th class="pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($steps as $step)
                    <tr>
                        <td class="ps-4 text-muted text-center">{{ $step->order }}</td>
                        <td>
                            <code class="text-primary bg-primary bg-opacity-10 px-2 py-1 rounded">{{ $step->slug }}</code>
                        </td>
                        <td class="fw-semibold">{{ $step->title }}</td>
                        <td class="text-muted small">{{ Str::limit($step->description, 50) }}</td>
                        <td>
                            @if($step->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success">Oui</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">Non</span>
                            @endif
                        </td>
                        <td class="pe-4">
                            <a href="{{ route('admin.onboarding-steps.edit', $step) }}"
                                class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                                <i data-lucide="pencil" style="width:14px;height:14px;"></i>
                                Modifier
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
