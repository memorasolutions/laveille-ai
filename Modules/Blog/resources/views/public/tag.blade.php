<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('blog::public.layout')

@section('title', $tag->name . ' — Blog')

@section('meta')
    <meta name="description" content="{{ $tag->description ?? 'Articles tagués ' . $tag->name }}">
    {!! \Modules\SEO\Services\JsonLdService::render(
        ['@type' => 'CollectionPage', 'name' => 'Tag : ' . $tag->name, 'url' => route('blog.tag', $tag), 'description' => $tag->description ?? ''],
        \Modules\SEO\Services\JsonLdService::breadcrumbs([
            ['name' => 'Accueil', 'url' => url('/')],
            ['name' => 'Blog', 'url' => route('blog.index')],
            ['name' => $tag->name],
        ])
    ) !!}
@endsection

@section('page_header')
    <div class="text-center">
        <h2 class="cs_fs_50 cs_mb_15 wow fadeInDown">Articles tagués « {{ $tag->name }} »</h2>
        @if($tag->description)
        <p class="mb-0 wow fadeInUp">{{ $tag->description }}</p>
        @endif
    </div>
@endsection

@section('blog_content')
<div class="cs_height_64 cs_height_lg_50"></div>
<div class="container">
    <div class="row cs_gap_y_40">

        {{-- Articles --}}
        <div class="col-lg-9">
            @if($articles->isEmpty())
                <div class="text-center py-5">
                    <p class="cs_fs_21 text-muted">Aucun article avec ce tag pour le moment.</p>
                    <a href="{{ route('blog.index') }}" class="cs_btn cs_style_1 cs_accent_bg cs_white_color cs_fs_16 cs_semibold cs_radius_30">
                        <span>Voir tous les articles</span>
                    </a>
                </div>
            @else
                <div class="row cs_gap_y_30">
                    @foreach($articles as $article)
                    <div class="col-md-6 col-xl-4">
                        <div class="cs_post cs_style_1 cs_radius_15 overflow-hidden h-100">
                            @if($article->featured_image)
                            <a href="{{ route('blog.show', $article->slug) }}" class="cs_post_thumbnail d-block overflow-hidden">
                                <img src="{{ Storage::url($article->featured_image) }}" alt="{{ $article->title }}" class="w-100" style="height:200px;object-fit:cover">
                            </a>
                            @endif
                            <div class="cs_post_info p-4">
                                <div class="cs_post_meta cs_fs_14 mb-2">
                                    <span>{{ $article->published_at?->format('d M Y') }}</span>
                                    @if($article->blogCategory)
                                    <span class="ms-2">{{ $article->blogCategory->name }}</span>
                                    @endif
                                </div>
                                <h3 class="cs_fs_21 cs_mb_10">
                                    <a href="{{ route('blog.show', $article->slug) }}" class="cs_heading_color text-decoration-none">
                                        {{ Str::limit($article->title, 60) }}
                                    </a>
                                </h3>
                                @if($article->excerpt)
                                <p class="cs_fs_16 mb-0">{{ Str::limit($article->excerpt, 100) }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="cs_height_40 cs_height_lg_30"></div>
                {{ $articles->links() }}
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-3">
            <aside class="cs_sidebar cs_style_1">
                @if($popularTags->isNotEmpty())
                <div class="cs_sidebar_widget">
                    <h3 class="cs_widget_title cs_fs_29 cs_normal cs_mb_23">Tags populaires</h3>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($popularTags as $t)
                        <a href="{{ route('blog.tag', $t) }}"
                           class="cs_btn cs_style_1 cs_fs_14 cs_semibold cs_radius_30 py-1 px-3 {{ $t->id === $tag->id ? 'cs_accent_bg cs_white_color' : 'cs_gray_bg_1 cs_heading_color' }}">
                            {{ $t->name }} ({{ $t->articles_count }})
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($categories->isNotEmpty())
                <div class="cs_sidebar_widget mt-4">
                    <h3 class="cs_widget_title cs_fs_29 cs_normal cs_mb_23">Catégories</h3>
                    <ul class="cs_category_list cs_mp_0">
                        @foreach($categories as $cat)
                        <li>
                            <a href="{{ route('blog.index', ['category' => $cat->slug]) }}" class="cs_heading_color text-decoration-none">
                                {{ $cat->name }} <span class="text-muted">({{ $cat->articles_count }})</span>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </aside>
        </div>
    </div>
</div>
<div class="cs_height_100 cs_height_lg_80"></div>
@endsection
