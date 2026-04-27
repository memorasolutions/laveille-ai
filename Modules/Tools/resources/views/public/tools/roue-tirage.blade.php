<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@section('og_image', $ogImage)
@section('title', $tool->name . ' - ' . config('app.name'))
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $tool->name, 'breadcrumbItems' => [__('Outils'), $tool->name]])
@endsection
@push('styles')
<style>
.wheel-container { position: relative; width: 400px; height: 400px; margin: 0 auto; max-width: 100%; }
.wheel-canvas-wrap {
    width: 400px; height: 400px; max-width: 100%; aspect-ratio: 1;
    /* Pas de CSS transition — rotation contrôlée par requestAnimationFrame */
}
.wheel-pointer {
    position: absolute; top: -8px; left: 50%; transform: translateX(-50%); z-index: 3;
    width: 30px; height: 50px; transform-origin: 50% 8px;
}
.wheel-pointer::before {
    content: ''; position: absolute; top: 0; left: 50%; transform: translateX(-50%);
    width: 16px; height: 16px; background: var(--c-accent); border-radius: 50%;
    box-shadow: 0 2px 6px rgba(0,0,0,0.3); border: 2px solid #fff;
}
.wheel-pointer::after {
    content: ''; position: absolute; top: 12px; left: 50%; transform: translateX(-50%);
    width: 0; height: 0; border-left: 10px solid transparent; border-right: 10px solid transparent;
    border-top: 28px solid var(--c-accent); filter: drop-shadow(0 2px 3px rgba(0,0,0,0.2));
}
.wheel-center {
    position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
    width: 54px; height: 54px; border-radius: 50%; background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center;
    justify-content: center; font-size: 1.5rem; z-index: 2;
}
.confetti-piece {
    position: fixed; width: 10px; height: 10px; border-radius: 2px;
    pointer-events: none; z-index: 9999;
    animation: confetti-fall 2s ease-out forwards;
}
@keyframes confetti-fall {
    0% { opacity: 1; transform: translateY(0) rotate(0deg) scale(1); }
    100% { opacity: 0; transform: translateY(400px) rotate(720deg) scale(0.3); }
}
.fs-overlay {
    position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    z-index: 9998; display: flex; flex-direction: column; align-items: center; justify-content: center;
}
.fs-overlay .wheel-container { width: 500px; height: 500px; }
.fs-overlay .wheel-canvas-wrap { width: 500px; height: 500px; }
.fs-overlay .fs-winner { font-size: 3rem; font-weight: 800; color: #fff; margin-top: 1.5rem; font-family: var(--f-heading); text-shadow: 0 2px 10px rgba(0,0,0,0.5); }
.stat-bar { background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden; }
.stat-bar-fill { height: 100%; background: var(--c-primary); border-radius: 4px; transition: width 0.3s; }
input[type=checkbox].rw-check { display: inline-block !important; width: 18px; height: 18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0; }
.weight-input { width: 50px; text-align: center; padding: 2px 4px; font-size: 0.75rem; }
.rw-drawer-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.3); z-index: 9990; }
.rw-drawer { position: fixed; top: 0; right: 0; width: 320px; max-width: 85vw; height: 100vh; background: #fff; box-shadow: -4px 0 20px rgba(0,0,0,0.15); z-index: 9991; overflow-y: auto; padding: 20px; transform: translateX(100%); transition: transform 0.3s ease; }
.rw-drawer.open { transform: translateX(0); }
.rw-drawer-section { margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #eee; }
.rw-drawer-section:last-child { border-bottom: none; }
.rw-drawer h5 { font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); font-size: 0.9rem; margin: 0 0 8px; }
.rw-gear-btn { position: absolute; top: 12px; right: 12px; z-index: 50; background: var(--c-primary); color: #fff; border: none; border-radius: 50%; width: 36px; height: 36px; font-size: 1.1rem; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.15); display: flex; align-items: center; justify-content: center; }
@media (prefers-reduced-motion: reduce) {
    .confetti-piece { animation: none !important; display: none !important; }
}
@media (max-width: 768px) {
    .wheel-container { width: 280px; height: 280px; }
    .wheel-canvas-wrap { width: 280px; height: 280px; }
}
</style>
@endpush
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-12">
                <div class="card shadow-sm" style="border-radius: var(--r-base);">
                    <div class="card-body p-4" x-data="spinWheel()" x-init="init()">

                        {{-- Header --}}
                        <div class="d-flex justify-content-between align-items-center mb-3" style="flex-wrap: wrap; gap: 8px; position: relative;">
                            <input type="text" x-model="title" style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); font-size: 1.3rem; border: none; background: transparent; outline: none; flex: 1; min-width: 200px;" aria-label="Titre de la roue">
                            <div class="d-flex gap-1">
                                <button class="ct-btn ct-btn-ghost ct-btn-sm" :style="soundEnabled ? '' : 'color: #dc2626;'" @click="soundEnabled = !soundEnabled; localStorage.setItem('rw_sound', soundEnabled)" aria-label="Son">
                                    <span x-text="soundEnabled ? '🔊' : '🔇'"></span>
                                </button>
                                <button class="ct-btn ct-btn-ghost ct-btn-sm" @click="toggleFullscreen()" aria-label="Plein écran">⛶</button>
                                <button class="ct-btn ct-btn-primary ct-btn-sm" @click="showDrawer = true; document.body.style.overflow = 'hidden'" aria-label="Paramètres">⚙ {{ __('Paramètres') }}</button>
                                <button class="ct-btn ct-btn-primary ct-btn-icon" @click="jQuery('#roueTirageHelpModal').modal('show')" style="border-radius:50%;width:28px;height:28px;padding:0;line-height:28px;">?</button>
                            </div>
                        </div>

                        {{-- Options compactes + roue --}}
                        <div class="text-center mb-3">
                            <textarea class="form-control" rows="3" x-model="names" @input="drawWheel()" aria-label="Liste des options" style="font-size: 0.85rem; max-width: 500px; margin: 0 auto; resize: vertical;" :placeholder="'{{ __('Entrez vos options, une par ligne...') }}'"></textarea>
                            <div class="d-flex justify-content-center gap-1 mt-1" style="font-size: 0.7rem;">
                                <span class="text-muted" x-text="items.length + ' options'"></span>
                                <span x-show="eliminationMode" class="text-muted" x-text="'· ' + weightedItems.length + ' restants'"></span>
                            </div>
                        </div>

                        {{-- Barre sauvegarde (connectés) --}}
                        <div x-show="isAuthenticated" x-cloak style="background: rgba(11,114,133,0.04); border: 1px solid rgba(11,114,133,0.12); border-radius: 10px; padding: 12px; margin-bottom: 12px;">
                            <div class="d-flex gap-2 align-items-center">
                                <input type="text" class="form-control form-control-sm flex-fill" x-model="saveName" placeholder="{{ __('Nommer cette configuration...') }}" aria-label="{{ __('Nom de la configuration') }}" style="border-radius: 8px;">
                                <button class="ct-btn ct-btn-primary ct-btn-sm" @click="saveToAccount()" :disabled="items.length < 2 || saving" style="white-space:nowrap;"
                                        x-text="saving ? '{{ __('Sauvegarde...') }}' : (_editingId ? '{{ __('Mettre à jour') }}' : '{{ __('Sauvegarder') }}')"></button>
                            </div>
                            <div class="small mt-1" style="font-size: 0.8rem; color: var(--c-text-muted);">
                                {{ __('Retrouvez vos configurations dans') }} <a href="{{ route('user.saved') }}" style="color: var(--c-primary); text-decoration: underline;">{{ __('vos sauvegardes') }}</a>.
                            </div>
                            <template x-if="saveError">
                                <div class="alert alert-danger small p-1 mt-2 mb-0" style="font-size: 0.8rem; border-radius: 6px;" x-text="saveError"></div>
                            </template>
                        </div>
                        <div x-show="!isAuthenticated" x-cloak style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 10px 14px; margin-bottom: 12px; font-size: 0.85rem; color: #0369a1;">
                            {{ __('Connectez-vous pour sauvegarder vos configurations dans votre compte.') }}
                        </div>

                        {{-- Roue centrée --}}
                        <div class="text-center">
                            <div class="wheel-container">
                                <div class="wheel-pointer" x-ref="pointer"></div>
                                <div class="wheel-canvas-wrap" x-ref="wheelWrap">
                                    <canvas x-ref="canvas" width="400" height="400" role="img" aria-label="Roue de tirage"></canvas>
                                </div>
                                <div class="wheel-center"><template x-if="centerLogo"><img :src="centerLogo" style="width:100%;height:100%;border-radius:50%;object-fit:cover;"></template><template x-if="!centerLogo"><img src="{{ asset('images/logo-eye.svg') }}" style="width:36px;height:36px;" alt="Logo"></template></div>
                            </div>

                            <button class="ct-btn ct-btn-accent ct-btn-lg mt-3" @click="spin()" :disabled="spinning || weightedItems.length < 2" style="min-width:220px;box-shadow:0 4px 0 rgba(0,0,0,0.2);transition:all 0.1s;" aria-label="Tourner la roue">
                                <span x-text="spinning ? '{{ __('En cours...') }}' : '{{ __('Tourner !') }}'"></span>
                            </button>
                        </div>

                        {{-- Drawer paramètres --}}
                        <template x-if="showDrawer">
                            <div>
                                <div class="rw-drawer-backdrop" @click="showDrawer = false; document.body.style.overflow = ''"></div>
                                <div class="rw-drawer open">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h4 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); margin: 0;">{{ __('Paramètres') }}</h4>
                                        <button @click="showDrawer = false; document.body.style.overflow = ''" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #374151;">&times;</button>
                                    </div>

                                    @php $acc = 'cursor:pointer;font-family:var(--f-heading);font-weight:700;color:var(--c-dark);font-size:0.9rem;padding:10px 0;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;'; @endphp

                                    {{-- Presets --}}
                                    <div class="rw-drawer-section">
                                        <div @click="openSection = openSection === 'presets' ? null : 'presets'" style="{{ $acc }}">{{ __('Presets') }} <span x-text="openSection === 'presets' ? '▲' : '▼'" style="font-size:0.7rem;color:#374151;"></span></div>
                                        <div x-show="openSection === 'presets'" x-transition style="padding: 8px 0;">
                                            <div class="d-flex flex-wrap gap-1">
                                                <template x-for="(p, i) in presets" :key="i">
                                                    <button class="ct-btn ct-btn-outline ct-btn-sm" @click="loadPreset(p); showDrawer = false; document.body.style.overflow = ''" x-text="p.name" style="border-radius: var(--r-btn); font-size: 0.7rem;"></button>
                                                </template>
                                            </div>
                                            <template x-if="!isAuthenticated && savedLists.length > 0">
                                                <div class="mt-2">
                                                    <small class="text-muted">{{ __('Mes listes') }}</small>
                                                    <template x-for="(s, i) in savedLists" :key="'ds'+i">
                                                        <div class="d-flex mt-1">
                                                            <button class="ct-btn ct-btn-outline ct-btn-sm" @click="loadPreset(s); showDrawer = false; document.body.style.overflow = ''" x-text="s.name" style="border-radius: var(--r-btn) 0 0 var(--r-btn); font-size: 0.7rem; flex: 1; text-align: left;"></button>
                                                            <button class="ct-btn ct-btn-outline-danger ct-btn-sm" @click="deleteSaved(i)" style="border-radius: 0 var(--r-btn) var(--r-btn) 0; font-size: 0.6rem; padding: 0 6px;">✕</button>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Gestion des listes --}}
                                    <div class="rw-drawer-section">
                                        <div @click="openSection = openSection === 'lists' ? null : 'lists'" style="{{ $acc }}">{{ __('Gestion des listes') }} <span x-text="openSection === 'lists' ? '▲' : '▼'" style="font-size:0.7rem;color:#374151;"></span></div>
                                        <div x-show="openSection === 'lists'" x-transition style="padding: 8px 0;">
                                            <div class="d-flex flex-wrap gap-1">
                                                <button class="ct-btn ct-btn-outline ct-btn-sm" @click="saveCurrentList()" style="border-radius: var(--r-btn); font-size: 0.75rem;">{{ __('Sauvegarder') }}</button>
                                                <button class="ct-btn ct-btn-outline ct-btn-sm" @click="shuffleItems()" style="border-radius: var(--r-btn); font-size: 0.75rem;">{{ __('Mélanger') }}</button>
                                                <button class="ct-btn ct-btn-outline ct-btn-sm" @click="exportList()" style="border-radius: var(--r-btn); font-size: 0.75rem;">{{ __('Exporter') }}</button>
                                                <label style="cursor: pointer; margin: 0;">
                                                    <input type="file" accept=".txt" @change="importList($event)" style="display:none" x-ref="impList">
                                                    <span class="ct-btn ct-btn-outline ct-btn-sm" @click="$refs.impList.click()" style="border-radius: var(--r-btn); font-size: 0.75rem;">{{ __('Importer') }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Apparence --}}
                                    <div class="rw-drawer-section">
                                        <div @click="openSection = openSection === 'appearance' ? null : 'appearance'" style="{{ $acc }}">{{ __('Apparence') }} <span x-text="openSection === 'appearance' ? '▲' : '▼'" style="font-size:0.7rem;color:#374151;"></span></div>
                                        <div x-show="openSection === 'appearance'" x-transition style="padding: 8px 0;">
                                            <label style="font-size: 0.8rem; font-weight: 600; margin-bottom: 4px; display: block;">{{ __('Palette de couleurs') }}</label>
                                            <select class="form-control" x-model="paletteName" @change="drawWheel()" style="font-size: 0.85rem; margin-bottom: 10px;" aria-label="Palette de couleurs">
                                                <option value="classic">{{ __('Classique') }}</option>
                                                <option value="pastel">{{ __('Pastel') }}</option>
                                                <option value="neon">{{ __('Néon') }}</option>
                                                <option value="nature">{{ __('Nature') }}</option>
                                            </select>
                                            <label style="font-size: 0.8rem; font-weight: 600; margin-bottom: 4px; display: block;">{{ __('Logo central') }}</label>
                                            <input type="file" accept="image/*" @change="uploadLogo($event)" class="form-control" style="font-size: 0.8rem;">
                                            <div x-show="centerLogo" class="mt-1">
                                                <button @click="removeLogo()" class="ct-btn ct-btn-outline-danger ct-btn-sm" style="font-size: 0.7rem; border-radius: var(--r-btn);">{{ __('Retirer le logo') }}</button>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Comportement --}}
                                    <div class="rw-drawer-section">
                                        <div @click="openSection = openSection === 'behavior' ? null : 'behavior'" style="{{ $acc }}">{{ __('Comportement') }} <span x-text="openSection === 'behavior' ? '▲' : '▼'" style="font-size:0.7rem;color:#374151;"></span></div>
                                        <div x-show="openSection === 'behavior'" x-transition style="padding: 8px 0;">
                                            <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; margin: 0 0 10px; font-size: 0.85rem;">
                                                <input type="checkbox" class="rw-check" x-model="eliminationMode">
                                                {{ __('Mode élimination') }}
                                            </label>
                                            <p class="text-muted mb-2" style="font-size: 0.7rem;">{{ __('Le gagnant est retiré automatiquement.') }}</p>

                                            <label style="font-size: 0.8rem; font-weight: 600; margin-bottom: 4px; display: block;">{{ __('Vitesse') }}</label>
                                            <select x-model.number="spinDuration" class="form-control" style="font-size: 0.85rem; margin-bottom: 10px;">
                                                <option value="3">{{ __('Rapide (3s)') }}</option>
                                                <option value="4">{{ __('Normale (4s)') }}</option>
                                                <option value="5">{{ __('Lente (5s)') }}</option>
                                                <option value="6">{{ __('Très lente (6s)') }}</option>
                                                <option value="7">{{ __('Cinéma (7s)') }}</option>
                                            </select>

                                            <label style="font-size: 0.8rem; font-weight: 600; margin-bottom: 4px; display: block;">{{ __('Sens de rotation') }}</label>
                                            <div class="d-flex gap-2" style="font-size: 0.85rem;">
                                                <label style="display: inline-flex; align-items: center; gap: 4px; cursor: pointer;">
                                                    <input type="radio" name="spinDir" value="cw" x-model="spinDirection"> {{ __('Horaire') }} ↻
                                                </label>
                                                <label style="display: inline-flex; align-items: center; gap: 4px; cursor: pointer;">
                                                    <input type="radio" name="spinDir" value="ccw" x-model="spinDirection"> {{ __('Anti-horaire') }} ↺
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Poids --}}
                                    <div class="rw-drawer-section">
                                        <div @click="openSection = openSection === 'weights' ? null : 'weights'" style="{{ $acc }}">{{ __('Poids (probabilités)') }} <span x-text="openSection === 'weights' ? '▲' : '▼'" style="font-size:0.7rem;color:#374151;"></span></div>
                                        <div x-show="openSection === 'weights'" x-transition style="padding: 8px 0;">
                                            <div style="max-height: 180px; overflow-y: auto; font-size: 0.75rem;">
                                                <template x-for="(item, i) in items" :key="'dw'+i">
                                                    <div class="d-flex align-items-center gap-1 mb-1">
                                                        <span x-text="item" style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></span>
                                                        <span style="color: #374151;">x</span>
                                                        <input type="number" min="1" max="10" :value="weights[i] || 1" @input="weights[i] = parseInt($event.target.value) || 1; drawWheel()" class="form-control weight-input" style="border-radius: var(--r-btn);">
                                                    </div>
                                                </template>
                                            </div>
                                            <p class="text-muted mb-0 mt-1" style="font-size: 0.7rem;">{{ __('x2 = 2 fois plus de chances') }}</p>
                                        </div>
                                    </div>

                                    {{-- Son et effets --}}
                                    <div class="rw-drawer-section">
                                        <div @click="openSection = openSection === 'audio' ? null : 'audio'" style="{{ $acc }}">{{ __('Son et effets') }} <span x-text="openSection === 'audio' ? '▲' : '▼'" style="font-size:0.7rem;color:#374151;"></span></div>
                                        <div x-show="openSection === 'audio'" x-transition style="padding: 8px 0;">
                                            <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; margin: 0 0 8px; font-size: 0.85rem;">
                                                <input type="checkbox" class="rw-check" x-model="soundEnabled" @change="localStorage.setItem('rw_sound', soundEnabled)">
                                                {{ __('Effets sonores') }}
                                            </label>
                                            <div x-show="soundEnabled" class="d-flex align-items-center gap-2 mt-1 mb-2">
                                                <button @click="soundVolume = Math.max(0.01, soundVolume - 0.03)" class="ct-btn ct-btn-outline ct-btn-sm" style="width: 30px; height: 30px; padding: 0; border-radius: var(--r-btn); font-weight: 700;">−</button>
                                                <span style="font-size: 0.8rem; min-width: 40px; text-align: center;" x-text="Math.round(soundVolume * 100) + ' %'"></span>
                                                <button @click="soundVolume = Math.min(0.3, soundVolume + 0.03)" class="ct-btn ct-btn-outline ct-btn-sm" style="width: 30px; height: 30px; padding: 0; border-radius: var(--r-btn); font-weight: 700;">+</button>
                                            </div>
                                            <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; margin: 0; font-size: 0.85rem;">
                                                <input type="checkbox" class="rw-check" x-model="confettiEnabled">
                                                {{ __('Confettis au résultat') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Résultat --}}
                        <template x-if="winner">
                            <div class="text-center p-4 rounded mt-3" style="background: var(--c-accent-light, #FDF5ED); border: 2px solid var(--c-accent);" aria-live="polite">
                                <h3 style="font-family: var(--f-heading); color: var(--c-accent); margin: 0;" x-text="winner"></h3>
                                <div class="d-flex justify-content-center gap-2 mt-2">
                                    <button class="ct-btn ct-btn-outline-danger ct-btn-sm" @click="removeWinner()">{{ __('Retirer et re-tirer') }}</button>
                                    <button class="ct-btn ct-btn-primary ct-btn-sm" @click="copyResult()">{{ __('Copier le résultat') }}</button>
                                </div>
                            </div>
                        </template>

                        {{-- Historique + Stats --}}
                        <div class="row mt-4" x-show="history.length > 0">
                            <div class="col-md-7">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 style="font-family: var(--f-heading); font-weight: 700; margin: 0;">{{ __('Historique') }} (<span x-text="history.length"></span>)</h6>
                                    <div class="d-flex gap-1">
                                        <button class="ct-btn ct-btn-outline ct-btn-sm" @click="copyHistory()" style="font-size: 0.65rem;">{{ __('Copier') }}</button>
                                        <button class="ct-btn ct-btn-outline-danger ct-btn-sm" @click="history = []" style="font-size: 0.65rem;">{{ __('Effacer') }}</button>
                                    </div>
                                </div>
                                <div style="max-height: 200px; overflow-y: auto;">
                                    <template x-for="(h, i) in history" :key="i">
                                        <div class="d-flex justify-content-between py-1 border-bottom" style="font-size: 0.8rem;">
                                            <span><strong x-text="'#' + (history.length - i)"></strong> — <span x-text="h.name"></span></span>
                                            <small class="text-muted" x-text="h.date"></small>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <h6 style="font-family: var(--f-heading); font-weight: 700; margin: 0 0 8px;">{{ __('Statistiques') }}</h6>
                                <template x-for="s in statsArray" :key="s.name">
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between" style="font-size: 0.75rem;">
                                            <span x-text="s.name"></span>
                                            <span><span x-text="s.count"></span> (<span x-text="s.pct"></span> %)</span>
                                        </div>
                                        <div class="stat-bar"><div class="stat-bar-fill" :style="'width:' + s.pct + '%'"></div></div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Fullscreen overlay --}}
                        <template x-if="isFullscreen">
                            <div class="fs-overlay" @keydown.escape.window="isFullscreen = false">
                                <button class="ct-btn ct-btn-ghost ct-btn-sm" @click="isFullscreen = false" style="position:absolute;top:1rem;right:1rem;background:rgba(255,255,255,0.2);color:#fff;font-size:1.2rem;">✕ {{ __('Fermer') }}</button>
                                <div class="wheel-container" style="width: 500px; height: 500px;">
                                    <div class="wheel-pointer" x-ref="pointer"></div>
                                    <div class="wheel-canvas-wrap" style="width: 500px; height: 500px;" :style="'transform: rotate(' + rotation + 'deg)'">
                                        <canvas x-ref="canvasFs" width="500" height="500" x-init="drawWheelOnCanvas($refs.canvasFs, 500)"></canvas>
                                    </div>
                                    <div class="wheel-center"><template x-if="centerLogo"><img :src="centerLogo" style="width:100%;height:100%;border-radius:50%;object-fit:cover;"></template><template x-if="!centerLogo"><img src="{{ asset('images/logo-eye.svg') }}" style="width:36px;height:36px;" alt="Logo"></template></div>
                                </div>
                                <button class="ct-btn ct-btn-accent ct-btn-lg mt-3" @click="spin()" :disabled="spinning || weightedItems.length < 2" style="min-width:250px;">
                                    <span x-text="spinning ? '{{ __('En cours...') }}' : '{{ __('Tourner !') }}'"></span>
                                </button>
                                <div class="fs-winner" x-show="winner" x-text="winner"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Modale aide --}}
