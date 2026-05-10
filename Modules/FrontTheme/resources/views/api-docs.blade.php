<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('API publique') . ' - ' . config('app.name'))
@section('meta_description', __("API JSON publique en lecture seule pour interroger l'annuaire d'outils IA et EdTech francophones de La veille. Sans authentification, rate-limited 60 req/min."))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('API publique')])
@endsection

@section('content')
<section style="padding: 60px 0 40px; background: #fff;">
    <div class="container">
        <div style="max-width: 880px; margin: 0 auto;">

            <header style="margin-bottom: 32px;">
                <p style="text-transform: uppercase; letter-spacing: 1.5px; font-size: 12px; font-weight: 700; color: var(--c-primary, #064E5A); margin: 0 0 10px;">
                    {{ __('API publique JSON — version 1') }}
                </p>
                <h1 style="margin: 0 0 14px; font-weight: 800; font-size: clamp(28px, 4vw, 42px); line-height: 1.15; letter-spacing: -0.5px; color: var(--c-dark, #1a1d23);">
                    {{ __("API La veille") }}
                </h1>
                <p style="font-size: 18px; color: #4b5563; margin: 0; line-height: 1.55;">
                    {{ __("Interrogez l'annuaire de 330+ outils IA / EdTech francophones depuis vos applications, agents IA ou tableaux de bord. Lecture seule, sans clé API, rate-limited à 60 requêtes/minute par IP.") }}
                </p>
            </header>

            <div style="background: #ECFDF5; border-left: 4px solid #14532d; padding: 18px 22px; border-radius: 8px; margin-bottom: 32px;">
                <p style="margin: 0; font-size: 15px; color: #14532d;">
                    <strong>{{ __('Licence') }} : CC BY 4.0</strong> — {{ __("Vous pouvez utiliser librement ces données, y compris commercialement, en mentionnant La veille (laveille.ai) comme source.") }}
                </p>
            </div>

            <h2 style="font-weight: 800; font-size: 22px; margin: 28px 0 12px;">{{ __('Base URL') }}</h2>
            <pre style="background: #1a1d23; color: #e5e7eb; padding: 16px 20px; border-radius: 8px; overflow-x: auto; font-size: 14px; line-height: 1.6;"><code>{{ url('/api/v1/directory') }}</code></pre>

            <h2 style="font-weight: 800; font-size: 22px; margin: 28px 0 12px;">{{ __('Endpoints') }}</h2>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="border: 1px solid #e5e7eb; border-radius: 10px; padding: 18px 22px;">
                    <code style="background: #064E5A; color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 700;">GET</code>
                    <code style="margin-left: 8px; font-size: 14px;">/api/v1/directory/tools</code>
                    <p style="margin: 10px 0 6px; color: #4b5563; font-size: 14px;">{{ __('Liste paginée des outils publiés.') }}</p>
                    <details style="margin-top: 8px; font-size: 13px;"><summary style="cursor: pointer; font-weight: 600;">{{ __('Paramètres optionnels') }}</summary>
                    <ul style="margin: 8px 0 0 20px; color: var(--c-text-muted, #52586a);">
                        <li><code>page</code> — numéro de page (défaut 1)</li>
                        <li><code>per_page</code> — résultats par page (défaut 30, max 100)</li>
                        <li><code>category</code> — filtrer par slug de catégorie</li>
                        <li><code>pricing</code> — <code>free</code>, <code>freemium</code>, <code>paid</code>, <code>enterprise</code></li>
                        <li><code>has_education_pricing</code> — <code>1</code> ou <code>true</code> pour filtrer aux outils avec programme édu</li>
                        <li><code>q</code> — recherche par nom ou description courte</li>
                    </ul>
                    </details>
                </div>

                <div style="border: 1px solid #e5e7eb; border-radius: 10px; padding: 18px 22px;">
                    <code style="background: #064E5A; color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 700;">GET</code>
                    <code style="margin-left: 8px; font-size: 14px;">/api/v1/directory/tools/{slug}</code>
                    <p style="margin: 10px 0 0; color: #4b5563; font-size: 14px;">{{ __("Détail complet d'un outil par son slug. Inclut description longue, screenshot, pricing, programme éducation, signaux de fraîcheur.") }}</p>
                </div>

                <div style="border: 1px solid #e5e7eb; border-radius: 10px; padding: 18px 22px;">
                    <code style="background: #064E5A; color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 700;">GET</code>
                    <code style="margin-left: 8px; font-size: 14px;">/api/v1/directory/collections</code>
                    <p style="margin: 10px 0 0; color: #4b5563; font-size: 14px;">{{ __('Liste des collections éditoriales publiques (top par tâche + stacks par persona).') }}</p>
                </div>

                <div style="border: 1px solid #e5e7eb; border-radius: 10px; padding: 18px 22px;">
                    <code style="background: #064E5A; color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 700;">GET</code>
                    <code style="margin-left: 8px; font-size: 14px;">/api/v1/directory/collections/{slug}</code>
                    <p style="margin: 10px 0 0; color: #4b5563; font-size: 14px;">{{ __("Détail d'une collection avec tous ses outils.") }}</p>
                </div>
            </div>

            <h2 style="font-weight: 800; font-size: 22px; margin: 28px 0 12px;">{{ __('Exemples') }}</h2>

            <p style="font-weight: 600; margin-top: 16px;">{{ __('Tous les outils avec programme éducation gratuit') }} :</p>
            <pre style="background: #1a1d23; color: #e5e7eb; padding: 16px 20px; border-radius: 8px; overflow-x: auto; font-size: 13px;"><code>curl '{{ url('/api/v1/directory/tools?has_education_pricing=1&pricing=free') }}'</code></pre>

            <p style="font-weight: 600; margin-top: 16px;">{{ __('Recherche par mot-clé') }} :</p>
            <pre style="background: #1a1d23; color: #e5e7eb; padding: 16px 20px; border-radius: 8px; overflow-x: auto; font-size: 13px;"><code>curl '{{ url('/api/v1/directory/tools?q=traduction') }}'</code></pre>

            <p style="font-weight: 600; margin-top: 16px;">{{ __('Stack IA pour startup SaaS Québec') }} :</p>
            <pre style="background: #1a1d23; color: #e5e7eb; padding: 16px 20px; border-radius: 8px; overflow-x: auto; font-size: 13px;"><code>curl '{{ url('/api/v1/directory/collections/stack-startup-saas-quebec') }}'</code></pre>

            <h2 style="font-weight: 800; font-size: 22px; margin: 28px 0 12px;">{{ __('Limites & politesse') }}</h2>
            <ul style="margin: 0 0 12px 20px; line-height: 1.7;">
                <li><strong>{{ __('Rate limit') }}</strong> : 60 requêtes / minute / IP. Au-delà → HTTP 429.</li>
                <li><strong>{{ __('Cache') }}</strong> : <code>Cache-Control: public, max-age=300</code> — merci de respecter pour limiter la charge.</li>
                <li><strong>{{ __('CORS') }}</strong> : <code>Access-Control-Allow-Origin: *</code> — utilisable depuis n'importe quel navigateur.</li>
                <li><strong>{{ __('Attribution') }}</strong> : si vous utilisez ces données dans une app publique, citez La veille (laveille.ai) avec un lien.</li>
            </ul>

            <h2 style="font-weight: 800; font-size: 22px; margin: 28px 0 12px;">{{ __('Pour aller plus loin') }}</h2>
            <p>{{ __("Vous voulez un endpoint, un filtre ou un format spécifique ? Vous avez bâti une intégration intéressante ? Faites signe :") }} <a href="{{ route('contact') }}" style="color: var(--c-primary); font-weight: 600;">{{ __('contactez-moi') }}</a>.</p>
            <p style="margin-top: 12px;">{{ __('La veille publie aussi des') }} <a href="{{ route('rss.concentres') }}" style="color: var(--c-primary); font-weight: 600;">{{ __('flux RSS') }}</a> {{ __('si votre cas d\'usage est de suivre les nouveautés.') }}</p>

        </div>
    </div>
</section>
@endsection
