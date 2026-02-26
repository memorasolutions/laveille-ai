@extends('backoffice::themes.backend.layouts.admin', ['title' => $article->title, 'subtitle' => 'Blog'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.blog.articles.index') }}">{{ __('Articles') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Détails') }}</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <h4 class="fw-bold mb-0 text-truncate" style="max-width:60%;">{{ $article->title }}</h4>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('admin.blog.articles.edit', $article) }}"
                   class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
                    <i data-lucide="pencil" class="icon-sm"></i>
                    {{ __('Modifier') }}
                </a>
                <a href="{{ route('admin.blog.articles.index') }}"
                   class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2">
                    <i data-lucide="arrow-left" class="icon-sm"></i>
                    {{ __('Retour') }}
                </a>
            </div>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-xl-8">
                @if($article->featured_image)
                    <img src="{{ Storage::url($article->featured_image) }}"
                         class="w-100 rounded-3 mb-3" style="max-height:18rem;object-fit:cover;"
                         alt="{{ $article->title }}">
                @endif
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted text-uppercase mb-2">{{ __('Contenu') }}</label>
                    <div class="border rounded-3 p-4">{!! $article->safe_content !!}</div>
                </div>
                @if($article->excerpt)
                <div>
                    <label class="form-label small fw-semibold text-muted text-uppercase mb-2">{{ __('Extrait') }}</label>
                    <p class="text-muted small">{{ $article->excerpt }}</p>
                </div>
                @endif
            </div>
            <div class="col-xl-4">
                <div class="card mb-3">
                    <div class="card-header py-3 px-4 border-bottom">
                        <h5 class="fw-semibold mb-0">{{ __('Informations') }}</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <span class="small text-muted">{{ __('Statut') }}</span>
                            <div class="mt-1">
                                @if($article->status === 'published')
                                    <span class="badge bg-success">{{ __('Publié') }}</span>
                                @elseif($article->status === 'draft')
                                    <span class="badge bg-warning text-dark">{{ __('Brouillon') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Archivé') }}</span>
                                @endif
                            </div>
                        </div>
                        @if($article->published_at)
                        <div class="mb-3">
                            <span class="small text-muted">{{ __('Publié le') }}</span>
                            <p class="small fw-medium text-body mt-1 mb-0">{{ $article->published_at->format('d/m/Y H:i') }}</p>
                        </div>
                        @endif
                        <div class="mb-3">
                            <span class="small text-muted">{{ __('Créé le') }}</span>
                            <p class="small fw-medium text-body mt-1 mb-0">{{ $article->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div>
                            <span class="small text-muted">{{ __('Mis à jour') }}</span>
                            <p class="small fw-medium text-body mt-1 mb-0">{{ $article->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
                @if($article->category)
                <div class="card mb-3">
                    <div class="card-header py-3 px-4 border-bottom">
                        <h5 class="fw-semibold mb-0">{{ __('Catégorie') }}</h5>
                    </div>
                    <div class="card-body p-4">
                        <span class="badge rounded-pill" style="background-color:{{ $article->category->color }}20;color:{{ $article->category->color }}">
                            {{ $article->category->name }}
                        </span>
                    </div>
                </div>
                @endif
                @if(!empty($article->tags))
                <div class="card">
                    <div class="card-header py-3 px-4 border-bottom">
                        <h5 class="fw-semibold mb-0">{{ __('Tags') }}</h5>
                    </div>
                    <div class="card-body p-4 d-flex flex-wrap gap-2">
                        @foreach($article->tags as $tag)
                            <span class="badge bg-primary bg-opacity-10 text-primary">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
