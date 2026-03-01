<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin', ['title' => __('Templates email'), 'subtitle' => __('Liste')])

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-2 mb-3">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card h-100 p-0">
    <div class="card-body p-0">
        @if($templates->isEmpty())
            <div class="text-center py-5">
                <i data-lucide="mail" class="text-muted d-block mx-auto mb-3" style="width:48px;height:48px;"></i>
                <p class="text-muted">{{ __('Aucun template email configuré.') }}</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm mb-0">
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
                                <td>{{ $t->name }}</td>
                                <td><code class="text-primary text-sm">{{ $t->slug }}</code></td>
                                <td>{{ $t->module ?? '-' }}</td>
                                <td>{{ Str::limit($t->subject, 50) }}</td>
                                <td>
                                    <span class="badge {{ $t->is_active ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}">
                                        {{ $t->is_active ? __('Actif') : __('Inactif') }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.email-templates.edit', $t) }}" class="btn btn-sm btn-primary rounded-2 d-flex align-items-center gap-2" style="width:fit-content">
                                        <i data-lucide="pen"></i> {{ __('Modifier') }}
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