<div class="modal fade" id="roueTirageHelpModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: var(--r-base);">
            <div class="modal-header" style="background: var(--c-primary); border-radius: var(--r-base) var(--r-base) 0 0;">
                <h4 class="modal-title" style="color: #fff; font-family: var(--f-heading); font-weight: 700;">{{ __('Comment utiliser la roue de tirage') }}</h4>
                <button type="button" onclick="jQuery('#roueTirageHelpModal').modal('hide')" style="background: none; border: none; color: #fff !important; opacity: 1; font-size: 1.5rem; font-weight: 700; cursor: pointer; float: right;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <div class="mb-3 p-3 rounded" style="background: #f0fdf4; border-left: 3px solid #059669;">
                    <strong style="color: #059669;">{{ __('Saisir les options') }}</strong>
                    <p class="mb-0 mt-1" style="font-size: 0.85rem;">{{ __('Entrez vos options dans la zone de texte, une par ligne. Utilisez les presets pour charger rapidement des listes prédéfinies. Vous pouvez aussi importer un fichier .txt.') }}</p>
                </div>
                <div class="mb-3 p-3 rounded" style="background: #eff6ff; border-left: 3px solid #3b82f6;">
                    <strong style="color: #3b82f6;">{{ __('Poids et probabilités') }}</strong>
                    <p class="mb-0 mt-1" style="font-size: 0.85rem;">{{ __('Ouvrez la section "Poids" pour attribuer un multiplicateur à chaque option. Par exemple, x3 signifie que cette option a 3 fois plus de chances d\'être tirée.') }}</p>
                </div>
                <div class="p-3 rounded" style="background: #fef3c7; border-left: 3px solid #f59e0b;">
                    <strong style="color: #d97706;">{{ __('Mode élimination et partage') }}</strong>
                    <p class="mb-0 mt-1" style="font-size: 0.85rem;">{{ __('Activez le mode élimination pour retirer automatiquement le gagnant après chaque tirage. Utilisez le bouton "Copier le résultat" pour partager facilement. Le plein écran est idéal pour les présentations.') }}</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="jQuery('#roueTirageHelpModal').modal('hide')" style="background: var(--c-primary); color: #fff; border-radius: var(--r-btn);">{{ __('Compris !') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('spinWheel', function() {
        var palettes = {
            classic: ['#0B7285', '#E67E22', '#6366f1', '#10b981', '#ef4444', '#8b5cf6', '#f59e0b', '#06b6d4'],
            pastel: ['#FFB7B2', '#FFDAC1', '#E2F0CB', '#B5EAD7', '#C7CEEA', '#F0E6EF', '#FFF1E6', '#E0F7FA'],
            neon: ['#FF006E', '#8338EC', '#3A86FF', '#FB5607', '#FFBE0B', '#06D6A0', '#FF00FF', '#00FFFF'],
            nature: ['#2D6A4F', '#40916C', '#52B788', '#74C69D', '#95D5B2', '#B7E4C7', '#D8F3DC', '#1B4332']
        };
        return {
            title: '{{ $tool->name }}',
            names: 'Option 1\nOption 2\nOption 3\nOption 4\nOption 5\nOption 6',
            weights: {},
            spinning: false,
            winner: '',
            rotation: 0,
            history: [],
            paletteName: 'classic',
            soundEnabled: localStorage.getItem('rw_sound') !== 'false',
            isFullscreen: false,
            showDrawer: false,
            openSection: 'presets',
            centerLogo: null,
            spinDuration: 5,
            spinDirection: 'cw',
            soundVolume: 0.08,
            confettiEnabled: true,
            eliminationMode: false,
            isAuthenticated: {{ auth()->check() ? 'true' : 'false' }},
            saveName: '',
            saving: false,
            saveError: '',
            _editingId: null,
            tickAudio: null,
            savedLists: [],
            presets: [
                { name: 'Numéros 1-10', items: '1\n2\n3\n4\n5\n6\n7\n8\n9\n10' },
                { name: 'Oui / Non', items: 'Oui\nNon' },
                { name: 'Jours', items: 'Lundi\nMardi\nMercredi\nJeudi\nVendredi' },
                { name: 'Mois', items: 'Janvier\nFévrier\nMars\nAvril\nMai\nJuin\nJuillet\nAoût\nSeptembre\nOctobre\nNovembre\nDécembre' },
                { name: 'Couleurs', items: 'Rouge\nBleu\nVert\nJaune\nOrange\nViolet' }
            ],

            uploadLogo: function(event) {
                var self = this;
                var file = event.target.files[0];
                if (!file) return;
                var reader = new FileReader();
                reader.onload = function(e) { self.centerLogo = e.target.result; };
                reader.readAsDataURL(file);
            },
            removeLogo: function() { this.centerLogo = null; },

            init: function() {
                try { this.savedLists = JSON.parse(localStorage.getItem('rw_saved') || '[]'); } catch(e) { this.savedLists = []; }
                this.tickAudio = new Audio('/sounds/tick.wav');
                this.tickAudio.volume = this.soundVolume;
                this.drawWheel();
                this.initEditMode();
            },

            get items() { return this.names.split('\n').map(function(n) { return n.trim(); }).filter(function(n) { return n; }); },

            get weightedItems() {
                var self = this;
                var result = [];
                this.items.forEach(function(item, i) {
                    var w = self.weights[i] || 1;
                    for (var j = 0; j < w; j++) result.push(item);
                });
                return result;
            },

            get colors() { return palettes[this.paletteName] || palettes.classic; },

            get statsArray() {
                var counts = {};
                var total = this.history.length;
                if (total === 0) return [];
                this.history.forEach(function(h) { counts[h.name] = (counts[h.name] || 0) + 1; });
                return Object.keys(counts).map(function(k) {
                    return { name: k, count: counts[k], pct: Math.round(counts[k] / total * 100) };
                }).sort(function(a, b) { return b.count - a.count; });
            },

            drawWheel: function() { this.drawWheelOnCanvas(this.$refs.canvas, 400); },

            // Animation flapper — porté de la version WP qui fonctionne
            triggerFlapper: function() {
                var flapper = this.$refs.pointer;
                if (!flapper) return;
                // Mouvement brusque vers la droite (-30°) puis retour élastique
                flapper.style.transition = 'none';
                flapper.style.transform = 'translateX(-50%) rotate(-25deg)';
                void flapper.offsetHeight; // force reflow
                flapper.style.transition = 'transform 0.08s cubic-bezier(0.4, 0.0, 0.2, 1)';
                flapper.style.transform = 'translateX(-50%) rotate(0deg)';
                setTimeout(function() {
                    flapper.style.transform = 'translateX(-50%) rotate(4deg)';
                    setTimeout(function() {
                        flapper.style.transform = 'translateX(-50%) rotate(0deg)';
                    }, 40);
                }, 80);
                if (this.soundEnabled && this.tickAudio) {
                    this.tickAudio.currentTime = 0;
                    this.tickAudio.volume = this.soundVolume;
                    this.tickAudio.play().catch(function() {});
                }
            },

            checkSegmentChange: function() {
                var count = this.weightedItems.length;
                if (count === 0) return;
                var segAngle = 360 / count;
                var currentRot = ((this.rotation % 360) + 360) % 360;
                var angleUnderPointer = ((360 - currentRot) % 360 + 360) % 360;
                var currentSegIdx = Math.floor(angleUnderPointer / segAngle) % count;
                if (this._lastSegIdx !== undefined && this._lastSegIdx !== -1 && currentSegIdx !== this._lastSegIdx) {
                    this.triggerFlapper();
                }
                this._lastSegIdx = currentSegIdx;
            },

            animateWheel: function(timestamp) {
                if (!this._animStart) this._animStart = timestamp;
                var elapsed = timestamp - this._animStart;
                var durationMs = this.spinDuration * 1000;
                var progress = Math.min(elapsed / durationMs, 1);
                var easeOut = 1 - Math.pow(1 - progress, 3);

                this.rotation = this._startRot + (this._targetRot - this._startRot) * easeOut;

                // Appliquer directement au DOM (pas de CSS transition)
                var wrap = this.$refs.wheelWrap;
                if (wrap) wrap.style.transform = 'rotate(' + this.rotation + 'deg)';

                this.checkSegmentChange();

                var self = this;
                if (progress < 1) {
                    requestAnimationFrame(function(t) { self.animateWheel(t); });
                } else {
                    // Fin de la rotation — déduire le gagnant
                    self.spinning = false;
                    var count = self.weightedItems.length;
                    var segAngle = 360 / count;
                    var finalAngle = ((self.rotation % 360) + 360) % 360;
                    var pointerAngle = ((360 - finalAngle) % 360 + 360) % 360;
                    var winIdx = Math.floor(pointerAngle / segAngle) % count;

                    self.winner = self.weightedItems[winIdx];
                    self.history.unshift({ name: self.winner, date: new Date().toLocaleTimeString('fr-CA') });
                    if (self.history.length > 30) self.history = self.history.slice(0, 30);
                    self.playWinSound();
                    if (self.confettiEnabled) self.confetti();
                    if (navigator.vibrate) navigator.vibrate(200);
                    if (self.eliminationMode) {
                        setTimeout(function() { self.removeWinner(); }, 2000);
                    }
                }
            },

            drawWheelOnCanvas: function(canvas, size) {
                if (!canvas) return;
                var ctx = canvas.getContext('2d');
                var items = this.weightedItems;
                var colors = this.colors;
                var count = items.length;
                var center = size / 2;
                var radius = center - 5;
                if (count === 0) { ctx.clearRect(0, 0, size, size); return; }
                var angle = (2 * Math.PI) / count;

                ctx.clearRect(0, 0, size, size);
                for (var i = 0; i < count; i++) {
                    var startAngle = i * angle - Math.PI / 2;
                    var endAngle = startAngle + angle;

                    ctx.beginPath();
                    ctx.fillStyle = colors[i % colors.length];
                    ctx.moveTo(center, center);
                    ctx.arc(center, center, radius, startAngle, endAngle);
                    ctx.closePath();
                    ctx.fill();
                    ctx.strokeStyle = '#fff';
                    ctx.lineWidth = 2;
                    ctx.stroke();

                    ctx.save();
                    ctx.translate(center, center);
                    ctx.rotate(startAngle + angle / 2);
                    ctx.fillStyle = '#fff';
                    ctx.font = 'bold ' + Math.max(10, Math.min(14, Math.round(size / 28))) + 'px sans-serif';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.shadowColor = 'rgba(0,0,0,0.3)';
                    ctx.shadowBlur = 2;
                    var maxLen = Math.max(8, Math.round(size / 25));
                    var label = items[i].length > maxLen ? items[i].substring(0, maxLen - 1) + '\u2026' : items[i];
                    ctx.fillText(label, radius * 0.6, 0);
                    ctx.restore();
                }
            },

            spin: function() {
                if (this.spinning || this.weightedItems.length < 2) return;
                this.spinning = true;
                this.winner = '';
                this._lastSegIdx = -1;

                var a = new Uint32Array(1);
                crypto.getRandomValues(a);
                var extraSpins = 2160 + (a[0] % 1800);
                var randomOffset = (a[0] % 360);
                var delta = extraSpins + randomOffset;

                this._startRot = this.rotation;
                this._targetRot = this.rotation + ((this.spinDirection === 'ccw') ? -delta : delta);
                this._animStart = null;

                var self = this;
                requestAnimationFrame(function(t) { self.animateWheel(t); });
            },

            removeWinner: function() {
                if (!this.winner) return;
                var w = this.winner;
                var lines = this.names.split('\n');
                var idx = -1;
                for (var i = 0; i < lines.length; i++) {
                    if (lines[i].trim() === w) { idx = i; break; }
                }
                if (idx !== -1) {
                    lines.splice(idx, 1);
                    this.names = lines.join('\n');
                }
                this.winner = '';
                this.drawWheel();
            },

            shuffleItems: function() {
                var arr = this.items.slice();
                for (var i = arr.length - 1; i > 0; i--) {
                    var j = Math.floor(Math.random() * (i + 1));
                    var tmp = arr[i]; arr[i] = arr[j]; arr[j] = tmp;
                }
                this.names = arr.join('\n');
                this.drawWheel();
            },

            confetti: function() {
                var colors = this.colors;
                for (var i = 0; i < 40; i++) {
                    var el = document.createElement('div');
                    el.className = 'confetti-piece';
                    el.style.backgroundColor = colors[i % colors.length];
                    el.style.left = (20 + Math.random() * 60) + 'vw';
                    el.style.top = (5 + Math.random() * 20) + 'vh';
                    el.style.animationDelay = (Math.random() * 0.5) + 's';
                    el.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
                    document.body.appendChild(el);
                    setTimeout(function() { if (el.parentNode) el.parentNode.removeChild(el); }, 2500);
                }
            },

            // Audio (un seul AudioContext réutilisé)
            _audioCtx: null,
            getAudioCtx: function() {
                if (!this._audioCtx) {
                    this._audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                }
                if (this._audioCtx.state === 'suspended') this._audioCtx.resume();
                return this._audioCtx;
            },
            playBeep: function(freq, dur) {
                if (!this.soundEnabled) return;
                try {
                    var ctx = this.getAudioCtx();
                    var osc = ctx.createOscillator();
                    var gain = ctx.createGain();
                    osc.connect(gain); gain.connect(ctx.destination);
                    osc.type = 'sine'; osc.frequency.value = freq;
                    gain.gain.value = this.soundVolume || 0.08;
                    osc.start(); osc.stop(ctx.currentTime + (dur / 1000));
                } catch(e) {}
            },
            playWinSound: function() {
                var self = this;
                [660, 880, 1100, 1320].forEach(function(f, i) {
                    setTimeout(function() { self.playBeep(f, 100); }, i * 120);
                });
            },

            // Fullscreen
            toggleFullscreen: function() {
                this.isFullscreen = !this.isFullscreen;
                if (this.isFullscreen) {
                    var self = this;
                    setTimeout(function() { self.drawWheelOnCanvas(self.$refs.canvasFs, 500); }, 50);
                }
            },

            // Presets & listes
            loadPreset: function(p) { this.names = p.items; this.winner = ''; this.weights = {}; this.drawWheel(); },
            exportList: function() {
                var blob = new Blob([this.names], { type: 'text/plain' });
                var a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'roue-options.txt';
                document.body.appendChild(a); a.click(); document.body.removeChild(a);
            },
            importList: function(event) {
                var self = this;
                var file = event.target.files[0];
                if (!file) return;
                var reader = new FileReader();
                reader.onload = function(e) { self.names = e.target.result; self.weights = {}; self.drawWheel(); };
                reader.readAsText(file);
            },
            saveCurrentList: function() {
                var name = prompt('Nom pour cette liste :');
                if (!name) return;
                this.savedLists.push({ name: name, items: this.names });
                localStorage.setItem('rw_saved', JSON.stringify(this.savedLists));
            },
            deleteSaved: function(index) {
                this.savedLists.splice(index, 1);
                localStorage.setItem('rw_saved', JSON.stringify(this.savedLists));
            },

            // Partage
            copyResult: function() {
                navigator.clipboard.writeText('🎯 Tirage : ' + this.winner + ' — roue de tirage laveille.ai');
            },
            copyHistory: function() {
                var txt = this.history.map(function(h, i) { return '#' + (i + 1) + ' ' + h.name + ' (' + h.date + ')'; }).join('\n');
                navigator.clipboard.writeText(txt);
            },

            _headers: function() {
                return { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' };
            },
            saveToAccount: function() {
                if (this.saving || this.items.length < 2) return;
                var self = this;
                var title = this.saveName.trim() || this.title || 'Roue de tirage';
                this.saving = true;
                this.saveError = '';
                var isEdit = !!this._editingId;
                var url = isEdit ? '/api/wheel-presets/' + this._editingId : '/api/wheel-presets';
                var method = isEdit ? 'PUT' : 'POST';
                fetch(url, {
                    method: method, headers: this._headers(),
                    body: JSON.stringify({ name: title, config_text: this.names, params: { paletteName: this.paletteName, spinDuration: this.spinDuration, spinDirection: this.spinDirection, soundVolume: this.soundVolume, confettiEnabled: this.confettiEnabled, eliminationMode: this.eliminationMode, weights: this.weights, title: this.title } })
                })
                .then(function(r) { if (!r.ok) throw new Error('Erreur ' + r.status); return r.json(); })
                .then(function() { self._editingId = null; self.saveName = ''; self.saving = false; window.dispatchEvent(new CustomEvent('toast', { detail: { message: '{{ __("Configuration sauvegardée") }}' } })); })
                .catch(function(e) { self.saveError = e.message; self.saving = false; setTimeout(function() { self.saveError = ''; }, 4000); });
            },
            initEditMode: function() {
                if (!this.isAuthenticated) return;
                var self = this;
                var editId = new URLSearchParams(window.location.search).get('edit');
                if (!editId) return;
                fetch('/api/wheel-presets', { headers: this._headers() })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        var found = (data.data || []).find(function(p) { return p.public_id === editId; });
                        if (!found) return;
                        self.names = found.config_text || '';
                        var pr = found.params || {};
                        if (pr.paletteName) self.paletteName = pr.paletteName;
                        if (pr.spinDuration) self.spinDuration = pr.spinDuration;
                        if (pr.spinDirection) self.spinDirection = pr.spinDirection;
                        if (pr.soundVolume !== undefined) self.soundVolume = pr.soundVolume;
                        if (pr.confettiEnabled !== undefined) self.confettiEnabled = pr.confettiEnabled;
                        if (pr.eliminationMode !== undefined) self.eliminationMode = pr.eliminationMode;
                        if (pr.weights) self.weights = pr.weights;
                        if (pr.title) self.title = pr.title;
                        self.saveName = found.name;
                        self._editingId = found.public_id;
                        self.$nextTick(function() { self.drawWheel(); });
                    });
            }
        };
    });
});
</script>
@endpush
