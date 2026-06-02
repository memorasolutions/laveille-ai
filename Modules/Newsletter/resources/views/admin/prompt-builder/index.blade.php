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

<style>
/* ===== STEPPER ===== */
.pb-stepper {
    display: flex;
    align-items: flex-start;
    gap: 0;
    margin-bottom: 1.5rem;
    overflow-x: auto;
    padding-bottom: 4px;
}
.pb-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    min-width: 0;
    position: relative;
    cursor: pointer;
    border: none;
    background: none;
    padding: 0;
    text-decoration: none;
    color: inherit;
}
.pb-step:focus-visible {
    outline: 2px solid var(--sys-action-accent, #9A2A06);
    outline-offset: 2px;
    border-radius: 4px;
}
/* Connector line between steps */
.pb-step::before {
    content: '';
    position: absolute;
    top: 18px;
    left: calc(-50% + 20px);
    right: calc(50% + 20px);
    height: 2px;
    background: #dee2e6;
    z-index: 0;
}
.pb-step:first-child::before { display: none; }
.pb-step[data-active="true"]::before,
.pb-step[data-done="true"]::before { background: var(--sys-primary, #064E5A); }

.pb-step-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.85rem;
    border: 2px solid #dee2e6;
    background: #fff;
    color: var(--c-text-muted, #52586a);
    position: relative;
    z-index: 1;
    transition: background 0.2s, border-color 0.2s, color 0.2s;
    flex-shrink: 0;
}
.pb-step[data-active="true"] .pb-step-circle {
    background: var(--sys-primary, #064E5A);
    border-color: var(--sys-primary, #064E5A);
    color: #fff;
}
.pb-step[data-done="true"] .pb-step-circle {
    background: var(--sys-primary, #064E5A);
    border-color: var(--sys-primary, #064E5A);
    color: #fff;
}
.pb-step-label {
    font-size: 0.72rem;
    color: var(--c-text-muted, #52586a);
    margin-top: 5px;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100px;
}
.pb-step[data-active="true"] .pb-step-label {
    color: var(--sys-primary, #064E5A);
    font-weight: 600;
}
.pb-step[data-done="true"] .pb-step-label {
    color: var(--sys-primary, #064E5A);
}

/* ===== PANELS ===== */
.pb-panel { display: none; }
.pb-panel[data-visible="true"] { display: block; }

/* ===== PREVIEW sticky ===== */
.pb-preview-sticky {
    position: sticky;
    top: 80px;
}
</style>

<div
    x-data="promptBuilder()"
    x-init="init()"
    class="row gy-4"
>
    {{-- ===== COLONNE GAUCHE : stepper + formulaire ===== --}}
    <div class="col-lg-7">

        {{-- ===== STEPPER HORIZONTAL ===== --}}
        <nav aria-label="Étapes du générateur de prompt" class="pb-stepper" role="tablist">
            <template x-for="(step, idx) in steps" :key="step.id">
                <button
                    type="button"
                    class="pb-step"
                    role="tab"
                    :id="'pb-tab-' + step.id"
                    :aria-controls="'pb-panel-' + step.id"
                    :aria-selected="currentStep === idx + 1"
                    :aria-current="currentStep === idx + 1 ? 'step' : undefined"
                    :data-active="currentStep === idx + 1 ? 'true' : 'false'"
                    :data-done="currentStep > idx + 1 ? 'true' : 'false'"
                    :data-idx="idx"
                    x-on:click="goToStep(idx + 1)"
                    x-on:keydown.arrow-right.prevent="focusAdjacentTab($event, 1)"
                    x-on:keydown.arrow-left.prevent="focusAdjacentTab($event, -1)"
                    x-on:keydown.home.prevent="goToStep(1)"
                    x-on:keydown.end.prevent="goToStep(steps.length)"
                    :tabindex="currentStep === idx + 1 ? 0 : -1"
                >
                    <span class="pb-step-circle" :aria-hidden="true">
                        <template x-if="currentStep > idx + 1">
                            <i data-lucide="check" style="width:16px;height:16px;"></i>
                        </template>
                        <template x-if="currentStep <= idx + 1">
                            <span x-text="idx + 1"></span>
                        </template>
                    </span>
                    <span class="pb-step-label" x-text="step.label"></span>
                </button>
            </template>
        </nav>

        {{-- ===== PANEL 1 : Éditorial ===== --}}
        <div
            id="pb-panel-editorial"
            role="tabpanel"
            :aria-labelledby="'pb-tab-editorial'"
            class="pb-panel"
            :data-visible="currentStep === 1 ? 'true' : 'false'"
        >
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i data-lucide="edit-3" class="text-primary" style="width:16px;height:16px;"></i>
                    <h6 class="mb-0">Éditorial</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="pb_subject" class="form-label">Sujet / Titre de la newsletter</label>
                        <input type="text" id="pb_subject" x-model="subject" class="form-control"
                               placeholder="ex: L'IA générative transforme le travail de bureau au Québec">
                    </div>
                    <div class="mb-3">
                        <label for="pb_angle" class="form-label">Angle rédactionnel</label>
                        <input type="text" id="pb_angle" x-model="angle" class="form-control"
                               placeholder="ex: Gains de productivité concrets pour les PME">
                    </div>
                    <div class="mb-3">
                        <label for="pb_tone" class="form-label">Tonalité</label>
                        <select id="pb_tone" x-model="tone" class="form-select">
                            <option value="Professionnel et chaleureux (QC)">Professionnel et chaleureux (QC)</option>
                            <option value="Informatif et concis">Informatif et concis</option>
                            <option value="Inspirant et motivateur">Inspirant et motivateur</option>
                            <option value="Technique et expert">Technique et expert</option>
                            <option value="Conversationnel et accessible">Conversationnel et accessible</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="pb_audience" class="form-label">Public cible</label>
                        <input type="text" id="pb_audience" x-model="audience" class="form-control"
                               placeholder="ex: professionnels québécois en veille stratégique IA">
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== PANEL 2 : Défi de la semaine ===== --}}
        <div
            id="pb-panel-defi"
            role="tabpanel"
            :aria-labelledby="'pb-tab-defi'"
            class="pb-panel"
            :data-visible="currentStep === 2 ? 'true' : 'false'"
        >
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i data-lucide="zap" class="text-warning" style="width:16px;height:16px;"></i>
                    <h6 class="mb-0">Défi de la semaine</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="pb_challenge" class="form-label">Consigne du défi <small class="text-muted">(micro-action mesurable)</small></label>
                        <textarea id="pb_challenge" x-model="challenge_instruction" rows="2" class="form-control"
                                  placeholder="ex: Essaie de résumer un document de travail avec ChatGPT"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="pb_duration" class="form-label">Durée estimée</label>
                        <input type="text" id="pb_duration" x-model="challenge_duration" class="form-control"
                               placeholder="ex: 10 minutes">
                    </div>
                    <p class="text-muted small mb-0">
                        <i data-lucide="info" style="width:13px;height:13px;"></i>
                        Laisser vide = le service applique le comportement automatique par défaut pour cette section.
                    </p>
                </div>
            </div>
        </div>

        {{-- ===== PANEL 3 : Actualités ===== --}}
        <div
            id="pb-panel-articles"
            role="tabpanel"
            :aria-labelledby="'pb-tab-articles'"
            class="pb-panel"
            :data-visible="currentStep === 3 ? 'true' : 'false'"
        >
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i data-lucide="newspaper" class="text-info" style="width:16px;height:16px;"></i>
                    <h6 class="mb-0">Articles / Concentrés <small class="text-muted fw-normal">(30 derniers jours)</small></h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <label for="pb_articles" class="form-label">
                            Sélectionner les articles à inclure
                            <small class="text-muted">(Ctrl+clic ou Cmd+clic pour multi-select)</small>
                        </label>
                        <select id="pb_articles" multiple class="form-select" style="height: 180px;">
                            @if($recentNewsArticles->isNotEmpty())
                                <optgroup label="Actualités IA">
                                    @foreach($recentNewsArticles as $article)
                                        <option value="{{ $article->url ?? '' }}">{{ $article->seo_title ?? $article->title }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if($recentBlogArticles->isNotEmpty())
                                <optgroup label="Articles de blogue">
                                    @foreach($recentBlogArticles as $article)
                                        <option value="{{ url('/blog/' . $article->slug) }}">{{ $article->title }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            x-on:click="deselectAll()" aria-label="Tout désélectionner">
                        Tout désélectionner
                    </button>
                    <p class="text-muted small mt-2 mb-0">
                        <i data-lucide="info" style="width:13px;height:13px;"></i>
                        Aucune sélection = le service applique le comportement automatique par défaut pour les actualités.
                    </p>
                </div>
            </div>
        </div>

        {{-- ===== PANEL 4 : Sections personnalisées ===== --}}
        <div
            id="pb-panel-sections"
            role="tabpanel"
            :aria-labelledby="'pb-tab-sections'"
            class="pb-panel"
            :data-visible="currentStep === 4 ? 'true' : 'false'"
        >
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i data-lucide="layout" class="text-secondary" style="width:16px;height:16px;"></i>
                    <h6 class="mb-0">Sections personnalisées</h6>
                </div>
                <div class="card-body">
                    <template x-for="(section, i) in sections" :key="i">
                        <div class="mb-3 border rounded p-3 bg-light">
                            <div class="mb-2">
                                <label :for="'pb_sec_title_' + i" class="form-label"
                                       x-text="'Titre — section ' + (i + 1)"></label>
                                <input :id="'pb_sec_title_' + i"
                                       x-model="sections[i].title"
                                       type="text" class="form-control"
                                       placeholder="ex: Focus outil">
                            </div>
                            <div class="mb-2">
                                <label :for="'pb_sec_content_' + i" class="form-label"
                                       x-text="'Contenu — section ' + (i + 1)"></label>
                                <textarea :id="'pb_sec_content_' + i"
                                          x-model="sections[i].content"
                                          rows="3" class="form-control"
                                          placeholder="Contenu ou instructions pour cette section"></textarea>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    x-on:click="sections.splice(i, 1)"
                                    :aria-label="'Supprimer la section ' + (i + 1)">
                                Supprimer
                            </button>
                        </div>
                    </template>
                    <button type="button" class="btn btn-sm btn-outline-primary"
                            x-on:click="sections.push({title: '', content: ''})">
                        <i data-lucide="plus" style="width:14px;height:14px;"></i>
                        Ajouter une section
                    </button>
                    <p class="text-muted small mt-2 mb-0">
                        <i data-lucide="info" style="width:13px;height:13px;"></i>
                        Aucune section ajoutée = le service applique le comportement automatique par défaut.
                    </p>
                </div>
            </div>
        </div>

        {{-- ===== PANEL 5 : Options & courriel test ===== --}}
        <div
            id="pb-panel-options"
            role="tabpanel"
            :aria-labelledby="'pb-tab-options'"
            class="pb-panel"
            :data-visible="currentStep === 5 ? 'true' : 'false'"
        >
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i data-lucide="sliders" class="text-muted" style="width:16px;height:16px;"></i>
                    <h6 class="mb-0">Options &amp; courriel test</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="pb_wordcount" class="form-label">Longueur cible</label>
                        <select id="pb_wordcount" x-model="word_count" class="form-select">
                            <option value="300-500 mots">Court — 300-500 mots</option>
                            <option value="500-700 mots">Moyen — 500-700 mots</option>
                            <option value="700-900 mots">Long — 700-900 mots</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" id="pb_send_test" x-model="send_test_email" class="form-check-input">
                        <label for="pb_send_test" class="form-check-label">Envoyer un courriel test après génération</label>
                    </div>
                    <div x-show="send_test_email" x-cloak class="mb-3">
                        <label for="pb_test_email" class="form-label">Adresse courriel de test</label>
                        <input type="email" id="pb_test_email" x-model="test_email" class="form-control"
                               placeholder="{{ config('mail.from.address', 'info@example.com') }}">
                    </div>
                    <div class="mb-3">
                        <label for="pb_notes" class="form-label">Notes complémentaires <small class="text-muted">(instructions libres pour Claude)</small></label>
                        <textarea id="pb_notes" x-model="extra_notes" rows="2" class="form-control"
                                  placeholder="ex: Mets l'accent sur les usages pratiques pour les PME québécoises"></textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== NAVIGATION PRÉCÉDENT / SUIVANT ===== --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            {{-- Précédent (masqué à l'étape 1) --}}
            <button
                type="button"
                class="btn btn-outline-secondary d-flex align-items-center gap-2"
                x-show="currentStep > 1"
                x-cloak
                x-on:click="prev()"
            >
                <i data-lucide="chevron-left" style="width:16px;height:16px;"></i>
                Précédent
            </button>
            <div x-show="currentStep === 1" x-cloak style="min-width:1px;"></div>

            {{-- Suivant (étapes 1-4) / Générer + Copier (étape 5) --}}
            <div class="d-flex flex-wrap gap-2">
                <template x-if="currentStep < 5">
                    <button
                        type="button"
                        class="btn btn-primary d-flex align-items-center gap-2"
                        x-on:click="next()"
                    >
                        Suivant
                        <i data-lucide="chevron-right" style="width:16px;height:16px;"></i>
                    </button>
                </template>
                <template x-if="currentStep === 5">
                    <div class="d-flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="btn btn-primary d-flex align-items-center gap-2"
                            x-on:click="generatePrompt()"
                            :disabled="loading"
                        >
                            <span x-show="!loading" x-cloak>
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
                </template>
            </div>
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
                               placeholder="ex: Newsletter PME — ton formel">
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
        // --- État formulaire ---
        subject: '',
        angle: '',
        tone: 'Professionnel et chaleureux (QC)',
        audience: '',
        challenge_instruction: '',
        challenge_duration: '',
        word_count: '300-500 mots',
        send_test_email: false,
        test_email: '',
        extra_notes: '',
        sections: [],

        // --- État UI ---
        promptText: '',
        loading: false,

        // --- Stepper ---
        currentStep: 1,
        steps: [
            { id: 'editorial', label: 'Éditorial' },
            { id: 'defi',      label: 'Défi' },
            { id: 'articles',  label: 'Actualités' },
            { id: 'sections',  label: 'Sections' },
            { id: 'options',   label: 'Options' },
        ],

        goToStep(n) {
            this.currentStep = Math.max(1, Math.min(n, this.steps.length));
            // Re-init lucide icons for newly visible panels
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },
        next() { this.goToStep(this.currentStep + 1); },
        prev() { this.goToStep(this.currentStep - 1); },

        /**
         * WAI-ARIA roving tabindex : déplace le focus ET l'activation vers l'onglet adjacent
         * à celui qui a le focus (pas forcément l'onglet courant).
         * direction: +1 (droite) ou -1 (gauche)
         */
        focusAdjacentTab(event, direction) {
            const currentBtn = event.currentTarget;
            const idx = parseInt(currentBtn.dataset.idx, 10);
            const targetIdx = idx + direction;
            if (targetIdx < 0 || targetIdx >= this.steps.length) return;
            this.goToStep(targetIdx + 1);
            this.$nextTick(() => {
                const targetBtn = document.getElementById('pb-tab-' + this.steps[targetIdx].id);
                if (targetBtn) targetBtn.focus();
            });
        },

        init() {
            // Charger le preset par défaut s'il existe
            const el = document.getElementById('defaultPresetData');
            if (el) {
                try {
                    const data = JSON.parse(el.textContent || '{}');
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
        },

        /**
         * Applique un objet blocks dans le state Alpine.
         */
        applyBlocks(data) {
            if (!data || typeof data !== 'object') return;
            const fields = ['subject', 'angle', 'tone', 'audience', 'challenge_instruction',
                            'challenge_duration', 'word_count', 'send_test_email', 'test_email', 'extra_notes'];
            fields.forEach(f => { if (data[f] !== undefined) this[f] = data[f]; });
            if (Array.isArray(data.sections)) this.sections = data.sections;
            // Restaurer les articles sélectionnés
            this.$nextTick(() => {
                const select = document.getElementById('pb_articles');
                if (select && Array.isArray(data.selected_articles)) {
                    for (const opt of select.options) {
                        opt.selected = data.selected_articles.some(a => a.url === opt.value);
                    }
                }
            });
        },

        /**
         * Construit l'objet blocks à envoyer au service.
         */
        buildBlocks() {
            const select = document.getElementById('pb_articles');
            const selectedArticles = select
                ? Array.from(select.selectedOptions).map(o => ({ title: o.text, url: o.value }))
                : [];
            return {
                subject:               this.subject,
                angle:                 this.angle,
                tone:                  this.tone,
                audience:              this.audience,
                challenge_instruction: this.challenge_instruction,
                challenge_duration:    this.challenge_duration,
                word_count:            this.word_count,
                send_test_email:       this.send_test_email,
                test_email:            this.test_email,
                extra_notes:           this.extra_notes,
                sections:              this.sections,
                selected_articles:     selectedArticles,
            };
        },

        deselectAll() {
            const select = document.getElementById('pb_articles');
            if (select) {
                for (const opt of select.options) opt.selected = false;
            }
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
                    { headers: { 'Accept': 'application/json',
                                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' } }
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

        /**
         * Injecte les blocs actuels dans le champ caché du formulaire modal.
         *
         * Appelée depuis 3 endroits pour garantie maximale :
         *   1. x-on:click sur le bouton « Sauvegarder le preset »
         *   2. Écouteur Bootstrap show.bs.modal dans init()
         *   3. Écouteur natif submit du formulaire dans init() (filet final)
         */
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
