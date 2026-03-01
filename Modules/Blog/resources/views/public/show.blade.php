<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('blog::public.layout')

@section('title', $article->meta_title ?? $article->title)

@section('meta')
    @if($article->meta_description)
        <meta name="description" content="{{ $article->meta_description }}">
    @endif
    <meta property="og:title" content="{{ $article->meta_title ?? $article->title }}">
    <meta property="og:description" content="{{ $article->meta_description ?? Str::limit($article->excerpt ?? '', 160) }}">
    <meta property="og:type" content="article">
    @if($article->cover_image)
        <meta property="og:image" content="{{ Storage::url($article->cover_image) }}">
    @endif
    {!! \Modules\SEO\Services\JsonLdService::render(
        \Modules\SEO\Services\JsonLdService::article($article),
        \Modules\SEO\Services\JsonLdService::breadcrumbs([
            ['name' => 'Accueil', 'url' => url('/')],
            ['name' => 'Blog', 'url' => route('blog.index')],
            ['name' => $article->title],
        ])
    ) !!}
@endsection

@section('page_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/') }}" class="cs_accent_color">Accueil</a></li>
            <li class="breadcrumb-item"><a href="{{ route('blog.index') }}" class="cs_accent_color">Blog</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($article->title, 50) }}</li>
        </ol>
    </nav>
@endsection

