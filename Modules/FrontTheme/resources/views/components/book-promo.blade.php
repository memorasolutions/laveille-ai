{{-- Composant promo livre réutilisable — DRY pattern pub/encart livre auteur. --}}
{{-- Usage : <x-fronttheme::book-promo /> (props ci-dessous, valeurs par défaut = livre courant). --}}
{{-- WCAG 2.2 AA — tokens charte Memora — Schema.org Book JSON-LD. --}}
@props([
    'title'             => "L'IA sans se faire poursuivre",
    'subtitle'          => 'Le guide pratique pour PME et professionnels — Édition 2026',
    'author'            => 'Stéphane Lapointe',
    'author_role'       => 'Fondateur MEMORA solutions',
    'publisher'         => 'MEMORA solutions',
    'date_published'    => '2026',
    'language'          => 'fr-CA',
    'description_short' => 'Loi 25 du Québec (sanctions jusqu\'à 25 M$ CA), RGPD européen (20 M€), AI Act européen (35 M€) : votre usage de l\'IA en entreprise est-il conforme ? 25 chapitres clairs et 18 annexes prêtes à l\'emploi (modèle EFVP, registre incidents IA, runbook crise IA, clauses contractuelles fournisseurs IA, fiches droit à l\'image et deepfakes). Pour PME francophones de 10 à 250 employés.',
    'description_full'  => null,
    'cover_url_webp'    => '/images/books/ia-sans-se-faire-poursuivre-cover-600.webp',
    'cover_url_webp_2x' => '/images/books/ia-sans-se-faire-poursuivre-cover-600.webp',
    'cover_url_jpg'     => '/images/books/ia-sans-se-faire-poursuivre-cover-600.jpg',
    'cover_url_300'     => '/images/books/ia-sans-se-faire-poursuivre-cover-300.webp',
    'og_image'          => '/images/books/ia-sans-se-faire-poursuivre-og-1200x630.jpg',
    'cover_alt'         => "L'IA sans se faire poursuivre — Édition 2026 — Guide pratique Loi 25 RGPD AI Act pour PME francophones par Stéphane Lapointe",
    'cta_label'         => 'Commander sur Amazon',
    'cta_url'           => 'https://a.co/d/0dN4X9m2',
    'schema'            => true,
    'variant'           => 'card', // 'card' | 'inline'
])
@php
    $descLong = $description_full ?? ("Vous utilisez l'IA en entreprise — mais êtes-vous vraiment en conformité ? Depuis 2022, la Loi 25 du Québec impose de nouvelles obligations strictes en matière de protection des renseignements personnels. Les sanctions peuvent atteindre 25 millions de dollars canadiens ou 4 % du chiffre d'affaires mondial. Parallèlement, le RGPD en Europe punit les manquements jusqu'à 20 millions d'euros ou 4 % du CA mondial, et l'AI Act européen prévoit des amendes allant jusqu'à 35 millions d'euros ou 7 % du chiffre d'affaires mondial. Pour les PME francophones de 10 à 250 employés, naviguer entre ces cadres juridiques complexes devient un impératif opérationnel. 25 chapitres clairs et 18 annexes immédiatement utilisables : modèle EFVP, registre incidents IA, runbook crise IA, clauses contractuelles fournisseurs IA, fiches droit à l'image et deepfakes. Appuyé sur ISO/IEC 42001 et NIST AI RMF, 18 cas concrets terrain québécois et européen.");
    $uid = 'bookpromo-' . substr(md5($title . $cta_url), 0, 8);
    $absOg = url($og_image);
