<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.app')

@section('title', __('Mes articles'))

@section('content')

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h1 class="fw-semibold mb-1">{{ __('Mes articles') }}</h1>
        <p class="text-muted mb-0">{{ __('Gérez vos articles de blog.') }}</p>
    </div>
    <a href="{{ route('user.articles.create') }}" class="btn btn-primary rounded-2">
        <i data-lucide="plus-circle"></i>
        {{ __('Nouvel article') }}
    </a>
</div>

@if(session('success'))
<div class="alert alert-success d-flex align-items-center gap-2 mb-3">
    <i data-lucide="check-circle"></i>
    {{ session('success') }}
</div>
@endif

<div class="card">
    <div class="card-body p-0">
        @if($articles->isEmpty())
            <div class="py-5 text-center text-muted">
                <i data-lucide="file-text" class="d-block mx-auto mb-2" style="width:48px;height:48px;"></i>
                <p class="fw-medium mb-1">{{ __('Aucun article pour le moment.') }}</p>
                <p class="text-sm mb-3">{{ __('Créez votre premier article dès maintenant.') }}</p>
                <a href="{{ route('user.articles.create') }}" class="btn btn-primary rounded-2">
                    {{ __('Créer un article') }}
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Titre') }}</th>
                            <th>{{ __('Statut') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($articles as $article)
                        <tr>
                            <td>
                                <p class="fw-semibold mb-0">{{ $article->title }}</p>
                                @if($article->excerpt)
                                    <p class="text-muted small mb-0">{{ Str::limit($article->excerpt, 60) }}</p>
                                @endif
                            </td>
                            <td>
                                @if($article->status === 'published')
                                    <span class="badge fw-semibold bg-success bg-opacity-10 text-success rounded-1">
                                        <span style="width:6px;height:6px;background:currentColor;border-radius:50%;display:inline-block;"></span>
                                        {{ __('Publié') }}
                                    </span>
                                @elseif($article->status === 'draft')
                                    <span class="badge fw-semibold bg-warning bg-opacity-10 text-warning rounded-1">
                                        <span style="width:6px;height:6px;background:currentColor;border-radius:50%;display:inline-block;"></span>
                                        {{ __('Brouillon') }}
                                    </span>
                                @else
                                    <span class="badge fw-semibold bg-secondary bg-opacity-10 text-secondary rounded-1">
                                        <span style="width:6px;height:6px;background:currentColor;border-radius:50%;display:inline-block;"></span>
                                        {{ __('Archivé') }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $article->created_at->format('d/m/Y') }}</td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <a href="{{ route('user.articles.edit', $article) }}"
                                       class="btn btn-sm btn-outline-primary rounded-2">
                                        <i data-lucide="pen"></i> {{ __('Modifier') }}
                                    </a>
                                    @if($article->status === 'published')
                                    <a href="{{ route('blog.show', $article->slug) }}" target="_blank"
                                       class="btn btn-sm btn-outline-secondary rounded-2">
                                        <i data-lucide="eye"></i>
                                    </a>
                                    @endif
                                    <form method="POST" action="{{ route('user.articles.destroy', $article) }}"
                                          onsubmit="return confirm('{{ __('Supprimer cet article ?') }}')" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger rounded-2">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($articles->hasPages())
            <div class="card-body border-top">
                {{ $articles->links() }}
            </div>
            @endif
        @endif
    </div>
</div>

@endsection
