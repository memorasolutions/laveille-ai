<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@php $shareData = $tool->getShareData(); @endphp
@section('meta_description', $shareData['meta_description'])
@section('og_type', $shareData['og_type'])
@section('og_image', $shareData['og_image'])
@section('share_text', $shareData['share_text'])
@section('title', $tool->name . ' - ' . config('app.name'))
@section('meta_description', 'Générateur de mots de passe sécurisé et phrases de passe. Contrôle granulaire, entropie, crypto sécurisé, passphrase diceware. Gratuit.')
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $tool->name, 'breadcrumbItems' => [__('Outils'), $tool->name]])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-12">
                <div class="card shadow-sm tool-fullscreen-target" style="border-radius: var(--r-base);">
                    <div class="card-body p-4 p-md-5" x-data="pwdGen()" x-init="generate(); generatePassphrase();">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h1 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); margin: 0;">{{ $tool->name }}</h1>
                            <div class="d-flex gap-1">
                                @include('tools::partials.fullscreen-btn')
                                @include('tools::partials.share-btn', ['tool' => $tool])
                            </div>
                        </div>
                        <p class="text-muted mb-3">{{ __('Génération sécurisée locale. Aucune donnée n\'est envoyée à un serveur.') }}</p>

                        @include('fronttheme::partials.tabs', ['tabs' => [
                            ['id' => 'password', 'label' => '🔐 ' . __('Mot de passe')],
                            ['id' => 'passphrase', 'label' => '📝 ' . __('Phrase de passe')],
                        ], 'model' => 'tab'])

                        {{-- ==================== ONGLET 1 : MOT DE PASSE ==================== --}}
                        <div x-show="tab === 'password'" x-transition>

                            {{-- Mot de passe généré --}}
                            <div class="p-3 rounded mb-3" style="background: var(--c-primary-light);">
                                <input type="text" class="form-control form-control-lg text-center mb-2" x-model="password" aria-label="Mot de passe généré" readonly
                                       style="font-family: 'Courier New', monospace; font-size: 1.3rem; letter-spacing: 2px; background: #fff; border: 2px solid var(--c-primary);">
                                <div style="background: #e9ecef; border-radius: 6px; height: 8px; overflow: hidden; margin-bottom: 6px;">
                                    <div :style="'width:' + Math.min(100, entropy / 1.28) + '%; background:' + strengthColor + '; height: 100%; transition: all 0.3s;'"></div>
                                </div>
                                <div class="d-flex justify-content-between" style="font-size: 0.85rem;">
                                    <span :style="'color:' + strengthColor + '; font-weight: 700;'" x-text="strengthLabel"></span>
                                    <span class="text-muted" x-text="Math.round(entropy) + ' bits'"></span>
                                </div>
                            </div>

                            {{-- Boutons --}}
                            <div class="d-flex gap-2 mb-4">
                                <button class="ct-btn ct-btn-accent flex-fill" @click="generate(); addToHistory();">
                                    🎲 {{ __('Générer') }}
                                </button>
                                <button class="btn" @click="copy(password)" style="background: var(--c-primary); color: #fff; border-radius: var(--r-btn);"
                                        x-text="copied ? '✅ Copié !' : '📋 Copier'"></button>
                            </div>

                            {{-- Longueur --}}
                            <div class="mb-3">
                                <label class="form-label fw-medium">{{ __('Longueur') }} : <strong x-text="length"></strong></label>
                                <input type="range" class="form-range" x-model.number="length" aria-label="Longueur du mot de passe" min="6" max="64" @input="generate()">
                            </div>

                            {{-- Types de caractères --}}
                            <label class="form-label fw-medium mb-2">{{ __('Caractères à inclure') }}</label>
                            <template x-for="t in types" :key="t.key">
                                <div class="p-2 mb-2 rounded d-flex align-items-center justify-content-between" style="background: #f8f9fa;">
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="checkbox" x-model="t.enabled" @change="generate()" style="display:inline-block !important; width:18px; height:18px; cursor:pointer; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                        <span x-text="t.label + ' (' + t.sample + ')'" style="font-size: 0.9rem;"></span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2" x-show="t.enabled">
                                        <small class="text-muted">{{ __('Qté') }} :</small>
                                        <input type="number" x-model.number="t.qty" min="0" max="50" @input="generate()"
                                               class="form-control form-control-sm" style="width: 55px; text-align: center;">
                                    </div>
                                </div>
                            </template>

                            {{-- Info restant --}}
                            <div class="text-center mt-2 mb-3" style="font-size: 0.85rem;">
                                <span class="text-muted" x-show="remaining > 0" x-text="remaining + ' caractères aléatoires ajoutés'"></span>
                                <span style="color: #059669;" x-show="remaining === 0">✅ {{ __('Configuration exacte') }}</span>
                                <span style="color: #DC2626;" x-show="remaining < 0" x-text="'⚠️ Total dépasse la longueur de ' + Math.abs(remaining)"></span>
                            </div>

                            {{-- Options avancées --}}
                            <div class="mb-3">
                                <button class="ct-btn ct-btn-outline ct-btn-sm ct-btn-full" @click="showAdvanced = !showAdvanced">
                                    <span x-text="showAdvanced ? '▲ Masquer' : '▼ Options avancées'"></span>
                                </button>
                            </div>

                            <div x-show="showAdvanced" x-transition class="p-3 rounded mb-3" style="background: #f8f9fa;">
                                <div class="mb-2 d-flex align-items-center gap-2">
                                    <input type="checkbox" x-model="excludeSimilar" @change="generate()" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                    <span style="font-size: 0.9rem;">{{ __('Exclure caractères similaires') }} <code>0 O o 1 l I |</code></span>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label" style="font-size: 0.85rem;">{{ __('Exclure caractères personnalisés') }}</label>
                                    <input type="text" class="form-control form-control-sm" x-model="excludeChars" aria-label="Caractères à exclure" @input="generate()" placeholder="{{ __('Ex: {}[]~') }}">
                                </div>
                                <div>
                                    <label class="form-label" style="font-size: 0.85rem;">{{ __('Génération en lot') }} : <strong x-text="batchCount"></strong></label>
                                    <input type="range" class="form-range" x-model.number="batchCount" aria-label="Nombre de mots de passe" min="1" max="10" @input="generate()">
                                </div>
                            </div>

                            {{-- Lot de mots de passe --}}
                            <template x-if="batchPasswords.length > 1">
                                <div class="mb-3">
                                    <label class="form-label fw-medium mb-2">{{ __('Mots de passe générés') }} (<span x-text="batchPasswords.length"></span>)</label>
                                    <template x-for="(bp, i) in batchPasswords" :key="i">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <code class="flex-fill p-1 rounded" style="background: #fff; font-size: 0.85rem; word-break: break-all;" x-text="bp"></code>
                                            <button class="ct-btn ct-btn-primary ct-btn-xs" @click="copy(bp)">📋</button>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            {{-- Historique --}}
                            <template x-if="history.length > 0">
                                <div class="mt-3 pt-3 border-top">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 style="font-family: var(--f-heading); font-weight: 700; margin: 0;">{{ __('Historique') }}</h6>
                                        <button class="ct-btn ct-btn-outline-danger ct-btn-sm" @click="clearHistory()" style="font-size: 0.75rem;">{{ __('Effacer') }}</button>
                                    </div>
                                    <template x-for="(h, i) in history" :key="i">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <code class="flex-fill" style="font-size: 0.8rem; color: var(--c-text-muted, #52586a);" x-text="h"></code>
                                            <button class="ct-btn ct-btn-outline ct-btn-sm" @click="copy(h)" style="font-size: 0.7rem;">📋</button>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        {{-- ==================== ONGLET 2 : PHRASE DE PASSE ==================== --}}
                        <div x-show="tab === 'passphrase'" x-transition>

                            {{-- Passphrase générée --}}
                            <div class="p-3 rounded mb-3" style="background: var(--c-primary-light);">
                                <input type="text" class="form-control form-control-lg text-center mb-2" x-model="passphrase" aria-label="Phrase de passe générée" readonly
                                       style="font-family: 'Courier New', monospace; font-size: 1.2rem; letter-spacing: 1px; background: #fff; border: 2px solid var(--c-primary);">
                                <div style="background: #e9ecef; border-radius: 6px; height: 8px; overflow: hidden; margin-bottom: 6px;">
                                    <div :style="'width:' + Math.min(100, passphraseEntropy / 1.28) + '%; background:' + (passphraseEntropy < 40 ? '#DC2626' : passphraseEntropy < 60 ? '#D97706' : '#059669') + '; height: 100%; transition: all 0.3s;'"></div>
                                </div>
                                <div class="d-flex justify-content-between" style="font-size: 0.85rem;">
                                    <span :style="'color:' + (passphraseEntropy < 40 ? '#DC2626' : passphraseEntropy < 60 ? '#D97706' : '#059669') + '; font-weight: 700;'" x-text="passphraseEntropy < 40 ? 'Faible' : passphraseEntropy < 60 ? 'Bon' : 'Excellent'"></span>
                                    <span class="text-muted" x-text="Math.round(passphraseEntropy) + ' bits'"></span>
                                </div>
                            </div>

                            {{-- Boutons --}}
                            <div class="d-flex gap-2 mb-4">
                                <button class="ct-btn ct-btn-accent flex-fill" @click="generatePassphrase()">
                                    🎲 {{ __('Générer') }}
                                </button>
                                <button class="btn" @click="copyPassphrase()" style="background: var(--c-primary); color: #fff; border-radius: var(--r-btn);"
                                        x-text="passphraseCopied ? '✅ Copié !' : '📋 Copier'"></button>
                            </div>

                            {{-- Nombre de mots --}}
                            <div class="mb-3">
                                <label class="form-label fw-medium">{{ __('Nombre de mots') }} : <strong x-text="wordCount"></strong></label>
                                <input type="range" class="form-range" x-model.number="wordCount" aria-label="Nombre de mots" min="3" max="8" @input="generatePassphrase()">
                            </div>

                            {{-- Séparateur --}}
                            <div class="mb-3">
                                <label class="form-label fw-medium">{{ __('Séparateur') }}</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <template x-for="s in separators" :key="s.value">
                                        <button class="ct-btn ct-btn-sm" :class="separator === s.value ? 'ct-btn-primary' : 'ct-btn-outline'"
                                                @click="separator = s.value; generatePassphrase();" x-text="s.label" style="border-radius: var(--r-btn);"></button>
                                    </template>
                                </div>
                            </div>

                            {{-- Options --}}
                            <div class="mb-3 d-flex flex-wrap gap-3">
                                <label class="d-flex align-items-center gap-2" style="cursor: pointer;">
                                    <input type="checkbox" x-model="capitalize" @change="generatePassphrase()" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                    <span style="font-size: 0.9rem;">{{ __('Majuscule initiale') }}</span>
                                </label>
                                <label class="d-flex align-items-center gap-2" style="cursor: pointer;">
                                    <input type="checkbox" x-model="addNumber" @change="generatePassphrase()" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                    <span style="font-size: 0.9rem;">{{ __('Ajouter un nombre') }}</span>
                                </label>
                            </div>

                            <p class="text-muted mb-0" style="font-size: 0.8rem;">
                                {{ __('Les phrases de passe sont plus faciles à mémoriser tout en restant très sécurisées. 4 mots = ~25 bits par mot.') }}
                            </p>
                        </div>

                        <p class="text-muted mt-3 mb-0" style="font-size: 0.75rem;">
                            🔒 {{ __('Généré localement avec crypto.getRandomValues(). Aucune donnée n\'est envoyée.') }}
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
    Alpine.data('pwdGen', function() {
        return {
            tab: 'password',
            length: 16,
            password: '',
            copied: false,
            entropy: 0,
            showAdvanced: false,
            excludeSimilar: false,
            excludeChars: '',
            batchCount: 1,
            batchPasswords: [],
            history: JSON.parse(localStorage.getItem('pwd_history') || '[]'),

            types: [
                { key: 'upper', label: 'Majuscules', sample: 'A-Z', chars: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', enabled: true, qty: 4 },
                { key: 'lower', label: 'Minuscules', sample: 'a-z', chars: 'abcdefghijklmnopqrstuvwxyz', enabled: true, qty: 4 },
                { key: 'nums', label: 'Chiffres', sample: '0-9', chars: '0123456789', enabled: true, qty: 4 },
                { key: 'syms', label: 'Symboles', sample: '!@#$%', chars: '!@#$%^&*()_+-=[]{}|;:,.<>?', enabled: true, qty: 4 }
            ],

            // Passphrase
            wordCount: 4,
            separator: '-',
            capitalize: true,
            addNumber: true,
            passphrase: '',
            passphraseEntropy: 0,
            passphraseCopied: false,
            separators: [
                { value: '-', label: 'Tiret (-)' },
                { value: '.', label: 'Point (.)' },
                { value: '_', label: 'Souligné (_)' },
                { value: ' ', label: 'Espace' },
                { value: 'none', label: 'Aucun' }
            ],
            wordList: [
                'soleil','maison','jardin','nuage','piano','orange','montagne','riviere',
                'bateau','fenetre','musique','voyage','etoile','cheval','cerise','dragon',
                'flacon','girafe','hameau','jungle','koala','loutre','marbre','nature',
                'oiseau','papier','requin','salade','tigre','valise','ancien','brave',
                'calme','douce','facile','grand','habile','juste','large','noble',
                'rapide','simple','timide','utile','vaste','citron','melon','fraise',
                'peche','pomme','raisin','tulipe','lilas','cactus','bambou','plume',
                'sable','vague','corail','granite','argent','bronze','cuivre','platine',
                'jade','opale','rubis','saphir','perle','ambre','ivoire','chrome',
                'cobalt','titane','quartz','prisme','aurore','blazer','flocon','nectar'
            ],

            get remaining() {
                var total = 0;
                for (var i = 0; i < this.types.length; i++) {
                    if (this.types[i].enabled) total += this.types[i].qty;
                }
                return this.length - total;
            },

            get strengthLabel() {
                if (this.entropy < 40) return 'Faible';
                if (this.entropy < 60) return 'Moyen';
                if (this.entropy < 80) return 'Bon';
                if (this.entropy < 100) return 'Très bon';
                return 'Excellent';
            },

            get strengthColor() {
                if (this.entropy < 40) return '#DC2626';
                if (this.entropy < 60) return '#D97706';
                if (this.entropy < 80) return '#2563EB';
                if (this.entropy < 100) return '#059669';
                return '#059669';
            },

            secureRandom: function(max) {
                var arr = new Uint32Array(1);
                crypto.getRandomValues(arr);
                return arr[0] % max;
            },

            filterChars: function(chars) {
                var result = chars;
                if (this.excludeSimilar) {
                    result = result.replace(/[0Ool1lI|`]/g, '');
                }
                if (this.excludeChars) {
                    for (var c = 0; c < this.excludeChars.length; c++) {
                        result = result.split(this.excludeChars[c]).join('');
                    }
                }
                return result;
            },

            generateOne: function() {
                var pool = '';
                var chars = [];
                var self = this;

                for (var i = 0; i < this.types.length; i++) {
                    var t = this.types[i];
                    if (!t.enabled) continue;
                    var filtered = this.filterChars(t.chars);
                    pool += filtered;
                    for (var j = 0; j < t.qty; j++) {
                        if (filtered.length > 0) chars.push(filtered[this.secureRandom(filtered.length)]);
                    }
                }

                if (pool.length === 0) return '';

                var rem = this.length - chars.length;
                for (var k = 0; k < Math.max(0, rem); k++) {
                    chars.push(pool[this.secureRandom(pool.length)]);
                }

                // Fisher-Yates
                for (var m = chars.length - 1; m > 0; m--) {
                    var n = this.secureRandom(m + 1);
                    var tmp = chars[m]; chars[m] = chars[n]; chars[n] = tmp;
                }

                return chars.slice(0, this.length).join('');
            },

            generate: function() {
                this.password = this.generateOne();

                var pool = '';
                for (var i = 0; i < this.types.length; i++) {
                    if (this.types[i].enabled) pool += this.filterChars(this.types[i].chars);
                }
                // Deduplicate pool for accurate entropy
                var unique = '';
                for (var c = 0; c < pool.length; c++) {
                    if (unique.indexOf(pool[c]) === -1) unique += pool[c];
                }
                this.entropy = unique.length > 0 ? Math.log2(Math.pow(unique.length, this.length)) : 0;

                if (this.batchCount > 1) {
                    this.batchPasswords = [];
                    for (var b = 0; b < this.batchCount; b++) {
                        this.batchPasswords.push(this.generateOne());
                    }
                } else {
                    this.batchPasswords = [];
                }
            },

            copy: function(text) {
                var self = this;
                navigator.clipboard.writeText(text);
                this.copied = true;
                setTimeout(function() { self.copied = false; }, 2000);
            },

            addToHistory: function() {
                if (!this.password) return;
                this.history.unshift(this.password);
                if (this.history.length > 10) this.history = this.history.slice(0, 10);
                localStorage.setItem('pwd_history', JSON.stringify(this.history));
            },

            clearHistory: function() {
                this.history = [];
                localStorage.removeItem('pwd_history');
            },

            generatePassphrase: function() {
                var words = [];
                var arr = new Uint32Array(this.wordCount);
                crypto.getRandomValues(arr);
                for (var i = 0; i < this.wordCount; i++) {
                    var word = this.wordList[arr[i] % this.wordList.length];
                    if (this.capitalize) word = word.charAt(0).toUpperCase() + word.slice(1);
                    words.push(word);
                }
                var sep = this.separator === 'none' ? '' : this.separator;
                this.passphrase = words.join(sep);
                if (this.addNumber) {
                    var num = crypto.getRandomValues(new Uint8Array(1))[0] % 100;
                    this.passphrase += (num < 10 ? '0' : '') + num;
                }
                var combos = Math.pow(this.wordList.length, this.wordCount) * (this.addNumber ? 100 : 1);
                this.passphraseEntropy = Math.log2(combos);
            },

            copyPassphrase: function() {
                var self = this;
                navigator.clipboard.writeText(this.passphrase);
                this.passphraseCopied = true;
                setTimeout(function() { self.passphraseCopied = false; }, 2000);
            }
        };
    });
});
</script>
@endpush