@section('blog_content')
<div class="cs_height_40 cs_height_lg_30"></div>
<div class="container">
    <div class="row cs_gap_y_40">

        {{-- Article principal --}}
        <div class="col-lg-9">
            <article class="cs_post_details">

                @if($article->cover_image)
                <div class="cs_post_thumbnail cs_radius_15 overflow-hidden mb-4">
                    <img src="{{ Storage::url($article->cover_image) }}" alt="{{ $article->title }}" class="w-100" style="max-height:450px;object-fit:cover">
                </div>
                @endif

                {{-- Meta --}}
                <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                    @if($article->blogCategory)
                    <a href="{{ route('blog.index', ['category' => $article->blogCategory->slug]) }}" class="cs_btn cs_style_1 cs_accent_bg cs_white_color cs_fs_14 cs_radius_30 py-1 px-3">
                        {{ $article->blogCategory->name }}
                    </a>
                    @endif
                    <span class="cs_fs_14 cs_heading_color">
                        <i class="fa-regular fa-calendar me-1"></i>
                        {{ $article->published_at ? $article->published_at->format('d M Y') : '' }}
                    </span>
                    @if($article->user)
                    <span class="cs_fs_14 cs_heading_color">
                        <i class="fa-regular fa-user me-1"></i>
                        {{ $article->user->name }}
                    </span>
                    @endif
                </div>

                <h1 class="cs_fs_50 cs_mb_20">{{ $article->title }}</h1>

                <div class="cs_post_content cs_fs_18" style="line-height:1.8">
                    {!! render_shortcodes($article->safe_content) !!}
                </div>

                {{-- Author box --}}
                @if($article->user)
                <div class="cs_author_box cs_gray_bg_1 cs_radius_15 p-4 mt-5 d-flex gap-4 align-items-start">
                    @if($article->user->avatar)
                    <img src="{{ $article->user->avatar }}" class="rounded-circle flex-shrink-0" width="60" height="60" alt="{{ $article->user->name }}">
                    @else
                    <div class="cs_accent_bg cs_white_color rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:60px;height:60px;font-size:1.5rem;font-weight:700">
                        {{ strtoupper(substr($article->user->name, 0, 1)) }}
                    </div>
                    @endif
                    <div>
                        <h4 class="cs_fs_21 mb-1">
                            <a href="{{ route('blog.author', $article->user) }}" class="cs_heading_color text-decoration-none">{{ $article->user->name }}</a>
                        </h4>
                        @if($article->user->bio)
                        <p class="mb-0 cs_fs_16">{{ $article->user->bio }}</p>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Tags --}}
                @if($article->tagsRelation->isNotEmpty())
                <div class="mt-4 mb-2">
                    <p class="cs_fs_14 cs_semibold mb-2">Tags :</p>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($article->tagsRelation as $tag)
                        <a href="{{ route('blog.tag', $tag) }}" class="cs_btn cs_style_1 cs_fs_14 cs_semibold cs_radius_30 py-1 px-3" style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}; border: 1px solid {{ $tag->color }}40;">{{ $tag->name }}</a>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="mt-4">
                    <a href="{{ route('blog.index') }}" class="cs_btn cs_style_1 cs_heading_bg cs_white_color cs_fs_16 cs_semibold cs_radius_30">
                        <span>← Retour au blog</span>
                    </a>
                </div>

                {{-- Commentaires --}}
                <div class="mt-5 pt-4 border-top">
                    <h3 class="cs_fs_29 cs_mb_20">Commentaires</h3>

                    @if($comments->isNotEmpty())
                    <div class="mb-4">
                        @foreach($comments as $comment)
                        <div class="cs_gray_bg_1 cs_radius_10 p-4 mb-3">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="cs_accent_bg cs_white_color rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px;font-size:0.9rem;font-weight:700">
                                    {{ strtoupper(substr($comment->author->name ?? $comment->guest_name ?? 'A', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="mb-0 cs_fs_14 cs_semibold">{{ $comment->author->name ?? $comment->guest_name ?? 'Anonyme' }}</p>
                                    <p class="mb-0 cs_fs_13 text-muted">{{ $comment->created_at->format('d M Y') }}</p>
                                </div>
                            </div>
                            <p class="mb-0 cs_fs_16">{{ $comment->content }}</p>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    <div class="cs_gray_bg_1 cs_radius_15 p-4">
                        <h4 class="cs_fs_21 cs_mb_15">Laisser un commentaire</h4>
                        <form method="POST" action="{{ route('blog.comments.store', $article->slug) }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <input type="text" name="name" class="form-control" placeholder="Votre nom" value="{{ old('name') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="email" name="email" class="form-control" placeholder="Votre email" value="{{ old('email') }}">
                                </div>
                                <div class="col-12">
                                    <textarea name="content" class="form-control" rows="4" placeholder="Votre commentaire..." required>{{ old('content') }}</textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="cs_btn cs_style_1 cs_accent_bg cs_purple_hover cs_white_color cs_fs_16 cs_semibold cs_radius_30">
                                        <span>Envoyer le commentaire</span>
                                        <span class="cs_btn_icon cs_center overflow-hidden"><i class="fa-solid fa-arrow-right"></i></span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </article>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-3">
            <aside class="cs_sidebar cs_style_1">
                @if(isset($relatedArticles) && count($relatedArticles) > 0)
                <div class="cs_sidebar_widget">
                    <h3 class="cs_widget_title cs_fs_29 cs_normal cs_mb_23">Articles liés</h3>
                    <ul class="cs_latestpost_list cs_mp_0">
                        @foreach($relatedArticles as $related)
                        <li>
                            <div class="cs_latest_post cs_style_1">
                                <h3 class="cs_fs_21 cs_normal cs_mb_6">
                                    <a href="{{ route('blog.show', $related->slug) }}" class="cs_heading_color text-decoration-none">{{ $related->title }}</a>
                                </h3>
                                <p class="mb-0 cs_fs_14">{{ $related->published_at ? $related->published_at->format('d M Y') : '' }}</p>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- Newsletter --}}
                <div class="cs_sidebar_widget cs_gray_bg_1 cs_radius_15 p-4 text-center">
                    <h4 class="cs_fs_21 cs_mb_10">Newsletter</h4>
                    <p class="cs_fs_14 mb-3">Recevez nos derniers articles par email.</p>
                    <form method="POST" action="/newsletter/subscribe">
                        @csrf
                        <input type="email" name="email" class="form-control mb-2" placeholder="votre@email.com" required>
                        <button type="submit" class="cs_btn cs_style_1 cs_accent_bg cs_white_color cs_fs_14 cs_semibold cs_radius_30 w-100">
                            <span>S'abonner</span>
                        </button>
                    </form>
                </div>

                <div class="cs_sidebar_widget text-center">
                    <a href="{{ route('blog.index') }}" class="cs_btn cs_style_1 cs_accent_bg cs_purple_hover cs_white_color cs_fs_16 cs_semibold cs_radius_30 w-100">
                        <span>Tous les articles</span>
                    </a>
                </div>
            </aside>
        </div>

    </div>
</div>
<div class="cs_height_100 cs_height_lg_80"></div>
@endsection
