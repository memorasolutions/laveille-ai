@extends('backoffice::layouts.admin', ['title' => 'Templates email', 'subtitle' => 'Liste'])

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
            <i class="ti ti-mail me-2"></i> Templates email
        </h3>
    </div>
    <div class="card-body p-0">
        @if($templates->isEmpty())
            <div class="text-center py-5">
                <i class="ti ti-mail-off text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-3">{{ __('Aucun template email configuré.') }}</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-vcenter table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Nom') }}</th>
                            <th>Slug</th>
                            <th>{{ __('Module') }}</th>
                            <th>{{ __('Sujet') }}</th>
                            <th>{{ __('Statut') }}</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($templates as $t)
                            <tr>
                                <td class="fw-bold">{{ $t->name }}</td>
                                <td><code class="text-primary">{{ $t->slug }}</code></td>
                                <td class="text-muted">{{ $t->module ?? '-' }}</td>
                                <td class="text-muted">{{ Str::limit($t->subject, 50) }}</td>
                                <td>
                                    @if($t->is_active)
                                        <span class="badge bg-success-lt text-success">{{ __('Actif') }}</span>
                                    @else
                                        <span class="badge bg-danger-lt text-danger">{{ __('Inactif') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.email-templates.edit', $t) }}" class="btn btn-sm btn-primary">
                                        <i class="ti ti-pencil me-1"></i> {{ __('Modifier') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection
