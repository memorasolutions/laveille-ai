<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@php
    $shareData = $tool->getShareData();

    // Gabarits de sections de départ (BOUTONS seulement — le contenu réel de chaque gabarit
    // est injecté par prompteur-core.js dans Alpine.data('prompteurApp'), pas ici : cette vue
    // ne fait que poser les boutons déclencheurs avec des ids stables).
    $prTemplates = [
        ['key' => 'tutoriel', 'label' => 'Tutoriel logiciel', 'icon' => '🖥️'],
        ['key' => 'actualites', 'label' => 'Capsule actualités', 'icon' => '📰'],
        ['key' => 'formation', 'label' => 'Formation / cours', 'icon' => '🎓'],
        ['key' => 'vide', 'label' => 'Vide', 'icon' => '📄'],
    ];
@endphp

@section('title', $tool->name . ' - ' . config('app.name'))
@section('meta_description', $shareData['meta_description'] ?? 'Prompteur gratuit : générez un script vidéo structuré avec votre IA (BYOA), éditez-le en sections Action/Texte, puis lisez-le en mode téléprompteur plein écran. 100 % gratuit, 100 % dans votre navigateur.')
@section('og_type', $shareData['og_type'] ?? 'website')
@section('og_image', $shareData['og_image'] ?? '')
@section('share_text', $shareData['share_text'] ?? '')

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $tool->name, 'breadcrumbItems' => [__('Outils'), $tool->name]])
@endsection

@push('head')
<link rel="stylesheet" href="{{ asset('assets/tools/prompteur/prompteur.css') }}?v={{ config('version.semver') }}">
<link rel="canonical" href="{{ url('/outils/prompteur') }}">
@endpush

