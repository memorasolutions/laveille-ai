<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin', ['title' => 'Générateur de prompt newsletter', 'subtitle' => 'Newsletter'])

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Fermer') }}"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Fermer') }}"></button>
    </div>
@endif

{{-- Données du preset par défaut injectées pour Alpine --}}
@if($defaultPreset)
<script id="defaultPresetData" type="application/json">@json($defaultPreset->blocks ?? [])</script>
@endif

{{-- Mapping sections (injecté depuis PHP pour DRY — source unique = sectionsMap()) --}}
<script id="sectionsMeta" type="application/json">@json($sectionsMeta)</script>

<style>
/* ===== SECTION CARD ===== */
.pb-section-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    margin-bottom: .75rem;
    overflow: hidden;
    transition: border-color .2s;
}
.pb-section-card[data-custom="true"] {
    border-color: var(--sys-primary, #064E5A);
}
.pb-section-header {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .75rem 1rem;
    background: #fff;
    cursor: pointer;
    user-select: none;
    min-height: 52px;
}
.pb-section-card[data-custom="true"] .pb-section-header {
    background: #f0fafa;
}
.pb-section-header:focus-visible {
    outline: 2px solid var(--sys-action-accent, #9A2A06);
    outline-offset: -2px;
}
.pb-section-toggle {
    display: flex;
    gap: .5rem;
    margin-left: auto;
    flex-shrink: 0;
}
/* Radio pill toggle */
.pb-toggle-label {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .25rem .7rem;
    border-radius: 999px;
    font-size: .78rem;
    font-weight: 600;
    cursor: pointer;
    border: 1.5px solid #dee2e6;
    background: #f8f9fa;
    color: var(--c-text-muted, #52586a);
    transition: background .15s, border-color .15s, color .15s;
    white-space: nowrap;
}
.pb-toggle-input:checked + .pb-toggle-label[data-value="auto"] {
    background: color-mix(in srgb, var(--sys-success, #198754) 12%, #fff);
    border-color: var(--sys-success, #198754);
    color: color-mix(in srgb, var(--sys-success, #198754) 70%, #000);
}
.pb-toggle-input:checked + .pb-toggle-label[data-value="custom"] {
    background: color-mix(in srgb, var(--sys-action-accent, #9A2A06) 8%, #fff);
    border-color: var(--sys-primary, #064E5A);
    color: var(--sys-primary, #064E5A);
}
.pb-toggle-input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    pointer-events: none;
}
.pb-toggle-input:focus-visible + .pb-toggle-label {
    outline: 2px solid var(--sys-action-accent, #9A2A06);
    outline-offset: 2px;
}
.pb-section-body {
    padding: .75rem 1rem 1rem;
    border-top: 1px solid #dee2e6;
    background: #fafafa;
}
.pb-section-card[data-custom="true"] .pb-section-body {
    background: #f5fffe;
}
/* ===== PREVIEW sticky ===== */
.pb-preview-sticky {
    position: sticky;
    top: 80px;
}
/* Badge AUTO en petit */
.pb-auto-badge {
    font-size: .68rem;
    font-weight: 600;
    padding: .1rem .45rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--sys-success, #198754) 12%, #fff);
    color: color-mix(in srgb, var(--sys-success, #198754) 70%, #000);
    border: 1px solid color-mix(in srgb, var(--sys-success, #198754) 35%, #fff);
    white-space: nowrap;
}
.pb-custom-badge {
    font-size: .68rem;
    font-weight: 600;
    padding: .1rem .45rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--sys-action-accent, #9A2A06) 8%, #fff);
    color: var(--sys-primary, #064E5A);
    border: 1px solid color-mix(in srgb, var(--sys-primary, #064E5A) 35%, #fff);
    white-space: nowrap;
}
</style>

<div
    x-data="promptBuilder()"
    x-init="init()"
    class="row gy-4"
>
    {{-- ===== COLONNE GAUCHE : sections ===== --}}
    <div class="col-lg-7">

        {{-- ===== EN-TÊTE GLOBAL ===== --}}
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center gap-2">
                <i data-lucide="settings-2" style="width:16px;height:16px;" class="text-muted"></i>
                <h6 class="mb-0">Options globales</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label for="pb_subject" class="form-label">
                            Objet du courriel
                            <small class="text-muted fw-normal">(optionnel, max 45 car.)</small>
                        </label>
                        <input type="text" id="pb_subject" x-model="subject" class="form-control"
                               maxlength="45"
                               placeholder="ex: L'IA en cuisine — 3 usages surprenants">
                    </div>
                    <div class="col-md-5">
                        <label for="pb_test_email" class="form-label">
                            Adresse courriel de test
                        </label>
                        <input type="email" id="pb_test_email" x-model="test_email" class="form-control"
                               placeholder="ex: votre@adresse.com">
                    </div>
                </div>
                <div class="mt-3">
                    <label for="pb_notes" class="form-label">
                        Notes complémentaires
                        <small class="text-muted fw-normal">(instructions libres pour Claude Code)</small>
                    </label>
                    <textarea id="pb_notes" x-model="extra_notes" rows="2" class="form-control"
                              placeholder="ex: accentue les usages pratiques pour les PME québécoises cette semaine"></textarea>
                </div>
                <div class="mt-2 d-flex align-items-center gap-2">
                    <span class="text-muted small">
                        <i data-lucide="info" style="width:13px;height:13px;"></i>
                        Semaine cible :
                        <strong>{{ now()->year }}, semaine {{ now()->weekOfYear }}</strong>
                        &mdash; NewsletterIssue(year={{ now()->year }}, week_number={{ now()->weekOfYear }})
                    </span>
                </div>
            </div>
        </div>

        {{-- ===== SECTIONS DU GABARIT ===== --}}
        <div class="mb-2 d-flex align-items-center justify-content-between">
            <h6 class="mb-0 text-muted fw-semibold" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.05em;">
                Sections du gabarit digest-weekly
            </h6>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;"
                        x-on:click="setAllSections('auto')"
                        aria-label="Passer toutes les sections en mode Automatique">
                    Tout auto
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" style="font-size:.75rem;"
                        x-on:click="setAllSections('custom')"
                        aria-label="Passer toutes les sections en mode Personnaliser">
                    Tout personnaliser
                </button>
            </div>
        </div>

        <template x-for="(meta, key) in sectionsMeta" :key="key">
            <div
                class="pb-section-card"
                :data-custom="sections[key] && sections[key].mode === 'custom' ? 'true' : 'false'"
            >
                {{-- En-tête cliquable --}}
                <button
                    type="button"
                    class="pb-section-header"
                    :aria-expanded="sections[key] && sections[key].mode === 'custom' ? 'true' : 'false'"
                    :aria-label="'Section ' + meta.label + ' — ' + (sections[key] && sections[key].mode === 'custom' ? 'personnalisée' : 'automatique')"
                    x-on:click="toggleSection(key)"
                >
                    <i :data-lucide="getSectionIcon(key)" style="width:15px;height:15px;flex-shrink:0;" class="text-muted"></i>
                    <span class="fw-semibold" style="font-size:.9rem;" x-text="meta.label"></span>

                    {{-- Badge mode courant --}}
                    <template x-if="!sections[key] || sections[key].mode === 'auto'">
                        <span class="pb-auto-badge" aria-hidden="true">AUTO</span>
                    </template>
                    <template x-if="sections[key] && sections[key].mode === 'custom'">
                        <span class="pb-custom-badge" aria-hidden="true">PERSO</span>
                    </template>

                    {{-- Toggle pills Auto / Personnaliser --}}
                    <div class="pb-section-toggle" role="group" :aria-label="'Mode section ' + meta.label"
                         x-on:click.stop>
                        {{-- AUTO --}}
                        <input type="radio"
                               class="pb-toggle-input"
                               :id="'pb_mode_auto_' + key"
                               :name="'pb_mode_' + key"
                               value="auto"
                               x-on:change="setMode(key, 'auto')"
                               :checked="!sections[key] || sections[key].mode === 'auto'"
                               :aria-label="'Automatique pour ' + meta.label">
                        <label :for="'pb_mode_auto_' + key" class="pb-toggle-label" data-value="auto">
                            <i data-lucide="zap-off" style="width:11px;height:11px;"></i>
                            Auto
                        </label>

                        {{-- PERSONNALISER --}}
                        <input type="radio"
                               class="pb-toggle-input"
                               :id="'pb_mode_custom_' + key"
                               :name="'pb_mode_' + key"
                               value="custom"
                               x-on:change="setMode(key, 'custom')"
                               :checked="sections[key] && sections[key].mode === 'custom'"
                               :aria-label="'Personnaliser la section ' + meta.label">
                        <label :for="'pb_mode_custom_' + key" class="pb-toggle-label" data-value="custom">
                            <i data-lucide="edit-3" style="width:11px;height:11px;"></i>
                            Personnaliser
                        </label>
                    </div>
                </button>

                {{-- Corps de la section (visible si mode=custom) --}}
                <div
                    class="pb-section-body"
                    x-show="sections[key] && sections[key].mode === 'custom'"
                    x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                >
                    {{-- Source auto (info) --}}
                    <p class="text-muted small mb-2" style="font-size:.75rem;">
                        <i data-lucide="info" style="width:12px;height:12px;"></i>
                        <strong>Source auto :</strong> <span x-text="meta.auto_source"></span>
                    </p>

                    {{-- Champ adapté selon field_type --}}
                    <template x-if="meta.field_type === 'textarea'">
                        <div>
                            <label :for="'pb_val_' + key" class="form-label visually-hidden" x-text="'Consigne pour ' + meta.label"></label>
                            <textarea
                                :id="'pb_val_' + key"
                                x-model="sections[key].value"
                                rows="3"
                                class="form-control"
                                :placeholder="meta.placeholder"
                                :aria-label="'Contenu personnalisé pour ' + meta.label"
                            ></textarea>
                        </div>
                    </template>
                    <template x-if="meta.field_type === 'text'">
                        <div>
                            <label :for="'pb_val_' + key" class="form-label visually-hidden" x-text="'Consigne pour ' + meta.label"></label>
                            <input
                                type="text"
                                :id="'pb_val_' + key"
                                x-model="sections[key].value"
                                class="form-control"
                                :placeholder="meta.placeholder"
                                :aria-label="'Consigne pour ' + meta.label"
                            >
                            <p class="text-muted mt-1 mb-0" style="font-size:.73rem;">
                                <i data-lucide="info" style="width:11px;height:11px;"></i>
                                Claude Code trouvera l'enregistrement en DB et écrira l'ID correspondant dans NewsletterIssue.content.
                            </p>
                        </div>
                    </template>
                </div>

                {{-- Corps auto (info visible si mode=auto) --}}
                <div
                    class="pb-section-body"
                    style="background:#f8fafb;"
                    x-show="!sections[key] || sections[key].mode === 'auto'"
                    x-cloak
                >
                    <p class="text-muted mb-0" style="font-size:.75rem;">
                        <i data-lucide="check-circle" style="width:12px;height:12px;color:#198754;"></i>
                        <strong>Automatique</strong> —
                        <span x-text="meta.auto_source"></span>
                    </p>
                </div>
            </div>
        </template>

        {{-- ===== BOUTONS PRINCIPAUX ===== --}}
        <div class="d-flex flex-wrap gap-2 mt-4 mb-4">
            <button
                type="button"
                class="btn btn-primary d-flex align-items-center gap-2"
                x-on:click="generatePrompt()"
                :disabled="loading"
            >
                <span x-show="!loading">
                    <i data-lucide="wand-2" style="width:16px;height:16px;"></i>
                    Générer le prompt
                </span>
                <span x-show="loading" x-cloak>
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Génération en cours…
                </span>
            </button>
            <button
                type="button"
                class="btn btn-outline-primary d-flex align-items-center gap-1"
                x-on:click="copyPrompt()"
                :disabled="!promptText"
            >
                <i data-lucide="copy" style="width:16px;height:16px;"></i>
                Copier le prompt
            </button>
            <button
                type="button"
                class="btn btn-outline-secondary"
                data-bs-toggle="modal"
                data-bs-target="#presetModal"
                x-on:click="injectBlocksIntoPresetForm()"
            >
                <i data-lucide="save" style="width:16px;height:16px;"></i>
                Sauvegarder le preset
            </button>
        </div>

    </div>{{-- /col-lg-7 --}}

    {{-- ===== COLONNE DROITE : aperçu live + presets ===== --}}
    <div class="col-lg-5">
        <div class="pb-preview-sticky">

            {{-- Aperçu du prompt --}}
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between gap-2">
                    <span class="d-flex align-items-center gap-2">
                        <i data-lucide="terminal" style="width:16px;height:16px;"></i>
                        <h6 class="mb-0">Aperçu du prompt</h6>
                    </span>
                    <small class="text-muted" x-text="promptText.length + ' caractères'" aria-live="polite" aria-atomic="true"></small>
                </div>
                <div class="card-body p-0">
                    <textarea
                        id="pb_preview"
                        x-model="promptText"
                        readonly
                        rows="22"
                        class="form-control border-0 font-monospace rounded-0"
                        style="font-size: 0.78rem; resize: vertical; min-height: 300px;"
                        aria-label="Aperçu du prompt généré"
                        placeholder="Cliquez « Générer le prompt » pour voir l'aperçu…"
                    ></textarea>
                </div>
                <div class="card-footer d-flex flex-wrap gap-2">
                    <button type="button"
                            class="btn btn-sm btn-primary d-flex align-items-center gap-1"
                            x-on:click="generatePrompt()"
                            :disabled="loading"
                            aria-label="Générer le prompt à partir des paramètres saisis">
                        <span x-show="!loading">
                            <i data-lucide="wand-2" style="width:14px;height:14px;"></i>
                            Générer le prompt
                        </span>
                        <span x-show="loading" x-cloak>
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            Génération…
                        </span>
                    </button>
                    <button type="button"
                            class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                            x-on:click="copyPrompt()"
                            :disabled="!promptText"
                            aria-label="Copier le prompt dans le presse-papiers">
                        <i data-lucide="copy" style="width:14px;height:14px;"></i>
                        Copier le prompt
                    </button>
                </div>
            </div>

            {{-- Presets sauvegardés --}}
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <i data-lucide="bookmark" style="width:16px;height:16px;"></i>
                    <h6 class="mb-0">Presets sauvegardés</h6>
                </div>
                <div class="card-body p-0">
                    @if($presets->isEmpty())
                        <p class="text-muted small p-3 mb-0">Aucun preset encore. Utilisez le bouton « Sauvegarder le preset ».</p>
                    @else
                        <ul class="list-group list-group-flush" aria-label="Liste des presets">
                            @foreach($presets as $preset)
                                <li class="list-group-item d-flex align-items-center justify-content-between gap-2 py-2">
                                    <span class="d-flex align-items-center gap-2">
                                        <span>{{ $preset->name }}</span>
                                        @if($preset->is_default)
                                            <span class="badge bg-primary" title="Preset par défaut">Défaut</span>
                                        @endif
                                    </span>
                                    <div class="d-flex gap-1 flex-shrink-0">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                x-on:click="loadPreset({{ $preset->id }})"
                                                aria-label="Charger le preset {{ $preset->name }}">
                                            Charger
                                        </button>
                                        @if(!$preset->is_default)
                                            <form method="POST"
                                                  action="{{ route('admin.newsletter.prompt-builder.preset.default', $preset) }}"
                                                  class="d-inline">
                                                @csrf
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-info"
                                                        aria-label="Définir {{ $preset->name }} comme défaut">
                                                    Par défaut
                                                </button>
                                            </form>
                                        @endif
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                aria-label="Supprimer le preset {{ $preset->name }}"
                                                data-confirm-message="{{ e('Supprimer le preset « ' . $preset->name . ' » ?') }}"
                                                data-confirm-target="delete-preset-{{ $preset->id }}"
                                                x-on:click="
                                                    window.dispatchEvent(new CustomEvent('confirm-action', {
                                                        detail: {
                                                            title: 'Supprimer le preset',
                                                            message: $el.dataset.confirmMessage,
                                                            action: () => document.getElementById($el.dataset.confirmTarget).submit()
                                                        }
                                                    }))
                                                ">
                                            Supprimer
                                        </button>
                                        <form id="delete-preset-{{ $preset->id }}"
                                              method="POST"
                                              action="{{ route('admin.newsletter.prompt-builder.preset.destroy', $preset) }}"
                                              class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

        </div>
    </div>{{-- /col-lg-5 --}}

</div>{{-- /row --}}

{{-- Modal : Sauvegarder un preset --}}
<div class="modal fade" id="presetModal" tabindex="-1"
     aria-labelledby="presetModalLabel" aria-modal="true" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.newsletter.prompt-builder.preset.store') }}"
                  id="presetForm">
                @csrf
                <input type="hidden" name="blocks" id="presetBlocksInput">
                <div class="modal-header">
                    <h5 class="modal-title" id="presetModalLabel">Sauvegarder le preset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="{{ __('Fermer') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="preset_name" class="form-label">Nom du preset <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="text" id="preset_name" name="name" class="form-control"
                               required maxlength="150"
                               placeholder="ex: Newsletter focus PME — ton formel">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" id="preset_is_default" name="is_default" value="1"
                               class="form-check-input">
                        <label for="preset_is_default" class="form-check-label">
                            Définir comme preset par défaut
                            <small class="text-muted d-block">Sera pré-chargé à l'ouverture de cette page</small>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Sauvegarder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function promptBuilder() {
    return {
        // --- État formulaire global ---
        subject:    '',
        test_email: '{{ config('newsletter.test_email', '') }}',
        extra_notes: '',

        // --- Sections : { [key]: { mode: 'auto'|'custom', value: '' } } ---
        sections: {},

        // --- Métadonnées des sections (injectées depuis PHP) ---
        sectionsMeta: {},

        // --- État UI ---
        promptText: '',
        loading:    false,

        /**
         * Icône Lucide par section (pour l'aperçu visuel de la section).
         */
        getSectionIcon(key) {
            const icons = {
                editorial:        'feather',
                challenge:        'zap',
                highlight:        'star',
                top_news:         'newspaper',
                tool:             'wrench',
                term:             'book-open',
                article:          'file-text',
                interactive_tool: 'mouse-pointer-click',
            };
            return icons[key] || 'circle';
        },

        /**
         * Bascule entre auto et custom pour une section.
         * Le clic sur l'en-tête ouvre/ferme sans changer le mode — le changement
         * se fait via les radios uniquement. Cette fonction gère le focus visuel.
         */
        toggleSection(key) {
            // Ne basculer que si clic sur l'en-tête (pas sur les radios gérés séparément)
            // Ici on peut faire un aperçu : si auto → custom ; si custom → auto
            const current = this.sections[key]?.mode ?? 'auto';
            this.setMode(key, current === 'auto' ? 'custom' : 'auto');
        },

        setMode(key, mode) {
            if (!this.sections[key]) {
                this.sections[key] = { mode, value: '' };
            } else {
                this.sections[key].mode = mode;
            }
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        setAllSections(mode) {
            Object.keys(this.sectionsMeta).forEach(key => {
                this.setMode(key, mode);
            });
        },

        init() {
            // Charger les métadonnées des sections depuis le script JSON injecté par PHP
            const metaEl = document.getElementById('sectionsMeta');
            if (metaEl) {
                try { this.sectionsMeta = JSON.parse(metaEl.textContent || '{}'); } catch (e) {}
            }

            // Initialiser toutes les sections en mode auto
            Object.keys(this.sectionsMeta).forEach(key => {
                this.sections[key] = { mode: 'auto', value: '' };
            });

            // Charger le preset par défaut s'il existe
            const presetEl = document.getElementById('defaultPresetData');
            if (presetEl) {
                try {
                    const data = JSON.parse(presetEl.textContent || '{}');
                    this.applyBlocks(data);
                } catch (e) {
                    console.warn('Impossible de charger le preset par défaut', e);
                }
            }

            // Double protection : injecter les blocs chaque fois que la modale s'ouvre
            const presetModal = document.getElementById('presetModal');
            if (presetModal) {
                presetModal.addEventListener('show.bs.modal', () => {
                    this.injectBlocksIntoPresetForm();
                });
                const presetForm = document.getElementById('presetForm');
                if (presetForm) {
                    presetForm.addEventListener('submit', () => {
                        const input = document.getElementById('presetBlocksInput');
                        if (input && !input.value) {
                            input.value = JSON.stringify(this.buildBlocks());
                        }
                    });
                }
            }

            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        /**
         * Applique un objet blocks dans le state Alpine (chargement preset).
         */
        applyBlocks(data) {
            if (!data || typeof data !== 'object') return;
            if (data.subject    !== undefined) this.subject    = data.subject;
            if (data.test_email !== undefined) this.test_email = data.test_email;
            if (data.extra_notes !== undefined) this.extra_notes = data.extra_notes;
            if (data.sections && typeof data.sections === 'object') {
                Object.keys(data.sections).forEach(key => {
                    const sec = data.sections[key];
                    if (sec && typeof sec === 'object') {
                        this.sections[key] = {
                            mode:  sec.mode  ?? 'auto',
                            value: sec.value ?? '',
                        };
                    }
                });
            }
        },

        /**
         * Construit l'objet blocks à envoyer au service / preset.
         */
        buildBlocks() {
            return {
                subject:     this.subject,
                test_email:  this.test_email,
                extra_notes: this.extra_notes,
                sections:    this.sections,
            };
        },

        async generatePrompt() {
            this.loading = true;
            try {
                const res = await fetch('{{ route('admin.newsletter.prompt-builder.compile') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ blocks: this.buildBlocks() }),
                });
                const data = await res.json();
                if (res.ok && data.prompt) {
                    this.promptText = data.prompt;
                    window.dispatchEvent(new CustomEvent('toast-show', {
                        detail: { message: 'Prompt généré !', variant: 'success', duration: 2500 }
                    }));
                } else {
                    window.dispatchEvent(new CustomEvent('toast-show', {
                        detail: { message: data.error ?? 'Erreur lors de la génération.', variant: 'danger', duration: 5000 }
                    }));
                }
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast-show', {
                    detail: { message: 'Erreur réseau.', variant: 'danger', duration: 5000 }
                }));
            } finally {
                this.loading = false;
            }
        },

        async loadPreset(id) {
            try {
                const res = await fetch(
                    '{{ url('admin/newsletter/prompt-builder/presets') }}/' + id,
                    { headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''
                    }}
                );
                const data = await res.json();
                if (res.ok && data.preset) {
                    this.applyBlocks(data.preset.blocks ?? {});
                    window.dispatchEvent(new CustomEvent('toast-show', {
                        detail: { message: 'Preset chargé.', variant: 'info', duration: 2500 }
                    }));
                } else {
                    window.dispatchEvent(new CustomEvent('toast-show', {
                        detail: { message: 'Erreur lors du chargement.', variant: 'danger', duration: 4000 }
                    }));
                }
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast-show', {
                    detail: { message: 'Erreur réseau.', variant: 'danger', duration: 4000 }
                }));
            }
        },

        async copyPrompt() {
            if (!this.promptText) return;
            try {
                await navigator.clipboard.writeText(this.promptText);
                window.dispatchEvent(new CustomEvent('toast-show', {
                    detail: { message: 'Prompt copié dans le presse-papiers !', variant: 'success', duration: 3000 }
                }));
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast-show', {
                    detail: { message: 'Échec de la copie — sélectionnez le texte manuellement.', variant: 'warning', duration: 4000 }
                }));
            }
        },

        injectBlocksIntoPresetForm() {
            const input = document.getElementById('presetBlocksInput');
            if (input) {
                input.value = JSON.stringify(this.buildBlocks());
            }
        },
    };
}
</script>
@endsection
