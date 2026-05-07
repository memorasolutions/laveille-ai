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
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-12">

                {{-- Intro auteur : avatar + rôle + boutons sociaux --}}
                <div style="display: flex; gap: 24px; align-items: center; flex-wrap: wrap; margin-bottom: 36px; padding: 24px; background: var(--c-surface, #F8FAFB); border-radius: var(--r-base, 0.75rem); border: 1px solid #e5e7eb;">
                    <img src="{{ asset('images/logo-avatar.png') }}"
                         alt="{{ $author['name'] }}"
                         style="width: 96px; height: 96px; border-radius: 50%; flex-shrink: 0; object-fit: cover; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    <div style="flex: 1; min-width: 200px;">
                        <p style="font-size: 1rem; font-weight: 600; color: var(--c-primary, #064E5A); margin: 0 0 6px 0;">{{ $author['role'] }}</p>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            @if(! empty($author['linkedin']))
                                <a href="{{ $author['linkedin'] }}" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 6px; background: var(--c-primary, #064E5A); color: #fff; padding: 8px 18px; border-radius: 8px; font-weight: 700; text-decoration: none; min-height: 36px; font-size: 0.9rem;">
                                    LinkedIn →
                                </a>
                            @endif
                            @if(! empty($author['website']))
                                <a href="{{ $author['website'] }}" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 6px; background: #fff; color: var(--c-primary, #064E5A); padding: 8px 18px; border-radius: 8px; font-weight: 700; text-decoration: none; min-height: 36px; font-size: 0.9rem; border: 1px solid var(--c-primary, #064E5A);">
                                    Site web →
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <h2 style="margin-bottom: 20px; font-family: var(--f-heading); color: var(--c-dark);">{{ __('À propos') }}</h2>
                <p style="font-size: 1.05rem; line-height: 1.7; color: var(--c-text-secondary);">{{ $author['bio'] }}</p>

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
