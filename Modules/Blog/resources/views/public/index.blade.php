@extends('blog::public.layout')

@section('title', $currentCategory ? $currentCategory->name.' — Blog' : 'Blog')

@section('meta')
    {!! \Modules\SEO\Services\JsonLdService::render(
        ['@type' => 'CollectionPage', 'name' => 'Blog', 'url' => route('blog.index'), 'description' => 'Articles, tutoriels et actualités.'],
        \Modules\SEO\Services\JsonLdService::breadcrumbs([
            ['name' => 'Accueil', 'url' => url('/')],
            ['name' => 'Blog'],
        ])
    ) !!}
@endsection

@section('page_header')
    <div class="text-center">
        <h2 class="cs_fs_50 cs_mb_15 wow fadeInDown">
            @if($currentCategory) {{ $currentCategory->name }} @else Blog @endif
        </h2>
        <p class="mb-0 wow fadeInUp">Articles, tutoriels et actualités.</p>
    </div>
@endsection

@section('blog_content')
<div class="cs_height_64 cs_height_lg_50"></div>
<div class="container">
    <div class="row cs_gap_y_60">

        {{-- Sidebar --}}
        <div class="col-lg-3 order-lg-2 wow fadeInRight">
            <aside class="cs_sidebar cs_style_1">
                {{-- Recherche --}}
                <div class="cs_sidebar_widget">
                    <form method="GET" action="{{ route('blog.index') }}" class="cs_search_form position-relative">
                        <span class="cs_search_icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="search" name="q" placeholder="Rechercher..." class="cs_form_field cs_radius_30" value="{{ request('q') }}">
                    </form>
                </div>

                {{-- Catégories --}}
                @if($categories->isNotEmpty())
                <div class="cs_sidebar_widget">
                    <h3 class="cs_widget_title cs_fs_29 cs_normal cs_mb_16">Catégories</h3>
                    <ul class="cs_category_list cs_mp_0">
                        <li>
                            <a href="{{ route('blog.index') }}" aria-label="Tous">
                                Tous les articles
                            </a>
                        </li>
                        @foreach($categories as $cat)
                        @if($cat->articles_count > 0)
                        <li>
                            <a href="{{ route('blog.index', ['category' => $cat->slug]) }}" aria-label="{{ $cat->name }}">
                                {{ $cat->name }} ({{ $cat->articles_count }})
                            </a>
                        </li>
                        @endif
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- Articles récents --}}
                <div class="cs_sidebar_widget">
                    <h3 class="cs_widget_title cs_fs_29 cs_normal cs_mb_23">Articles récents</h3>
                    <ul class="cs_latestpost_list cs_mp_0">
                        @foreach($articles->take(4) as $recent)
                        <li>
                            <div class="cs_latest_post cs_style_1">
                                <h3 class="cs_fs_21 cs_normal cs_mb_6">
                                    <a href="{{ route('blog.show', $recent->slug) }}" aria-label="{{ $recent->title }}">{{ $recent->title }}</a>
                                </h3>
                                <p class="mb-0">{{ $recent->published_at ? $recent->published_at->format('d M Y') : '' }}</p>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Tags populaires --}}
                @if($popularTags->isNotEmpty())
                <div class="cs_sidebar_widget">
                    <h3 class="cs_widget_title cs_fs_29 cs_normal cs_mb_23">Tags populaires</h3>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($popularTags as $tag)
                        <a href="{{ route('blog.tag', $tag) }}" class="cs_btn cs_style_1 cs_gray_bg_1 cs_heading_color cs_fs_14 cs_radius_30 py-1 px-3 {{ ($currentTag ?? '') === $tag->slug ? 'cs_accent_bg cs_white_color' : '' }}">
                            {{ $tag->name }} ({{ $tag->articles_count }})
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </aside>
        </div>

        {{-- Contenu principal --}}
        <div class="col-lg-9 order-lg-1">
            @forelse($articles as $article)
            <article class="cs_post cs_style_1 mb-5 wow fadeInUp">
                <div class="row align-items-center g-4">
                    @if($article->cover_image)
                    <div class="col-md-4">
                        <div class="cs_post_thumbnail cs_radius_15 overflow-hidden">
                            <a href="{{ route('blog.show', $article->slug) }}">
                                <img src="{{ Storage::url($article->cover_image) }}" alt="{{ $article->title }}" class="w-100" style="height:180px;object-fit:cover">
                            </a>
                        </div>
                    </div>
                    @endif
                    <div class="{{ $article->cover_image ? 'col-md-8' : 'col-12' }}">
                        <div class="cs_post_info">
                            @if($article->blogCategory)
                            <span class="cs_post_category cs_accent_color cs_semibold cs_fs_14 mb-2 d-block">
                                <a href="{{ route('blog.index', ['category' => $article->blogCategory->slug]) }}" class="cs_accent_color">
                                    {{ $article->blogCategory->name }}
                                </a>
                            </span>
                            @endif
                            <h3 class="cs_fs_21 cs_mb_10">
                                <a href="{{ route('blog.show', $article->slug) }}" class="cs_heading_color text-decoration-none">{{ $article->title }}</a>
                            </h3>
                            <p class="cs_fs_14 mb-2">{{ $article->published_at ? $article->published_at->format('d M Y') : '' }}</p>
                            @if($article->excerpt)
                            <p class="mb-3 cs_fs_16">{{ Str::limit($article->excerpt, 120) }}</p>
                            @endif
                            <a href="{{ route('blog.show', $article->slug) }}" class="cs_btn cs_style_1 cs_accent_bg cs_white_color cs_fs_14 cs_semibold cs_radius_30" aria-label="Lire l'article">
                                <span>Lire la suite</span>
                                <span class="cs_btn_icon cs_center overflow-hidden"><i class="fa-solid fa-arrow-right"></i></span>
                            </a>
                        </div>
                    </div>
                </div>
            </article>
            @empty
            <div class="text-center py-5">
                <i class="fa-solid fa-newspaper fa-3x cs_accent_color mb-3"></i>
                <h3 class="cs_fs_29">Aucun article pour le moment</h3>
                <p>Revenez bientôt pour découvrir nos publications.</p>
            </div>
            @endforelse

            {{-- Pagination Bootstrap 5 --}}
            @if($articles->hasPages())
            <div class="mt-4">
                {{ $articles->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
            @endif
        </div>

    </div>
</div>
<div class="cs_height_100 cs_height_lg_80"></div>
@endsection
