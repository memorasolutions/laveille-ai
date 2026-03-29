@extends(fronttheme_layout())

@section('title', $article->title . ' - ' . __('Actualités') . ' - ' . config('app.name'))
@section('meta_description', Str::limit($article->summary ?? strip_tags($article->description), 160))

@push('styles')
<style>
    .news-show-img { width: 100%; max-height: 400px; object-fit: cover; border-radius: 12px; margin-bottom: 24px; }
    .news-show-meta { font-size: 13px; color: #6B7280; margin-bottom: 20px; }
    .news-show-meta span { margin-right: 16px; }
    .news-summary-box { background: #E6F7F9; border-left: 4px solid var(--c-primary); border-radius: 8px; padding: 16px 20px; margin-bottom: 24px; }
    .news-summary-box strong { display: block; margin-bottom: 8px; color: var(--c-primary); font-size: 14px; }
    .news-summary-box p { margin: 0; color: #1a365d; line-height: 1.6; }
    .news-description { line-height: 1.7; color: var(--c-dark); margin-bottom: 30px; }
</style>
@endpush

@section('content')
<div class="container" style="padding: 30px 15px;">
    <ol class="breadcrumb">
        <li><a href="{{ url('/') }}">{{ __('Accueil') }}</a></li>
        <li><a href="{{ route('news.index') }}">{{ __('Actualités') }}</a></li>
        <li class="active">{{ Str::limit($article->title, 40) }}</li>
    </ol>

    <h1 style="font-family: var(--f-heading); margin-bottom: 12px;">{{ $article->title }}</h1>

    <div class="news-show-meta">
        <span>{{ $article->source->name ?? __('Source inconnue') }}</span>
        @if($article->author)
            <span>{{ $article->author }}</span>
        @endif
        <span>{{ format_date($article->pub_date) }}</span>
    </div>

    @if($article->image_url)
        <img src="{{ $article->image_url }}" alt="{{ $article->title }}" class="news-show-img" loading="lazy">
    @endif

    @if($article->summary)
    <div class="news-summary-box">
        <strong>⚡ {{ __('Résumé IA') }}</strong>
        <p>{{ $article->summary }}</p>
    </div>
    @endif

    <div class="news-description">
        {!! nl2br(e($article->description)) !!}
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $article->url }}" target="_blank" rel="noopener" style="display: inline-block; background: var(--c-primary); color: #fff; padding: 10px 24px; border-radius: 8px; text-decoration: none; font-weight: 600;">{{ __('Voir l\'article original') }}</a>
    </div>

    <div style="text-align: center;">
        <a href="{{ route('news.index') }}" style="color: var(--c-primary); font-weight: 600; text-decoration: none;">← {{ __('Retour aux actualités') }}</a>
    </div>
</div>
@endsection