@section('content')
<section class="wpo-blog-single-section section-padding">
    {{-- Même gabarit que tous les outils "carte unique" du site (minuteur-visuel, constructeur-prompts,
         etc.) : .container > .row.justify-content-center > .col-lg-10.col-12. --}}
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-12">
                @include('tools::public.partials.tool-geo')

                <div class="card shadow-sm tool-fullscreen-target" id="prompteur-fullscreen-target" style="border-radius: var(--r-base);">
                    <div class="card-body p-4 p-md-5 pr-wrap"
                         id="prompteur-app-root"
                         x-data="prompteurApp()"
                         x-init="init()"
                         :class="{ 'pr-reduced-motion': reducedMotion, 'pr-compact': compactView, 'pr-high-contrast': highContrast, [`pr-text-${textSize}`]: true }"
                         :data-theme="themePreference">

                        {{-- En-tête : titre + actions (plein écran / partage / réglages) --}}
                        <div class="d-flex justify-content-between align-items-start mb-2 pr-header-row">
                            <h1 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); margin: 0;">{{ $tool->name }}</h1>
                            <div class="d-flex gap-1 align-items-center" style="flex-shrink:0;">
                                <button type="button"
                                        id="prompteur-settings-toggle-btn"
                                        class="ct-btn ct-btn-ghost ct-btn-icon"
                                        style="border-radius:50%;"
                                        @click="openSettings()"
                                        aria-haspopup="dialog"
                                        :aria-expanded="settingsOpen.toString()"
                                        aria-controls="prompteur-settings-panel"
                                        title="{{ __('Réglages du Prompteur') }}"
                                        aria-label="{{ __('Réglages du Prompteur') }}">
                                    <span aria-hidden="true">⚙️</span>
                                </button>
                                @include('tools::partials.fullscreen-btn', ['targetId' => '#prompteur-fullscreen-target'])
                                @include('tools::partials.share-btn', ['tool' => $tool])
                            </div>
                        </div>
                        <p class="text-muted mb-3">{{ $tool->description }}</p>

                        {{-- ═══════════════════════════════ ONGLETS (ARIA tablist) ═══════════════════════════════
                             Pattern conforme WCAG 2.2 AAA : role="tablist"/"tab"/"tabpanel" + navigation clavier
                             flèches gauche/droite/Home/End déléguée à handleTabKeydown() (Alpine, JS à venir). --}}
                        <div role="tablist"
                             id="prompteur-tablist"
                             aria-label="{{ __('Étapes du Prompteur') }}"
                             class="pr-tablist">
                            <button type="button"
                                    role="tab"
                                    id="prompteur-tab-byoa"
                                    class="pr-tab"
                                    :class="{ 'pr-tab--active': activeTab === 'byoa' }"
                                    aria-controls="prompteur-panel-byoa"
                                    :aria-selected="(activeTab === 'byoa').toString()"
                                    :tabindex="activeTab === 'byoa' ? 0 : -1"
                                    @click="setActiveTab('byoa')"
                                    @keydown="handleTabKeydown($event, 'byoa')">
                                <span class="pr-tab__num" aria-hidden="true">1</span>
                                <span>{{ __('Générer avec votre IA') }}</span>
                            </button>
                            <button type="button"
                                    role="tab"
                                    id="prompteur-tab-edit"
                                    class="pr-tab"
                                    :class="{ 'pr-tab--active': activeTab === 'edit' }"
                                    aria-controls="prompteur-panel-edit"
                                    :aria-selected="(activeTab === 'edit').toString()"
                                    :tabindex="activeTab === 'edit' ? 0 : -1"
                                    @click="setActiveTab('edit')"
                                    @keydown="handleTabKeydown($event, 'edit')">
                                <span class="pr-tab__num" aria-hidden="true">2</span>
                                <span>{{ __('Importer / Éditer le script') }}</span>
                            </button>
                            <button type="button"
                                    role="tab"
                                    id="prompteur-tab-teleprompter"
                                    class="pr-tab"
                                    :class="{ 'pr-tab--active': activeTab === 'teleprompter' }"
                                    aria-controls="prompteur-panel-teleprompter"
                                    :aria-selected="(activeTab === 'teleprompter').toString()"
                                    :tabindex="activeTab === 'teleprompter' ? 0 : -1"
                                    @click="setActiveTab('teleprompter')"
                                    @keydown="handleTabKeydown($event, 'teleprompter')">
                                <span class="pr-tab__num" aria-hidden="true">3</span>
                                <span>{{ __('Téléprompteur') }}</span>
                            </button>
                        </div>

                        {{-- ═══════════════════════ ONGLET 1 — BYOA (Bring Your Own AI) ═══════════════════════ --}}
                        <div role="tabpanel"
                             id="prompteur-panel-byoa"
                             aria-labelledby="prompteur-tab-byoa"
                             tabindex="0"
                             class="pr-panel"
                             x-show="activeTab === 'byoa'">

                            <form id="prompteur-byoa-form" class="pr-byoa-form" @submit.prevent="generatePrompt()">
                                <div class="pr-field">
                                    <label for="prompteur-goal-textarea">{{ __('Objectif de la vidéo') }}</label>
                                    <textarea id="prompteur-goal-textarea"
                                              class="pr-textarea"
                                              rows="2"
                                              x-model="form.goal"
                                              @input="generatePrompt()"
                                              placeholder="{{ __('Ex. : Expliquer comment configurer une adresse courriel Gmail pour la classe') }}"></textarea>
                                </div>
                                <div class="pr-field-row">
                                    <div class="pr-field">
                                        <label for="prompteur-audience-input">{{ __('Public cible') }}</label>
                                        <input type="text"
                                               id="prompteur-audience-input"
                                               class="pr-input"
                                               x-model="form.audience"
                                               @input="generatePrompt()"
                                               placeholder="{{ __('Ex. : Élèves du secondaire 3') }}">
                                    </div>
                                    <div class="pr-field">
                                        <label for="prompteur-duration-select">{{ __('Durée visée') }}</label>
                                        <select id="prompteur-duration-select" class="pr-select" x-model="form.duration" @change="generatePrompt()">
                                            <option value="2-3">{{ __('2 à 3 minutes') }}</option>
                                            <option value="5-10">{{ __('5 à 10 minutes') }}</option>
                                            <option value="autre">{{ __('Autre') }}</option>
                                        </select>
                                        <input type="text"
                                               id="prompteur-duration-custom-input"
                                               class="pr-input mt-1"
                                               x-show="form.duration === 'autre'"
                                               x-cloak
                                               x-model="form.durationCustom"
                                               @input="generatePrompt()"
                                               placeholder="{{ __('Ex. : environ 15 minutes') }}"
                                               aria-label="{{ __('Durée personnalisée') }}">
                                    </div>
                                    <div class="pr-field">
                                        <label for="prompteur-tone-select">{{ __('Ton') }}</label>
                                        <select id="prompteur-tone-select" class="pr-select" x-model="form.tone" @change="generatePrompt()">
                                            <option value="professionnel">{{ __('Professionnel') }}</option>
                                            <option value="pedagogique">{{ __('Pédagogique') }}</option>
                                            <option value="conversationnel">{{ __('Conversationnel') }}</option>
                                            <option value="humoristique">{{ __('Humoristique') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </form>

                            <div class="pr-field">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label for="prompteur-generated-prompt-textarea">{{ __('Votre méta-prompt (personnalisé automatiquement ci-dessus)') }}</label>
                                </div>
                                <textarea id="prompteur-generated-prompt-textarea"
                                          class="pr-textarea pr-generated-prompt"
                                          rows="10"
                                          readonly
                                          x-model="generatedPrompt"
                                          aria-label="{{ __('Méta-prompt généré, prêt à copier') }}"></textarea>
                                <div class="pr-copy-row">
                                    <button type="button"
                                            id="prompteur-copy-prompt-btn"
                                            class="ct-btn ct-btn-primary"
                                            @click="copyPromptToClipboard()">
                                        <span aria-hidden="true">📋</span> {{ __('Copier le prompt') }}
                                    </button>
                                    <span id="prompteur-copy-confirm"
                                          class="pr-copy-confirm"
                                          role="status"
                                          aria-live="polite"
                                          x-cloak
                                          x-show="promptCopied">{{ __('Copié !') }}</span>
                                </div>
                            </div>

                            {{-- Piège Blade connu de ce projet : ne jamais écrire le mot-clé de directive PHP
                                 en toutes lettres dans un commentaire Blade (il serait interprété comme du
                                 vrai code et avalerait le bloc suivant). Instructions numérotées ci-dessous. --}}
                            <ol id="prompteur-byoa-steps" class="pr-byoa-steps">
                                <li>{{ __('Personnalisez et copiez ce prompt.') }}</li>
                                <li>{{ __('Collez-le dans votre IA préférée (ChatGPT, Claude, Gemini...).') }}</li>
                                <li>{{ __('Copiez sa réponse et collez-la dans l\'onglet suivant.') }}</li>
                            </ol>

                            <div class="pr-panel-actions">
                                <button type="button" id="prompteur-goto-import-btn" class="ct-btn ct-btn-accent" @click="setActiveTab('edit')">
                                    {{ __('Passer à l\'import') }} <span aria-hidden="true">→</span>
                                </button>
                            </div>
                        </div>

                        {{-- ═══════════════════════ ONGLET 2 — Importer / Éditer le script ═══════════════════════ --}}
                        <div role="tabpanel"
                             id="prompteur-panel-edit"
                             aria-labelledby="prompteur-tab-edit"
                             tabindex="0"
                             class="pr-panel"
                             x-show="activeTab === 'edit'">

                            <div class="pr-field">
                                <label for="prompteur-import-textarea">{{ __('Collez ici la réponse de votre IA') }}</label>
                                <textarea id="prompteur-import-textarea"
                                          class="pr-textarea"
                                          rows="8"
                                          x-model="importRawText"
                                          placeholder="{{ __('Collez le script généré par votre IA ici...') }}"
                                          aria-describedby="prompteur-import-status"></textarea>
                                <div class="pr-import-actions">
                                    <button type="button" id="prompteur-import-btn" class="ct-btn ct-btn-primary" @click="importScript()">
                                        {{ __('Importer') }}
                                    </button>
                                    <button type="button" id="prompteur-paste-clipboard-btn" class="ct-btn ct-btn-outline" @click="pasteFromClipboard()">
                                        <span aria-hidden="true">📥</span> {{ __('Coller depuis le presse-papier') }}
                                    </button>
                                </div>
                                <p id="prompteur-import-status" class="pr-import-status" role="status" aria-live="polite" x-text="importStatusMessage" x-show="importStatusMessage" x-cloak></p>
                            </div>

                            <hr class="pr-divider">

                            {{-- Gabarits prêts à l'emploi --}}
                            <div class="pr-templates" role="group" aria-label="{{ __('Gabarits de sections prêts à l\'emploi') }}">
                                @foreach($prTemplates as $t)
                                    <button type="button"
                                            id="prompteur-template-{{ $t['key'] }}"
                                            class="ct-btn ct-btn-outline ct-btn-sm"
                                            @click="loadTemplate('{{ $t['key'] }}')">
                                        <span aria-hidden="true">{{ $t['icon'] }}</span> {{ $t['label'] }}
                                    </button>
                                @endforeach
                            </div>

                            {{-- Barre d'actions projet --}}
                            <div class="pr-project-actions" role="group" aria-label="{{ __('Actions sur le projet') }}">
                                <button type="button" id="prompteur-new-project-btn" class="ct-btn ct-btn-outline ct-btn-sm" @click="newProject()">
                                    {{ __('Nouveau projet') }}
                                </button>
                                <button type="button" id="prompteur-duplicate-project-btn" class="ct-btn ct-btn-outline ct-btn-sm" @click="duplicateProject()">
                                    {{ __('Dupliquer le projet') }}
                                </button>
                                <button type="button" id="prompteur-export-json-btn" class="ct-btn ct-btn-outline ct-btn-sm" @click="exportProjectJson()">
                                    {{ __('Exporter le projet (.json)') }}
                                </button>
                                <button type="button" id="prompteur-import-json-btn" class="ct-btn ct-btn-outline ct-btn-sm" @click="$refs.prompteurImportJsonFile.click()">
                                    {{ __('Importer un projet (.json)') }}
                                </button>
                                <input type="file"
                                       id="prompteur-import-json-file-input"
                                       x-ref="prompteurImportJsonFile"
                                       accept="application/json"
                                       class="pr-visually-hidden"
                                       @change="importProjectJson($event)"
                                       aria-label="{{ __('Choisir un fichier de projet .json à importer') }}">
                                <button type="button" id="prompteur-export-text-btn" class="ct-btn ct-btn-outline ct-btn-sm" @click="exportProjectText()">
                                    {{ __('Exporter en .md/.txt') }}
                                </button>
                            </div>

                            {{-- Éditeur de sections — peuplé par Alpine (x-for), structure DOM figée ici. --}}
                            <div id="prompteur-sections-list" class="pr-sections-list" role="list" aria-label="{{ __('Sections du script') }}">
                                <template x-for="(section, index) in sections" :key="section.id">
                                    <div class="pr-section-card" role="listitem" :id="'prompteur-section-' + section.id" :data-section-id="section.id" :data-section-index="index">

                                        <div class="pr-section-card__header">
                                            <span class="pr-section-card__index" aria-hidden="true" x-text="index + 1"></span>
                                            <div class="pr-field pr-field--inline">
                                                <label :for="'prompteur-section-title-' + section.id">{{ __('Titre de la section') }}</label>
                                                <input type="text" :id="'prompteur-section-title-' + section.id" class="pr-input" x-model="section.title">
                                            </div>
                                            <div class="pr-field pr-field--inline pr-field--duration">
                                                <label :for="'prompteur-section-duration-' + section.id">{{ __('Durée estimée') }}</label>
                                                <input type="text" :id="'prompteur-section-duration-' + section.id" class="pr-input pr-input--sm" x-model="section.durationEstimate" placeholder="{{ __('Ex. : 30 s') }}">
                                            </div>

                                            <div class="pr-section-card__toolbar" role="group" :aria-label="'{{ __('Actions pour la section') }} ' + (index + 1)">
                                                <button type="button" :id="'prompteur-section-' + section.id + '-move-up-btn'" class="pr-icon-btn" @click="moveSectionUp(index)" :disabled="index === 0" aria-label="{{ __('Déplacer cette section vers le haut') }}" title="{{ __('Monter') }}">
                                                    <span aria-hidden="true">↑</span>
                                                </button>
                                                <button type="button" :id="'prompteur-section-' + section.id + '-move-down-btn'" class="pr-icon-btn" @click="moveSectionDown(index)" :disabled="index === sections.length - 1" aria-label="{{ __('Déplacer cette section vers le bas') }}" title="{{ __('Descendre') }}">
                                                    <span aria-hidden="true">↓</span>
                                                </button>
                                                <button type="button" :id="'prompteur-section-' + section.id + '-duplicate-btn'" class="pr-icon-btn" @click="duplicateSection(index)" aria-label="{{ __('Dupliquer cette section') }}" title="{{ __('Dupliquer') }}">
                                                    <span aria-hidden="true">⧉</span>
                                                </button>
                                                <button type="button" :id="'prompteur-section-' + section.id + '-delete-btn'" class="pr-icon-btn pr-icon-btn--danger" @click="deleteSection(index)" aria-label="{{ __('Supprimer cette section') }}" title="{{ __('Supprimer') }}">
                                                    <span aria-hidden="true">🗑️</span>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Deux colonnes visuellement distinctes : Action/Visuel vs Ce que vous dites. --}}
                                        <div class="pr-section-card__body">
                                            <div class="pr-section-col pr-section-col--visual">
                                                <p class="pr-section-col__label"><span aria-hidden="true">🎥</span> {{ __('Action / Visuel (ce qu\'on montre à l\'écran)') }}</p>
                                                <label :for="'prompteur-section-visual-' + section.id" class="pr-visually-hidden">{{ __('Action ou visuel de la section') }}</label>
                                                <textarea :id="'prompteur-section-visual-' + section.id" class="pr-textarea pr-textarea--visual" rows="3" x-model="section.visual" placeholder="{{ __('Ex. : Capture d\'écran de la boîte de réception Gmail') }}"></textarea>
                                            </div>
                                            <div class="pr-section-col pr-section-col--script">
                                                <div class="pr-section-col__header">
                                                    <p class="pr-section-col__label"><span aria-hidden="true">🗣️</span> {{ __('Ce que vous dites') }}</p>
                                                    <div class="pr-mode-toggle" role="radiogroup" :aria-label="'{{ __('Mode du texte pour la section') }} ' + (index + 1)">
                                                        <input type="radio" :id="'prompteur-section-' + section.id + '-mode-exact'" :name="'prompteur-section-' + index + '-mode'" value="exact" x-model="section.mode">
                                                        <label :for="'prompteur-section-' + section.id + '-mode-exact'">{{ __('Texte exact') }}</label>
                                                        <input type="radio" :id="'prompteur-section-' + section.id + '-mode-outline'" :name="'prompteur-section-' + index + '-mode'" value="outline" x-model="section.mode">
                                                        <label :for="'prompteur-section-' + section.id + '-mode-outline'">{{ __('Grandes lignes') }}</label>
                                                    </div>
                                                </div>
                                                <label :for="'prompteur-section-script-' + section.id" class="pr-visually-hidden">{{ __('Texte à dire pour la section') }}</label>
                                                <textarea :id="'prompteur-section-script-' + section.id" class="pr-textarea pr-textarea--script" rows="5" x-model="section.script" placeholder="{{ __('Ce que vous dites à voix haute...') }}"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="sections.length === 0">
                                    <p id="prompteur-sections-empty" class="pr-sections-empty">{{ __('Aucune section pour l\'instant. Importez un script, choisissez un gabarit, ou ajoutez une section vide.') }}</p>
                                </template>
                            </div>

                            <div class="pr-panel-actions">
                                <button type="button" id="prompteur-add-section-btn" class="ct-btn ct-btn-outline" @click="addSection()">
                                    <span aria-hidden="true">+</span> {{ __('Ajouter une section') }}
                                </button>
                                <button type="button" id="prompteur-goto-teleprompter-btn" class="ct-btn ct-btn-accent" @click="setActiveTab('teleprompter')" :disabled="sections.length === 0">
                                    {{ __('Passer au téléprompteur') }} <span aria-hidden="true">→</span>
                                </button>
                            </div>
                        </div>

                        {{-- ═══════════════════════ ONGLET 3 — Téléprompteur (lecture plein écran) ═══════════════════════ --}}
                        <div role="tabpanel"
                             id="prompteur-panel-teleprompter"
                             aria-labelledby="prompteur-tab-teleprompter"
                             tabindex="0"
                             class="pr-panel pr-panel--teleprompter tool-fullscreen-target"
                             x-show="activeTab === 'teleprompter'"
                             :class="{ 'pr-mirror-mode': mirrorMode, 'pr-high-contrast-reading': teleprompterHighContrast }">

                            {{-- Compte à rebours 3-2-1 avant démarrage --}}
                            <div id="prompteur-countdown-display" class="pr-countdown" role="status" aria-live="assertive" x-show="countdownValue > 0" x-cloak x-text="countdownValue"></div>

                            <div id="prompteur-reading-area" class="pr-reading-area">
                                <div class="pr-reading-fade pr-reading-fade--top" aria-hidden="true"></div>
                                <div class="pr-reading-guide-line" aria-hidden="true"></div>
                                <div id="prompteur-reading-content" class="pr-reading-content">
                                    <template x-for="(section, index) in sections" :key="'read-' + section.id">
                                        <div class="pr-teleprompter-block" :id="'prompteur-teleprompter-block-' + section.id">
                                            {{-- Badge d'action/visuel — TOUJOURS visuellement distinct du texte à lire
                                                 (« mode réalisateur »), jamais mélangé dans le flux de lecture. --}}
                                            <div class="pr-teleprompter-badge" :id="'prompteur-teleprompter-badge-' + section.id" aria-hidden="true">
                                                <span class="pr-teleprompter-badge__icon">🎥</span>
                                                <span class="pr-teleprompter-badge__text" x-text="section.visual"></span>
                                            </div>
                                            <p class="pr-teleprompter-script" x-text="section.script"></p>
                                        </div>
                                    </template>
                                </div>
                                <div class="pr-reading-fade pr-reading-fade--bottom" aria-hidden="true"></div>
                            </div>

                            {{-- Annonce ARIA sobre pour lecteurs d'écran (état lecture/pause) --}}
                            <div class="pr-live" aria-live="polite" role="status" x-text="teleprompterAriaMessage"></div>

                            {{-- Barre de contrôles --}}
                            <div class="pr-teleprompter-controls" role="group" aria-label="{{ __('Contrôles du téléprompteur') }}">
                                <button type="button" id="prompteur-play-pause-btn" class="ct-btn ct-btn-accent" @click="togglePlayPause()">
                                    <span aria-hidden="true" x-text="teleprompterState === 'running' ? '⏸' : '▶'"></span>
                                    <span x-text="teleprompterState === 'running' ? '{{ __('Pause') }}' : '{{ __('Démarrer') }}'"></span>
                                </button>

                                <div class="pr-control-group">
                                    <label for="prompteur-speed-slider">{{ __('Vitesse') }}</label>
                                    <input type="range" id="prompteur-speed-slider" min="1" max="10" step="1" x-model.number="scrollSpeed" aria-valuetext="{{ __('Vitesse de défilement') }}">
                                </div>

                                <div class="pr-control-group">
                                    <span class="pr-control-group__label" id="prompteur-font-size-label">{{ __('Taille du texte') }}</span>
                                    <button type="button" id="prompteur-font-decrease-btn" class="pr-icon-btn" @click="decreaseReadingFontSize()" aria-label="{{ __('Diminuer la taille du texte') }}" aria-describedby="prompteur-font-size-label">−</button>
                                    <button type="button" id="prompteur-font-increase-btn" class="pr-icon-btn" @click="increaseReadingFontSize()" aria-label="{{ __('Augmenter la taille du texte') }}" aria-describedby="prompteur-font-size-label">+</button>
                                </div>

                                <button type="button" id="prompteur-contrast-toggle-btn" class="ct-btn ct-btn-outline ct-btn-sm" @click="toggleTeleprompterContrast()" :aria-pressed="teleprompterHighContrast.toString()">
                                    {{ __('Contraste') }}
                                </button>

                                <button type="button" id="prompteur-mirror-toggle-btn" class="ct-btn ct-btn-outline ct-btn-sm" @click="toggleMirrorMode()" :aria-pressed="mirrorMode.toString()">
                                    {{ __('Mode miroir') }}
                                </button>

                                @include('tools::partials.fullscreen-btn', ['targetId' => '#prompteur-panel-teleprompter'])

                                <button type="button" id="prompteur-voice-scroll-btn" class="ct-btn ct-btn-outline ct-btn-sm" @click="startVoiceScroll()" :aria-pressed="voiceScrollActive.toString()">
                                    <span aria-hidden="true">🎙️</span> {{ __('Démarrer le défilement à la voix') }}
                                    <span id="prompteur-voice-scroll-badge" class="pr-badge-experimental">{{ __('expérimental') }}</span>
                                </button>
                            </div>

                            {{-- Barre de progression + temps restant --}}
                            <div class="pr-progress-row">
                                <progress id="prompteur-progress-bar" class="pr-progress-bar" :value="teleprompterProgress" max="100"></progress>
                                <span id="prompteur-time-remaining" class="pr-time-remaining" x-text="teleprompterTimeRemainingLabel"></span>
                            </div>

                            {{-- Raccourcis clavier — légende accessible, pas décorative. --}}
                            <div id="prompteur-keyboard-shortcuts-legend" class="pr-shortcuts-legend">
                                <p class="pr-shortcuts-legend__title">{{ __('Raccourcis clavier') }}</p>
                                <ul>
                                    <li><kbd>{{ __('Espace') }}</kbd> {{ __('= pause / lecture') }}</li>
                                    <li><kbd>←</kbd> <kbd>→</kbd> {{ __('= vitesse') }}</li>
                                    <li><kbd>↑</kbd> <kbd>↓</kbd> {{ __('= taille du texte') }}</li>
                                    <li><kbd>F</kbd> {{ __('= plein écran') }}</li>
                                    <li><kbd>Échap</kbd> {{ __('= quitter') }}</li>
                                </ul>
                            </div>
                        </div>

                        {{-- ═══════════════════════ Panneau de personnalisation (accessible de partout) ═══════════════════════ --}}
                        <template x-if="settingsOpen">
                            <div>
                                <div id="prompteur-settings-backdrop" class="pr-settings-backdrop" @click="closeSettings()"></div>
                                <div id="prompteur-settings-panel"
                                     class="pr-settings-panel"
                                     role="dialog"
                                     aria-modal="true"
                                     aria-labelledby="prompteur-settings-title"
                                     @keydown.escape.window="closeSettings()">
                                    <div class="pr-settings-panel__header">
                                        <h2 id="prompteur-settings-title">{{ __('Réglages et personnalisation') }}</h2>
                                        <button type="button" id="prompteur-settings-close-btn" class="pr-icon-btn" @click="closeSettings()" aria-label="{{ __('Fermer les réglages') }}">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>

                                    <div class="pr-settings-panel__body">
                                        <fieldset class="pr-settings-field">
                                            <legend>{{ __('Thème') }}</legend>
                                            <div class="pr-radio-row">
                                                <input type="radio" id="prompteur-theme-clair" name="prompteur-theme" value="clair" x-model="themePreference">
                                                <label for="prompteur-theme-clair">{{ __('Clair') }}</label>
                                                <input type="radio" id="prompteur-theme-sombre" name="prompteur-theme" value="sombre" x-model="themePreference">
                                                <label for="prompteur-theme-sombre">{{ __('Sombre') }}</label>
                                                <input type="radio" id="prompteur-theme-systeme" name="prompteur-theme" value="systeme" x-model="themePreference">
                                                <label for="prompteur-theme-systeme">{{ __('Système') }}</label>
                                            </div>
                                        </fieldset>

                                        <div class="pr-settings-field pr-settings-row">
                                            <label for="prompteur-high-contrast-toggle">{{ __('Contraste renforcé') }}</label>
                                            <input type="checkbox" id="prompteur-high-contrast-toggle" x-model="highContrast">
                                        </div>

                                        <div class="pr-settings-field">
                                            <label for="prompteur-text-size-select">{{ __('Taille de texte de base') }}</label>
                                            <select id="prompteur-text-size-select" class="pr-select" x-model="textSize">
                                                <option value="petit">{{ __('Petit') }}</option>
                                                <option value="moyen">{{ __('Moyen') }}</option>
                                                <option value="grand">{{ __('Grand') }}</option>
                                            </select>
                                        </div>

                                        <div class="pr-settings-field pr-settings-row">
                                            <label for="prompteur-compact-view-toggle">{{ __('Vue compacte') }}</label>
                                            <input type="checkbox" id="prompteur-compact-view-toggle" x-model="compactView">
                                        </div>

                                        <div class="pr-settings-field pr-settings-row">
                                            <label for="prompteur-reduced-motion-toggle">{{ __('Réduire les animations') }}</label>
                                            <input type="checkbox" id="prompteur-reduced-motion-toggle" x-model="reducedMotion">
                                        </div>
                                    </div>

                                    <div class="pr-settings-panel__footer">
                                        <button type="button" id="prompteur-settings-reset-btn" class="ct-btn ct-btn-outline ct-btn-sm" @click="resetSettings()">
                                            {{ __('Réinitialiser les réglages') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('fronttheme::partials.tools-newsletter-cta', ['toolSource' => 'prompteur'])
@endsection

@push('scripts')
<script src="{{ asset('assets/tools/prompteur/prompteur-core.js') }}?v={{ config('version.semver') }}" defer></script>
@endpush
