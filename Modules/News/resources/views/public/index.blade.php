@extends(fronttheme_layout())

@section('title', __('Actualités IA') . ' - ' . config('app.name'))
@section('meta_description', __('Les dernières actualités IA et technologie, résumées par l\'intelligence artificielle.'))

@push('styles')
<style>
    .news-grid { display: flex !important; flex-wrap: wrap !important; }
    .news-grid > [class*='col-'] { display: flex !important; flex-direction: column !important; }
    .news-card { border: 1px solid #E5E7EB; border-radius: 12px; overflow: hidden; background: #fff; transition: transform 0.2s, box-shadow 0.2s; height: 100%; margin-bottom: 20px; display: flex !important; flex-direction: column !important; }
    .news-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); border-color: var(--c-primary); }
    .news-card-img { width: 100%; height: 180px; object-fit: cover; }
    .news-card-gradient { width: 100%; height: 180px; background: linear-gradient(135deg, #0B7285, #1a365d); display: flex !important; align-items: center !important; justify-content: center !important; }
    .news-card-gradient span { font-size: 2rem; color: #fff; opacity: 0.7; }
    .news-card-body { padding: 16px; flex-grow: 1; display: flex !important; flex-direction: column !important; }
    .news-card-body h3 { margin: 0 0 8px; font-size: 1.1rem; font-weight: 700; font-family: var(--f-heading); }
    .news-card-body h3 a { color: var(--c-dark); text-decoration: none; }
    .news-card-body h3 a:hover { color: var(--c-primary); }
    .news-card-summary { font-size: 13px; color: #6B7280; line-height: 1.5; flex-grow: 1; margin-bottom: 12px; }
    .news-card-meta { font-size: 12px; color: #9CA3AF; padding-top: 10px; border-top: 1px solid #F3F4F6; display: flex !important; justify-content: space-between !important; align-items: center !important; }
    .news-card-source { background: #E5E7EB; color: #374151; padding: 2px 8px; border-radius: 4px; font-weight: 600; font-size: 11px; }
    .news-card-link { display: inline-block; margin-top: 10px; color: var(--c-primary); font-weight: 600; font-size: 13px; text-decoration: none; }
    .news-card-link:hover { text-decoration: underline; }
</style>
@endpush

@section('content')
<div class="container" style="padding: 30px 15px;">
    <ol class="breadcrumb">
        <li><a href="{{ url('/') }}">{{ __('Accueil') }}</a></li>
        <li class="active">{{ __('Actualités') }}</li>
    </ol>

    <h1 style="font-family: var(--f-heading); margin-bottom: 4px;">{{ __('Actualités IA et technologie') }}</h1>
    <p style="color: #6B7280; margin-bottom: 24px;">{{ __('Résumés automatiques par intelligence artificielle') }}</p>

    <div class="row news-grid">
        @forelse($articles as $article)
        <div class="col-md-6 col-sm-6 col-xs-12">
            <div class="news-card">
                @if($article->image_url)
                    <img src="{{ $article->image_url }}" alt="{{ $article->title }}" class="news-card-img" loading="lazy">
                @else
                    <div class="news-card-gradient"><span>📰</span></div>
                @endif
                <div class="news-card-body">
                    <h3><a href="{{ route('news.show', $article) }}">{{ $article->title }}</a></h3>
                    <p class="news-card-summary">{{ Str::limit($article->summary ?? strip_tags($article->description), 150) }}</p>
                    <div class="news-card-meta">
                        <span class="news-card-source">{{ $article->source->name ?? __('Source inconnue') }}</span>
                        <span>{{ format_date($article->pub_date) }}</span>
                    </div>
                    <a href="{{ $article->url }}" target="_blank" rel="noopener" class="news-card-link">{{ __('Lire l\'article original') }} →</a>
                </div>
            </div>
        </div>
        @empty
        <div class="col-xs-12">
            <div style="text-align: center; padding: 60px 20px; color: #9CA3AF;">
                <div style="font-size: 3rem; margin-bottom: 12px;">📰</div>
                <p>{{ __('Aucune actualité pour le moment.') }}</p>
            </div>
        </div>
        @endforelse
    </div>

    @if($articles->hasPages())
    <div style="text-align: center; margin-top: 20px;">
        {{ $articles->links() }}
    </div>
    @endif
</div>
@endsection
