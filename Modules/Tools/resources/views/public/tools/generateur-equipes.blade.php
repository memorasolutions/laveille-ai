<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@php $shareData = $tool->getShareData(); @endphp
@section('meta_description', $shareData['meta_description'])
@section('og_type', $shareData['og_type'])
@section('og_image', $shareData['og_image'])
@section('share_text', $shareData['share_text'])
@section('title', $tool->name . ' - ' . config('app.name'))
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $tool->name, 'breadcrumbItems' => [__('Outils'), $tool->name]])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-12">
                {{-- #707 : tool-geo (JSON-LD + answer-box) déplacé DANS le conteneur pour respecter
                     la largeur du contenu (auparavant plein-largeur hors .container, cf. blog/show.blade.php). --}}
                @include('tools::public.partials.tool-geo')
                <div class="card shadow-sm tool-fullscreen-target" style="border-radius: var(--r-base);">
                    <div class="card-body p-4 p-md-5" x-data="teamGen()" x-init="init()">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h1 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); margin: 0;">{{ $tool->name }}</h1>
                            <div class="d-flex gap-1">
                                @include('tools::partials.fullscreen-btn')
                                @include('tools::partials.share-btn', ['tool' => $tool])
                            </div>
                        </div>
                        <p class="text-muted mb-3">{{ __('Répartition équitable et aléatoire. Glissez-déposez pour ajuster, excluez des paires, sauvegardez vos configurations.') }}</p>

                        {{-- Barre sauvegarde (connectés) --}}
                        <div x-show="isAuthenticated" x-cloak style="background: rgba(11,114,133,0.04); border: 1px solid rgba(11,114,133,0.12); border-radius: 10px; padding: 12px; margin-bottom: 16px;">
                            <div class="d-flex gap-2 align-items-center">
                                <input type="text" class="form-control form-control-sm flex-fill" x-model="saveName" placeholder="{{ __('Nommer cette configuration...') }}" aria-label="{{ __('Nom de la configuration') }}" style="border-radius: 8px;">
                                <button class="ct-btn ct-btn-primary ct-btn-sm" @click="saveToAccount()" :disabled="nameList.length < 2 || saving" style="white-space:nowrap;"
                                        x-text="saving ? '{{ __('Sauvegarde...') }}' : (_editingId ? '{{ __('Mettre à jour') }}' : '{{ __('Sauvegarder') }}')"></button>
                            </div>
                            <div class="small mt-2" style="font-size: 0.8rem; color: var(--c-text-muted);">
                                {{ __('Retrouvez vos configurations dans') }} <a href="{{ route('user.saved') }}?tab=team-configs" style="color: var(--c-primary); text-decoration: underline;">{{ __('vos sauvegardes') }}</a>.
                            </div>
                            <template x-if="saveError">
                                <div class="alert alert-danger small p-1 mt-2 mb-0" style="font-size: 0.8rem; border-radius: 6px;" x-text="saveError"></div>
                            </template>
                        </div>
                        {{-- Bandeau visiteurs --}}
                        <div x-show="!isAuthenticated" x-cloak style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; font-size: 0.85rem; color: #0369a1;">
                            {{ __('Connectez-vous pour sauvegarder vos configurations dans votre compte.') }}
                        </div>

                        @include('fronttheme::partials.tabs', ['tabs' => [
                            ['id' => 'setup', 'label' => '👥 ' . __('Configuration')],
                            ['id' => 'options', 'label' => '⚙️ ' . __('Options avancées')],
                        ], 'model' => 'tab'])

                        {{-- ==================== ONGLET CONFIGURATION ==================== --}}
                        <div x-show="tab === 'setup'" x-transition>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">{{ __('Participants') }} (<span x-text="nameList.length"></span>)</label>
                                    <textarea class="form-control" rows="6" x-model="names" aria-label="Liste des participants" placeholder="{{ __("Alice\nBob\nCharlie\nDiane\nÉric\nFrançoise\nGabriel\nHélène") }}"></textarea>
                                    <div class="d-flex gap-2 mt-2">
                                        <button class="ct-btn ct-btn-outline ct-btn-sm" @click="addFromClipboard()" style="border-radius: var(--r-btn); font-size: 0.8rem;">📋 {{ __('Coller') }}</button>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">{{ __('Répartir par') }}</label>
                                    <div class="btn-group w-100 mb-3">
                                        <button class="ct-btn ct-btn-sm" :class="mode === 'count' ? 'ct-btn-primary' : 'ct-btn-outline'" @click="mode = 'count'">{{ __('Nombre d\'équipes') }}</button>
                                        <button class="ct-btn ct-btn-sm" :class="mode === 'size' ? 'ct-btn-primary' : 'ct-btn-outline'" @click="mode = 'size'">{{ __('Taille d\'équipe') }}</button>
                                    </div>
                                    <div x-show="mode === 'count'" class="mb-3">
                                        <label class="form-label">{{ __('Nombre d\'équipes') }} : <strong x-text="teamCount"></strong></label>
                                        <input type="range" class="form-range" x-model.number="teamCount" aria-label="Nombre d'équipes" min="2" max="20">
                                    </div>
                                    <div x-show="mode === 'size'" class="mb-3">
                                        <label class="form-label">{{ __('Personnes par équipe') }} : <strong x-text="teamSize"></strong></label>
                                        <input type="range" class="form-range" x-model.number="teamSize" aria-label="Taille des équipes" min="2" max="20">
                                    </div>
                                    <div class="text-muted" style="font-size: 0.85rem;">
                                        → <span x-text="actualTeamCount"></span> {{ __('équipes') }}
                                        <span x-show="nameList.length > 0" x-text="'(' + Math.ceil(nameList.length / actualTeamCount) + ' pers./équipe)'"></span>
                                    </div>
                                </div>
                            </div>

                            {{-- Boutons action --}}
                            <div class="d-flex gap-2 mb-4">
                                <button class="ct-btn ct-btn-accent flex-fill" @click="generate()" :disabled="nameList.length < 2"
                                        style="background: var(--c-accent); color: #fff; border-radius: var(--r-btn); font-family: var(--f-heading); font-weight: 700;">
                                    <span x-text="drawn ? '🔀 Re-mélanger' : '🎲 Générer les équipes'"></span>
                                </button>
                                <button class="ct-btn ct-btn-outline" @click="undo()" x-show="previousTeams" style="border-radius: var(--r-btn);" title="{{ __('Annuler') }}">↩️</button>
                                <button class="ct-btn ct-btn-outline" @click="reset()" x-show="drawn" style="border-radius: var(--r-btn);" title="{{ __('Réinitialiser') }}">🗑️</button>
                            </div>
                        </div>

                        {{-- ==================== ONGLET OPTIONS AVANCÉES ==================== --}}
                        <div x-show="tab === 'options'" x-transition>
                            {{-- Exclusions --}}
                            <div class="mb-4">
                                <label class="form-label fw-medium">🚫 {{ __('Exclure des paires') }}</label>
                                <p class="text-muted" style="font-size: 0.8rem;">{{ __('Touchez un nom, puis touchez la personne qui ne doit jamais être dans la même équipe.') }}</p>

                                <template x-if="nameList.length === 0">
                                    <p class="text-muted" style="font-size: 0.85rem;">{{ __("Saisissez d'abord les participants dans l'onglet précédent.") }}</p>
                                </template>

                                <template x-if="nameList.length > 0">
                                    <div>
                                        <div class="d-flex flex-wrap gap-2 mb-2" role="group" aria-label="{{ __('Participants') }}">
                                            <template x-for="nom in nameList" :key="nom">
                                                <button type="button"
                                                        class="ct-btn ct-btn-sm"
                                                        :class="pivot === nom ? 'ct-btn-primary' : 'ct-btn-outline'"
                                                        :aria-pressed="pivot === nom"
                                                        @click="togglePivot(nom)"
                                                        :style="pivot === nom ? 'min-width:44px; min-height:44px; border-radius: var(--r-btn); border-width: 3px; font-weight: 700;' : 'min-width:44px; min-height:44px; border-radius: var(--r-btn); border-width: 2px;'">
                                                    <span x-show="pivot === nom" aria-hidden="true">📌 </span><span x-text="nom"></span>
                                                </button>
                                            </template>
                                        </div>

                                        <p class="mb-2" style="font-size: 0.85rem; color: var(--c-primary); font-weight: 600;" x-show="pivot" x-text="pivot ? '{{ __('Choisissez qui ne doit pas être avec') }} ' + pivot : ''"></p>

                                        {{-- Exclusions actives, en phrases lisibles --}}
                                        <template x-for="item in exclusionsAffichables" :key="'excl-' + item.index">
                                            <div class="d-flex align-items-center gap-2 mb-1 p-2 rounded" style="background: #FEE2E2; font-size: 0.85rem;">
                                                <span x-text="item.name1 + ' {{ __('et') }} ' + item.name2 + ' {{ __('ne seront jamais ensemble') }}'"></span>
                                                <button type="button" class="ct-btn ct-btn-outline-danger ct-btn-sm ms-auto" @click="removeExclusion(item.index)" style="min-height:44px; white-space:nowrap;">{{ __('Retirer') }}</button>
                                            </div>
                                        </template>
                                        <p class="text-muted mt-1" x-show="exclusions.length === 0" style="font-size: 0.8rem;">{{ __('Aucune exclusion définie.') }}</p>

                                        {{-- Exclusions orphelines : la liste des participants a changé depuis --}}
                                        <template x-if="exclusionsOrphelines.length > 0">
                                            <div class="mt-3 p-2 rounded" style="background: #FFF7ED; border: 1px solid #FDBA74;">
                                                <p class="mb-2 d-flex align-items-center gap-2" style="font-size: 0.8rem; font-weight: 600; color: #7C2D12;"><span aria-hidden="true">⚠️</span> {{ __('Ces exclusions ne correspondent plus à personne dans votre liste') }}</p>
                                                <template x-for="item in exclusionsOrphelines" :key="'orphan-' + item.index">
                                                    <div class="d-flex align-items-center gap-2 mb-1 p-2 rounded" style="background: #fff; font-size: 0.85rem;">
                                                        <span x-text="item.name1 + ' ≠ ' + item.name2"></span>
                                                        <button type="button" class="ct-btn ct-btn-outline-danger ct-btn-sm ms-auto" @click="removeExclusion(item.index)" style="min-height:44px; white-space:nowrap;">{{ __('Retirer') }}</button>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>

                                        {{-- Indicateur vivant --}}
                                        <p class="mt-3 mb-0" style="font-size: 0.85rem; color: var(--c-text-muted);" x-show="exclusionsAffichables.length > 0" x-text="'{{ __('Minimum') }} ' + minimumEquipesRequis() + ' {{ __('équipes avec ces contraintes.') }}'"></p>
                                    </div>
                                </template>
                            </div>

                            {{-- Presets (localStorage uniquement pour visiteurs, masqué pour connectés qui utilisent la barre en haut) --}}
                            <div class="mb-4" x-show="!isAuthenticated">
                                <label class="form-label fw-medium">💾 {{ __('Configurations sauvegardées') }}</label>
                                <div class="d-flex gap-2 mb-2">
                                    <input type="text" class="form-control form-control-sm" x-model="presetName" aria-label="Nom de la configuration" placeholder="{{ __('Nom de la configuration...') }}">
                                    <button class="ct-btn ct-btn-primary ct-btn-sm" @click="savePreset()" style="white-space:nowrap;">💾 {{ __('Sauvegarder') }}</button>
                                </div>
                                <template x-for="(p, i) in presets" :key="i">
                                    <div class="d-flex align-items-center gap-2 mb-1 p-2 rounded" style="background: #f8f9fa; font-size: 0.85rem;">
                                        <span class="flex-fill" x-text="p.name"></span>
                                        <button class="ct-btn ct-btn-outline ct-btn-sm" @click="loadPreset(i)" style="font-size: 0.7rem;">{{ __('Charger') }}</button>
                                        <button class="ct-btn ct-btn-outline-danger ct-btn-sm" @click="deletePreset(i)" style="font-size: 0.7rem;">✕</button>
                                    </div>
                                </template>
                                <p class="text-muted mt-1" x-show="presets.length === 0" style="font-size: 0.8rem;">{{ __('Aucune configuration sauvegardée.') }}</p>
                            </div>
                        </div>

                        {{-- ==================== MESSAGE D'IMPOSSIBILITÉ ==================== --}}
                        {{-- Contraste vérifié : #7f1d1d sur #FEF2F2 = ratio ~9,17:1 (AAA 1.4.6 exige 7:1). --}}
                        <div x-show="impossibleMessage" x-cloak role="status" aria-live="polite" class="mt-2 mb-3 p-3" style="background: #FEF2F2; border: 2px solid #7f1d1d; border-radius: var(--r-base);">
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <span aria-hidden="true" style="font-size: 1.3rem; line-height: 1;">⚠️</span>
                                <p class="mb-0" style="color: #7f1d1d; font-weight: 600;" x-text="impossibleMessage ? impossibleMessage.texte : ''"></p>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="ct-btn ct-btn-primary ct-btn-sm" @click="appliquerSuggestionEquipes()" x-show="impossibleMessage && impossibleMessage.suggestion" style="border-radius: var(--r-btn); min-height: 44px;">
                                    <span x-text="impossibleMessage ? '{{ __('Passer à') }} ' + impossibleMessage.suggestion + ' {{ __('équipes') }}' : ''"></span>
                                </button>
                                <button type="button" class="ct-btn ct-btn-outline ct-btn-sm" @click="tab = 'options'" style="border-radius: var(--r-btn); min-height: 44px;">{{ __('Modifier les exclusions') }}</button>
                            </div>
                        </div>

                        {{-- ==================== RÉSULTATS ==================== --}}
                        <template x-if="drawn">
                            <div>
                                {{-- Mention tirage périmé : reprend #7f1d1d sur #FEF2F2 (ratio ~9,17:1,
                                     AAA 1.4.6). Le texte reste à opacité pleine (hors du conteneur
                                     assombri ci-dessous) pour rester lisible par lecteur d'écran ET
                                     visuellement, la mise en retrait ne repose pas sur la couleur seule. --}}
                                <template x-if="tirageObsolete">
                                    <p role="status" aria-live="polite" class="mb-2 p-2" style="color: #7f1d1d; background: #FEF2F2; border: 1px solid #7f1d1d; border-radius: var(--r-base); font-weight: 600; font-size: 0.9rem;">
                                        <span aria-hidden="true">⚠️</span> {{ __('Tirage précédent : il ne respecte pas vos dernières exclusions.') }}
                                    </p>
                                </template>
                                <div class="row" :style="tirageObsolete ? 'opacity: 0.55;' : ''">
                                    <template x-for="(team, ti) in teams" :key="ti">
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="p-3 rounded h-100"
                                                 :style="'background:' + team.color + '10; border-left: 4px solid ' + team.color + ';'"
                                                 @dragover.prevent @drop="dropOnTeam($event, ti)">
                                                <input type="text" class="form-control form-control-sm mb-2 fw-bold"
                                                       x-model="teams[ti].name"
                                                       :style="'background: transparent; border: 1px dashed ' + team.color + '; color: ' + team.color + '; font-family: var(--f-heading);'">
                                                <ul class="mb-0" style="padding-left: 0; list-style: none;">
                                                    <template x-for="(member, mi) in team.members" :key="mi">
                                                        <li class="mb-1 p-1 rounded d-flex align-items-center"
                                                            draggable="true"
                                                            @dragstart="dragStart($event, member, ti)"
                                                            style="cursor: grab; font-size: 0.9rem; background: rgba(255,255,255,0.7);">
                                                            <span style="width: 8px; height: 8px; border-radius: 50; display: inline-block; margin-right: 8px; flex-shrink: 0;" :style="'background:' + team.color"></span>
                                                            <span x-text="member"></span>
                                                        </li>
                                                    </template>
                                                </ul>
                                                <small class="text-muted" x-text="team.members.length + ' personne(s)'"></small>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <button class="ct-btn ct-btn-primary ct-btn-full mt-2" @click="copyResults()"
                                        x-text="copied ? '✅ Copié !' : '📋 Copier les résultats'"></button>
                            </div>
                        </template>

                        <p class="text-muted mt-3 mb-0" style="font-size: 0.8rem;">
                            🔒 {{ __('Les équipes sont générées aléatoirement dans votre navigateur avec crypto.getRandomValues(). Glissez-déposez les noms entre les équipes pour ajuster.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('fronttheme::partials.tools-newsletter-cta', ['toolSource' => 'generateur-equipes'])
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('teamGen', function() {
        return {
            tab: 'setup',
            names: localStorage.getItem('tg_names') || '',
            mode: 'count',
            teamCount: 2,
            teamSize: 3,
            teams: [],
            drawn: false,
            copied: false,
            previousTeams: null,
            colors: ['#0B7285','#E67E22','#6366f1','#10b981','#ef4444','#8b5cf6','#f59e0b','#06b6d4','#ec4899','#14b8a6'],

            // Options avancées
            exclusions: [],
            pivot: null,
            impossibleMessage: null,
            tirageObsolete: false,
            presets: [],
            presetName: '',

            // Sauvegarde compte
            isAuthenticated: {{ auth()->check() ? 'true' : 'false' }},
            saveName: '',
            saving: false,
            saveError: '',
            _editingId: null,
            _derniereBorneAtteinte: false,

            init: function() {
                var self = this;
                this.$watch('names', function() {
                    if (self.pivot && self.nameList.indexOf(self.pivot) === -1) self.pivot = null;
                });
                if (this.isAuthenticated) {
                    // Charger depuis API
                    fetch('/api/team-presets', { headers: this._headers() })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            self.presets = (data.data || []).map(function(p) {
                                return { id: p.public_id, name: p.name, names: p.config_text, mode: (p.params||{}).mode || 'count', teamCount: (p.params||{}).teamCount || 2, teamSize: (p.params||{}).teamSize || 3, exclusions: (p.params||{}).exclusions || [] };
                            });
                        }).catch(function() {
                            try { self.presets = JSON.parse(localStorage.getItem('tg_presets') || '[]'); } catch(e) { self.presets = []; }
                        });

                    // Mode édition ?edit=PUBLIC_ID
                    var params = new URLSearchParams(window.location.search);
                    var editId = params.get('edit');
                    if (editId) {
                        fetch('/api/team-presets', { headers: this._headers() })
                            .then(function(r) { return r.json(); })
                            .then(function(data) {
                                var found = (data.data || []).find(function(p) { return p.public_id === editId; });
                                if (found) {
                                    self.names = found.config_text || '';
                                    var pr = found.params || {};
                                    self.mode = pr.mode || 'count';
                                    self.teamCount = pr.teamCount || 2;
                                    self.teamSize = pr.teamSize || 3;
                                    self.exclusions = pr.exclusions || [];
                                    self.saveName = found.name;
                                    self._editingId = found.public_id;
                                }
                            });
                    }
                } else {
                    try { this.presets = JSON.parse(localStorage.getItem('tg_presets') || '[]'); } catch(e) { this.presets = []; }
                }
            },

            _headers: function() {
                return { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' };
            },

            get nameList() {
                return this.names.split('\n').map(function(n) { return n.trim(); }).filter(function(n) { return n; });
            },

            get actualTeamCount() {
                if (this.mode === 'count') return Math.max(1, this.teamCount);
                return Math.max(1, Math.ceil(this.nameList.length / Math.max(1, this.teamSize)));
            },

            // Exclusions actives dont les deux noms existent encore dans la liste des participants
            get exclusionsAffichables() {
                var noms = this.nameList;
                return this.exclusions.map(function(e, i) { return { name1: e.name1, name2: e.name2, index: i }; }).filter(function(item) {
                    return noms.indexOf(item.name1) !== -1 && noms.indexOf(item.name2) !== -1;
                });
            },

            // Exclusions dont au moins un nom ne correspond plus à la liste actuelle des participants
            get exclusionsOrphelines() {
                var noms = this.nameList;
                return this.exclusions.map(function(e, i) { return { name1: e.name1, name2: e.name2, index: i }; }).filter(function(item) {
                    return noms.indexOf(item.name1) === -1 || noms.indexOf(item.name2) === -1;
                });
            },

            secureRandom: function(max) {
                var arr = new Uint32Array(1);
                crypto.getRandomValues(arr);
                return arr[0] % max;
            },

            // Retour arrière (backtracking) déterministe et exact : un échec ici PROUVE l'impossibilité,
            // contrairement à un tirage aléatoire répété dont l'échec ne distingue jamais "impossible" de
            // "malchanceux". Retourne un tableau d'équipes (tableau de tableaux de noms), ou null.
            construireEquipes: function(noms, nbEquipes) {
                this._derniereBorneAtteinte = false;
                if (!noms || noms.length === 0) return null;
                if (nbEquipes < 1 || nbEquipes > noms.length) return null;

                var maxParEquipe = Math.ceil(noms.length / nbEquipes);
                var minParEquipe = Math.floor(noms.length / nbEquipes);
                var equipes = [];
                for (var e = 0; e < nbEquipes; e++) equipes.push([]);

                // Ordre d'essai des équipes mélangé : deuxième source d'aléa, en plus du mélange des noms
                var ordreEquipes = [];
                for (var oe = 0; oe < nbEquipes; oe++) ordreEquipes.push(oe);
                for (var oi = ordreEquipes.length - 1; oi > 0; oi--) {
                    var oj = this.secureRandom(oi + 1);
                    var otmp = ordreEquipes[oi]; ordreEquipes[oi] = ordreEquipes[oj]; ordreEquipes[oj] = otmp;
                }

                var self = this;
                var explorations = 0;
                var BORNE_EXPLORATIONS = 200000;
                var borneAtteinte = false;

                function backtrack(idx) {
                    if (borneAtteinte) return false;
                    explorations++;
                    if (explorations > BORNE_EXPLORATIONS) { borneAtteinte = true; return false; }

                    // Élagage : plus assez de personnes pour combler les équipes sous le minimum équitable
                    var placesManquantes = 0;
                    for (var e3 = 0; e3 < equipes.length; e3++) {
                        if (equipes[e3].length < minParEquipe) placesManquantes += (minParEquipe - equipes[e3].length);
                    }
                    if ((noms.length - idx) < placesManquantes) return false;

                    if (idx === noms.length) return true;

                    var nom = noms[idx];
                    for (var k = 0; k < ordreEquipes.length; k++) {
                        var e2 = ordreEquipes[k];
                        if (equipes[e2].length >= maxParEquipe) continue;
                        var conflit = false;
                        for (var m = 0; m < equipes[e2].length; m++) {
                            if (self._sontExclus(nom, equipes[e2][m])) { conflit = true; break; }
                        }
                        if (conflit) continue;
                        equipes[e2].push(nom);
                        if (backtrack(idx + 1)) return true;
                        equipes[e2].pop();
                    }
                    return false;
                }

                var succes = backtrack(0);
                this._derniereBorneAtteinte = borneAtteinte;
                if (!succes) return null;
                return equipes.map(function(eq) { return eq.slice(); });
            },

            // Premier nombre d'équipes (à partir de 2) qui satisfait les exclusions actuelles.
            minimumEquipesRequis: function() {
                var noms = this.nameList;
                if (noms.length < 2) return null;
                for (var k = 2; k <= noms.length; k++) {
                    if (this.construireEquipes(noms.slice(), k) !== null) return k;
                }
                return null;
            },

            _sontExclus: function(a, b) {
                for (var i = 0; i < this.exclusions.length; i++) {
                    var ex = this.exclusions[i];
                    if ((ex.name1 === a && ex.name2 === b) || (ex.name1 === b && ex.name2 === a)) return true;
                }
                return false;
            },

            // Recherche gloutonne d'un groupe de personnes toutes mutuellement exclues (une clique du
            // graphe de conflit). Sert uniquement à NOMMER les personnes en cause dans le message
            // d'impossibilité - la faisabilité elle-même reste toujours tranchée par construireEquipes.
            _trouverGroupeConflit: function(noms) {
                var grafo = {};
                noms.forEach(function(n) { grafo[n] = []; });
                this.exclusions.forEach(function(e) {
                    if (grafo[e.name1] !== undefined && grafo[e.name2] !== undefined) {
                        grafo[e.name1].push(e.name2);
                        grafo[e.name2].push(e.name1);
                    }
                });
                var meilleur = [];
                noms.forEach(function(depart) {
                    var groupe = [depart];
                    var candidats = grafo[depart].slice();
                    while (candidats.length > 0) {
                        var choisi = candidats[0];
                        groupe.push(choisi);
                        candidats = candidats.filter(function(c) {
                            return c !== choisi && grafo[choisi].indexOf(c) !== -1;
                        });
                    }
                    if (groupe.length > meilleur.length) meilleur = groupe;
                });
                return meilleur;
            },

            _listeFr: function(noms) {
                if (noms.length === 0) return '';
                if (noms.length === 1) return noms[0];
                return noms.slice(0, -1).join(', ') + ' {{ __('et') }} ' + noms[noms.length - 1];
            },

            _afficherImpossibilite: function(tcDemande) {
                var minReq = this.minimumEquipesRequis();
                var texte, suggestion;
                if (minReq !== null && minReq > tcDemande) {
                    var groupe = this._trouverGroupeConflit(this.nameList);
                    if (groupe.length >= minReq) {
                        texte = this._listeFr(groupe.slice(0, minReq)) + ' {{ __('doivent tous être séparés') }} : {{ __('il faut au moins') }} ' + minReq + ' {{ __('équipes.') }}';
                    } else {
                        texte = '{{ __('Les exclusions actuelles exigent au moins') }} ' + minReq + ' {{ __("équipes, ce qui dépasse le nombre demandé.") }}';
                    }
                    suggestion = minReq;
                } else {
                    texte = '{{ __("Cette configuration est trop complexe à résoudre avec les contraintes actuelles. Réduisez le nombre d'exclusions ou augmentez le nombre d'équipes.") }}';
                    suggestion = null;
                }
                this.impossibleMessage = { texte: texte, suggestion: suggestion };
            },

            appliquerSuggestionEquipes: function() {
                if (!this.impossibleMessage || !this.impossibleMessage.suggestion) return;
                var n = this.impossibleMessage.suggestion;
                this.mode = 'count';
                this.teamCount = n;
                this.impossibleMessage = null;
                this.generate();
            },

            generate: function() {
                if (this.nameList.length < 2) return;

                var noms = this.nameList.slice();
                // Fisher-Yates avec crypto : source d'aléa principale
                for (var i = noms.length - 1; i > 0; i--) {
                    var j = this.secureRandom(i + 1);
                    var tmp = noms[i]; noms[i] = noms[j]; noms[j] = tmp;
                }

                var tc = this.actualTeamCount;
                var resultat = this.construireEquipes(noms, tc);

                if (resultat === null) {
                    this._afficherImpossibilite(tc);
                    // le tirage précédent, s'il existe, reste affiché tel quel, mais il ne respecte
                    // plus les exclusions actuelles : on le marque pour ne jamais le faire passer
                    // pour le résultat de cette demande.
                    if (this.drawn) this.tirageObsolete = true;
                    return;
                }

                this.impossibleMessage = null;
                this.tirageObsolete = false;
                this.previousTeams = this.drawn ? JSON.parse(JSON.stringify(this.teams)) : null;
                this.teams = resultat.map(function(membres, t) {
                    return { name: 'Équipe ' + (t + 1), members: membres, color: this.colors[t % this.colors.length] };
                }, this);
                this.drawn = true;
                localStorage.setItem('tg_names', this.names);
            },

            undo: function() {
                if (this.previousTeams) {
                    this.teams = JSON.parse(JSON.stringify(this.previousTeams));
                    this.previousTeams = null;
                }
            },

            reset: function() {
                this.teams = [];
                this.drawn = false;
                this.previousTeams = null;
                this.impossibleMessage = null;
                this.tirageObsolete = false;
            },

            copyResults: function() {
                var self = this;
                var text = this.teams.map(function(team) {
                    return team.name + ':\n  - ' + team.members.join('\n  - ');
                }).join('\n\n');
                navigator.clipboard.writeText(text);
                window.toast('{{ __("Équipes copiées") }}', 'success', 2000);
                this.copied = true;
                setTimeout(function() { self.copied = false; }, 2000);
            },

            addFromClipboard: function() {
                var self = this;
                navigator.clipboard.readText().then(function(text) {
                    if (self.names.length > 0 && !self.names.endsWith('\n')) self.names += '\n';
                    self.names += text;
                });
            },

            // Drag & drop
            dragStart: function(event, memberName, fromTeamIndex) {
                event.dataTransfer.setData('text/plain', JSON.stringify({ member: memberName, from: fromTeamIndex }));
            },

            dropOnTeam: function(event, toTeamIndex) {
                event.preventDefault();
                var data = JSON.parse(event.dataTransfer.getData('text/plain'));
                var fromIdx = data.from;
                if (fromIdx === toTeamIndex) return;
                var fromTeam = this.teams[fromIdx];
                var memberIdx = fromTeam.members.indexOf(data.member);
                if (memberIdx !== -1) {
                    fromTeam.members.splice(memberIdx, 1);
                    this.teams[toTeamIndex].members.push(data.member);
                }
            },

            // Exclusions par pastilles : 1er tap = pivot, 2e tap sur un autre nom = crée l'exclusion
            // (désactive le pivot), re-tap sur le pivot lui-même = annule la sélection.
            togglePivot: function(nom) {
                if (this.pivot === null) {
                    this.pivot = nom;
                    return;
                }
                if (this.pivot === nom) {
                    this.pivot = null;
                    return;
                }
                if (!this._sontExclus(this.pivot, nom)) {
                    this.exclusions.push({ name1: this.pivot, name2: nom });
                }
                this.pivot = null;
            },
            removeExclusion: function(index) { this.exclusions.splice(index, 1); },

            // Sauvegarde compte (API)
            saveToAccount: function() {
                if (this.saving || this.nameList.length < 2) return;
                var self = this;
                var title = this.saveName.trim() || 'Configuration équipes';
                this.saving = true;
                this.saveError = '';
                var isEdit = !!this._editingId;
                var url = isEdit ? '/api/team-presets/' + this._editingId : '/api/team-presets';
                var method = isEdit ? 'PUT' : 'POST';
                fetch(url, {
                    method: method, headers: this._headers(),
                    body: JSON.stringify({ name: title, config_text: this.names, params: { mode: this.mode, teamCount: this.teamCount, teamSize: this.teamSize, exclusions: this.exclusions } })
                })
                .then(function(r) { if (!r.ok) throw new Error('Erreur ' + r.status); return r.json(); })
                .then(function(data) {
                    if (isEdit) {
                        var idx = self.presets.findIndex(function(p) { return p.id === self._editingId; });
                        if (idx >= 0) self.presets[idx] = { id: data.public_id, name: data.name, names: data.config_text, mode: (data.params||{}).mode, teamCount: (data.params||{}).teamCount, teamSize: (data.params||{}).teamSize, exclusions: (data.params||{}).exclusions || [] };
                        self._editingId = null;
                    } else {
                        self.presets.unshift({ id: data.public_id, name: data.name, names: data.config_text, mode: (data.params||{}).mode, teamCount: (data.params||{}).teamCount, teamSize: (data.params||{}).teamSize, exclusions: (data.params||{}).exclusions || [] });
                    }
                    self.saveName = '';
                    self.saving = false;
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: '{{ __("Configuration sauvegardée") }}' } }));
                })
                .catch(function(e) { self.saveError = e.message; self.saving = false; setTimeout(function() { self.saveError = ''; }, 4000); });
            },

            // Presets (localStorage pour visiteurs, API pour connectés)
            savePreset: function() {
                if (!this.presetName.trim()) return;
                if (this.isAuthenticated) {
                    this.saveName = this.presetName;
                    this.saveToAccount();
                    this.presetName = '';
                    return;
                }
                this.presets.push({
                    name: this.presetName.trim(),
                    names: this.names,
                    mode: this.mode,
                    teamCount: this.teamCount,
                    teamSize: this.teamSize,
                    exclusions: JSON.parse(JSON.stringify(this.exclusions))
                });
                localStorage.setItem('tg_presets', JSON.stringify(this.presets));
                this.presetName = '';
            },
            loadPreset: function(index) {
                var p = this.presets[index];
                this.names = p.names;
                this.mode = p.mode || 'count';
                this.teamCount = p.teamCount || 2;
                this.teamSize = p.teamSize || 3;
                this.exclusions = p.exclusions || [];
                this.tab = 'setup';
            },
            deletePreset: function(index) {
                var self = this;
                var preset = this.presets[index];
                if (this.isAuthenticated && preset.id) {
                    window.dispatchEvent(new CustomEvent('open-confirm-global', { detail: { message: 'Supprimer cette configuration ?', callback: function() {
                        fetch('/api/team-presets/' + preset.id, { method: 'DELETE', headers: self._headers() })
                            .then(function() { self.presets.splice(index, 1); });
                    } } }));
                } else {
                    window.dispatchEvent(new CustomEvent('open-confirm-global', { detail: { message: 'Supprimer cette configuration locale ?', callback: function() {
                        self.presets.splice(index, 1);
                        localStorage.setItem('tg_presets', JSON.stringify(self.presets));
                    } } }));
                }
            }
        };
    });
});
</script>
@endpush
