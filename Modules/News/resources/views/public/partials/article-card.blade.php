{{--
    Carte d'actualité réutilisable - source de vérité unique.

    Utilisation :
        @include('news::public.partials.article-card', ['article' => $article])

    Variable requise :
        $article  (\Modules\News\Models\NewsArticle)

    Ce partial injecte ses propres styles via @once afin d'être utilisable
    dans n'importe quelle vue qui étend le layout principal (news index,
    fiche outil Directory, etc.) sans duplication CSS.

    @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
    @project laveille.ai
--}}

@once
@push('styles')
<style>
    /* === nw-card – carte d'actualité (source de vérité, ne pas dupliquer) === */
    .nw-card {
        display: flex; flex-direction: column; height: 100%;
        background: #fff; border-radius: 8px; overflow: hidden;
        transition: box-shadow 0.2s;
    }
    .nw-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
    .nw-card-link { text-decoration: none; color: inherit; display: flex; flex-direction: column; height: 100%; }
    .nw-card-img-wrap {
        position: relative; overflow: hidden; aspect-ratio: 16 / 9;
        background: linear-gradient(135deg, #1a2332 0%, #0b7285 100%);
        display: flex; align-items: center; justify-content: center;
    }
    .nw-card-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
    .nw-card:hover .nw-card-img { transform: scale(1.03); }
    .nw-card-placeholder { color: rgba(255,255,255,0.3); font-size: 2.5rem; font-weight: 700; font-family: var(--f-heading); }
    /* Actus 2.0 - badge « fiche comparative » (design doc section 7) : identifie dans le fil
       une fiche issue de la fusion multi-sources, sinon indiscernable d'une actualité normale.
       Tailles/ombre reprises de .nw-source-pill et .nw-bd-flag (même fichier, aucune valeur inventée). */
    .nw-digest-badge {
        position: absolute; top: 8px; left: 8px; z-index: 2;
        display: inline-flex; align-items: center; max-width: calc(100% - 16px);
        padding: 4px 10px; border-radius: 999px;
        background: var(--c-primary); color: #fff;
        font-size: 0.6875rem; font-weight: 700; line-height: 1.3;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        box-shadow: 0 2px 6px rgba(0,0,0,0.18);
    }
    /* Indicateur « BD disponible » (standard visionneur de BD) : picto Octopus discret en coin */
    .nw-bd-flag {
        position: absolute; top: 8px; right: 8px; z-index: 2;
        display: inline-flex; align-items: center; justify-content: center;
        width: 34px; height: 34px; border-radius: 50%;
        background: rgba(255,255,255,0.92); box-shadow: 0 2px 6px rgba(0,0,0,0.18);
    }
    /* Indicateur « outils liés » (admin-only) : repère visuel pour savoir quelle actualité a déjà été traitée */
    .nw-tools-flag {
        position: absolute; bottom: 8px; left: 8px; z-index: 2;
        display: inline-flex; align-items: center; justify-content: center;
        width: 34px; height: 34px; min-width: 44px; min-height: 44px; margin: -5px;
        padding: 0; line-height: 1; border-radius: 50%; font-size: 1rem;
        background: rgba(255,255,255,0.92); box-shadow: 0 2px 6px rgba(0,0,0,0.18);
    }
    /* Bouton engrenage admin : ouvre la popup rapide de liaison d'outils sans quitter la liste */
    .nw-gear-btn {
        position: absolute; bottom: 8px; right: 8px; z-index: 3;
        display: inline-flex; align-items: center; justify-content: center;
        width: 34px; height: 34px; min-width: 44px; min-height: 44px; margin: -5px;
        padding: 0; line-height: 1; font-size: 1rem;
        border-radius: 50%; border: none; cursor: pointer;
        background: rgba(255,255,255,0.92); box-shadow: 0 2px 6px rgba(0,0,0,0.18);
        transition: background 0.15s, transform 0.15s;
    }
    .nw-gear-btn:hover { background: #fff; transform: scale(1.06); }
    .nw-gear-btn:focus-visible { outline: 3px solid #064E5A; outline-offset: 2px; }
    .nw-card-body { padding: 1rem; flex: 1; display: flex; flex-direction: column; }
    .nw-card-title {
        font-family: var(--f-heading); font-size: 1.05rem; font-weight: 700;
        line-height: 1.35; color: var(--c-dark); margin: 0 0 0.5rem;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .nw-card-hook {
        font-size: 0.9rem; color: #4b5563; line-height: 1.55; margin-bottom: 0.75rem;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; flex: 1;
    }
    .nw-meta { display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; margin-top: auto; padding-top: 0.75rem; border-top: 1px solid #f3f4f6; }
    .nw-source-pill { background: #f3f4f6; color: #374151; font-size: 0.6875rem; font-weight: 600; padding: 0.15rem 0.5rem; border-radius: 4px; text-transform: uppercase; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .nw-date { font-size: 0.75rem; color: #6b7280; display: flex; align-items: center; gap: 0.375rem; }
    .nw-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
    .nw-dot-high { background: #10b981; }
    .nw-dot-mid { background: #3b82f6; }
    .nw-dot-low { background: #d1d5db; }
    .nw-impact-bar { border-left: 3px solid transparent; }
    .nw-impact-high { border-left-color: #ef4444; }
    .nw-impact-mid { border-left-color: #f59e0b; }
    .nw-impact-low { border-left-color: #e5e7eb; }
    .nw-empty { text-align: center; padding: 4rem 1rem; color: #6b7280; }
</style>
@endpush
@endonce

@php
    /** @var \Modules\News\Models\NewsArticle $article */
    $ss          = $article->structured_summary;
    // Actus 2.0 : $isDigest calculée à partir de la colonne déjà chargée avec la carte
    // (aucune requête additionnelle - même expression que show.blade.php).
    $isDigest    = (bool) ($article->is_comparative_digest ?? false);
    $digestSourcesCount = $isDigest && is_array($ss) ? count($ss['sources'] ?? []) : 0;
    $score       = $article->relevance_score ?? 0;
    $dotClass    = $score >= 8 ? 'nw-dot-high' : ($score >= 6 ? 'nw-dot-mid' : 'nw-dot-low');
    $impactClass = match($article->impact_level ?? '') {
        'Élevé' => 'nw-impact-high',
        'Moyen' => 'nw-impact-mid',
        default => 'nw-impact-low',
    };
    // Temps de lecture calculé sur le résumé publié, jamais sur le texte source (design doc
    // "Actus - zéro copie du texte source", 2026-08-13, section 4.5) : bloc réutilisable
    // unique, identique à show.blade.php.
    $readMinutes = reading_time_minutes($article->structuredBodyText());
@endphp

<article class="nw-card nw-impact-bar {{ $impactClass }}">
    <a href="{{ route('news.show', $article) }}" class="nw-card-link">
        <div class="nw-card-img-wrap">
            {{-- Actus 2.0 - badge « fiche comparative » : seule condition d'affichage, sinon rien
                 (aucune régression visuelle sur une actualité normale). --}}
            @if($isDigest)
                <span class="nw-digest-badge" title="{{ __('Cette fiche compare et regroupe plusieurs sources sur le même sujet') }}">
                    {{ __('Fiche comparative') }}{{ $digestSourcesCount > 0 ? ' - ' . trans_choice(':count source|:count sources', $digestSourcesCount, ['count' => $digestSourcesCount]) : '' }}
                </span>
            @endif
            @if($article->image_url)
                <img src="{{ $article->image_url }}{{ str_contains($article->image_url, 'http') ? '' : '?v='.($article->updated_at?->timestamp ?? time()) }}"
                     alt="{{ $article->seo_title ?? $article->title }}"
                     class="nw-card-img" loading="lazy">
            @else
                <span class="nw-card-placeholder">{{ mb_strtoupper(mb_substr($article->category_tag ?? 'N', 0, 2)) }}</span>
            @endif
            {{-- Indicateur BD (standard visionneur de BD) : picto Octopus si public/bd/{slug}/manifest.json existe --}}
            @if(class_exists(\Modules\Dictionary\Support\ComicLibrary::class) && \Modules\Dictionary\Support\ComicLibrary::hasComic((string) $article->slug))
                <span class="nw-bd-flag" aria-label="{{ __('Bande dessinée disponible') }}" title="{{ __('Bande dessinée disponible') }}">
                    <x-tools::octopus variant="happy" :size="24" :animate="false" />
                </span>
            @endif
            {{-- Indicateur « outils liés » (admin-only) : repère visuel pour savoir quelle actualité a déjà été traitée --}}
            @can('view_admin_panel')
                @if($article->relationLoaded('tools') && $article->tools->isNotEmpty())
                    <span class="nw-tools-flag" aria-label="{{ __('Outils liés à cette actualité') }}" title="{{ __('Outils liés à cette actualité') }}">
                        🔧
                    </span>
                @endif
            @endcan
            {{-- Bouton engrenage admin : popup rapide pour lier des outils sans quitter la liste --}}
            @can('view_admin_panel')
                <button
                    type="button"
                    class="nw-gear-btn"
                    onclick="event.preventDefault(); event.stopPropagation(); window.Livewire.dispatch('open-news-tools-editor', { articleId: {{ $article->id }} });"
                    aria-label="{{ __('Gérer les outils liés à :title', ['title' => $article->title]) }}"
                    title="{{ __('Gérer les outils liés') }}"
                >⚙️</button>
            @endcan
        </div>
        <div class="nw-card-body">
            <h3 class="nw-card-title" role="heading" aria-level="2">
                {{-- Point rouge "déjà publié" (superadmin only) — même composant partagé que la fiche
                     article et la liste admin. Voir components/admin-shared-dot.blade.php. --}}
                <x-news::admin-shared-dot :article="$article" />{{ $article->seo_title ?? $article->title }}
            </h3>
            {{-- Cascade accroche de carte (design doc section 4.5) : hook du résumé structuré,
                 sinon NewsArticle::displayExcerpt() (résumé court, sinon accroche du résumé
                 structuré, sinon repli configuré) - jamais $article->description. --}}
            <p class="nw-card-hook">
                @if($ss && isset($ss['hook']))
                    {{ $ss['hook'] }}
                @else
                    {{ $article->displayExcerpt(120) }}
                @endif
            </p>
            <div class="nw-meta">
                <span class="nw-source-pill">{{ $readMinutes }} min</span>
                <span class="nw-source-pill">{{ $article->source->name ?? __('Source') }}</span>
                <span class="nw-date">
                    {{ $article->pub_date?->diffForHumans() }}
                    @if($score)
                        <span class="nw-dot {{ $dotClass }}" title="{{ $score }}/10"></span>
                    @endif
                </span>
            </div>
        </div>
    </a>
</article>
