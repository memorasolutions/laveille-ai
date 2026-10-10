{{--
    Index des dossiers thématiques.

    Variable : $dossiers (collection d'objets entity_slug, entity_label, total).

    @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
    @project laveille.ai
--}}
@extends(fronttheme_layout())

@section('title', __('Dossiers thématiques').' - '.config('app.name'))
@section('meta_description', __('Chaque dossier réunit les actualités qui portent sur une même entreprise ou un même produit, de la première annonce à la plus récente.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => __('Dossiers thématiques'),
        'breadcrumbItems' => [__('Actualités'), __('Dossiers thématiques')],
    ])
@endsection

@include('news::public.partials.dossier-styles')

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <h1 style="font-family: var(--f-heading); margin-bottom: 0.25rem;">{{ __('Dossiers thématiques') }}</h1>
        <p class="nw-dossier-intro">{{ __('Chaque dossier réunit les actualités qui portent sur une même entreprise ou un même produit, pour suivre l\'ensemble d\'un sujet plutôt qu\'une annonce isolée.') }}</p>

        @if($dossiers->isEmpty())
            <p>{{ __('Aucun dossier n\'est encore assez fourni pour être publié.') }}</p>
        @else
        @php
            // Données du filtre client : la route est en responsecache, la recherche et le tri ne
            // touchent donc jamais le serveur (aucune clé de cache faussée).
            $dossiersJson = $dossiers->map(function ($d) {
                $date = $d->last_activity ? \Illuminate\Support\Carbon::parse($d->last_activity) : null;

                return [
                    'slug' => $d->entity_slug,
                    'label' => $d->entity_label,
                    'url' => route('news.dossier', $d->entity_slug),
                    'count' => (int) $d->total,
                    'countLabel' => trans_choice(':count actualité|:count actualités', $d->total, ['count' => $d->total]),
                    'ts' => $date?->timestamp ?? 0,
                    'dateLabel' => $date ? $date->locale('fr')->translatedFormat('j F Y') : '',
                ];
            })->values();
        @endphp
        <div x-data="dossiersFiltre(@js($dossiersJson))">
            <div class="nw-dossiers-outils" role="search" style="display:none" x-show="pret">
                <div class="nw-dossiers-champ">
                    <label for="dossiers-recherche">{{ __('Rechercher un dossier') }}</label>
                    <input type="search" id="dossiers-recherche" x-model="q" autocomplete="off" placeholder="{{ __('Ex. : OpenAI, Google…') }}">
                </div>
                <div class="nw-dossiers-champ">
                    <label for="dossiers-tri">{{ __('Trier par') }}</label>
                    <select id="dossiers-tri" x-model="tri">
                        <option value="activite">{{ __('Activité récente') }}</option>
                        <option value="volume">{{ __('Nombre d\'articles') }}</option>
                        <option value="az">{{ __('A-Z') }}</option>
                    </select>
                </div>
                <button type="button" class="nw-dossiers-effacer" x-show="q !== ''" @click="q = ''; $nextTick(() => $refs.vide && $refs.vide.focus())">{{ __('Effacer') }}</button>
            </div>
            <p class="nw-dossiers-compteur" aria-live="polite" style="display:none" x-show="pret" x-text="compteur"></p>
            <p style="display:none" x-show="pret && resultats.length === 0" x-ref="vide" tabindex="-1">{{ __('Aucun dossier ne correspond à cette recherche.') }}</p>
            <div class="row nw-dossiers-grid" style="display:none" x-show="pret">
                <template x-for="d in resultats" :key="d.slug">
                    <div class="col-sm-6 col-md-4" style="margin-bottom: 1.25rem;">
                        <div class="nw-dossier-card">
                            <a :href="d.url" class="nw-dossier-card-link">
                                <h2 class="nw-dossier-card-title" x-text="d.label"></h2>
                                <span class="nw-dossier-card-count" x-text="d.countLabel + (d.dateLabel ? ' · {{ __('Dernier article le') }} ' + d.dateLabel : '')"></span>
                            </a>
                        </div>
                    </div>
                </template>
            </div>
            {{-- Liste rendue par le serveur : visible tant qu'Alpine n'a pas démarré (ou s'il échoue
                 ou sans JavaScript), masquée ensuite au profit de la grille filtrée. --}}
            <div x-show="!pret">
                <div class="row nw-dossiers-grid">
                    @foreach($dossiers as $dossier)
                    <div class="col-sm-6 col-md-4" style="margin-bottom: 1.25rem;">
                        <div class="nw-dossier-card">
                            <a href="{{ route('news.dossier', $dossier->entity_slug) }}" class="nw-dossier-card-link">
                                <h2 class="nw-dossier-card-title">{{ $dossier->entity_label }}</h2>
                                <span class="nw-dossier-card-count">{{ trans_choice(':count actualité|:count actualités', $dossier->total, ['count' => $dossier->total]) }}</span>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
{{-- Filtre léger des dossiers (recherche + tri côté client, ~40 éléments déjà rendus). --}}
function dossiersFiltre(dossiers) {
    var norm = function (t) {
        return String(t).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    };
    return {
        tous: dossiers, q: '', tri: 'activite', pret: false,
        init: function () { this.pret = true; },
        get resultats() {
            var n = norm(this.q);
            var l = this.tous.filter(function (d) { return n === '' || norm(d.label).indexOf(n) !== -1; });
            var tri = this.tri;
            return l.slice().sort(function (a, b) {
                if (tri === 'az') { return a.label.localeCompare(b.label, 'fr', { sensitivity: 'base' }); }
                if (tri === 'volume') { return (b.count - a.count) || (b.ts - a.ts); }
                return (b.ts - a.ts) || (b.count - a.count);
            });
        },
        get compteur() {
            var n = this.resultats.length;
            return n + (n > 1 ? ' dossiers' : ' dossier');
        }
    };
}
</script>
@endpush
