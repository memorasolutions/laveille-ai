@extends('fronttheme::layouts.master')

@section('title', $author['name'] . ' - Auteur - La veille')
@section('meta_description', \Illuminate\Support\Str::limit($author['bio'], 160))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $author['name'] ?? __('Auteur'), 'breadcrumbItems' => [__('Auteur'), $author['name'] ?? '']])
@endsection

@push('head')
    <script type="application/ld+json">{!! $schemaJson !!}</script>
@endpush

@section('content')
<section style="background: var(--c-primary); color: #fff; padding: 80px 0; text-align: center;">
    <div class="container">
        <img src="{{ asset('images/logo-avatar.png') }}"
             alt="{{ $author['name'] }}"
             style="width: 128px; height: 128px; border-radius: 50%; border: 4px solid #fff; box-shadow: 0 4px 16px rgba(0,0,0,0.15); margin-bottom: 20px; object-fit: cover;">
        <h1 style="color: #fff; margin: 0 0 8px 0; font-family: var(--f-heading);">{{ $author['name'] }}</h1>
        <p style="font-size: 1.1rem; color: rgba(255,255,255,0.92); margin-bottom: 24px;">{{ $author['role'] }}</p>
        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
            @if(! empty($author['linkedin']))
                <a href="{{ $author['linkedin'] }}" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 6px; background: #fff; color: var(--c-primary); padding: 10px 22px; border-radius: 8px; font-weight: 700; text-decoration: none; min-height: 44px;">
                    LinkedIn →
                </a>
            @endif
            @if(! empty($author['website']))
                <a href="{{ $author['website'] }}" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.18); color: #fff; padding: 10px 22px; border-radius: 8px; font-weight: 700; text-decoration: none; min-height: 44px; border: 1px solid rgba(255,255,255,0.35);">
                    Site web →
                </a>
            @endif
        </div>
    </div>
</section>

<section style="padding: 60px 0;">
    <div class="container" style="max-width: 720px; margin: 0 auto;">
        <h2 style="margin-bottom: 20px; font-family: var(--f-heading); color: var(--c-dark);">À propos</h2>
        <p style="font-size: 1.05rem; line-height: 1.7; color: var(--c-text-secondary);">{{ $author['bio'] }}</p>

        @if(! empty($author['qualifications']) && is_array($author['qualifications']))
        <h2 style="margin: 48px 0 20px; font-family: var(--f-heading); color: var(--c-dark);">Qualifications</h2>
        <ul style="list-style: none; padding: 0; margin: 0;">
            @foreach($author['qualifications'] as $qual)
                <li style="padding: 12px 0; border-bottom: 1px solid #e5e7eb; color: var(--c-text-secondary); display: flex; gap: 12px; align-items: flex-start;">
                    <span style="color: var(--c-primary); font-weight: 700; flex-shrink: 0;">✓</span>
                    <span>{{ $qual }}</span>
                </li>
            @endforeach
        </ul>
        @endif

        @if($articles->isNotEmpty())
        <h2 style="margin: 48px 0 20px; font-family: var(--f-heading); color: var(--c-dark);">Derniers articles</h2>
        <ul style="list-style: none; padding: 0; margin: 0;">
            @foreach($articles as $article)
                <li style="padding: 16px 0; border-bottom: 1px solid #e5e7eb;">
                    <a href="{{ route('blog.show', $article->slug) }}" style="color: var(--c-primary); font-weight: 600; text-decoration: none; font-size: 1.05rem;">{{ $article->title }}</a>
                    @if($article->published_at ?? null)
                        <small style="display: block; color: var(--c-text-muted); margin-top: 4px;">{{ $article->published_at->locale('fr_CA')->isoFormat('LL') }}</small>
                    @endif
                </li>
            @endforeach
        </ul>
        @endif
    </div>
</section>
@endsection
