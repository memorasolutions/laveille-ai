<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@section('title', ($issue->subject ?? __('Infolettre')) . ' - ' . config('app.name'))
@section('meta_description', __('Infolettre La veille') . ' #' . ($weekNumber ?? '') . ' - ' . __('veille technologique IA'))
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $issue->subject ?? __('Infolettre'),
        'breadcrumbItems' => [__('Infolettre'), $issue->subject ?? ''],
    ])
@endsection

@push('styles')
<style>
    .wpo-blog-content .entry-details .newsletter-section-title {
        font-size: 20px;
        color: #1a1a2e;
        margin-top: 2rem;
        margin-bottom: 16px;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--c-primary);
    }
    .wpo-blog-content .entry-details .newsletter-challenge {
        background-color: #0c1427;
        padding: 24px;
        border-radius: 8px;
        margin: 20px 0;
    }
    .wpo-blog-content .entry-details .newsletter-challenge h3 {
        color: #3dc9d8;
        font-size: 18px;
        margin-bottom: 12px;
    }
    .wpo-blog-content .entry-details .newsletter-challenge p {
        color: #e2e8f0;
        line-height: 1.6;
    }
    .wpo-blog-content .entry-details .media {
        margin-bottom: 16px;
        padding-bottom: 16px;
        border-bottom: 1px solid #f0f0f0;
    }
    .wpo-blog-content .entry-details .media:last-of-type {
        border-bottom: none;
    }
    .wpo-blog-content .entry-details .media .media-left img {
        border-radius: 6px;
        object-fit: cover;
    }
    .wpo-blog-content .entry-details .media h4 a {
        color: #1a1a2e;
        transition: color 0.2s;
    }
    .wpo-blog-content .entry-details .media h4 a:hover {
        color: var(--c-primary);
    }
    .wpo-blog-content .entry-details blockquote {
        border-left: 4px solid var(--c-primary);
        padding: 12px 20px;
        background: #f8fafc;
        border-radius: 0 6px 6px 0;
        margin: 0 0 20px;
        font-style: italic;
    }
    .wpo-blog-content .entry-details .btn {
        border-radius: 4px;
        font-weight: 600;
        padding: 8px 20px;
    }
    .wpo-blog-content .entry-details h3 {
        font-size: 18px;
        margin: 0 0 8px;
        color: #1a1a2e;
    }
    .wpo-blog-content .entry-details .entry-media img {
        border-radius: 8px;
        margin-bottom: 16px;
        max-height: 350px;
        width: 100%;
        object-fit: cover;
    }
</style>
@endpush