@endphp
<aside class="lv-book-promo lv-book-promo--{{ $variant }}" role="complementary" aria-labelledby="{{ $uid }}-title" style="--c-primary:#064E5A;--c-accent:#9A2A06;--c-bg:#F8FAFB;--c-text:#1a1d23;--c-text-muted:#555B6A;--c-border:#e2e8f0;background:var(--c-bg);border:1px solid var(--c-border);border-radius:0.75rem;padding:1.5rem;margin:2rem 0;display:flex;gap:1.5rem;align-items:flex-start;flex-wrap:wrap;color:var(--c-text);font-family:'DM Sans','Plus Jakarta Sans',system-ui,sans-serif;">
    <div style="flex:0 0 200px;max-width:200px;">
        <picture>
            <source type="image/webp" srcset="{{ $cover_url_webp }} 1x, {{ $cover_url_webp_2x }} 2x">
            <img
                src="{{ $cover_url_jpg }}"
                alt="{{ $cover_alt }}"
                loading="lazy"
                decoding="async"
                width="600"
                height="903"
                style="width:100%;height:auto;border-radius:0.5rem;box-shadow:0 4px 12px rgba(11,114,133,0.15);display:block;"
            >
        </picture>
    </div>
    <div style="flex:1 1 280px;min-width:240px;" x-data="{ open: false }">
        <p style="margin:0 0 0.25rem;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.08em;color:var(--c-text-muted);font-weight:600;">Publication de l'auteur</p>
        <h3 id="{{ $uid }}-title" style="font-family:'Plus Jakarta Sans',system-ui,sans-serif;font-weight:800;margin:0 0 0.35rem;font-size:1.35rem;line-height:1.25;color:var(--c-text);">{!! lv_typo_fr($title) !!}</h3>
        <p style="margin:0 0 0.5rem;font-size:0.95rem;color:var(--c-text-muted);font-weight:500;">{!! lv_typo_fr($subtitle) !!}</p>
        <p style="margin:0 0 1rem;font-size:0.85rem;color:var(--c-text-muted);">Par <strong style="color:var(--c-text);">{{ $author }}</strong>, {{ $author_role }}</p>
        <p style="margin:0 0 1rem;color:var(--c-text);font-size:0.95rem;line-height:1.55;">{!! lv_typo_fr($description_short) !!}</p>

        <div style="margin:0 0 1rem;">
            <button
                type="button"
                x-on:click="open = !open"
                :aria-expanded="open.toString()"
                aria-controls="{{ $uid }}-details"
                style="background:transparent;border:none;padding:0;color:var(--c-primary);font-weight:600;font-size:0.9rem;cursor:pointer;text-decoration:underline;text-underline-offset:3px;min-height:44px;display:inline-flex;align-items:center;gap:0.35rem;"
            >
                <span x-show="!open">Voir les détails</span>
                <span x-show="open" x-cloak>Masquer les détails</span>
                <span aria-hidden="true" x-text="open ? '▲' : '▼'"></span>
            </button>
            <div id="{{ $uid }}-details" x-show="open" x-collapse x-cloak style="margin-top:0.75rem;padding:1rem;background:#fff;border-radius:0.5rem;border:1px solid var(--c-border);">
                <p style="margin:0;font-size:0.9rem;line-height:1.6;color:var(--c-text);">{!! lv_typo_fr($descLong) !!}</p>
            </div>
        </div>

        <a
            href="{{ $cta_url }}"
            target="_blank"
            rel="noopener sponsored"
            aria-label="{{ $cta_label }} — {{ $title }} (nouvel onglet)"
            style="display:inline-flex;align-items:center;justify-content:center;gap:0.5rem;min-height:44px;padding:0.75rem 1.5rem;background:var(--c-primary);color:#fff;border-radius:0.5rem;text-decoration:none;font-weight:700;font-size:0.95rem;letter-spacing:0.02em;box-shadow:0 2px 6px rgba(11,114,133,0.25);transition:background 0.15s ease,transform 0.15s ease;"
            onmouseover="this.style.background='#085c6b';this.style.transform='translateY(-1px)'"
            onmouseout="this.style.background='#064E5A';this.style.transform='translateY(0)'"
            onfocus="this.style.outline='3px solid #9A2A06';this.style.outlineOffset='2px'"
            onblur="this.style.outline='none'"
        >
            {{ $cta_label }}
            <span aria-hidden="true">→</span>
        </a>
    </div>

    @if($schema)
    @php
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Book',
            'name' => $title,
            'alternativeHeadline' => $subtitle,
            'author' => [
                '@type' => 'Person',
                'name' => $author,
                'jobTitle' => $author_role,
                'worksFor' => [
                    '@type' => 'Organization',
                    'name' => $publisher,
                ],
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $publisher,
            ],
            'datePublished' => $date_published,
            'inLanguage' => $language,
            'bookFormat' => 'https://schema.org/Paperback',
            'image' => $absOg,
            'description' => $description_short,
            'offers' => [
                '@type' => 'Offer',
                'url' => $cta_url,
                'availability' => 'https://schema.org/InStock',
                'priceCurrency' => 'CAD',
                'seller' => [
                    '@type' => 'Organization',
                    'name' => 'Amazon',
                ],
            ],
            'sameAs' => [$cta_url],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}</script>
    @endif
</aside>

@once
<style>
    {{-- @media protégé par @verbatim car Blade interprète @media comme directive --}}
    @verbatim
    @media (max-width: 640px) {
        .lv-book-promo { flex-direction: column !important; align-items: stretch !important; }
        .lv-book-promo > div:first-child { flex: 0 0 auto !important; max-width: 200px !important; margin: 0 auto !important; }
        .lv-book-promo h3 { font-size: 1.2rem !important; }
    }
    @endverbatim
    .lv-book-promo a:focus-visible { outline: 3px solid #9A2A06 !important; outline-offset: 2px !important; }
    [x-cloak] { display: none !important; }
</style>
@endonce
