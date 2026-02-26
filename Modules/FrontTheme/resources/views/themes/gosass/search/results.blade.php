@extends('fronttheme::themes.gosass.layouts.app')

@section('title', __('Résultats de recherche'))

@section('description', __('Résultats de recherche'))

@section('meta')
<style>
.cs_search_section {
    padding-top: 160px;
    padding-bottom: 80px;
    min-height: calc(100vh - 300px);
}
@media (max-width: 991px) {
    .cs_search_section {
        padding-top: 120px;
        padding-bottom: 60px;
    }
}
</style>
@endsection

@section('content')
<section class="cs_gray_bg cs_search_section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Search Form -->
                <div class="mb-5">
                    <form action="{{ route('search.front') }}" method="GET" class="d-flex">
                        <input type="text" name="q" value="{{ $q }}"
                               class="form-control cs_radius_30 me-16"
                               placeholder="{{ __('Rechercher...') }}" autofocus>
                        <button type="submit" class="btn cs_accent_bg cs_white_color cs_radius_30 cs_semibold px-32">
                            {{ __('Rechercher') }}
                        </button>
                    </form>
                </div>

                @if(!empty($q))
                <!-- Results Count -->
                <div class="mb-32">
                    <h2 class="cs_fs_48 cs_heading_color mb-8">
                        {{ $total }} {{ __('résultat(s) pour') }} "{{ $q }}"
                    </h2>
                </div>

                @if($total == 0)
                    <!-- Empty State -->
                    <div class="text-center py-60">
                        <div class="cs_fs_48 cs_heading_color mb-24">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3 class="cs_heading_color mb-16">{{ __('Aucun résultat trouvé') }}</h3>
                        <p class="text-muted">{{ __('Essayez avec d\'autres termes de recherche.') }}</p>
                    </div>
                @else
                    <!-- Articles Results -->
                    @if($articles->count() > 0)
                        <div class="mb-48">
                            <h3 class="cs_heading_color mb-24">
                                <i class="fas fa-newspaper me-8 cs_accent_color"></i>
                                {{ __('Articles') }}
                                <span class="badge bg-primary ms-2">{{ $articles->total() }}</span>
                            </h3>

                            @foreach($articles as $article)
                                <div class="bg-white radius-8 p-24 mb-16 shadow-sm">
                                    <h4 class="cs_heading_color mb-12">
                                        <a href="{{ route('blog.show', $article) }}" class="text-decoration-none cs_heading_color">
                                            {{ $article->title }}
                                        </a>
                                    </h4>
                                    @if($article->excerpt)
                                    <p class="text-muted mb-16">
                                        {{ Str::limit(strip_tags($article->excerpt), 160) }}
                                    </p>
                                    @endif
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <span class="text-muted me-16">
                                                <i class="far fa-calendar me-4"></i>
                                                {{ $article->created_at->format('d/m/Y') }}
                                            </span>
                                            @if($article->category)
                                                <span class="cs_accent_color cs_semibold">
                                                    <i class="far fa-folder me-4"></i>
                                                    {{ $article->category->name }}
                                                </span>
                                            @endif
                                        </div>
                                        <a href="{{ route('blog.show', $article) }}" class="cs_accent_color cs_semibold text-decoration-none">
                                            {{ __('Lire') }} <i class="fas fa-arrow-right ms-2"></i>
                                        </a>
                                    </div>
                                </div>
                            @endforeach

                            @if($articles->hasPages())
                                <div class="mt-32">
                                    {{ $articles->appends(['q' => $q])->links() }}
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Pages Results -->
                    @if($pages->count() > 0)
                        <div class="mb-48">
                            <h3 class="cs_heading_color mb-24">
                                <i class="fas fa-file-alt me-8 cs_accent_color"></i>
                                {{ __('Pages') }}
                                <span class="badge bg-secondary ms-2">{{ $pages->total() }}</span>
                            </h3>

                            @foreach($pages as $page)
                                <div class="bg-white radius-8 p-24 mb-16 shadow-sm">
                                    <h4 class="cs_heading_color mb-12">
                                        <a href="{{ route('pages.show', $page->slug) }}" class="text-decoration-none cs_heading_color">
                                            {{ $page->title }}
                                        </a>
                                    </h4>
                                    @if($page->content)
                                    <p class="text-muted mb-16">
                                        {{ Str::limit(strip_tags($page->content), 160) }}
                                    </p>
                                    @endif
                                    <div class="d-flex justify-content-end">
                                        <a href="{{ route('pages.show', $page->slug) }}" class="cs_accent_color cs_semibold text-decoration-none">
                                            {{ __('Voir la page') }} <i class="fas fa-arrow-right ms-2"></i>
                                        </a>
                                    </div>
                                </div>
                            @endforeach

                            @if($pages->hasPages())
                                <div class="mt-32">
                                    {{ $pages->appends(['q' => $q])->links() }}
                                </div>
                            @endif
                        </div>
                    @endif
                @endif
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
