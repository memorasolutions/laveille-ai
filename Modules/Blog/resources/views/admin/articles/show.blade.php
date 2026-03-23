<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin', ['title' => $article->title, 'subtitle' => 'Blog'])

@section('content')

<div class="card">
    <div class="card-header border-bottom py-3 px-4 d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h6 class="mb-0 text-truncate" style="max-width:60%;">{{ $article->title }}</h6>
        <div class="d-flex gap-2 flex-shrink-0">
            <a href="{{ route('admin.blog.articles.edit', $article) }}" class="btn btn-sm btn-primary rounded-2 d-flex align-items-center gap-2">
                <i data-lucide="pen-line"></i>
                {{ __('Modifier') }}
            </a>
            <a href="{{ route('admin.blog.articles.index') }}" class="btn btn-sm btn-outline-secondary rounded-2 d-flex align-items-center gap-2">
                <i data-lucide="arrow-left"></i>
                {{ __('Retour') }}
            </a>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="row gy-3">
            <div class="col-md-8">
                @if($article->featured_image)
                    <img src="{{ asset($article->featured_image) }}" class="img-fluid rounded mb-3 w-100" style="max-height:300px;object-fit:cover;" alt="{{ $article->title }}">
                @endif
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted text-sm">{{ __('Contenu') }}</label>
                    <div class="border rounded-2 p-3">{!! $article->safe_content !!}</div>
                </div>
                @if($article->excerpt)
                <div class="mb-0">
                    <label class="form-label fw-semibold text-muted text-sm">{{ __('Extrait') }}</label>
                    <p class="text-muted">{{ $article->excerpt }}</p>
                </div>
                @endif
            </div>
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">{{ __('Informations') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <span class="text-muted text-sm">{{ __('Statut') }}</span>
                            <div class="mt-1">
                                @if($article->status === 'published')
                                    <span class="badge bg-success bg-opacity-10 text-success">{{ __('Publié') }}</span>
                                @elseif($article->status === 'draft')
                                    <span class="badge bg-warning bg-opacity-10 text-warning">{{ __('Brouillon') }}</span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ __('Archivé') }}</span>
                                @endif
                            </div>
                        </div>
                        @if($article->published_at)
                        <div class="mb-2">
                            <span class="text-muted text-sm">{{ __('Publié le') }}</span>
                            <p class="mb-0 fw-medium text-sm">{{ $article->published_at->format('d/m/Y H:i') }}</p>
                        </div>
                        @endif
                        <div class="mb-2">
                            <span class="text-muted text-sm">{{ __('Créé le') }}</span>
                            <p class="mb-0 fw-medium text-sm">{{ $article->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="mb-0">
                            <span class="text-muted text-sm">{{ __('Mis à jour') }}</span>
                            <p class="mb-0 fw-medium text-sm">{{ $article->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
                @if($article->category)
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">{{ __('Catégorie') }}</h6>
                    </div>
                    <div class="card-body">
                        <span class="badge" style="background-color:{{ $article->category->color }}20;color:{{ $article->category->color }}">
                            {{ $article->category->name }}
                        </span>
                    </div>
                </div>
                @endif
                @if(!empty($article->tags))
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">{{ __('Tags') }}</h6>
                    </div>
                    <div class="card-body d-flex flex-wrap gap-2">
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
