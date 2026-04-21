<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Recherche') . ' : ' . $query . ' - ' . config('app.name'))
@section('meta_description', __('Résultats de recherche pour') . ' « ' . $query . ' » - ' . config('app.name'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => __('Recherche'),
        'breadcrumbItems' => [__('Recherche'), $query],
    ])
@endsection

@section('content')
    <section class="wpo-blog-single-section section-padding">
        <div class="container">
            <div class="row">
                <div class="col col-lg-8 col-12">
                    <div class="wpo-blog-content">
                        <h1>Résultats pour « {{ $query }} »</h1>
                        <p style="color:#777;margin-bottom:20px;">{{ $results['total'] }} résultat(s) trouvé(s)</p>

                        @if($results['total'] === 0)
                            <div class="alert alert-info">
                                Aucun résultat trouvé pour votre recherche. Essayez avec des mots-clés différents ou vérifiez l'orthographe.
                            </div>
                        @endif

                        {{-- Articles blog --}}
                        @if($results['articles']->count() > 0)
                        <section style="margin-bottom:30px;">
                            <h2>Articles <span class="badge" style="background:var(--c-primary);color:#fff;margin-left:8px;">{{ $results['articles']->total() }}</span></h2>
                            @foreach($results['articles'] as $article)
                            <div class="media" style="margin-bottom:16px;padding-bottom:16px;{{ !$loop->last ? 'border-bottom:1px solid #f0f0f0;' : '' }}">
                                @if($article->featured_image)
                                <div class="media-left"><img src="{{ asset($article->featured_image) }}" alt="{{ $article->title }}" style="width:80px;height:80px;border-radius:6px;object-fit:cover;" loading="lazy"></div>
                                @endif
                                <div class="media-body">
                                    <h4 style="margin:0 0 4px;font-size:15px;">
                                        @if(Route::has('blog.show'))<a href="{{ route('blog.show', $article->slug) }}">{{ $article->title }}</a>@else{{ $article->title }}@endif
                                    </h4>
                                    <p style="color:#777;font-size:13px;margin:0 0 4px;">{{ Str::limit(strip_tags($article->excerpt ?? $article->content ?? ''), 120) }}</p>
                                    <span style="font-size:12px;color:#999;">{{ $article->published_at?->translatedFormat('j F Y') }}</span>
                                </div>
                            </div>
                            @endforeach
                            <div class="text-center">{{ $results['articles']->appends(['q' => $query])->links() }}</div>
                        </section>
                        @endif

                        {{-- Actualités --}}
                        @if($results['news']->count() > 0)
                        <section style="margin-bottom:30px;">
                            <h2>Actualités <span class="badge" style="background:var(--c-primary);color:#fff;margin-left:8px;">{{ $results['news']->total() }}</span></h2>
                            @foreach($results['news'] as $news)
                            <div class="media" style="margin-bottom:16px;padding-bottom:16px;{{ !$loop->last ? 'border-bottom:1px solid #f0f0f0;' : '' }}">
                                @if($news->image_url)
                                <div class="media-left"><img src="{{ asset($news->image_url) }}" alt="{{ $news->seo_title ?? $news->title }}" style="width:80px;height:80px;border-radius:6px;object-fit:cover;" loading="lazy"></div>
                                @endif
                                <div class="media-body">
                                    <h4 style="margin:0 0 4px;font-size:15px;">
                                        @if(Route::has('news.show'))<a href="{{ route('news.show', $news->slug) }}">{{ $news->seo_title ?? $news->title }}</a>@else{{ $news->seo_title ?? $news->title }}@endif
                                    </h4>
                                    <p style="color:#777;font-size:13px;margin:0 0 4px;">{{ Str::limit(strip_tags($news->summary ?? ''), 120) }}</p>
                                    <span style="font-size:12px;color:#999;">{{ $news->pub_date?->translatedFormat('j F Y') }}</span>
                                </div>
                            </div>
                            @endforeach
                            <div class="text-center">{{ $results['news']->appends(['q' => $query])->links() }}</div>
                        </section>
                        @endif

                        {{-- Répertoire IA --}}
                        @if($results['tools']->count() > 0)
                        <section style="margin-bottom:30px;">
                            <h2>Répertoire IA <span class="badge" style="background:var(--c-primary);color:#fff;margin-left:8px;">{{ $results['tools']->total() }}</span></h2>
                            @foreach($results['tools'] as $tool)
                            <div class="media" style="margin-bottom:16px;padding-bottom:16px;{{ !$loop->last ? 'border-bottom:1px solid #f0f0f0;' : '' }}">
                                @if($tool->screenshot)
                                <div class="media-left"><img src="{{ str_starts_with($tool->screenshot, 'http') ? $tool->screenshot : asset($tool->screenshot).'?v='.($tool->updated_at?->timestamp ?? '0') }}" alt="{{ $tool->name }}" style="width:80px;height:80px;border-radius:6px;object-fit:cover;" loading="lazy"></div>
                                @endif
                                <div class="media-body">
                                    <h4 style="margin:0 0 4px;font-size:15px;">
                                        @if(Route::has('directory.show'))<a href="{{ route('directory.show', $tool->slug) }}">{{ $tool->name }}</a>@else{{ $tool->name }}@endif
                                    </h4>
                                    <p style="color:#777;font-size:13px;margin:0;">{{ Str::limit(strip_tags($tool->short_description ?? $tool->description ?? ''), 120) }}</p>
                                </div>
                            </div>
                            @endforeach
                            <div class="text-center">{{ $results['tools']->appends(['q' => $query])->links() }}</div>
                        </section>
                        @endif

                        {{-- Glossaire --}}
                        @if($results['terms']->count() > 0)
                        <section style="margin-bottom:30px;">
                            <h2>Glossaire <span class="badge" style="background:var(--c-primary);color:#fff;margin-left:8px;">{{ $results['terms']->total() }}</span></h2>
                            @foreach($results['terms'] as $term)
                            <div style="margin-bottom:12px;padding-bottom:12px;{{ !$loop->last ? 'border-bottom:1px solid #f0f0f0;' : '' }}">
                                @if(Route::has('dictionary.index'))<a href="{{ route('dictionary.index') }}#{{ Str::slug($term->name) }}"><strong>{{ $term->name }}</strong></a>@else<strong>{{ $term->name }}</strong>@endif
                                <p style="color:#777;font-size:13px;margin:4px 0 0;">{{ Str::limit(strip_tags($term->definition ?? ''), 120) }}</p>
                            </div>
                            @endforeach
                            <div class="text-center">{{ $results['terms']->appends(['q' => $query])->links() }}</div>
                        </section>
                        @endif

                        {{-- Acronymes --}}
                        @if($results['acronyms']->count() > 0)
                        <section style="margin-bottom:30px;">
                            <h2>Acronymes <span class="badge" style="background:var(--c-primary);color:#fff;margin-left:8px;">{{ $results['acronyms']->total() }}</span></h2>
                            @foreach($results['acronyms'] as $acronym)
                            <div style="margin-bottom:12px;padding-bottom:12px;{{ !$loop->last ? 'border-bottom:1px solid #f0f0f0;' : '' }}">
                                <strong>{{ $acronym->acronym }}</strong> — {{ $acronym->full_name }}
                                <p style="color:#777;font-size:13px;margin:4px 0 0;">{{ Str::limit(strip_tags($acronym->description ?? ''), 100) }}</p>
                            </div>
                            @endforeach
                            <div class="text-center">{{ $results['acronyms']->appends(['q' => $query])->links() }}</div>
                        </section>
                        @endif

                    </div>
                </div>
                <div class="col col-lg-4 col-12">
                    @include('fronttheme::partials.sidebar')
                </div>
            </div>
        </div>
    </section>
@endsection
