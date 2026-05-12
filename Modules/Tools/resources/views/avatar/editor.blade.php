@extends('fronttheme::layouts.app')

@section('title', 'Avatar Studio (Beta admin) — La veille')
@section('meta_description', 'Créateur d\'avatar cartoon personnalisé avec compatibilité WCAG AAA et touches du Québec.')

@push('robots')
<meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<style>
:root {
    --c-primary: #064E5A;
    --c-accent: #9A2A06;
    --c-bg: #F0F4F8;
    --c-surface: #ffffff;
    --c-dark: #1a1d23;
    --c-text-muted: #52586a;
}
[x-cloak] { display: none !important; }
.ava-editor { padding: 1.5rem 1rem 6rem; max-width: 1180px; margin: 0 auto; }
.ava-editor *, .ava-editor *::before, .ava-editor *::after { box-sizing: border-box; }
.ava-editor__header { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
.ava-editor__title { font-size: 1.875rem; color: var(--c-primary); margin: 0 0 .25rem; }
.ava-editor__subtitle { color: var(--c-text-muted); font-size: 1.0625rem; margin: 0; }
.ava-editor__beta { background: var(--c-accent); color: #fff; padding: .35rem .85rem; border-radius: 999px; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; }
.ava-editor__layout { display: grid; grid-template-columns: minmax(0,1fr); gap: 1.5rem; }
@media (min-width: 900px) { .ava-editor__layout { grid-template-columns: 420px 1fr; align-items: start; } }
.ava-editor__preview { background: var(--c-surface); border-radius: 16px; padding: 1rem; box-shadow: 0 8px 28px rgba(6,78,90,.08); position: sticky; top: 1rem; }
.ava-editor__preview-canvas { width: 100%; aspect-ratio: 1; background: var(--c-bg); border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.ava-editor__preview-canvas > div, .ava-editor__preview-canvas svg { max-width: 100%; height: auto; }
.ava-editor__controls { background: var(--c-surface); border-radius: 16px; padding: 1rem 1rem 1.5rem; box-shadow: 0 8px 28px rgba(6,78,90,.06); }
.ava-tabs { display: flex; gap: .35rem; overflow-x: auto; padding: .25rem .25rem .75rem; margin-bottom: 1rem; border-bottom: 1px solid #e5e7eb; scrollbar-width: thin; }
.ava-tab { flex: 0 0 auto; display: inline-flex; align-items: center; gap: .35rem; padding: .55rem .85rem; background: var(--c-bg); color: var(--c-dark); border: 2px solid transparent; border-radius: 999px; font-weight: 600; font-size: .875rem; cursor: pointer; white-space: nowrap; transition: all .15s ease; }
.ava-tab:hover { background: #dde6ef; }
.ava-tab[aria-selected="true"] { background: var(--c-primary); color: #fff; }
.ava-tab:focus-visible { outline: 3px solid var(--c-primary); outline-offset: 2px; }
.ava-section h2 { font-size: .8125rem; text-transform: uppercase; letter-spacing: 1.5px; color: var(--c-primary); margin: 1rem 0 .65rem; font-weight: 700; }
.ava-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; margin-bottom: 1rem; }
@media (min-width: 600px) { .ava-grid { grid-template-columns: repeat(5, 1fr); } }
.ava-cell { aspect-ratio: 1; background: var(--c-bg); border: 2px solid transparent; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: .75rem; color: var(--c-dark); text-align: center; padding: .25rem; transition: all .15s ease; overflow: hidden; }
.ava-cell:hover { background: #dde6ef; transform: translateY(-1px); }
.ava-cell[aria-pressed="true"] { border-color: var(--c-primary); background: rgba(6,78,90,.08); box-shadow: 0 2px 8px rgba(6,78,90,.15); }
.ava-cell:focus-visible { outline: 3px solid var(--c-primary); outline-offset: 2px; }
.ava-cell--swatch { padding: 0; }
.ava-swatch { width: 100%; height: 100%; border-radius: 8px; display: block; }
.ava-cell--swatch[aria-pressed="true"] { padding: 3px; }
.ava-cell--quebec svg { width: 60%; height: 60%; }
.ava-adv-group { background: var(--c-bg); border-radius: 12px; padding: 1rem; margin-bottom: 1rem; }
.ava-adv-label { display: flex; justify-content: space-between; align-items: center; font-size: .8125rem; font-weight: 600; color: var(--c-dark); margin-bottom: .5rem; }
.ava-adv-value { font-variant-numeric: tabular-nums; color: var(--c-primary); font-weight: 700; }
.ava-slider { width: 100%; height: 6px; accent-color: var(--c-primary); }
.ava-slider:focus-visible { outline: 3px solid var(--c-primary); outline-offset: 4px; }
.ava-select { width: 100%; padding: .65rem .85rem; border: 1px solid #d1d5db; border-radius: 10px; background: #fff; font-size: 1rem; }
.ava-toggle { display: inline-flex; align-items: center; gap: .65rem; cursor: pointer; user-select: none; }
.ava-toggle input { width: 1.25rem; height: 1.25rem; accent-color: var(--c-primary); }
.ava-color { width: 100%; height: 44px; border: 1px solid #d1d5db; border-radius: 10px; padding: 4px; cursor: pointer; }
.ava-actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid #e5e7eb; }
.ava-btn { display: inline-flex; align-items: center; gap: .4rem; padding: .65rem 1.1rem; background: var(--c-bg); color: var(--c-dark); border: 2px solid transparent; border-radius: 10px; font-weight: 600; font-size: .9rem; cursor: pointer; transition: all .15s ease; min-height: 44px; }
.ava-btn:hover { background: #dde6ef; }
.ava-btn:focus-visible { outline: 3px solid var(--c-primary); outline-offset: 2px; }
.ava-btn:disabled { opacity: .4; cursor: not-allowed; }
.ava-btn--primary { background: var(--c-primary); color: #fff; }
.ava-btn--primary:hover { background: #053640; }
.ava-btn--accent { background: var(--c-accent); color: #fff; }
.ava-btn--accent:hover { background: #7c2105; }
.ava-toast { position: fixed; bottom: 1rem; right: 1rem; background: var(--c-primary); color: #fff; padding: .75rem 1.25rem; border-radius: 10px; box-shadow: 0 8px 28px rgba(0,0,0,.2); z-index: 1000; }
.sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
@media (prefers-reduced-motion: reduce) {
    .ava-editor *, .ava-editor *::before, .ava-editor *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; }
}
@media (max-width: 899px) {
    .ava-editor__preview { position: static; }
}
</style>

<div class="ava-editor" x-data="avatarStudio()" x-cloak>
    <header class="ava-editor__header">
        <div>
            <h1 class="ava-editor__title">🎨 Avatar Studio</h1>
            <p class="ava-editor__subtitle">Crée ton compagnon de quête IA</p>
        </div>
        <span class="ava-editor__beta">🚧 Beta admin</span>
    </header>

    <div class="ava-editor__layout">
        <aside class="ava-editor__preview" aria-label="Aperçu de l'avatar">
            <div class="ava-editor__preview-canvas" :style="`background: ${config.backgroundColor}`">
                <div x-html="svgAvatar" aria-live="polite"></div>
            </div>
        </aside>

        <main class="ava-editor__controls">
            <div class="ava-tabs" role="tablist" x-ref="tablist" aria-label="Catégories de personnalisation">
                <template x-for="tab in tabs" :key="tab.id">
                    <button type="button" class="ava-tab"
                        :aria-selected="currentTab === tab.id"
                        :id="'tab-' + tab.id"
                        :aria-controls="'panel-' + tab.id"
                        role="tab"
                        :tabindex="currentTab === tab.id ? 0 : -1"
                        @click="currentTab = tab.id"
                        @keydown.right.prevent="focusNextTab"
                        @keydown.left.prevent="focusPrevTab"
                        @keydown.home.prevent="currentTab = tabs[0].id"
                        @keydown.end.prevent="currentTab = tabs[tabs.length - 1].id">
                        <span aria-hidden="true" x-text="tab.icon"></span>
                        <span x-text="tab.label"></span>
                    </button>
                </template>
            </div>

            <section x-show="currentTab === 'colors'" id="panel-colors" role="tabpanel" aria-labelledby="tab-colors" class="ava-section">
                <h2>Couleur de peau</h2>
                <div class="ava-grid" role="radiogroup" aria-label="Couleur de peau">
                    <template x-for="color in skinColors" :key="color">
                        <button type="button" class="ava-cell ava-cell--swatch"
                            :aria-pressed="config.skinColor === color"
                            :aria-label="'Couleur de peau ' + color"
                            @click="setAndCommit('skinColor', color)">
                            <span class="ava-swatch" :style="`background:${color}`"></span>
                        </button>
                    </template>
                </div>
                <h2>Couleur des cheveux</h2>
                <div class="ava-grid" role="radiogroup" aria-label="Couleur de cheveux">
                    <template x-for="color in hairColors" :key="color">
                        <button type="button" class="ava-cell ava-cell--swatch"
                            :aria-pressed="config.hairColor === color"
                            :aria-label="'Cheveux ' + color"
                            @click="setAndCommit('hairColor', color)">
                            <span class="ava-swatch" :style="`background:${color}`"></span>
                        </button>
                    </template>
                </div>
            </section>

            <section x-show="currentTab === 'face'" id="panel-face" role="tabpanel" aria-labelledby="tab-face" class="ava-section">
                <h2>Style des yeux</h2>
                <div class="ava-grid">
                    <template x-for="opt in eyeStyles" :key="opt">
                        <button type="button" class="ava-cell"
                            :aria-pressed="config.eyes === opt"
                            :aria-label="'Yeux ' + opt"
                            @click="setAndCommit('eyes', opt)" x-text="opt"></button>
                    </template>
                </div>
                <h2>Sourcils</h2>
                <div class="ava-grid">
                    <template x-for="opt in eyebrowStyles" :key="opt">
                        <button type="button" class="ava-cell"
                            :aria-pressed="config.eyebrows === opt"
                            :aria-label="'Sourcils ' + opt"
                            @click="setAndCommit('eyebrows', opt)" x-text="opt"></button>
                    </template>
                </div>
                <h2>Bouche</h2>
                <div class="ava-grid">
                    <template x-for="opt in mouthStyles" :key="opt">
                        <button type="button" class="ava-cell"
                            :aria-pressed="config.mouth === opt"
                            :aria-label="'Bouche ' + opt"
                            @click="setAndCommit('mouth', opt)" x-text="opt"></button>
                    </template>
                </div>
            </section>

            <section x-show="currentTab === 'hair'" id="panel-hair" role="tabpanel" aria-labelledby="tab-hair" class="ava-section">
                <h2>Style de cheveux</h2>
                <div class="ava-grid">
                    <template x-for="opt in hairStyles" :key="opt">
                        <button type="button" class="ava-cell"
                            :aria-pressed="config.hair === opt"
                            :aria-label="'Cheveux ' + opt"
                            @click="setAndCommit('hair', opt)" x-text="opt"></button>
                    </template>
                </div>
            </section>

            <section x-show="currentTab === 'accessories'" id="panel-accessories" role="tabpanel" aria-labelledby="tab-accessories" class="ava-section">
                <h2>Accessoires faciaux</h2>
                <div class="ava-grid">
                    <template x-for="opt in faceAccessories" :key="opt">
                        <button type="button" class="ava-cell"
                            :aria-pressed="config.accessories === opt"
                            :aria-label="'Accessoire ' + opt"
                            @click="setAndCommit('accessories', opt)" x-text="opt"></button>
                    </template>
                </div>
            </section>

            <section x-show="currentTab === 'quebec'" id="panel-quebec" role="tabpanel" aria-labelledby="tab-quebec" class="ava-section">
                <h2>Touches du Québec 🇨🇦</h2>
                <p style="color:var(--c-text-muted);font-size:.875rem;margin:0 0 1rem;">Sélectionnez les éléments à superposer à votre avatar.</p>
                <div class="ava-grid">
                    <template x-for="item in quebecItems" :key="item.id">
                        <button type="button" class="ava-cell ava-cell--quebec"
                            :aria-pressed="activeQuebec.includes(item.id)"
                            :aria-label="item.label"
                            @click="toggleQuebec(item.id)">
                            <span x-html="item.icon"></span>
                        </button>
                    </template>
                </div>
            </section>

            <section x-show="currentTab === 'background'" id="panel-background" role="tabpanel" aria-labelledby="tab-background" class="ava-section">
                <h2>Couleur de fond</h2>
                <div class="ava-grid">
                    <template x-for="color in backgroundColors" :key="color">
                        <button type="button" class="ava-cell ava-cell--swatch"
                            :aria-pressed="config.backgroundColor === color"
                            :aria-label="'Fond ' + color"
                            @click="setAndCommit('backgroundColor', color)">
                            <span class="ava-swatch" :style="`background:${color}`"></span>
                        </button>
                    </template>
                </div>
            </section>

            <section x-show="currentTab === 'advanced'" id="panel-advanced" role="tabpanel" aria-labelledby="tab-advanced" class="ava-section">
                <h2>⚙️ Ajustement avancé</h2>
                <p style="color:var(--c-text-muted);font-size:.875rem;margin:0 0 1rem;">Affinez chaque élément avec position, échelle, rotation, couleur et miroir.</p>

                <div class="ava-adv-group">
                    <label for="adv-element-select" class="ava-adv-label" style="display:block;">Élément à ajuster</label>
                    <select id="adv-element-select" class="ava-select" x-model="activeAdv">
                        <option value="eyes">👁️ Yeux</option>
                        <option value="nose">👃 Nez</option>
                        <option value="mouth">👄 Bouche</option>
                        <option value="ears">👂 Oreilles</option>
                        <option value="hair">💇 Cheveux</option>
                        <option value="eyebrows">🤨 Sourcils</option>
                    </select>
                </div>

                <div class="ava-adv-group">
                    <label class="ava-adv-label" :for="'slider-x-' + activeAdv">
                        <span>Position horizontale (X)</span>
                        <span class="ava-adv-value" x-text="adv[activeAdv].x + ' px'"></span>
                    </label>
                    <input type="range" :id="'slider-x-' + activeAdv" class="ava-slider"
                        min="-50" max="50" step="1"
                        x-model.number="adv[activeAdv].x"
                        @change="commitAdv()"
                        :aria-valuetext="`${adv[activeAdv].x} pixels ${adv[activeAdv].x > 0 ? 'à droite' : (adv[activeAdv].x < 0 ? 'à gauche' : 'centré')}`">
                </div>

                <div class="ava-adv-group">
                    <label class="ava-adv-label" :for="'slider-y-' + activeAdv">
                        <span>Position verticale (Y)</span>
                        <span class="ava-adv-value" x-text="adv[activeAdv].y + ' px'"></span>
                    </label>
                    <input type="range" :id="'slider-y-' + activeAdv" class="ava-slider"
                        min="-50" max="50" step="1"
                        x-model.number="adv[activeAdv].y"
                        @change="commitAdv()"
                        :aria-valuetext="`${adv[activeAdv].y} pixels ${adv[activeAdv].y > 0 ? 'vers le bas' : (adv[activeAdv].y < 0 ? 'vers le haut' : 'centré')}`">
                </div>

                <div class="ava-adv-group">
                    <label class="ava-adv-label" :for="'slider-scale-' + activeAdv">
                        <span>Échelle</span>
                        <span class="ava-adv-value" x-text="adv[activeAdv].scale + ' %'"></span>
                    </label>
                    <input type="range" :id="'slider-scale-' + activeAdv" class="ava-slider"
                        min="50" max="150" step="5"
                        x-model.number="adv[activeAdv].scale"
                        @change="commitAdv()"
                        :aria-valuetext="`Échelle ${adv[activeAdv].scale} pourcent`">
                </div>

                <div class="ava-adv-group">
                    <label class="ava-adv-label" :for="'slider-rot-' + activeAdv">
                        <span>Rotation</span>
                        <span class="ava-adv-value" x-text="adv[activeAdv].rotation + '°'"></span>
                    </label>
                    <input type="range" :id="'slider-rot-' + activeAdv" class="ava-slider"
                        min="-30" max="30" step="1"
                        x-model.number="adv[activeAdv].rotation"
                        @change="commitAdv()"
                        :aria-valuetext="`Rotation ${adv[activeAdv].rotation} degrés`">
                </div>

                <div class="ava-adv-group">
                    <label class="ava-adv-label" :for="'slider-color-' + activeAdv" style="display:block;">Teinte de couleur</label>
                    <input type="color" :id="'slider-color-' + activeAdv" class="ava-color"
                        x-model="adv[activeAdv].color"
                        @change="commitAdv()"
                        :aria-label="`Teinte de l'élément ${activeAdv}`">
                </div>

                <div class="ava-adv-group">
                    <label class="ava-toggle">
                        <input type="checkbox" x-model="adv[activeAdv].mirror" @change="commitAdv()" :aria-label="`Miroir horizontal pour ${activeAdv}`">
                        <span>🔄 Miroir horizontal</span>
                    </label>
                </div>

                <button type="button" class="ava-btn" @click="resetActiveAdv()" aria-label="Réinitialiser les paramètres de l'élément actif">
                    ↺ Réinitialiser cet élément
                </button>
            </section>

            <div class="ava-actions" role="group" aria-label="Actions sur l'avatar">
                <button type="button" class="ava-btn" @click="randomize()" aria-label="Générer un avatar aléatoire">🎲 Aléatoire</button>
                <button type="button" class="ava-btn" @click="resetAll()" aria-label="Réinitialiser tous les paramètres">↺ Réinitialiser</button>
                <button type="button" class="ava-btn" @click="undo()" :disabled="historyIndex <= 0" aria-label="Annuler">↶ Annuler</button>
                <button type="button" class="ava-btn" @click="redo()" :disabled="historyIndex >= history.length - 1" aria-label="Rétablir">↷ Rétablir</button>
                <button type="button" class="ava-btn ava-btn--primary" @click="downloadPng()" aria-label="Télécharger l'avatar en PNG 512x512">📥 PNG</button>
                <button type="button" class="ava-btn" @click="copyJson()" aria-label="Copier la configuration JSON">📋 JSON</button>
                <button type="button" class="ava-btn ava-btn--accent" @click="saveAvatar()" aria-label="Sauvegarder l'avatar dans mon compte">💾 Sauvegarder</button>
            </div>
        </main>
    </div>

    <div class="ava-toast" x-show="toast" x-text="toast" x-transition role="status" aria-live="polite"></div>
</div>

<script type="module">
    import { createAvatar } from 'https://esm.sh/@dicebear/core@7';
    import { bigSmile } from 'https://esm.sh/@dicebear/big-smile@7';

    window.__avatarLib = { createAvatar, bigSmile };
    window.dispatchEvent(new CustomEvent('avatar-lib-ready'));
</script>

<script>
function avatarStudio() {
    const QUEBEC_ICONS = {
        'tuque-rouge': '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 40c0-14 9-26 20-26s20 12 20 26v6H12v-6z" fill="#d62828"/><rect x="10" y="44" width="44" height="8" rx="4" fill="#f8edeb"/><circle cx="32" cy="14" r="5" fill="#f8edeb"/></svg>',
        'tuque-bleue': '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 40c0-14 9-26 20-26s20 12 20 26v6H12v-6z" fill="#064E5A"/><rect x="10" y="44" width="44" height="8" rx="4" fill="#f8edeb"/><circle cx="32" cy="14" r="5" fill="#f8edeb"/></svg>',
        'tuque-noire': '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 40c0-14 9-26 20-26s20 12 20 26v6H12v-6z" fill="#1a1d23"/><rect x="10" y="44" width="44" height="8" rx="4" fill="#9A2A06"/><circle cx="32" cy="14" r="5" fill="#9A2A06"/></svg>',
        'mitaines': '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 20c-4 0-7 3-7 7v18c0 7 5 13 12 13h6V20H16z" fill="#9A2A06"/><path d="M48 20c4 0 7 3 7 7v18c0 7-5 13-12 13h-6V20h11z" fill="#9A2A06"/><rect x="10" y="36" width="20" height="4" fill="#f8edeb"/><rect x="34" y="36" width="20" height="4" fill="#f8edeb"/></svg>',
        'foulard-erable': '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 26h48v10H8z" fill="#9A2A06"/><path d="M12 36l4 16h12l-2-16M52 36l-4 16H36l2-16" fill="#9A2A06"/><path d="M32 18l3 5h5l-4 4 2 6-6-3-6 3 2-6-4-4h5z" fill="#f8edeb"/></svg>',
        'hoodie-ours': '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 28h36v28H14z" fill="#dde6ef"/><circle cx="32" cy="22" r="10" fill="#dde6ef"/><circle cx="27" cy="20" r="2" fill="#1a1d23"/><circle cx="37" cy="20" r="2" fill="#1a1d23"/><ellipse cx="32" cy="26" rx="3" ry="2" fill="#1a1d23"/></svg>',
        'lunettes-carrees': '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="6" y="22" width="22" height="20" rx="3" stroke="#1a1d23" stroke-width="3" fill="none"/><rect x="36" y="22" width="22" height="20" rx="3" stroke="#1a1d23" stroke-width="3" fill="none"/><path d="M28 32h8" stroke="#1a1d23" stroke-width="3"/></svg>',
        'casque-hockey': '<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 36c0-12 8-22 18-22s18 10 18 22v8H14v-8z" fill="#064E5A"/><rect x="12" y="42" width="40" height="4" fill="#1a1d23"/><path d="M20 36h24v6H20z" fill="rgba(255,255,255,0.15)"/></svg>',
    };

    const DEFAULT_CONFIG = {
        skinColor: '#f9c9b6',
        hairColor: '#3e2723',
        backgroundColor: '#F0F4F8',
        eyes: 'normal',
        eyebrows: 'normal',
        mouth: 'openedSmile',
        hair: 'shortHair',
        accessories: 'none',
    };
    const DEFAULT_ADV = () => ({
        eyes: { x: 0, y: 0, scale: 100, rotation: 0, color: '#1a1d23', mirror: false },
        nose: { x: 0, y: 0, scale: 100, rotation: 0, color: '#1a1d23', mirror: false },
        mouth: { x: 0, y: 0, scale: 100, rotation: 0, color: '#1a1d23', mirror: false },
        ears: { x: 0, y: 0, scale: 100, rotation: 0, color: '#1a1d23', mirror: false },
        hair: { x: 0, y: 0, scale: 100, rotation: 0, color: '#3e2723', mirror: false },
        eyebrows: { x: 0, y: 0, scale: 100, rotation: 0, color: '#1a1d23', mirror: false },
    });

    const initialFromBackend = @json($existingConfig ?? null);

    return {
        tabs: [
            { id: 'colors', icon: '🎨', label: 'Couleurs' },
            { id: 'face', icon: '👤', label: 'Visage' },
            { id: 'hair', icon: '💇', label: 'Cheveux' },
            { id: 'accessories', icon: '🎩', label: 'Accessoires' },
            { id: 'quebec', icon: '🇨🇦', label: 'Québec' },
            { id: 'background', icon: '🌅', label: 'Fond' },
            { id: 'advanced', icon: '⚙️', label: 'Avancé' },
        ],
        currentTab: 'colors',
        activeAdv: 'eyes',

        skinColors: ['#ffe0c9','#f9c9b6','#e9b48f','#d49870','#a87655','#7a5238','#5b3a20','#3e2723'],
        hairColors: ['#1a1d23','#3e2723','#6b4226','#a05a2c','#d49870','#f3d250','#c0392b','#7a4f8c'],
        backgroundColors: ['#F0F4F8','#fef3e8','#e6f7ff','#e6fffa','#f0fff4','#fffaf0','#fff5f5','#f9f3ff','#ffd6e0','#d6e7ff','#fff1c1','#ffd7ba'],
        eyeStyles: ['normal','happy','starstruck','winking','sleepClose','sad','angry','confused'],
        eyebrowStyles: ['normal','raised','angry','sad','concerned'],
        mouthStyles: ['openedSmile','teethSmile','awkwardSmile','kawaii','unimpressed','sad','tongueOut'],
        hairStyles: ['shortHair','mohawk','wavyBob','bunHair','froBun','bowlCutHair','curlyBob','curlyShortHair','straightHair','curlyHair','spikedHair','braids'],
        faceAccessories: ['none','catEars','glasses','sailormoonCrown','clownNose','sleepMask','sunglasses'],
        quebecItems: Object.keys(QUEBEC_ICONS).map(id => ({
            id,
            label: id.replace(/-/g,' '),
            icon: QUEBEC_ICONS[id],
        })),

        config: initialFromBackend?.config ? { ...DEFAULT_CONFIG, ...initialFromBackend.config } : { ...DEFAULT_CONFIG },
        activeQuebec: initialFromBackend?.quebec ?? [],
        adv: initialFromBackend?.adv ? { ...DEFAULT_ADV(), ...initialFromBackend.adv } : DEFAULT_ADV(),

        svgAvatar: '',
        history: [],
        historyIndex: -1,
        toast: '',
        libReady: !!window.__avatarLib,

        init() {
            this.loadStorage();
            this.snapshot();
            if (this.libReady) {
                this.render();
            } else {
                window.addEventListener('avatar-lib-ready', () => { this.libReady = true; this.render(); }, { once: true });
            }
            this.$watch('config', () => this.debouncedRender(), { deep: true });
            this.$watch('activeQuebec', () => this.debouncedRender(), { deep: true });
            this.$watch('adv', () => this.debouncedRender(), { deep: true });
        },

        debounceTimer: null,
        debouncedRender() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => { this.render(); this.persistStorage(); }, 150);
        },

        render() {
            if (!this.libReady) return;
            try {
                const av = window.__avatarLib.createAvatar(window.__avatarLib.bigSmile, {
                    size: 320,
                    backgroundColor: [this.config.backgroundColor.replace('#','')],
                    skinColor: [this.config.skinColor.replace('#','')],
                    hairColor: [this.config.hairColor.replace('#','')],
                    eyes: [this.config.eyes],
                    eyebrows: [this.config.eyebrows],
                    mouth: [this.config.mouth],
                    hair: [this.config.hair],
                    accessories: this.config.accessories !== 'none' ? [this.config.accessories] : [],
                    accessoriesProbability: this.config.accessories !== 'none' ? 100 : 0,
                });
                let svg = av.toString();
                svg = this.applyAdvancedTransforms(svg);
                svg = this.appendQuebecOverlay(svg);
                this.svgAvatar = svg;
            } catch (e) {
                console.error('Avatar render error', e);
                this.svgAvatar = '<p style="color:#9A2A06;text-align:center;padding:2rem;">Erreur de rendu. Recharger la page.</p>';
            }
        },

        applyAdvancedTransforms(svg) {
            const elementSelectors = {
                eyes: '.bigSmile-eyes, [class*="eyes"]',
                nose: '.bigSmile-nose, [class*="nose"]',
                mouth: '.bigSmile-mouth, [class*="mouth"]',
                ears: '.bigSmile-ears, [class*="ears"]',
                hair: '.bigSmile-hair, [class*="hair"]',
                eyebrows: '.bigSmile-eyebrows, [class*="eyebrows"]',
            };
            const parser = new DOMParser();
            const doc = parser.parseFromString(svg, 'image/svg+xml');
            for (const [el, sel] of Object.entries(elementSelectors)) {
                const cfg = this.adv[el];
                if (!cfg) continue;
                const isIdentity = cfg.x === 0 && cfg.y === 0 && cfg.scale === 100 && cfg.rotation === 0 && !cfg.mirror;
                if (isIdentity) continue;
                const targets = doc.querySelectorAll(sel);
                targets.forEach(node => {
                    const transform = [
                        `translate(${cfg.x} ${cfg.y})`,
                        cfg.rotation ? `rotate(${cfg.rotation} 160 160)` : '',
                        cfg.scale !== 100 ? `scale(${cfg.scale / 100} ${(cfg.mirror ? -1 : 1) * (cfg.scale / 100)})` : (cfg.mirror ? 'scale(-1 1)' : ''),
                    ].filter(Boolean).join(' ');
                    if (transform) node.setAttribute('transform', transform);
                });
            }
            return new XMLSerializer().serializeToString(doc.documentElement);
        },

        appendQuebecOverlay(svg) {
            if (!this.activeQuebec.length) return svg;
            const overlayMap = {
                'tuque-rouge': { x: 80, y: 30, w: 160, scale: 1 },
                'tuque-bleue': { x: 80, y: 30, w: 160, scale: 1 },
                'tuque-noire': { x: 80, y: 30, w: 160, scale: 1 },
                'mitaines': { x: 30, y: 220, w: 260, scale: 1 },
                'foulard-erable': { x: 50, y: 180, w: 220, scale: 1 },
                'hoodie-ours': { x: 60, y: 200, w: 200, scale: 1 },
                'lunettes-carrees': { x: 60, y: 130, w: 200, scale: 1 },
                'casque-hockey': { x: 60, y: 20, w: 200, scale: 1 },
            };
            let overlaySvg = '';
            this.activeQuebec.forEach(id => {
                const cfg = overlayMap[id];
                if (!cfg) return;
                const icon = QUEBEC_ICONS[id].replace('<svg ', `<svg x="${cfg.x}" y="${cfg.y}" width="${cfg.w}" height="${cfg.w}" `);
                overlaySvg += icon;
            });
            return svg.replace('</svg>', overlaySvg + '</svg>');
        },

        setAndCommit(key, value) {
            this.config[key] = value;
            this.snapshot();
        },

        commitAdv() {
            this.snapshot();
        },

        toggleQuebec(id) {
            const i = this.activeQuebec.indexOf(id);
            if (i >= 0) this.activeQuebec.splice(i, 1); else this.activeQuebec.push(id);
            this.snapshot();
        },

        snapshot() {
            const state = {
                config: JSON.parse(JSON.stringify(this.config)),
                activeQuebec: [...this.activeQuebec],
                adv: JSON.parse(JSON.stringify(this.adv)),
            };
            this.history = this.history.slice(0, this.historyIndex + 1);
            this.history.push(state);
            if (this.history.length > 50) { this.history.shift(); }
            this.historyIndex = this.history.length - 1;
        },

        undo() {
            if (this.historyIndex <= 0) return;
            this.historyIndex--;
            this.applyState(this.history[this.historyIndex]);
        },

        redo() {
            if (this.historyIndex >= this.history.length - 1) return;
            this.historyIndex++;
            this.applyState(this.history[this.historyIndex]);
        },

        applyState(state) {
            this.config = JSON.parse(JSON.stringify(state.config));
            this.activeQuebec = [...state.activeQuebec];
            this.adv = JSON.parse(JSON.stringify(state.adv));
        },

        randomize() {
            const rand = arr => arr[Math.floor(Math.random() * arr.length)];
            this.config = {
                skinColor: rand(this.skinColors),
                hairColor: rand(this.hairColors),
                backgroundColor: rand(this.backgroundColors),
                eyes: rand(this.eyeStyles),
                eyebrows: rand(this.eyebrowStyles),
                mouth: rand(this.mouthStyles),
                hair: rand(this.hairStyles),
                accessories: rand(this.faceAccessories),
            };
            this.activeQuebec = [];
            this.adv = DEFAULT_ADV();
            this.snapshot();
        },

        resetAll() {
            this.config = { ...DEFAULT_CONFIG };
            this.activeQuebec = [];
            this.adv = DEFAULT_ADV();
            this.snapshot();
            this.flash('Réinitialisé');
        },

        resetActiveAdv() {
            this.adv[this.activeAdv] = DEFAULT_ADV()[this.activeAdv];
            this.snapshot();
        },

        flash(msg) {
            this.toast = msg;
            setTimeout(() => { this.toast = ''; }, 2200);
        },

        persistStorage() {
            try {
                localStorage.setItem('lv_avatar_v1', JSON.stringify({
                    config: this.config,
                    quebec: this.activeQuebec,
                    adv: this.adv,
                }));
            } catch (e) {}
        },

        loadStorage() {
            try {
                const raw = localStorage.getItem('lv_avatar_v1');
                if (!raw) return;
                const data = JSON.parse(raw);
                if (data.config) this.config = { ...DEFAULT_CONFIG, ...data.config };
                if (Array.isArray(data.quebec)) this.activeQuebec = data.quebec;
                if (data.adv) this.adv = { ...DEFAULT_ADV(), ...data.adv };
            } catch (e) {}
        },

        async downloadPng() {
            if (!this.svgAvatar) return;
            const blob = new Blob([this.svgAvatar], { type: 'image/svg+xml;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const img = new Image();
            img.crossOrigin = 'anonymous';
            await new Promise((res, rej) => { img.onload = res; img.onerror = rej; img.src = url; });
            const canvas = document.createElement('canvas');
            canvas.width = 512; canvas.height = 512;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = this.config.backgroundColor;
            ctx.fillRect(0,0,512,512);
            ctx.drawImage(img, 0, 0, 512, 512);
            URL.revokeObjectURL(url);
            canvas.toBlob(b => {
                const a = document.createElement('a');
                a.href = URL.createObjectURL(b);
                a.download = 'mon-avatar-laveille.png';
                a.click();
                this.flash('PNG téléchargé');
            }, 'image/png');
        },

        copyJson() {
            const payload = { config: this.config, quebec: this.activeQuebec, adv: this.adv };
            navigator.clipboard.writeText(JSON.stringify(payload, null, 2));
            this.flash('Configuration JSON copiée');
        },

        async saveAvatar() {
            try {
                const r = await fetch('{{ route("tools.avatar.save") }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                    },
                    body: JSON.stringify({ config: { config: this.config, quebec: this.activeQuebec, adv: this.adv } }),
                });
                const j = await r.json();
                if (j.ok) this.flash('Sauvegardé ✓ slug=' + j.slug); else this.flash('Erreur sauvegarde');
            } catch (e) { console.error(e); this.flash('Erreur réseau'); }
        },

        focusNextTab() {
            const idx = this.tabs.findIndex(t => t.id === this.currentTab);
            this.currentTab = this.tabs[(idx + 1) % this.tabs.length].id;
            this.$nextTick(() => { this.$refs.tablist.querySelector('[aria-selected="true"]')?.focus(); });
        },
        focusPrevTab() {
            const idx = this.tabs.findIndex(t => t.id === this.currentTab);
            this.currentTab = this.tabs[(idx - 1 + this.tabs.length) % this.tabs.length].id;
            this.$nextTick(() => { this.$refs.tablist.querySelector('[aria-selected="true"]')?.focus(); });
        },
    };
}
</script>
@endsection
