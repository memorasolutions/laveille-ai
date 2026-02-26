@extends('backoffice::layouts.admin', ['title' => __('Templates email'), 'subtitle' => __('Liste')])

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show radius-8 mb-20">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card h-100 p-0 radius-12">
    {{-- Principe ADHD: zero bruit visuel - pas de card-header vide --}}
    <div class="card-body p-0">
        @if($templates->isEmpty())
            <div class="text-center py-40">
                <iconify-icon icon="solar:letter-outline" class="text-6xl text-secondary-light mb-16"></iconify-icon>
                <p class="text-secondary-light">{{ __('Aucun template email configuré.') }}</p>
            </div>
        @else
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
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
                                <td><code class="text-primary-600 text-sm">{{ $t->slug }}</code></td>
                                <td>{{ $t->module ?? '-' }}</td>
                                <td>{{ Str::limit($t->subject, 50) }}</td>
                                <td>
                                    <span class="badge {{ $t->is_active ? 'bg-success-focus text-success-main' : 'bg-danger-focus text-danger-main' }}">
                                        {{ $t->is_active ? __('Actif') : __('Inactif') }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.email-templates.edit', $t) }}" class="btn btn-sm btn-primary-600 radius-8 d-flex align-items-center gap-2" style="width:fit-content">
                                        <iconify-icon icon="solar:pen-outline" class="icon"></iconify-icon> {{ __('Modifier') }}
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
