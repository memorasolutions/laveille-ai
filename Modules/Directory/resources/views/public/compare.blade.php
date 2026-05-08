{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())

@php
    $count = $tools->count();
    $title = $count >= 2
        ? __('Comparatif :').' '.$tools->pluck('name')->implode(' · ')
        : ($category ? __('Comparatif :').' '.$category->name : __('Comparateur d\'outils IA'));
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
    .lv-cmp-back { display: inline-flex; align-items: center; gap: 6px; color: var(--c-primary, #064E5A); font-weight: 600; font-size: 14px; text-decoration: none !important; margin-bottom: 18px; }
    .lv-cmp-back:hover { color: var(--c-dark, #1a1d23); }
    .lv-cmp-header { margin-bottom: 24px; }
    .lv-cmp-header h1 { font-family: var(--f-heading); font-weight: 800; color: var(--c-dark, #1a1d23); margin: 0 0 6px; font-size: 1.85rem; letter-spacing: -0.5px; }
    .lv-cmp-header p { color: var(--c-text-muted, #52586a); margin: 0 0 14px; font-size: 1rem; }
    .lv-cmp-toolbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 28px; }
    .lv-cmp-permalink-btn {
        background: #F0F4F8; color: var(--c-dark, #1a1d23); border: 1px solid var(--c-border, #E5E7EB);
        padding: 8px 16px; border-radius: 50px; font-size: 13px; font-weight: 600; cursor: pointer; min-height: 40px;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .lv-cmp-permalink-btn:hover { background: #E5E7EB; }
    .lv-cmp-permalink-btn:focus-visible { outline: 2px solid var(--c-primary, #064E5A); outline-offset: 2px; }

    .lv-cmp-empty { background: #fff; border: 2px dashed var(--c-border, #E5E7EB); border-radius: 16px; padding: 50px 30px; text-align: center; }
    .lv-cmp-empty .icon { font-size: 3rem; margin-bottom: 14px; }
    .lv-cmp-empty h2 { color: var(--c-dark, #1a1d23); font-size: 1.4rem; margin: 0 0 10px; }
    .lv-cmp-empty p { color: var(--c-text-muted, #52586a); margin: 0 0 22px; }
    .lv-cmp-empty .btn { background: var(--c-primary, #064E5A); color: #fff; padding: 12px 24px; border-radius: 50px; text-decoration: none !important; font-weight: 700; min-height: 44px; display: inline-flex; align-items: center; }
    .lv-cmp-empty .btn:hover { opacity: 0.9; }

    .lv-cmp-suggestions { margin-top: 30px; }
    .lv-cmp-suggestions h3 { font-family: var(--f-heading); font-size: 1.2rem; color: var(--c-dark, #1a1d23); margin: 0 0 6px; font-weight: 800; }
    .lv-cmp-suggestions .hint { color: var(--c-text-muted, #52586a); font-size: 0.95rem; margin: 0 0 18px; }
    .lv-cmp-suggestions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 14px;
    }
    .lv-cmp-suggestion-chip {
        background: #fff;
        border: 1.5px solid var(--c-border, #E5E7EB);
        border-radius: 12px;
        padding: 16px 18px;
        text-decoration: none !important;
        color: var(--c-dark, #1a1d23);
        display: flex;
        flex-direction: column;
        gap: 4px;
        transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
        min-height: 96px;
    }
    .lv-cmp-suggestion-chip:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.06);
        border-color: var(--c-primary, #064E5A);
        color: var(--c-dark, #1a1d23);
    }
    .lv-cmp-suggestion-chip:focus-visible { outline: 3px solid var(--c-primary, #064E5A); outline-offset: 3px; }
    .lv-cmp-suggestion-chip .emoji { font-size: 1.8rem; line-height: 1; }
    .lv-cmp-suggestion-chip .title { font-weight: 800; font-size: 0.95rem; margin-top: 4px; }
    .lv-cmp-suggestion-chip .subtitle { color: var(--c-text-muted, #52586a); font-size: 0.85rem; }

    .lv-cmp-tools-header { display: grid; gap: 16px; margin-bottom: 26px; }
    .lv-cmp-tool-cell { background: #fff; border-radius: 14px; padding: 16px; border-top: 4px solid var(--c-primary, #064E5A); box-shadow: 0 2px 8px rgba(0,0,0,0.04); text-align: center; position: relative; cursor: grab; transition: transform 0.15s, box-shadow 0.15s; }
    .lv-cmp-tool-cell:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(0,0,0,0.06); }
    .lv-cmp-tool-cell:focus-visible { outline: 3px solid var(--c-primary, #064E5A); outline-offset: 3px; }
    .lv-cmp-tool-cell:active { cursor: grabbing; }
    .lv-cmp-drag-ghost { opacity: 0.4; }
    .lv-cmp-drag-chosen { box-shadow: 0 12px 24px rgba(0,0,0,0.15); }
    .lv-cmp-tool-cell .remove-btn { position: absolute; top: 8px; right: 8px; background: none; border: none; color: var(--c-text-muted, #52586a); font-size: 20px; cursor: pointer; line-height: 1; min-width: 28px; min-height: 28px; border-radius: 50%; }
    .lv-cmp-tool-cell .remove-btn:hover { color: var(--c-accent, #9A2A06); background: #F0F4F8; }
    .lv-cmp-tool-cell .remove-btn:focus-visible { outline: 2px solid var(--c-accent, #9A2A06); outline-offset: 2px; }
    .lv-cmp-tool-cell img.thumb { width: 100%; max-width: 220px; height: 110px; object-fit: cover; border-radius: 8px; margin-bottom: 10px; }
    .lv-cmp-tool-cell .tool-name { font-weight: 800; color: var(--c-dark, #1a1d23); font-size: 1.1rem; text-decoration: none !important; display: block; margin-bottom: 8px; }
    .lv-cmp-tool-cell .tool-name:hover { color: var(--c-primary, #064E5A); }
    .lv-cmp-tool-cell .visit-link { display: inline-block; background: var(--c-primary, #064E5A); color: #fff; padding: 8px 18px; border-radius: 50px; text-decoration: none !important; font-weight: 700; font-size: 13px; min-height: 38px; }
    .lv-cmp-tool-cell .visit-link:hover { opacity: 0.9; color: #fff; }

    .lv-cmp-section { background: #fff; border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 18px; overflow: hidden; }
    .lv-cmp-section-head { background: linear-gradient(135deg, #F0F4F8 0%, #E5E7EB 100%); padding: 14px 20px; font-weight: 800; color: var(--c-dark, #1a1d23); font-size: 1rem; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid var(--c-border, #E5E7EB); }
    .lv-cmp-section-head .icon { font-size: 1.2rem; }
    .lv-cmp-table { width: 100%; border-collapse: collapse; }
    .lv-cmp-table th, .lv-cmp-table td { padding: 12px 16px; vertical-align: top; text-align: left; border-bottom: 1px solid #F3F4F6; }
    .lv-cmp-table tbody tr:last-child th, .lv-cmp-table tbody tr:last-child td { border-bottom: none; }
    .lv-cmp-table th.criterion {
        font-weight: 600; color: var(--c-text-muted, #52586a); font-size: 0.9rem;
        white-space: normal; min-width: 180px; max-width: 240px;
        background: #FAFBFC; border-right: 1px solid #F3F4F6;
    }
    .lv-cmp-table td.value { font-weight: 600; color: var(--c-dark, #1a1d23); font-size: 0.95rem; line-height: 1.5; }
    .lv-cmp-table td.value.neutral { color: var(--c-dark, #1a1d23); }
    .lv-cmp-table td.value.same { color: var(--c-text-muted, #52586a); }
    .lv-cmp-table td.value.best {
        background: rgba(6, 78, 90, 0.06);
        color: #064E5A;
        position: relative;
        font-weight: 700;
    }
    .lv-cmp-table td.value.best::before {
        content: '✓';
        display: inline-block;
        margin-right: 6px;
        color: #064E5A;
        font-weight: 900;
    }
    .lv-cmp-table td.value.worst {
        background: rgba(154, 42, 6, 0.04);
        color: #9A2A06;
    }

    .lv-cmp-mobile { display: none; }

    @media (max-width: 768px) {
        .lv-cmp-tools-header { grid-template-columns: 1fr 1fr !important; }
        .lv-cmp-desktop { display: none; }
        .lv-cmp-mobile { display: block; }
        .lv-cmp-section-head { font-size: 0.95rem; padding: 12px 16px; }

        .lv-cmp-mobile-card { padding: 14px 16px; border-bottom: 1px solid #F3F4F6; }
        .lv-cmp-mobile-card:last-child { border-bottom: none; }
        .lv-cmp-mobile-card .crit-label { color: var(--c-text-muted, #52586a); font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; }
        .lv-cmp-mobile-card .tool-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 6px 0; border-radius: 6px; }
        .lv-cmp-mobile-card .tool-row.best { background: rgba(6, 78, 90, 0.06); padding: 8px 10px; color: #064E5A; font-weight: 700; }
        .lv-cmp-mobile-card .tool-row.worst { background: rgba(154, 42, 6, 0.04); padding: 8px 10px; color: #9A2A06; }
        .lv-cmp-mobile-card .tool-row .name { font-size: 0.85rem; font-weight: 700; flex: 0 0 auto; }
        .lv-cmp-mobile-card .tool-row .val { font-size: 0.9rem; text-align: right; flex: 1 1 auto; min-width: 0; word-break: break-word; }
    }

    @media (max-width: 480px) {
        .lv-cmp-tools-header { grid-template-columns: 1fr !important; }
        .lv-cmp-tool-cell img.thumb { max-width: 180px; height: 90px; }
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
                {{ trans_choice(':count outils côte-à-côte selon leur tarification, capacités IA, intégrations et confidentialité.|:count outils côte-à-côte selon leur tarification, capacités IA, intégrations et confidentialité.', $count, ['count' => $count]) }}
            @elseif($count === 1)
                {{ __('Sélectionnez au moins un autre outil pour démarrer la comparaison.') }}
            @else
                {{ __('Sélectionnez 2 à 4 outils dans le répertoire pour les comparer côte-à-côte.') }}
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

        {{-- Suggestions populaires --}}
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
    <div class="lv-cmp-toolbar"
         x-data="{ copied: false, copyLink() { navigator.clipboard.writeText(window.location.href).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000); }); } }">
        <button type="button" class="lv-cmp-permalink-btn" @click="copyLink()">
            <span x-show="!copied">🔗 {{ __('Copier le lien partage') }}</span>
            <span x-show="copied" x-cloak>✅ {{ __('Lien copié !') }}</span>
        </button>
        <button type="button" class="lv-cmp-permalink-btn" x-data @click="$store.compare.clear(); window.location.href = '{{ route('directory.index') }}';">
            {{ __('🗑️ Vider la sélection') }}
        </button>
    </div>

    {{-- Header outils (côte-à-côte, drag-to-reorder) --}}
    <p style="font-size:0.85rem;color:var(--c-text-muted,#52586a);margin-bottom:8px;">
        <span aria-hidden="true">↔️</span> {{ __('Glissez les vignettes pour réorganiser l\'ordre des colonnes.') }}
    </p>
    <div class="lv-cmp-tools-header"
         id="lvCmpToolsHeader"
         style="grid-template-columns: repeat({{ $count }}, 1fr);"
         role="list"
         aria-label="{{ __('Outils sélectionnés (réorganisables)') }}">
        @foreach($tools as $tool)
        <div class="lv-cmp-tool-cell" data-tool-id="{{ (int) $tool->id }}" role="listitem" tabindex="0" x-data>
            <button type="button" class="remove-btn"
                    @click="$store.compare.remove({{ (int) $tool->id }}); window.location.href = '{{ route('directory.compare-by-ids') }}?ids=' + $store.compare.ids.join(',');"
                    aria-label="{{ __('Retirer') }} {{ $tool->name }}">×</button>
            @php
                $thumbSrc = $tool->screenshot
                    ? (str_starts_with($tool->screenshot, 'http') ? $tool->screenshot : asset($tool->screenshot).'?v='.($tool->updated_at?->timestamp ?? '0'))
                    : null;
            @endphp
            @if($thumbSrc)
                <img src="{{ $thumbSrc }}" alt="" class="thumb" loading="lazy"
                     onerror="this.onerror=null; this.src='/images/directory-fallback.svg';">
            @endif
            <a href="{{ route('directory.show', $tool->slug) }}" class="tool-name">{{ $tool->name }}</a>
            @if($tool->url)
                <a href="{{ $tool->url }}" target="_blank" rel="noopener noreferrer nofollow" class="visit-link">{{ __('Visiter') }} ↗</a>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Sections critères --}}
    @foreach($criteria as $sectionKey => $section)
        @php
            $hasAnyValue = false;
            foreach ($section['criteria'] as $critKey => $crit) {
                foreach ($tools as $t) {
                    $v = $service->getValue($t, $crit['accessor']);
                    if ($v !== null && $v !== '' && $v !== []) { $hasAnyValue = true; break 2; }
                }
            }
        @endphp
        @if($hasAnyValue)
        <section class="lv-cmp-section" aria-labelledby="cmp-section-{{ $sectionKey }}">
            <div class="lv-cmp-section-head" id="cmp-section-{{ $sectionKey }}">
                <span class="icon" aria-hidden="true">{{ $section['icon'] }}</span>
                <span>{{ $section['label'] }}</span>
            </div>

            {{-- Desktop : table --}}
            <div class="lv-cmp-desktop">
                <table class="lv-cmp-table">
                    <tbody>
                        @foreach($section['criteria'] as $critKey => $crit)
                            @php $diff = $service->computeDiff($tools, $crit); @endphp
                            <tr>
                                <th scope="row" class="criterion">{{ $crit['label'] }}</th>
                                @foreach($tools as $tool)
                                    @php
                                        $raw = $service->getValue($tool, $crit['accessor']);
                                        $formatted = $service->formatValue($raw, $crit['type']);
                                        $cls = $diff[$tool->id] ?? 'neutral';
                                    @endphp
                                    <td class="value {{ $cls }}">{{ $formatted }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile : cards par critère --}}
            <div class="lv-cmp-mobile">
                @foreach($section['criteria'] as $critKey => $crit)
                    @php $diff = $service->computeDiff($tools, $crit); @endphp
                    <div class="lv-cmp-mobile-card">
                        <div class="crit-label">{{ $crit['label'] }}</div>
                        @foreach($tools as $tool)
                            @php
                                $raw = $service->getValue($tool, $crit['accessor']);
                                $formatted = $service->formatValue($raw, $crit['type']);
                                $cls = $diff[$tool->id] ?? 'neutral';
                            @endphp
                            <div class="tool-row {{ $cls }}">
                                <span class="name">{{ $tool->name }}</span>
                                <span class="val">{{ $formatted }}</span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </section>
        @endif
    @endforeach
    @endif

    @if($category && $count >= 2)
        <p style="margin-top: 24px; color: var(--c-text-muted, #52586a); font-size: 0.9rem;">
            {{ __('Pré-sélection automatique des :n outils les plus populaires de la catégorie « :cat »', ['n' => $count, 'cat' => $category->name]) }}
        </p>
    @endif

    @include('directory::public.partials._category_slider', [
        'categories' => $allCategories,
        'currentRoute' => 'compare',
        'activeSlug' => $category?->slug,
    ])
</div>

<x-directory::compare-bar />
@endsection

@push('scripts')
@if($count >= 2)
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js" integrity="sha384-Yq4VsBsEQHJDmyA6TpewE2lBC5dyECk7dlgT7ekxNIHJsi4lirh54SyPfAYK0M3a" crossorigin="anonymous" defer></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const header = document.getElementById('lvCmpToolsHeader');
    if (!header || typeof Sortable === 'undefined') return;
    Sortable.create(header, {
        animation: 220,
        ghostClass: 'lv-cmp-drag-ghost',
        chosenClass: 'lv-cmp-drag-chosen',
        forceFallback: true,
        fallbackOnBody: true,
        onEnd: function () {
            const ids = Array.from(header.querySelectorAll('[data-tool-id]'))
                .map(el => el.dataset.toolId)
                .filter(Boolean);
            if (ids.length < 2) return;
            const url = new URL(window.location.href);
            url.searchParams.set('ids', ids.join(','));
            window.location.href = url.toString();
        },
    });
});
</script>
@endif
@endpush