@section('content')
    <section class="wpo-blog-single-section section-padding">
        <div class="container">
            <div class="row">
                <div class="col col-lg-8 col-12">
                    <div class="wpo-blog-content">
                        <div class="post format-standard-image">
                            <div class="entry-meta">
                                <ul>
                                    <li><i class="fi flaticon-user"></i> {{ config('app.name') }}</li>
                                    <li><i class="fi flaticon-calendar"></i> {{ $issue->sent_at?->translatedFormat('j F Y') }}</li>
                                    <li><i class="fi flaticon-tag"></i> Semaine {{ $weekNumber }}</li>
                                </ul>
                            </div>
                            <h1>{{ $subject }}</h1>
                            @if(class_exists(\Modules\Newsletter\Models\NewsletterIssue::class))
                                @include('fronttheme::partials.article-action-bar', ['model' => $issue, 'modelType' => 'Modules\\Newsletter\\Models\\NewsletterIssue'])
                            @endif
                            <div class="entry-details">

                                {{-- Éditorial --}}
                                @if($editorial ?? null)
                                    <blockquote style="border-left: 3px solid var(--c-primary); padding-left: 1rem;">
                                        {{ $editorial }}
                                    </blockquote>
                                @endif

                                {{-- Fait marquant --}}
                                @if($highlight ?? null)
                                    <h2 class="newsletter-section-title">Le fait marquant</h2>
                                    @if($highlight->image_url)
                                    <div class="entry-media"><img src="{{ asset($highlight->image_url) }}" alt="{{ $highlight->seo_title ?? $highlight->title }}"></div>
                                    @endif
                                    <h3>{{ $highlight->seo_title ?? $highlight->title }}</h3>
                                    <p>{{ Str::limit($highlight->summary ?? strip_tags($highlight->content ?? ''), 200) }}</p>
                                    <a href="{{ $highlight->url ?? route('news.show', $highlight->slug ?? '') }}" style="color:var(--c-primary);font-weight:bold;">Lire l'article &rarr;</a>
                                @endif

                                {{-- Défi de la semaine --}}
                                @if($weeklyPrompt ?? null)
                                    <div class="newsletter-challenge">
                                        <h3>Défi de la semaine</h3>
                                        <p>Essayez ce prompt cette semaine :</p>
                                        <div style="background-color:#1e293b;border:1px solid #3dc9d8;border-radius:6px;padding:15px;margin-bottom:12px;">
                                            <p style="color:#e2e8f0;font-style:italic;margin:0;line-height:1.5;">{{ is_array($weeklyPrompt) ? ($weeklyPrompt['prompt'] ?? '') : $weeklyPrompt }}</p>
                                        </div>
                                        @if(is_array($weeklyPrompt) && ($weeklyPrompt['technique'] ?? null))
                                        <p><strong style="color:#3dc9d8;">Technique utilisée :</strong> <span style="color:#94a3b8;">{{ $weeklyPrompt['technique'] }}</span></p>
                                        @endif
                                    </div>
                                @endif

                                {{-- Actualités --}}
                                @if(($topNews ?? null) && $topNews->count())
                                    <h2 class="newsletter-section-title">Actualités de la semaine</h2>
                                    @foreach($topNews as $news)
                                    <div class="media" style="{{ !$loop->last ? 'border-bottom:1px solid #f0f0f0;padding-bottom:16px;margin-bottom:16px;' : '' }}">
                                        @if($news->image_url)
                                        <div class="media-left"><img src="{{ asset($news->image_url) }}" alt="{{ $news->seo_title ?? $news->title }}" style="width:80px;height:80px;border-radius:6px;object-fit:cover;" loading="lazy"></div>
                                        @endif
                                        <div class="media-body">
                                            <h4 style="margin:0 0 4px;font-size:15px;"><a href="{{ $news->url ?? route('news.show', $news->slug ?? '') }}">{{ $news->seo_title ?? $news->title }}</a></h4>
                                            @if($news->summary)<p style="color:#777;font-size:13px;margin:0;">{{ Str::limit(strip_tags($news->summary), 140) }}</p>@endif
                                        </div>
                                    </div>
                                    @endforeach
                                @endif

                                {{-- Outil de la semaine --}}
                                @if($toolOfWeek ?? null)
                                    <h2 class="newsletter-section-title">Outil de la semaine</h2>
                                    <div class="media">
                                        @if($toolOfWeek->screenshot)
                                        <div class="media-left"><img src="{{ str_starts_with($toolOfWeek->screenshot, 'http') ? $toolOfWeek->screenshot : asset($toolOfWeek->screenshot) }}" alt="{{ $toolOfWeek->name }}" width="200" style="border-radius:8px;" loading="lazy"></div>
                                        @endif
                                        <div class="media-body">
                                            <h3 style="margin:0 0 6px;">{{ $toolOfWeek->name }}</h3>
                                            <p>{{ Str::limit(strip_tags($toolOfWeek->short_description ?? $toolOfWeek->description ?? ''), 150) }}</p>
                                            <a href="{{ route('directory.show', $toolOfWeek->slug) }}" style="color:var(--c-primary);font-weight:bold;">Découvrir &rarr;</a>
                                        </div>
                                    </div>
                                @endif

                                {{-- Article à lire --}}
                                @if($featuredArticle ?? null)
                                    <h2 class="newsletter-section-title">À lire cette semaine</h2>
                                    <div class="media">
                                        @if($featuredArticle->featured_image)
                                        <div class="media-left"><img src="{{ asset($featuredArticle->featured_image) }}" alt="{{ $featuredArticle->title }}" style="width:180px;border-radius:6px;" loading="lazy"></div>
                                        @endif
                                        <div class="media-body">
                                            <h3 style="margin:0 0 8px;"><a href="{{ route('blog.show', $featuredArticle->slug) }}">{{ $featuredArticle->title }}</a></h3>
                                            <p>{{ Str::limit(strip_tags($featuredArticle->excerpt ?? $featuredArticle->content ?? ''), 150) }}</p>
                                            <a href="{{ route('blog.show', $featuredArticle->slug) }}" style="color:var(--c-primary);font-weight:bold;">Lire l'article &rarr;</a>
                                        </div>
                                    </div>
                                @endif

                                {{-- Outil gratuit --}}
                                @if($interactiveTool ?? null)
                                    <h2 class="newsletter-section-title" style="color:#d97706;border-color:#d97706;">Outil gratuit à essayer</h2>
                                    <h3>{{ $interactiveTool->icon ?? '' }} {{ $interactiveTool->name }}</h3>
                                    <p>{{ Str::limit(strip_tags($interactiveTool->description ?? ''), 150) }}</p>
                                    <a href="{{ route('tools.show', $interactiveTool->slug) }}" class="btn" style="background-color:#d97706;color:#fff;border:none;border-radius:4px;">Essayer gratuitement &rarr;</a>
                                @endif

                                {{-- Terme IA --}}
                                @if($aiTerm ?? null)
                                    <h2 class="newsletter-section-title">Terme IA de la semaine</h2>
                                    <div class="media">
                                        @if($aiTerm->hero_image)
                                        <div class="media-left"><img src="{{ asset($aiTerm->hero_image) }}" alt="{{ $aiTerm->name }}" style="width:150px;height:150px;border-radius:8px;object-fit:cover;" loading="lazy"></div>
                                        @endif
                                        <div class="media-body">
                                            <h3>{{ $aiTerm->name }}</h3>
                                            <p>{{ Str::limit(strip_tags($aiTerm->definition ?? ''), 200) }}</p>
                                        </div>
                                    </div>
                                    @if($aiTerm->analogy)
                                    <blockquote style="border-left:4px solid var(--c-primary);padding-left:15px;margin-top:14px;">
                                        <p style="color:var(--c-primary);font-size:13px;font-style:italic;margin-bottom:4px;">En d'autres mots...</p>
                                        <p>{{ strip_tags($aiTerm->analogy) }}</p>
                                    </blockquote>
                                    @endif
                                @endif

                                {{-- CTA abonnement --}}
                                <div class="text-center" style="margin:30px 0;">
                                    <a href="{{ route('home') }}#newsletter" class="btn btn-lg" style="background-color:var(--c-primary);color:#fff;border:none;border-radius:4px;padding:12px 30px;font-weight:bold;">S'abonner à l'infolettre</a>
                                    @if(Route::has('newsletter.archive'))
                                    <br/><a href="{{ route('newsletter.archive') }}" style="color:var(--c-primary);font-size:14px;margin-top:10px;display:inline-block;">Voir tous les numéros &rarr;</a>
                                    @endif
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="col col-lg-4 col-12">
                    @include('fronttheme::partials.sidebar')
                </div>
            </div>
        </div>
    </section>
@endsection
