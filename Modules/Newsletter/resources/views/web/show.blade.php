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
    /* Newsletter section system */
    .nl-section-label { font-size:11px; text-transform:uppercase; letter-spacing:1.5px; color:#0B7285; font-weight:bold; margin-bottom:12px; }
    .nl-section { padding:20px 0; border-bottom:1px solid #e5e7eb; }
    .nl-section:last-child { border-bottom:0; }
    .nl-highlight .media .media-left img { width:200px; max-width:100%; border-radius:6px; }
    .nl-challenge { background:#0c1427; color:#e2e8f0; padding:24px; border-radius:8px; margin:20px 0; }
    .nl-challenge h3 { color:#3dc9d8; font-size:18px; }
    .nl-challenge p { color:#e2e8f0; }
    .nl-prompt-box { background:#1e293b; border:1px solid #3dc9d8; border-radius:6px; padding:15px; font-style:italic; color:#e2e8f0; line-height:1.6; margin-bottom:12px; }
    .nl-technique { border-left:3px solid #3dc9d8; padding-left:12px; color:#94a3b8; font-size:13px; margin-bottom:12px; }
    .nl-challenge .btn { background:#3dc9d8; color:#0c1427; font-weight:bold; border:none; }
    .nl-news .media .media-left img { width:80px; height:80px; object-fit:cover; border-radius:6px; }
    .nl-tool { background:#f0fdfa; padding:20px; border-radius:6px; }
    .nl-tool .media .media-left img { width:200px; max-width:100%; border-radius:8px; }
    .nl-pricing { display:inline-block; font-size:10px; font-weight:bold; padding:3px 8px; border-radius:3px; color:#fff; vertical-align:middle; margin-left:6px; }
    .nl-free-tool { background:#fffbeb; padding:20px; border-radius:6px; }
    .nl-free-tool .btn { background:#d97706; color:#fff; border:none; }
    .nl-term { background:#f8fafc; padding:20px; border-radius:6px; }
    .nl-term .media .media-left img { width:150px; height:150px; object-fit:cover; border-radius:8px; }
    .nl-promo { background:#0c1427; color:#e2e8f0; padding:24px; border-radius:8px; }
    .nl-promo a { color:#3dc9d8; }
    .nl-cta { text-align:center; padding:30px 0; }
    .nl-cta .btn { background:var(--c-primary); color:#fff; padding:12px 30px; font-weight:bold; border-radius:4px; border:none; }
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
                            @if(class_exists(\Modules\Newsletter\Models\NewsletterIssue::class) && $issue instanceof \Modules\Newsletter\Models\NewsletterIssue)
                                @include('fronttheme::partials.article-action-bar', ['model' => $issue, 'modelType' => 'Modules\\Newsletter\\Models\\NewsletterIssue'])
                            @endif
                            <div class="entry-details">

                                {{-- Sections welcome --}}
                                @if($isWelcome ?? false)
                                    {{-- Mot de Stef --}}
                                    <div class="media" style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid #f0f0f0;">
                                        <div class="media-left" style="padding-right:16px;">
                                            <img src="{{ asset('images/logo-eye.svg') }}" alt="La veille" style="width:80px;height:80px;">
                                        </div>
                                        <div class="media-body">
                                            <h2 class="newsletter-section-title" style="margin-top:0;">Mot de Stef</h2>
                                            <p>Je suis ravi de vous retrouver pour cette nouvelle étape où <strong>laveilledestef.com</strong> devient officiellement <strong><a href="{{ config('app.url') }}">laveille.ai</a></strong> avec un site entièrement repensé pour vous. Je vous donne désormais rendez-vous <strong>chaque mercredi</strong> dans votre boîte courriel, et notre envoi régulier débutera officiellement le <strong>15 avril</strong> prochain.</p>
                                            <p>Je tiens sincèrement à vous remercier de nous suivre dans cette aventure technologique qui évolue si rapidement. D'ici notre première édition, je vous souhaite un excellent congé de Pâques entouré de vos proches !</p>
                                            <p style="font-family:'Dancing Script','Brush Script MT',cursive;font-size:24px;color:var(--c-primary);">Stef</p>
                                        </div>
                                    </div>

                                    {{-- Chaque semaine --}}
                                    <h2 class="newsletter-section-title">Chaque semaine dans votre boîte</h2>
                                    <ul style="list-style:none;padding:0;">
                                        <li style="padding:4px 0;">📢 <strong>Le fait marquant</strong> — l'actualité IA incontournable</li>
                                        <li style="padding:4px 0;">📰 <strong>5 actualités</strong> — résumées et triées pour vous</li>
                                        <li style="padding:4px 0;">🎯 <strong>Un défi prompt</strong> — un prompt à essayer immédiatement</li>
                                        <li style="padding:4px 0;">🔧 <strong>L'outil de la semaine</strong> — testé et recommandé</li>
                                        <li style="padding:4px 0;">📖 <strong>Un terme IA expliqué</strong> — pour comprendre sans jargon</li>
                                        <li style="padding:4px 0;">📝 <strong>Un article approfondi</strong> — analyse ou tutoriel</li>
                                        <li style="padding:4px 0;">🎁 <strong>Un outil gratuit</strong> — à essayer dans votre navigateur</li>
                                    </ul>
                                    <p style="font-style:italic;color:#555;">Voici votre premier numéro. Bonne lecture !</p>

                                    {{-- Le nouveau laveille.ai --}}
                                    <h2 class="newsletter-section-title">Le nouveau laveille.ai</h2>
                                    <div style="margin-bottom:20px;">
                                        <p><strong>Répertoire de 75+ outils IA</strong> — fiches détaillées, screenshots, avis de la communauté</p>
                                        <p><strong>Glossaire IA interactif</strong> — 140+ termes expliqués simplement avec analogies</p>
                                        <p><strong>Outils gratuits en ligne</strong> — calculatrices, générateurs, constructeur de prompts</p>
                                        <p><strong>Acronymes en éducation</strong> — 300+ acronymes du milieu éducatif québécois</p>
                                        <a href="{{ config('app.url') }}" class="btn" style="background-color:var(--c-primary);color:#fff;">Explorer le site &rarr;</a>
                                    </div>
                                @endif

                                {{-- Éditorial --}}
                                @if($editorial ?? null)
                                <div class="nl-section">
                                    <p class="nl-section-label">Éditorial</p>
                                    <blockquote>{{ $editorial }}</blockquote>
                                </div>
                                @endif

                                {{-- Fait marquant --}}
                                @if($highlight ?? null)
                                <div class="nl-section nl-highlight">
                                    <p class="nl-section-label">Le fait marquant</p>
                                    <div class="media">
                                        @if($highlight->image_url)
                                        <div class="media-left"><img src="{{ asset($highlight->image_url) }}" alt="{{ $highlight->seo_title ?? $highlight->title }}" loading="lazy"></div>
                                        @endif
                                        <div class="media-body">
                                            <h2>{{ $highlight->seo_title ?? $highlight->title }}</h2>
                                            <p>{{ Str::limit($highlight->summary ?? strip_tags($highlight->content ?? ''), 200) }}</p>
                                            <a href="{{ $highlight->url ?? route('news.show', $highlight->slug ?? '') }}" class="btn btn-sm" style="background:var(--c-primary);color:#fff;">Lire l'article &rarr;</a>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                {{-- Défi de la semaine (masqué dans welcome) --}}
                                @if(($weeklyPrompt ?? null) && !($isWelcome ?? false))
                                <div class="nl-challenge">
                                    <p class="nl-section-label" style="color:#3dc9d8;">Défi de la semaine</p>
                                    <h3>Essayez ce prompt cette semaine :</h3>
                                    <div class="nl-prompt-box">{{ is_array($weeklyPrompt) ? ($weeklyPrompt['prompt'] ?? '') : $weeklyPrompt }}</div>
                                    @if(is_array($weeklyPrompt) && ($weeklyPrompt['technique'] ?? null))
                                    <div class="nl-technique"><strong style="color:#3dc9d8;">Technique utilisée :</strong> {{ $weeklyPrompt['technique'] }}</div>
                                    @endif
                                    <a href="{{ config('app.url') }}/outils/constructeur-prompts" class="btn">Construire mon prompt &rarr;</a>
                                </div>
                                @endif

                                {{-- Actualités --}}
                                @if(($topNews ?? null) && $topNews->count())
                                <div class="nl-section nl-news">
                                    <p class="nl-section-label">Actualités de la semaine</p>
                                    @foreach($topNews as $news)
                                    <div class="media">
                                        @if($news->image_url)
                                        <div class="media-left"><img src="{{ asset($news->image_url) }}" alt="{{ $news->seo_title ?? $news->title }}" loading="lazy"></div>
                                        @endif
                                        <div class="media-body">
                                            <h4><a href="{{ $news->url ?? route('news.show', $news->slug ?? '') }}">{{ $news->seo_title ?? $news->title }}</a></h4>
                                            @if($news->summary)<p>{{ Str::limit(strip_tags($news->summary), 140) }}</p>@endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif

                                {{-- Outil de la semaine --}}
                                @if($toolOfWeek ?? null)
                                <div class="nl-section nl-tool">
                                    <p class="nl-section-label">Outil de la semaine</p>
                                    <div class="media">
                                        @if($toolOfWeek->screenshot)
                                        <div class="media-left"><img src="{{ str_starts_with($toolOfWeek->screenshot, 'http') ? $toolOfWeek->screenshot : asset($toolOfWeek->screenshot) }}" alt="{{ $toolOfWeek->name }}" loading="lazy"></div>
                                        @endif
                                        <div class="media-body">
                                            <h3>{{ $toolOfWeek->name }}
                                                @php $pColor = match(strtolower($toolOfWeek->pricing ?? '')) { 'free','gratuit' => '#10b981', 'freemium' => '#f97316', default => '#6b7280' }; $pLabel = match(strtolower($toolOfWeek->pricing ?? '')) { 'free','gratuit' => 'Gratuit', 'freemium' => 'Freemium', default => 'Payant' }; @endphp
                                                <span class="nl-pricing" style="background:{{ $pColor }};">{{ $pLabel }}</span>
                                            </h3>
                                            <p>{{ Str::limit(strip_tags($toolOfWeek->short_description ?? $toolOfWeek->description ?? ''), 150) }}</p>
                                            @if($toolOfWeek->use_cases)<p><strong style="color:var(--c-primary);">Pour qui ?</strong> {{ Str::limit(strip_tags($toolOfWeek->use_cases), 100) }}</p>@endif
                                            @if($toolOfWeek->pros)<p><strong style="color:var(--c-primary);">Pourquoi l'essayer ?</strong> {{ Str::limit(strip_tags($toolOfWeek->pros), 100) }}</p>@endif
                                            <a href="{{ route('directory.show', $toolOfWeek->slug) }}" class="btn btn-sm" style="background:var(--c-primary);color:#fff;">Découvrir &rarr;</a>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                {{-- Article à lire --}}
                                @if($featuredArticle ?? null)
                                <div class="nl-section">
                                    <p class="nl-section-label">À lire cette semaine</p>
                                    <div class="media">
                                        @if($featuredArticle->featured_image)
                                        <div class="media-left"><img src="{{ asset($featuredArticle->featured_image) }}" alt="{{ $featuredArticle->title }}" loading="lazy" style="width:180px;border-radius:6px;"></div>
                                        @endif
                                        <div class="media-body">
                                            <h3><a href="{{ route('blog.show', $featuredArticle->slug) }}">{{ $featuredArticle->title }}</a></h3>
                                            <p>{{ Str::limit(strip_tags($featuredArticle->excerpt ?? $featuredArticle->content ?? ''), 150) }}</p>
                                            <a href="{{ route('blog.show', $featuredArticle->slug) }}" class="btn btn-sm" style="background:var(--c-primary);color:#fff;">Lire l'article &rarr;</a>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                {{-- Outil gratuit --}}
                                @if($interactiveTool ?? null)
                                <div class="nl-section nl-free-tool">
                                    <p class="nl-section-label" style="color:#d97706;">Outil gratuit à essayer</p>
                                    <h3>{{ $interactiveTool->icon ?? '' }} {{ $interactiveTool->name }}</h3>
                                    <p>{{ Str::limit(strip_tags($interactiveTool->description ?? ''), 150) }}</p>
                                    <p style="font-size:13px;color:#555;">100% gratuit, dans votre navigateur, aucune inscription.</p>
                                    <a href="{{ route('tools.show', $interactiveTool->slug) }}" class="btn">Essayer gratuitement &rarr;</a>
                                </div>
                                @endif

                                {{-- Terme IA --}}
                                @if($aiTerm ?? null)
                                <div class="nl-section nl-term">
                                    <p class="nl-section-label">Terme IA de la semaine</p>
                                    <div class="media">
                                        @if($aiTerm->hero_image)
                                        <div class="media-left"><img src="{{ asset($aiTerm->hero_image) }}" alt="{{ $aiTerm->name }}" loading="lazy"></div>
                                        @endif
                                        <div class="media-body">
                                            <h3>{{ $aiTerm->name }}</h3>
                                            <p>{{ Str::limit(strip_tags($aiTerm->definition ?? ''), 200) }}</p>
                                        </div>
                                    </div>
                                    @if($aiTerm->analogy)
                                    <blockquote>
                                        <p style="color:var(--c-primary);font-size:13px;font-style:italic;margin-bottom:4px;">En d'autres mots...</p>
                                        <p>{{ strip_tags($aiTerm->analogy) }}</p>
                                    </blockquote>
                                    @endif
                                    @if($aiTerm->did_you_know ?? null)
                                    <p><strong style="color:#d97706;">Le saviez-vous ?</strong> {{ Str::limit(strip_tags($aiTerm->did_you_know), 150) }}</p>
                                    @endif
                                </div>
                                @endif

                                {{-- CTA abonnement --}}
                                <div class="nl-cta">
                                    <a href="{{ route('home') }}#newsletter" class="btn btn-lg">S'abonner à l'infolettre</a>
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
