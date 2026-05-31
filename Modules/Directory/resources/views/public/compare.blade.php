{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())

@php
    $count = $tools->count();
    $title = $count >= 2
        ? __('Comparatif :').' '.$tools->pluck('name')->implode(' · ')
        : ($category ? __('Comparatif :').' '.$category->name : __('Comparateur d\'outils IA'));

    // Classification des sections en "common" / "specific" (DRY heuristique).
    $sectionClass = [];
    if ($count >= 2 && !empty($classification)) {
        foreach ($classification as $sectionKey => $crits) {
            $commonCount = collect($crits)->filter(fn ($c) => $c === 'common')->count();
            $totalCount = count($crits);
            $sectionClass[$sectionKey] = ($totalCount > 0 && $commonCount / $totalCount >= 0.5) ? 'common' : 'specific';
        }
    }

    $commonSections = collect($criteria)->filter(fn ($_, $key) => ($sectionClass[$key] ?? 'common') === 'common');
    $specificSections = collect($criteria)->filter(fn ($_, $key) => ($sectionClass[$key] ?? 'common') === 'specific');
@endphp

@section('title', $title.' - '.config('app.name'))
@section('meta_description', __('Comparez les outils IA côte-à-côte selon leur tarification, capacités, intégrations et confidentialité.'))
@section('meta_robots', $count >= 2 ? 'index, follow' : 'noindex, follow')

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Comparateur')])
@endsection

