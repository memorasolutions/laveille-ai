<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@section('title', $tool->name . ' - ' . config('app.name'))
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $tool->name, 'breadcrumbItems' => [__('Outils'), $tool->name]])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-12">
                <div class="card shadow-sm tool-fullscreen-target" style="border-radius: var(--r-base);">
                    <div class="card-body p-4 p-md-5" x-data="teamGen()" x-init="init()">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h1 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); margin: 0;">{{ $tool->name }}</h1>
                            <div class="d-flex gap-1">
                                @include('tools::partials.fullscreen-btn')
                            </div>
                        </div>
                        <p class="text-muted mb-3">{{ __('Répartition équitable et aléatoire. Glissez-déposez pour ajuster, excluez des paires, sauvegardez vos configurations.') }}</p>

                        {{-- Barre sauvegarde (connectés) --}}
                        <div x-show="isAuthenticated" x-cloak style="background: rgba(11,114,133,0.04); border: 1px solid rgba(11,114,133,0.12); border-radius: 10px; padding: 12px; margin-bottom: 16px;">
                            <div class="d-flex gap-2 align-items-center">
                                <input type="text" class="form-control form-control-sm flex-fill" x-model="saveName" placeholder="{{ __('Nommer cette configuration...') }}" aria-label="{{ __('Nom de la configuration') }}" style="border-radius: 8px;">
                                <button class="btn btn-sm" @click="saveToAccount()" :disabled="nameList.length < 2 || saving" style="background: var(--c-primary); color: #fff; border-radius: 8px; font-weight: 600; white-space: nowrap; padding: 6px 16px;"
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
                                        <button class="btn btn-sm btn-outline-secondary" @click="addFromClipboard()" style="border-radius: var(--r-btn); font-size: 0.8rem;">📋 {{ __('Coller') }}</button>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">{{ __('Répartir par') }}</label>
                                    <div class="btn-group w-100 mb-3">
                                        <button class="btn btn-sm" :class="mode === 'count' ? 'btn-primary' : 'btn-outline-secondary'" @click="mode = 'count'">{{ __('Nombre d\'équipes') }}</button>
                                        <button class="btn btn-sm" :class="mode === 'size' ? 'btn-primary' : 'btn-outline-secondary'" @click="mode = 'size'">{{ __('Taille d\'équipe') }}</button>
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
                                <button class="btn flex-fill" @click="generate()" :disabled="nameList.length < 2"
                                        style="background: var(--c-accent); color: #fff; border-radius: var(--r-btn); font-family: var(--f-heading); font-weight: 700;">
                                    <span x-text="drawn ? '🔀 Re-mélanger' : '🎲 Générer les équipes'"></span>
                                </button>
                                <button class="btn btn-outline-secondary" @click="undo()" x-show="previousTeams" style="border-radius: var(--r-btn);" title="{{ __('Annuler') }}">↩️</button>
                                <button class="btn btn-outline-secondary" @click="reset()" x-show="drawn" style="border-radius: var(--r-btn);" title="{{ __('Réinitialiser') }}">🗑️</button>
                            </div>
                        </div>

                        {{-- ==================== ONGLET OPTIONS AVANCÉES ==================== --}}
                        <div x-show="tab === 'options'" x-transition>
                            {{-- Exclusions --}}
                            <div class="mb-4">
                                <label class="form-label fw-medium">🚫 {{ __('Exclure des paires') }}</label>
                                <p class="text-muted" style="font-size: 0.8rem;">{{ __('Ces personnes ne seront jamais dans la même équipe.') }}</p>
                                <div class="d-flex gap-2 mb-2">
                                    <input type="text" class="form-control form-control-sm" x-model="newExcl1" aria-label="Personne 1 exclusion" placeholder="{{ __('Personne 1') }}">
                                    <span class="align-self-center">≠</span>
                                    <input type="text" class="form-control form-control-sm" x-model="newExcl2" aria-label="Personne 2 exclusion" placeholder="{{ __('Personne 2') }}">
                                    <button class="btn btn-sm" @click="addExclusion()" style="background: var(--c-primary); color: #fff; border-radius: var(--r-btn); white-space: nowrap;">+ {{ __('Ajouter') }}</button>
                                </div>
                                <template x-for="(excl, i) in exclusions" :key="i">
                                    <div class="d-flex align-items-center gap-2 mb-1 p-2 rounded" style="background: #FEE2E2; font-size: 0.85rem;">
                                        <span x-text="excl.name1"></span> <span>≠</span> <span x-text="excl.name2"></span>
                                        <button class="btn btn-sm btn-outline-danger ms-auto" @click="removeExclusion(i)" style="font-size: 0.7rem;">✕</button>
                                    </div>
                                </template>
                                <p class="text-muted mt-1" x-show="exclusions.length === 0" style="font-size: 0.8rem;">{{ __('Aucune exclusion définie.') }}</p>
                            </div>

                            {{-- Presets (localStorage uniquement pour visiteurs, masqué pour connectés qui utilisent la barre en haut) --}}
                            <div class="mb-4" x-show="!isAuthenticated">
                                <label class="form-label fw-medium">💾 {{ __('Configurations sauvegardées') }}</label>
                                <div class="d-flex gap-2 mb-2">
                                    <input type="text" class="form-control form-control-sm" x-model="presetName" aria-label="Nom de la configuration" placeholder="{{ __('Nom de la configuration...') }}">
                                    <button class="btn btn-sm" @click="savePreset()" style="background: var(--c-primary); color: #fff; border-radius: var(--r-btn); white-space: nowrap;">💾 {{ __('Sauvegarder') }}</button>
                                </div>
                                <template x-for="(p, i) in presets" :key="i">
                                    <div class="d-flex align-items-center gap-2 mb-1 p-2 rounded" style="background: #f8f9fa; font-size: 0.85rem;">
                                        <span class="flex-fill" x-text="p.name"></span>
                                        <button class="btn btn-sm btn-outline-primary" @click="loadPreset(i)" style="font-size: 0.7rem;">{{ __('Charger') }}</button>
                                        <button class="btn btn-sm btn-outline-danger" @click="deletePreset(i)" style="font-size: 0.7rem;">✕</button>
                                    </div>
                                </template>
                                <p class="text-muted mt-1" x-show="presets.length === 0" style="font-size: 0.8rem;">{{ __('Aucune configuration sauvegardée.') }}</p>
                            </div>
                        </div>

                        {{-- ==================== RÉSULTATS ==================== --}}
                        <template x-if="drawn">
                            <div>
                                <div class="row">
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
                                <button class="btn w-100 mt-2" @click="copyResults()" style="background: var(--c-primary); color: #fff; border-radius: var(--r-btn);"
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
            newExcl1: '',
            newExcl2: '',
            presets: [],
            presetName: '',

            // Sauvegarde compte
            isAuthenticated: {{ auth()->check() ? 'true' : 'false' }},
            saveName: '',
            saving: false,
            saveError: '',
            _editingId: null,

            init: function() {
                var self = this;
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

            secureRandom: function(max) {
                var arr = new Uint32Array(1);
                crypto.getRandomValues(arr);
                return arr[0] % max;
            },

            generate: function() {
                if (this.nameList.length < 2) return;
                this.previousTeams = this.drawn ? JSON.parse(JSON.stringify(this.teams)) : null;

                var list = this.nameList.slice();
                // Fisher-Yates avec crypto
                for (var i = list.length - 1; i > 0; i--) {
                    var j = this.secureRandom(i + 1);
                    var tmp = list[i]; list[i] = list[j]; list[j] = tmp;
                }

                var tc = this.actualTeamCount;
                this.teams = [];
                for (var t = 0; t < tc; t++) {
                    this.teams.push({
                        name: 'Équipe ' + (t + 1),
                        members: [],
                        color: this.colors[t % this.colors.length]
                    });
                }

                for (var n = 0; n < list.length; n++) {
                    this.teams[n % tc].members.push(list[n]);
                }

                // Résoudre exclusions
                if (this.exclusions.length > 0) {
                    var attempts = 0;
                    var maxAttempts = 100;
                    var resolved = false;
                    while (!resolved && attempts < maxAttempts) {
                        resolved = true;
                        for (var e = 0; e < this.exclusions.length; e++) {
                            var excl = this.exclusions[e];
                            for (var ti = 0; ti < this.teams.length; ti++) {
                                var team = this.teams[ti];
                                if (team.members.indexOf(excl.name1) !== -1 && team.members.indexOf(excl.name2) !== -1) {
                                    resolved = false;
                                    // Swap name1 avec un membre d'une autre équipe
                                    var otherIdx = (ti + 1) % this.teams.length;
                                    var otherTeam = this.teams[otherIdx];
                                    if (otherTeam.members.length > 0) {
                                        var swapIdx = this.secureRandom(otherTeam.members.length);
                                        var swapName = otherTeam.members[swapIdx];
                                        // Swap
                                        var idx1 = team.members.indexOf(excl.name1);
                                        team.members[idx1] = swapName;
                                        otherTeam.members[swapIdx] = excl.name1;
                                    }
                                }
                            }
                        }
                        attempts++;
                    }
                }

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
            },

            copyResults: function() {
                var self = this;
                var text = this.teams.map(function(team) {
                    return team.name + ':\n  - ' + team.members.join('\n  - ');
                }).join('\n\n');
                navigator.clipboard.writeText(text);
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

            // Exclusions
            addExclusion: function() {
                if (this.newExcl1.trim() && this.newExcl2.trim()) {
                    this.exclusions.push({ name1: this.newExcl1.trim(), name2: this.newExcl2.trim() });
                    this.newExcl1 = '';
                    this.newExcl2 = '';
                }
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
                    if (!confirm('Supprimer cette configuration?')) return;
                    fetch('/api/team-presets/' + preset.id, { method: 'DELETE', headers: this._headers() })
                        .then(function() { self.presets.splice(index, 1); });
                } else {
                    this.presets.splice(index, 1);
                    localStorage.setItem('tg_presets', JSON.stringify(this.presets));
                }
            }
        };
    });
});
</script>
@endpush
