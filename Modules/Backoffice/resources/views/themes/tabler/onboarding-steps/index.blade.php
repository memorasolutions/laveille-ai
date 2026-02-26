@extends('backoffice::layouts.admin', ['title' => 'Étapes onboarding', 'subtitle' => 'Gestion'])

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible mb-3">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="ti ti-list-check me-2"></i> Étapes onboarding
        </h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 60px;">Ordre</th>
                        <th>Slug</th>
                        <th>Titre</th>
                        <th>Description</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($steps as $step)
                        <tr>
                            <td class="text-center text-muted">{{ $step->order }}</td>
                            <td><code>{{ $step->slug }}</code></td>
                            <td class="fw-bold">{{ $step->title }}</td>
                            <td class="text-muted">{{ Str::limit($step->description, 50) }}</td>
                            <td>
                                @if($step->is_active)
                                    <span class="badge bg-success-lt text-success">Oui</span>
                                @else
                                    <span class="badge bg-danger-lt text-danger">Non</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.onboarding-steps.edit', $step) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-pencil me-1"></i> Modifier
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