@push('styles')
<style>
    .lv-cmp-page { padding: 30px 0 60px; }
    .lv-cmp-back { display: inline-flex; align-items: center; gap: 6px; color: var(--c-primary, #064E5A); font-weight: 600; font-size: 14px; text-decoration: none !important; margin-bottom: 18px; min-height: 44px; }
    .lv-cmp-back:hover { color: var(--c-dark, #1a1d23); }
    .lv-cmp-header { margin-bottom: 24px; }
    .lv-cmp-header h1 { font-family: var(--f-heading); font-weight: 800; color: var(--c-dark, #1a1d23); margin: 0 0 6px; font-size: 1.85rem; letter-spacing: -0.5px; }
    .lv-cmp-header p { color: var(--c-text-muted, #52586a); margin: 0 0 14px; font-size: 1rem; }

    .lv-cmp-toolbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 22px; }
    .lv-cmp-pill-btn {
        background: #F0F4F8; color: var(--c-dark, #1a1d23); border: 1px solid var(--c-border, #E5E7EB);
        padding: 10px 16px; border-radius: 50px; font-size: 13px; font-weight: 600; cursor: pointer; min-height: 44px;
        display: inline-flex; align-items: center; gap: 6px; transition: background 0.15s;
    }
    .lv-cmp-pill-btn:hover { background: #E5E7EB; }
    .lv-cmp-pill-btn:focus-visible { outline: 2px solid var(--c-primary, #064E5A); outline-offset: 2px; }

    /* ─────────── Empty state ─────────── */
    .lv-cmp-empty { background: #fff; border: 2px dashed var(--c-border, #E5E7EB); border-radius: 16px; padding: 50px 30px; text-align: center; }
    .lv-cmp-empty .icon { font-size: 3rem; margin-bottom: 14px; }
    .lv-cmp-empty h2 { color: var(--c-dark, #1a1d23); font-size: 1.4rem; margin: 0 0 10px; }
    .lv-cmp-empty p { color: var(--c-text-muted, #52586a); margin: 0 0 22px; }
    .lv-cmp-empty .btn { background: var(--c-primary, #064E5A); color: #fff; padding: 12px 24px; border-radius: 50px; text-decoration: none !important; font-weight: 700; min-height: 44px; display: inline-flex; align-items: center; }
    .lv-cmp-empty .btn:hover { opacity: 0.9; }

    /* ─────────── Suggestions empty state ─────────── */
    .lv-cmp-suggestions { margin-top: 30px; }
    .lv-cmp-suggestions h3 { font-family: var(--f-heading); font-size: 1.2rem; color: var(--c-dark, #1a1d23); margin: 0 0 6px; font-weight: 800; }
    .lv-cmp-suggestions .hint { color: var(--c-text-muted, #52586a); font-size: 0.95rem; margin: 0 0 18px; }
    .lv-cmp-suggestions-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px; }
    .lv-cmp-suggestion-chip { background: #fff; border: 1.5px solid var(--c-border, #E5E7EB); border-radius: 12px; padding: 16px 18px; text-decoration: none !important; color: var(--c-dark, #1a1d23); display: flex; flex-direction: column; gap: 4px; transition: all 0.15s; min-height: 96px; }
    .lv-cmp-suggestion-chip:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(0,0,0,0.06); border-color: var(--c-primary, #064E5A); color: var(--c-dark, #1a1d23); }
    .lv-cmp-suggestion-chip:focus-visible { outline: 3px solid var(--c-primary, #064E5A); outline-offset: 3px; }
    .lv-cmp-suggestion-chip .emoji { font-size: 1.8rem; line-height: 1; }
    .lv-cmp-suggestion-chip .title { font-weight: 800; font-size: 0.95rem; margin-top: 4px; }
    .lv-cmp-suggestion-chip .subtitle { color: var(--c-text-muted, #52586a); font-size: 0.85rem; }

    /* ─────────── Bandeau mismatch ─────────── */
    .lv-cmp-mismatch {
        background: linear-gradient(135deg, #FFF7E6 0%, #FFEFC8 100%);
        border: 1.5px solid #F5C77E;
        border-left: 5px solid #D97706;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 22px;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
    }
    .lv-cmp-mismatch-icon { font-size: 1.6rem; flex-shrink: 0; }
    .lv-cmp-mismatch-text { flex: 1; min-width: 220px; color: #78410F; font-size: 0.95rem; line-height: 1.5; }
    .lv-cmp-mismatch-text strong { color: #5A2F08; font-weight: 800; }
    .lv-cmp-mismatch-cta {
        background: #D97706; color: #fff !important; padding: 10px 18px; border-radius: 50px;
        text-decoration: none !important; font-weight: 700; font-size: 13px; min-height: 44px;
        display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0;
    }
    .lv-cmp-mismatch-cta:hover { background: #B65D04; color: #fff; }

    /* ─────────── Slider scroll horizontal ─────────── */
    .lv-cmp-scroll-wrap { position: relative; }
    .lv-cmp-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: #fff;
        border: 1.5px solid var(--c-border, #E5E7EB);
        color: var(--c-primary, #064E5A);
        width: 44px;
        height: 44px;
        border-radius: 50%;
        font-size: 22px;
        font-weight: 700;
        cursor: pointer;
        z-index: 11;
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: opacity 0.15s, transform 0.15s;
    }
    .lv-cmp-arrow:hover { transform: translateY(-50%) scale(1.05); }
    .lv-cmp-arrow:focus-visible { outline: 3px solid var(--c-primary, #064E5A); outline-offset: 3px; }
    .lv-cmp-arrow[disabled] { opacity: 0; pointer-events: none; }
    .lv-cmp-arrow-left { left: -8px; }
    .lv-cmp-arrow-right { right: -8px; }

    .lv-cmp-scroll {
        overflow-x: auto;
        overflow-y: visible;
        scroll-snap-type: x mandatory;
        scroll-padding-left: 220px;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: var(--c-primary, #064E5A) #F0F4F8;
    }
    .lv-cmp-scroll::-webkit-scrollbar { height: 8px; }
    .lv-cmp-scroll::-webkit-scrollbar-track { background: #F0F4F8; border-radius: 4px; }
    .lv-cmp-scroll::-webkit-scrollbar-thumb { background: var(--c-primary, #064E5A); border-radius: 4px; }

    /* ─────────── Table sémantique unique ─────────── */
    .lv-cmp-table {
        width: max-content;
        min-width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: #fff;
        font-size: 0.95rem;
    }
    .lv-cmp-table th, .lv-cmp-table td {
        padding: 12px 16px;
        text-align: left;
        vertical-align: middle;
        border-bottom: 1px solid #F3F4F6;
    }

    /* THEAD : sticky top + vignette par colonne */
    .lv-cmp-table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .lv-cmp-table thead th.cmp-th-criterion {
        position: sticky;
        top: 0;
        left: 0;
        z-index: 12;
        min-width: 200px;
        max-width: 220px;
        background: #FAFBFC;
        box-shadow: 2px 2px 8px rgba(0,0,0,0.06);
    }
    .lv-cmp-table thead th.cmp-th-tool {
        min-width: 220px;
        max-width: 240px;
        scroll-snap-align: start;
        border-top: 4px solid var(--c-primary, #064E5A);
        text-align: center;
        padding: 14px 12px;
    }
    .cmp-th-tool .tool-thumb { width: 100%; max-width: 200px; height: 100px; object-fit: cover; border-radius: 8px; margin-bottom: 8px; background: #F0F4F8; }
    .cmp-th-tool .tool-name { display: block; font-weight: 800; color: var(--c-dark, #1a1d23); font-size: 1rem; text-decoration: none !important; margin-bottom: 4px; line-height: 1.3; }
    .cmp-th-tool .tool-name:hover { color: var(--c-primary, #064E5A); }
    .cmp-th-tool .tool-meta { display: flex; gap: 6px; justify-content: center; flex-wrap: wrap; margin-bottom: 8px; align-items: center; }
    .cmp-th-tool .tool-meta .price { font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 50px; background: #F0F4F8; color: var(--c-dark, #1a1d23); }
    .cmp-th-tool .tool-actions { display: flex; gap: 6px; justify-content: center; }
    .cmp-th-tool .visit-link { display: inline-flex; align-items: center; gap: 4px; background: var(--c-primary, #064E5A); color: #fff !important; padding: 6px 12px; border-radius: 50px; text-decoration: none !important; font-weight: 700; font-size: 12px; min-height: 32px; }
    .cmp-th-tool .visit-link:hover { opacity: 0.9; color: #fff; }
    .cmp-th-tool .remove-btn { background: none; border: none; color: var(--c-text-muted, #52586a); cursor: pointer; font-size: 18px; line-height: 1; min-width: 32px; min-height: 32px; border-radius: 50%; }
    .cmp-th-tool .remove-btn:hover { color: var(--c-accent, #9A2A06); background: rgba(154,42,6,0.08); }
    .cmp-th-tool .remove-btn:focus-visible { outline: 2px solid var(--c-accent, #9A2A06); outline-offset: 2px; }

    /* TBODY : Sticky col 1 critère */
    .lv-cmp-table tbody th.cmp-th-criterion {
        position: sticky;
        left: 0;
        z-index: 5;
        background: #FAFBFC;
        font-weight: 600;
        color: var(--c-text-muted, #52586a);
        font-size: 0.9rem;
        min-width: 200px;
        max-width: 220px;
        white-space: normal;
        box-shadow: 2px 0 6px rgba(0,0,0,0.04);
    }
    .lv-cmp-table tbody td.cmp-value {
        font-weight: 600;
        color: var(--c-dark, #1a1d23);
        line-height: 1.5;
        text-align: center;
        min-width: 220px;
        max-width: 240px;
        scroll-snap-align: start;
    }
    .lv-cmp-table tbody td.cmp-value.same { color: var(--c-text-muted, #52586a); font-weight: 500; }
    .lv-cmp-table tbody td.cmp-value.best { background: rgba(6, 78, 90, 0.06); color: #064E5A; font-weight: 800; }
    .lv-cmp-table tbody td.cmp-value.best::before { content: '✓'; display: inline-block; margin-right: 6px; color: #064E5A; font-weight: 900; }
    .lv-cmp-table tbody td.cmp-value.worst { background: rgba(154, 42, 6, 0.04); color: #9A2A06; }
    .lv-cmp-table tbody td.cmp-value.unavailable { color: var(--c-text-muted, #52586a); font-weight: 500; font-style: italic; }

    /* Section header rows (sub-thead) */
    .cmp-section-row th {
        background: linear-gradient(135deg, #F0F4F8 0%, #E5E7EB 100%);
        font-weight: 800;
        color: var(--c-dark, #1a1d23);
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 16px;
        position: sticky;
        left: 0;
        z-index: 6;
        text-align: left;
    }
    .cmp-section-row.zone-common th { background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%); color: #064E3B; border-left: 4px solid #059669; }
    .cmp-section-row.zone-specific th { background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); color: #78410F; border-left: 4px solid #D97706; }

    /* Zone label divider */
    .cmp-zone-divider th {
        background: var(--c-dark, #1a1d23) !important;
        color: #fff !important;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        padding: 10px 16px;
        text-align: left;
        border-left: 0;
    }

    @media (max-width: 768px) {
        .lv-cmp-arrow { display: none !important; }
        .lv-cmp-table thead th.cmp-th-tool { min-width: 180px; max-width: 200px; padding: 10px 8px; }
        .cmp-th-tool .tool-thumb { height: 80px; }
        .lv-cmp-table th, .lv-cmp-table td { padding: 10px 8px; min-width: 180px; max-width: 200px; }
    }
</style>
@endpush

@section('content')
<div class="container lv-cmp-page">
    <a href="{{ route('directory.index') }}" class="lv-cmp-back">← {{ __('Retour au répertoire') }}</a>

    <div class="lv-cmp-header">
        <h1>📊 {{ __('Comparateur d\'outils IA') }}</h1>
        <p>
            @if($count >= 2)
                {{ __(':n outils côte-à-côte. Glissez horizontalement ou utilisez les flèches pour explorer.', ['n' => $count]) }}
            @elseif($count === 1)
                {{ __('Sélectionnez au moins un autre outil pour démarrer la comparaison.') }}
            @else
                {{ __('Sélectionnez 2 à :max outils dans le répertoire pour les comparer côte-à-côte.', ['max' => \Modules\Directory\Services\ToolComparisonService::MAX_TOOLS]) }}
            @endif
        </p>
    </div>

    @if($count === 0)
        <div class="lv-cmp-empty">
            <div class="icon" aria-hidden="true">🔍</div>
            <h2>{{ __('Aucun outil sélectionné') }}</h2>
            <p>{{ __('Parcourez l\'annuaire et cliquez sur ☐ pour ajouter un outil au comparateur.') }}</p>
            <a href="{{ route('directory.index') }}" class="btn">{{ __('Parcourir l\'annuaire') }}</a>
        </div>

        <div class="lv-cmp-suggestions">
            <h3>{{ __('💡 Comparatifs populaires') }}</h3>
            <p class="hint">{{ __('Démarrez avec un de ces comparatifs prêts :') }}</p>
            <div class="lv-cmp-suggestions-grid">
                <a href="{{ route('directory.compare-by-ids') }}?ids=1,2,6" class="lv-cmp-suggestion-chip">
                    <span class="emoji" aria-hidden="true">💬</span>
                    <span class="title">{{ __('Chatbots IA généralistes') }}</span>
                    <span class="subtitle">ChatGPT · Claude · Gemini</span>
                </a>
                <a href="{{ route('directory.compare-by-ids') }}?ids=3,75,19" class="lv-cmp-suggestion-chip">
                    <span class="emoji" aria-hidden="true">🎨</span>
                    <span class="title">{{ __('Génération d\'images') }}</span>
                    <span class="subtitle">Midjourney · DALL-E · Leonardo</span>
                </a>
                <a href="{{ route('directory.compare-by-ids') }}?ids=4,46,44" class="lv-cmp-suggestion-chip">
                    <span class="emoji" aria-hidden="true">💻</span>
                    <span class="title">{{ __('Assistants de code') }}</span>
                    <span class="subtitle">Cursor · Copilot · Codeium</span>
                </a>
                <a href="{{ route('directory.compare-by-ids') }}?ids=10,9,165" class="lv-cmp-suggestion-chip">
                    <span class="emoji" aria-hidden="true">🎙️</span>
                    <span class="title">{{ __('Audio et voix IA') }}</span>
                    <span class="subtitle">ElevenLabs · Suno · Gemini TTS</span>
                </a>
                <a href="{{ route('directory.compare-by-ids') }}?ids=11,26,37" class="lv-cmp-suggestion-chip">
                    <span class="emoji" aria-hidden="true">🎬</span>
                    <span class="title">{{ __('Génération vidéo') }}</span>
                    <span class="subtitle">Runway · Pika · Sora</span>
                </a>
                <a href="{{ route('directory.compare-by-ids') }}?ids=23,2,5" class="lv-cmp-suggestion-chip">
                    <span class="emoji" aria-hidden="true">🇫🇷</span>
                    <span class="title">{{ __('Outils respectueux vie privée') }}</span>
                    <span class="subtitle">Mistral · Claude · Perplexity</span>
                </a>
            </div>
        </div>
    @elseif($count === 1)
        <div class="lv-cmp-empty">
            <div class="icon" aria-hidden="true">➕</div>
            <h2>{{ __('1 outil sélectionné — il en faut au moins 2') }}</h2>
            <p>{{ __('Retournez à l\'annuaire et cochez au moins 1 outil supplémentaire.') }}</p>
            <a href="{{ route('directory.index') }}" class="btn">{{ __('Continuer la sélection') }}</a>
        </div>
    @else

    {{-- Toolbar permalien + clear --}}
    <div class="lv-cmp-toolbar"
         x-data="{ copied: false, copyLink() { navigator.clipboard.writeText(window.location.href).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000); }); } }">
        <button type="button" class="lv-cmp-pill-btn" @click="copyLink()">
            <span x-show="!copied">🔗 {{ __('Copier le lien partage') }}</span>
            <span x-show="copied" x-cloak>✅ {{ __('Lien copié !') }}</span>
        </button>
        <button type="button" class="lv-cmp-pill-btn" x-data @click="$store.compare.clear(); window.location.href = '{{ route('directory.index') }}';">
            🗑️ {{ __('Vider la sélection') }}
        </button>
    </div>

    {{-- Bandeau mismatch warning --}}
    @if($mismatch['is_mismatch'])
        <div class="lv-cmp-mismatch" role="alert">
            <span class="lv-cmp-mismatch-icon" aria-hidden="true">⚠️</span>
            <div class="lv-cmp-mismatch-text">
                <strong>{{ __('Outils de catégories différentes') }}</strong> —
                {{ __('Seuls :n sur :total outils partagent une catégorie commune. Certains critères seront marqués « — » lorsqu\'ils ne s\'appliquent pas.', ['n' => count($mismatch['dominant_tool_ids']), 'total' => $count]) }}
            </div>
            @if($mismatch['dominant_category'] && count($mismatch['dominant_tool_ids']) >= 2 && count($mismatch['dominant_tool_ids']) < $count)
                @php
                    $keepIds = implode(',', $mismatch['dominant_tool_ids']);
                @endphp
                <a href="{{ route('directory.compare-by-ids') }}?ids={{ $keepIds }}" class="lv-cmp-mismatch-cta">
                    {{ __('Filtrer « :cat » (:n outils)', ['cat' => $mismatch['dominant_category']->name, 'n' => count($mismatch['dominant_tool_ids'])]) }}
                </a>
            @endif
        </div>
    @endif

    {{-- Slider scroll horizontal avec flèches + table sémantique unique --}}
    <div class="lv-cmp-scroll-wrap"
         x-data="{
             canScrollLeft: false,
             canScrollRight: false,
             update() {
                 const el = this.$refs.scroll;
                 if (!el) return;
                 this.canScrollLeft = el.scrollLeft > 4;
                 this.canScrollRight = el.scrollLeft + el.clientWidth < el.scrollWidth - 4;
             },
             scrollBy(dir) {
                 const el = this.$refs.scroll;
                 if (!el) return;
                 const col = el.querySelector('.cmp-th-tool');
                 const step = col ? col.offsetWidth + 8 : 240;
                 el.scrollBy({ left: dir * step, behavior: 'smooth' });
             }
         }"
         x-init="$nextTick(() => update()); $refs.scroll && $refs.scroll.addEventListener('scroll', () => update(), { passive: true }); window.addEventListener('resize', () => update());">
        <button type="button"
                class="lv-cmp-arrow lv-cmp-arrow-left"
                :disabled="!canScrollLeft"
                @click="scrollBy(-1)"
                aria-label="{{ __('Défiler vers la gauche') }}">‹</button>
        <button type="button"
                class="lv-cmp-arrow lv-cmp-arrow-right"
                :disabled="!canScrollRight"
                @click="scrollBy(1)"
                aria-label="{{ __('Défiler vers la droite') }}">›</button>

        <div class="lv-cmp-scroll" x-ref="scroll" tabindex="0" aria-label="{{ __('Tableau de comparaison défilable') }}">
            <table class="lv-cmp-table">
                <thead>
                    <tr>
                        <th class="cmp-th-criterion" scope="col"><span class="visually-hidden">{{ __('Critère') }}</span></th>
                        @foreach($tools as $tool)
                            @php
                                $thumbSrc = $tool->screenshot
                                    ? (str_starts_with($tool->screenshot, 'http') ? $tool->screenshot : asset($tool->screenshot).'?v='.($tool->updated_at?->timestamp ?? '0'))
                                    : null;
                                $priceLabel = $pricingLabels[$tool->pricing] ?? ucfirst($tool->pricing ?? '—');
                            @endphp
                            <th class="cmp-th-tool" scope="col" data-tool-id="{{ (int) $tool->id }}" x-data>
                                @if($thumbSrc)
                                    <img src="{{ $thumbSrc }}" alt="" class="tool-thumb" loading="lazy" onerror="this.onerror=null; this.src='/images/directory-fallback.svg';">
                                @endif
                                <a href="{{ route('directory.show', $tool->slug) }}" class="tool-name">{{ $tool->name }}</a>
                                <div class="tool-meta">
                                    <span class="price">{{ $priceLabel }}</span>
                                </div>
                                <div class="tool-actions">
                                    @if($tool->url)
                                        <a href="{{ $tool->url }}" target="_blank" rel="noopener noreferrer nofollow" class="visit-link">{{ __('Visiter') }} ↗</a>
                                    @endif
                                    <button type="button" class="remove-btn"
                                            @click="
                                                const cur = $store.compare.ids.length ? $store.compare.ids : [{{ $tools->pluck('id')->implode(',') }}];
                                                const next = cur.filter(x => parseInt(x,10) !== {{ (int) $tool->id }});
                                                $store.compare.remove({{ (int) $tool->id }});
                                                window.location.href = next.length >= 2 ? '{{ route('directory.compare-by-ids') }}?ids=' + next.join(',') : '{{ route('directory.index') }}';
                                            "
                                            aria-label="{{ __('Retirer') }} {{ $tool->name }}">×</button>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    {{-- Zone Communs --}}
                    @if($commonSections->isNotEmpty())
                        <tr class="cmp-zone-divider">
                            <th colspan="{{ $count + 1 }}" scope="colgroup">{{ __('✓ Critères communs') }}</th>
                        </tr>
                        @foreach($commonSections as $sectionKey => $section)
                            <tr class="cmp-section-row zone-common">
                                <th colspan="{{ $count + 1 }}" scope="colgroup">{{ $section['icon'] }} {{ $section['label'] }}</th>
                            </tr>
                            @foreach($section['criteria'] as $critKey => $crit)
                                @php $diff = $service->computeDiff($tools, $crit); @endphp
                                <tr>
                                    <th scope="row" class="cmp-th-criterion">{{ $crit['label'] }}</th>
                                    @foreach($tools as $tool)
                                        @php
                                            $raw = $service->getValue($tool, $crit['accessor']);
                                            $hasValue = $raw !== null && $raw !== '' && $raw !== [];
                                            $cls = $hasValue ? ($diff[$tool->id] ?? 'neutral') : 'unavailable';
                                            $formatted = $hasValue ? $service->formatValue($raw, $crit['type']) : '—';
                                            $aria = $hasValue ? '' : __('Non applicable pour ce type d\'outil');
                                        @endphp
                                        <td class="cmp-value {{ $cls }}" @if($aria) aria-label="{{ $aria }}" @endif>{{ $formatted }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    @endif

                    {{-- Zone Spécifiques --}}
                    @if($specificSections->isNotEmpty())
                        <tr class="cmp-zone-divider">
                            <th colspan="{{ $count + 1 }}" scope="colgroup">{{ __('✕ Critères spécifiques') }}</th>
                        </tr>
                        @foreach($specificSections as $sectionKey => $section)
                            <tr class="cmp-section-row zone-specific">
                                <th colspan="{{ $count + 1 }}" scope="colgroup">{{ $section['icon'] }} {{ $section['label'] }}</th>
                            </tr>
                            @foreach($section['criteria'] as $critKey => $crit)
                                @php $diff = $service->computeDiff($tools, $crit); @endphp
                                <tr>
                                    <th scope="row" class="cmp-th-criterion">{{ $crit['label'] }}</th>
                                    @foreach($tools as $tool)
                                        @php
                                            $raw = $service->getValue($tool, $crit['accessor']);
                                            $hasValue = $raw !== null && $raw !== '' && $raw !== [];
                                            $cls = $hasValue ? ($diff[$tool->id] ?? 'neutral') : 'unavailable';
                                            $formatted = $hasValue ? $service->formatValue($raw, $crit['type']) : '—';
                                            $aria = $hasValue ? '' : __('Non applicable pour ce type d\'outil');
                                        @endphp
                                        <td class="cmp-value {{ $cls }}" @if($aria) aria-label="{{ $aria }}" @endif>{{ $formatted }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    @if($category && $count >= 2 && !$mismatch['is_mismatch'])
        <p style="margin-top: 24px; color: var(--c-text-muted, #52586a); font-size: 0.9rem;">
            {{ __('Pré-sélection automatique des :n outils les plus populaires de la catégorie « :cat »', ['n' => $count, 'cat' => $category->name]) }}
        </p>
    @endif

    @include('directory::public.partials._category_slider', [
        'categories' => $allCategories,
        'currentRoute' => 'compare',
        'activeSlug' => $category?->slug,
    ])
    @endif
</div>

<x-directory::compare-bar />
@endsection
