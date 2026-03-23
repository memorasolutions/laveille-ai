<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Répertoire techno')])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i data-lucide="layout-grid" class="icon-md text-primary"></i> {{ __('Répertoire techno') }}</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.directory.moderation') }}" class="btn btn-outline-warning btn-sm">🛡️ {{ __('Modération') }}</a>
        <a href="{{ route('admin.directory.create') }}" class="btn btn-primary btn-sm">+ {{ __('Nouvel outil') }}</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>{{ __('Outil') }}</th>
                    <th>{{ __('Tarification') }}</th>
                    <th>{{ __('Catégories') }}</th>
                    <th>{{ __('Clics') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tools as $tool)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if($tool->url)<img src="https://www.google.com/s2/favicons?domain={{ parse_url($tool->url, PHP_URL_HOST) }}&sz=16" alt="">@endif
                            <strong>{{ $tool->name }}</strong>
                        </div>
                    </td>
                    <td><span class="badge bg-primary bg-opacity-10 text-primary">{{ ucfirst($tool->pricing) }}</span></td>
                    <td>{{ $tool->categories->pluck('name')->implode(', ') ?: '-' }}</td>
                    <td>{{ $tool->clicks_count }}</td>
                    <td class="text-end">
                        <a href="{{ route('directory.show', $tool->slug) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i data-lucide="eye" class="icon-sm"></i></a>
                        <a href="{{ route('admin.directory.edit', $tool) }}" class="btn btn-sm btn-outline-primary"><i data-lucide="pencil" class="icon-sm"></i></a>
                        <form action="{{ route('admin.directory.destroy', $tool) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Supprimer ?') }}')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i data-lucide="trash-2" class="icon-sm"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $tools->links() }}</div>
</div>
@endsection
