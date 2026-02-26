@extends('backoffice::layouts.admin', ['title' => $article->title, 'subtitle' => 'Blog'])

@section('content')

<div class="card radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24 d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h6 class="mb-0 text-truncate" style="max-width:60%;">{{ $article->title }}</h6>
        <div class="d-flex gap-2 flex-shrink-0">
            <a href="{{ route('admin.blog.articles.edit', $article) }}" class="btn btn-sm btn-primary-600 radius-8 d-flex align-items-center gap-2">
                <iconify-icon icon="solar:pen-bold" class="icon"></iconify-icon>
                {{ __('Modifier') }}
            </a>
            <a href="{{ route('admin.blog.articles.index') }}" class="btn btn-sm btn-outline-secondary-600 radius-8 d-flex align-items-center gap-2">
                <iconify-icon icon="solar:arrow-left-outline" class="icon"></iconify-icon>
                {{ __('Retour') }}
            </a>
        </div>
    </div>
    <div class="card-body p-24">
        <div class="row gy-3">
            <div class="col-md-8">
                @if($article->featured_image)
                    <img src="{{ Storage::url($article->featured_image) }}" class="img-fluid rounded mb-20 w-100" style="max-height:300px;object-fit:cover;" alt="{{ $article->title }}">
                @endif
                <div class="mb-20">
                    <label class="form-label fw-semibold text-secondary-light text-sm">{{ __('Contenu') }}</label>
                    <div class="border radius-8 p-16">{!! $article->content !!}</div>
                </div>
                @if($article->excerpt)
                <div class="mb-0">
                    <label class="form-label fw-semibold text-secondary-light text-sm">{{ __('Extrait') }}</label>
                    <p class="text-secondary-light">{{ $article->excerpt }}</p>
                </div>
                @endif
            </div>
            <div class="col-md-4">
                <div class="card mb-20">
                    <div class="card-header">
                        <h6 class="mb-0">{{ __('Informations') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-12">
                            <span class="text-secondary-light text-sm">{{ __('Statut') }}</span>
                            <div class="mt-4">
                                @if($article->status === 'published')
                                    <span class="badge bg-success-100 text-success-600">{{ __('Publié') }}</span>
                                @elseif($article->status === 'draft')
                                    <span class="badge bg-warning-100 text-warning-600">{{ __('Brouillon') }}</span>
                                @else
                                    <span class="badge bg-secondary-100 text-secondary-600">{{ __('Archivé') }}</span>
                                @endif
                            </div>
                        </div>
                        @if($article->published_at)
                        <div class="mb-12">
                            <span class="text-secondary-light text-sm">{{ __('Publié le') }}</span>
                            <p class="mb-0 fw-medium text-sm">{{ $article->published_at->format('d/m/Y H:i') }}</p>
                        </div>
                        @endif
                        <div class="mb-12">
                            <span class="text-secondary-light text-sm">{{ __('Créé le') }}</span>
                            <p class="mb-0 fw-medium text-sm">{{ $article->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="mb-0">
                            <span class="text-secondary-light text-sm">{{ __('Mis à jour') }}</span>
                            <p class="mb-0 fw-medium text-sm">{{ $article->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
                @if($article->category)
                <div class="card mb-20">
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
                            <span class="badge bg-primary-100 text-primary-600">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
