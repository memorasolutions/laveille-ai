<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Tags', 'subtitle' => 'Blog'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.blog.articles.index') }}">Blog</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Tags') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="tags" class="icon-md text-primary"></i>{{ __('Tags') }}</h4>
    <x-backoffice::help-modal id="helpTagsModal" :title="__('Tags du blog')" icon="tags" :buttonLabel="__('Aide')">
        @include('blog::themes.backend.admin.tags._help')
    </x-backoffice::help-modal>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Fermer') }}"></button>
    </div>
@endif

<div class="card">
    <div class="card-header py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Tags ({{ $tags->total() }})</h5>
        <a href="{{ route('admin.blog.tags.create') }}" class="btn btn-primary btn-sm">
            <i data-lucide="plus" class="me-1"></i> {{ __('Nouveau tag') }}
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:60px">{{ __('Couleur') }}</th>
                        <th>{{ __('Nom') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th style="width:100px" class="text-center">{{ __('Articles') }}</th>
                        <th style="width:120px" class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tags as $tag)
                    <tr>
                        <td>
                            <div class="rounded-circle" style="width:20px;height:20px;background-color:{{ $tag->color }}"></div>
                        </td>
                        <td><strong>{{ $tag->name }}</strong></td>
                        <td class="text-muted">{{ Str::limit($tag->description, 60) }}</td>
                        <td class="text-center">
                            <span class="badge bg-primary">{{ $tag->articles_count }}</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.blog.tags.edit', $tag) }}" class="btn btn-sm btn-outline-primary me-1" title="{{ __('Modifier') }}">
                                <i data-lucide="pencil"></i>
                            </a>
                            <form action="{{ route('admin.blog.tags.destroy', $tag) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Supprimer') }}" onclick="return confirm('{{ __('Supprimer ce tag ?') }}')">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">{{ __('Aucun tag pour le moment.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tags->hasPages())
        <div class="px-4 py-3">
            {{ $tags->links() }}
        </div>
        @endif
    </div>
</div>

@endsection
