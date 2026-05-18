@extends('fronttheme::layouts.master')

@section('title', $author['name'] . ' — ' . ($author['role'] ?? __('Auteur')) . ' — La veille')
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($author['bio'] ?? ''), 160))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $author['name'] ?? __('Auteur'), 'breadcrumbItems' => [__('Auteur'), $author['name'] ?? '']])
@endsection

@push('head')
    <meta name="author" content="{{ $author['name'] ?? '' }}">
    <link rel="me" href="{{ $author['linkedin'] ?? '' }}">
    <script type="application/ld+json">{!! $schemaJson !!}</script>
@endpush

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-12">

                {{-- H1 EEAT 2026 NN/g : nom + rôle pour signal expertise immédiat --}}
                <h1 style="margin: 0 0 24px; font-family: var(--f-heading); color: var(--c-dark); font-size: 2rem;">
                    {{ $author['name'] }} — {{ $author['role'] }}
                </h1>

                {{-- Intro auteur : photo réelle + bio + boutons sociaux --}}
                <div style="display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap; margin-bottom: 36px; padding: 24px; background: var(--c-surface, #F8FAFB); border-radius: var(--r-base, 0.75rem); border: 1px solid #e5e7eb;">
                    <picture style="flex-shrink: 0;">
                        <source srcset="{{ asset('images/author/stephane-lapointe-256.webp') }}" type="image/webp">
                        <img src="{{ asset('images/author/stephane-lapointe-256.jpg') }}"
                             alt="{{ $author['name'] }}, {{ $author['role'] }}"
                             width="256" height="256"
                             loading="lazy" decoding="async"
                             style="width: 160px; height: 160px; border-radius: 50%; object-fit: cover; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.08);"
                             itemprop="image">
                    </picture>
                    <div style="flex: 1; min-width: 240px;">
                        <p style="font-size: 1.05rem; line-height: 1.65; color: var(--c-text-secondary); margin: 0 0 16px;">{{ $author['bio'] }}</p>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            @if(! empty($author['linkedin']))
                                {{-- rel="me" critique EEAT 2026 (signal IndieWeb + AI engines) --}}
                                <a href="{{ $author['linkedin'] }}" target="_blank" rel="me noopener noreferrer" style="display: inline-flex; align-items: center; gap: 6px; background: var(--c-primary, #064E5A); color: #fff; padding: 10px 20px; border-radius: 8px; font-weight: 700; text-decoration: none; min-height: 44px; min-width: 44px; font-size: 0.95rem;">
                                    LinkedIn →
                                </a>
                            @endif
                            @if(! empty($author['website']))
                                <a href="{{ $author['website'] }}" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 6px; background: #fff; color: var(--c-primary, #064E5A); padding: 10px 20px; border-radius: 8px; font-weight: 700; text-decoration: none; min-height: 44px; min-width: 44px; font-size: 0.95rem; border: 1px solid var(--c-primary, #064E5A);">
                                    MEMORA solutions →
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                @if(! empty($author['qualifications']) && is_array($author['qualifications']))
                <h2 style="margin: 48px 0 20px; font-family: var(--f-heading); color: var(--c-dark);">{{ __('Qualifications') }}</h2>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    @foreach($author['qualifications'] as $qual)
                        <li style="padding: 12px 0; border-bottom: 1px solid #e5e7eb; color: var(--c-text-secondary); display: flex; gap: 12px; align-items: flex-start;">
                            <span style="color: var(--c-primary); font-weight: 700; flex-shrink: 0;" aria-hidden="true">✓</span>
                            <span>{{ $qual }}</span>
                        </li>
                    @endforeach
                </ul>
                @endif

                {{-- Livre auteur (réutilise composant DRY <x-fronttheme::book-promo>) --}}
                <h2 style="margin: 48px 0 20px; font-family: var(--f-heading); color: var(--c-dark);">{{ __('Livre publié') }}</h2>
                <x-fronttheme::book-promo variant="inline" />

                @if($articles->isNotEmpty())
                <h2 style="margin: 48px 0 20px; font-family: var(--f-heading); color: var(--c-dark);">{{ __('Derniers articles') }}</h2>
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
        </div>
    </div>
</section>
@endsection
