<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Outils'), 'subtitle' => __('Gestion')])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i data-lucide="wrench" class="icon-md text-primary"></i> {{ __('Outils interactifs') }}</h4>
    <a href="{{ route('admin.tools.create') }}" class="btn btn-primary btn-sm">+ {{ __('Nouvel outil') }}</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>{{ __('Ordre') }}</th>
                    <th>{{ __('Nom') }}</th>
                    <th>{{ __('Slug') }}</th>
                    <th>{{ __('Statut') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tools as $tool)
                <tr>
                    <td>{{ $tool->sort_order }}</td>
                    <td><strong>{{ $tool->name }}</strong></td>
                    <td><code>{{ $tool->slug }}</code></td>
                    <td>
                        <form action="{{ route('admin.tools.toggle', $tool) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="badge border-0 cursor-pointer {{ $tool->is_active ? 'bg-success' : 'bg-danger' }}">
                                {{ $tool->is_active ? __('Actif') : __('Inactif') }}
                            </button>
                        </form>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('tools.show', $tool->slug) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="{{ __('Voir') }}"><i data-lucide="eye" class="icon-sm"></i></a>
                        <a href="{{ route('admin.tools.edit', $tool) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Modifier') }}"><i data-lucide="pencil" class="icon-sm"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
