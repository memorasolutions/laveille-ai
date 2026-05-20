@extends('statut::layouts.statut')

{{-- #253 — Hero charte Memora pleine largeur (gradient marine + H1 blanc + breadcrumb).
     Aligné sur /page/a-propos, /contact, /faq via le partial fronttheme::partials.breadcrumb.
     Le layout master expose deux yields séparés : @yield('breadcrumb') puis @yield('content'),
     donc on injecte le hero ici tout en conservant le contenu existant dans 'statut-content'. --}}
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => __('statut::messages.page_title'),
        'breadcrumbItems' => [__('Statut des services')],
    ])
@endsection

@section('statut-content')
    {{-- #251 — Charte Memora laveille.ai : tokens primary teal #064E5A + accent orange #C2410C
         + font Plus Jakarta Sans (titres) / DM Sans (corps). Override package vendor view via
         vendor:publish statut-views. Robuste à composer update memora/statut (hors vendor/). --}}
    <style>
        .statut-page {
            --statut-up: #16a34a;
            --statut-down: #C2410C;
            --statut-paused: #d97706;
            --statut-unknown: #6b7280;
            --statut-bg: #F8FAFB;
            --statut-fg: #064E5A;
            --statut-muted: #52586a;
            --statut-card-bg: #ffffff;
            --statut-border: #d8dde5;
            --statut-accent: #C2410C;
            --statut-heading: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            --statut-body: 'DM Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;

            max-width: 1100px;
            margin: 2rem auto;
            padding: 2rem;
            font-family: var(--statut-body);
            color: var(--statut-fg);
            background-color: var(--statut-bg);
            border-radius: 1.25rem;
            box-shadow: 0 4px 16px rgba(6, 78, 90, 0.06);
        }

        @media (prefers-color-scheme: dark) {
            .statut-page {
                --statut-bg: #051a1f;
                --statut-fg: #e9f1f2;
                --statut-muted: #b7c2c7;
                --statut-card-bg: #0d3d46;
                --statut-border: #1d4d57;
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35);
            }
        }

        .statut-page *,
        .statut-page *::before,
        .statut-page *::after {
            box-sizing: border-box;
        }

        .statut-page a {
            color: inherit;
            text-decoration: none;
        }

        .statut-page a:hover,
        .statut-page a:focus-visible {
            text-decoration: underline;
        }

        .statut-page a:focus-visible,
        .statut-page button:focus-visible {
            outline: 2px solid currentColor;
            outline-offset: 2px;
        }

        .statut-page h1,
        .statut-page h2,
        .statut-page h3 {
            margin: 0;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.01em;
            font-family: var(--statut-heading);
            color: var(--statut-fg);
        }

        .statut-page h1 { font-size: 2.25rem; }
        .statut-page h2 { font-size: 1.5rem; margin: 2.5rem 0 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--statut-border); }
        .statut-page h3 { font-size: 1.05rem; }

        .statut-page p { margin: 0.5rem 0; line-height: 1.5; }
        .statut-page small { font-size: 0.875rem; color: var(--statut-muted); }

        .statut-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .statut-brand { display: flex; align-items: center; gap: 0.75rem; }
        .statut-brand img { height: 2rem; width: auto; }
        .statut-brand-name { font-size: 1.125rem; font-weight: 600; }

        .statut-title-block { flex: 1 1 auto; }
        .statut-subtitle { color: var(--statut-muted); font-size: 1rem; margin-top: 0.25rem; }

        /* #260 — WCAG 2.2 AAA : bandeau global utilise des tokens dédiés (assombris)
           pour atteindre ≥ 7:1 avec texte blanc. Les tokens --statut-up/--statut-down/
           --statut-paused restent inchangés (pills de monitor-card, bordures cards). */
        .statut-badge-global {
            width: 100%;
            padding: 1.5rem;
            border-radius: 1rem;
            color: #ffffff;
            text-align: center;
            margin-bottom: 2rem;
        }
        .statut-badge-global__title { font-size: 1.5rem; font-weight: 700; margin: 0; color: #ffffff; }
        .statut-badge-global__counts { font-size: 0.95rem; margin-top: 0.5rem; color: #ffffff; }
        .statut-badge-global--up { background-color: #0f5f2c; } /* 7.80:1 vs #fff (AAA) */
        .statut-badge-global--down { background-color: #9a3409; } /* 7.32:1 vs #fff (AAA) */
        .statut-badge-global--paused { background-color: #7a3d05; } /* 8.41:1 vs #fff (AAA) */

        .statut-monitors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .statut-card {
            background-color: var(--statut-card-bg);
            border: 1px solid var(--statut-border);
            border-left-width: 4px;
            border-radius: 0.75rem;
            padding: 1rem;
        }
        .statut-card--up { border-left-color: var(--statut-up); }
        .statut-card--down { border-left-color: var(--statut-down); }
        .statut-card--paused { border-left-color: var(--statut-paused); }
        .statut-card--unknown { border-left-color: var(--statut-unknown); }

        .statut-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .statut-card h3 { font-size: 1.05rem; }

        .statut-pill {
            display: inline-block;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #ffffff;
            white-space: nowrap;
        }
        .statut-pill--up { background-color: var(--statut-up); }
        .statut-pill--down { background-color: var(--statut-down); }
        .statut-pill--paused { background-color: var(--statut-paused); }
        .statut-pill--unknown { background-color: var(--statut-unknown); }

        .statut-status-text {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            color: var(--statut-muted);
            font-size: 0.85rem;
        }

        .statut-uptime-bar {
            display: flex;
            gap: 4px;
            height: 8px;
            margin-top: 0.75rem;
        }
        .statut-uptime-segment { flex: 1; border-radius: 4px; }

        .statut-empty {
            color: var(--statut-muted);
            font-style: italic;
            margin: 1rem 0;
            padding: 1.5rem;
            text-align: center;
            background-color: var(--statut-card-bg);
            border: 1px dashed var(--statut-border);
            border-radius: 0.75rem;
        }

        .statut-footer {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--statut-border);
            text-align: center;
        }

        .statut-error-banner {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            color: #7c2d12;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            font-weight: 500;
        }
        @media (prefers-color-scheme: dark) {
            .statut-error-banner {
                background-color: #422006;
                border-color: #b45309;
                color: #fef3c7;
            }
        }
    </style>

    <noscript>
        <meta http-equiv="refresh" content="60">
    </noscript>

    <main class="statut-page" role="main" aria-label="{{ __('statut::messages.page_title') }}">
        {{-- #253 — Header local supprimé : H1 + breadcrumb désormais dans le hero charte
             ('breadcrumb' section ci-dessus). Subtitle déplacée en intro douce ci-dessous. --}}
        <p class="statut-subtitle">{{ __('statut::messages.page_subtitle') }}</p>

        @if($hasError)
            @include('statut::components.error-banner')
        @else
            @include('statut::components.badge-global', ['overview' => $overview])
        @endif

        <section aria-labelledby="statut-services-title">
            <h2 id="statut-services-title">{{ __('statut::messages.services') }}</h2>
            @if(count($monitors) > 0)
                <div class="statut-monitors-grid">
                    @foreach($monitors as $monitor)
                        @include('statut::components.monitor-card', ['monitor' => $monitor])
                    @endforeach
                </div>
            @else
                <p class="statut-empty">{{ __('statut::messages.no_monitors') }}</p>
            @endif
        </section>

        <section aria-labelledby="statut-incidents-title">
            <h2 id="statut-incidents-title">{{ __('statut::messages.active_incidents') }}</h2>
            @if(count($incidents) > 0)
                @foreach($incidents as $incident)
                    @include('statut::components.incident-card', ['incident' => $incident])
                @endforeach
            @else
                <p class="statut-empty">{{ __('statut::messages.no_active_incidents') }}</p>
            @endif
        </section>

        <footer class="statut-footer">
            <small>
                {{ __('statut::messages.refreshed_every_60s') }}
                @if(!empty($brand['name']) && !empty($brand['url']))
                    —
                    <a href="{{ $brand['url'] }}" rel="noopener noreferrer">{{ $brand['name'] }}</a>
                @elseif(!empty($brand['name']))
                    — {{ $brand['name'] }}
                @endif
            </small>
        </footer>
    </main>

    <script>
        (function () {
            var ttl = 60000;
            setTimeout(function () {
                if (typeof window !== 'undefined' && document.visibilityState !== 'hidden') {
                    window.location.reload();
                }
            }, ttl);
        })();
    </script>
@endsection
