<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@section('no_ads', '1') {{-- Aucune pub : l'outil peut contenir des données personnelles (posture Loi 25) --}}
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
                <div class="card shadow-sm tool-fullscreen-target" style="border-radius: var(--r-base);">
                    <div class="card-body p-4 p-md-5" x-data="promptBuilder()" x-init="init()" @keydown.escape.window="closeIconPicker()">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h1 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); margin: 0;">{{ $tool->name }}</h1>
                                <p class="text-muted mb-0">{{ __('Créez des prompts optimisés pour ChatGPT, Claude, Gemini et autres IA.') }}</p>
                            </div>
                            <div class="d-flex gap-1">
                                @include('tools::partials.fullscreen-btn')
                                @include('tools::partials.share-btn', ['tool' => $tool])
                                <button class="ct-btn ct-btn-primary ct-btn-icon" @click="jQuery('#promptHelpModal').modal('show')" style="border-radius:50%;width:32px;height:32px;padding:0;line-height:32px;flex-shrink:0;" title="{{ __('Aide') }}">?</button>
                            </div>
                        </div>
                        @include('tools::public.partials.tool-geo')
                        {{-- Barre sauvegarde (visible avant les étapes) --}}
                        <div class="mt-3 mb-3 p-3 rounded" x-show="isAuthenticated" x-cloak style="background: var(--c-primary-light); border: 1px solid rgba(11,114,133,0.12); border-radius: 10px;">
                            <div class="d-flex gap-2 align-items-center">
                                <input type="text" class="form-control form-control-sm flex-fill" x-model="saveName" placeholder="{{ __('Nommer ce prompt pour le retrouver...') }}" aria-label="{{ __('Titre du prompt') }}" style="border-radius: 8px;">
                                <button class="ct-btn ct-btn-primary ct-btn-sm" @click="addToHistory()" :disabled="!isValid || saving || !historyLoaded" :aria-busy="saving || !historyLoaded ? 'true' : 'false'" :title="!historyLoaded ? '{{ __('Chargement de votre historique en cours...') }}' : null" style="white-space:nowrap; min-height:44px;"
                                        x-text="saving ? '{{ __('Sauvegarde...') }}' : (_editingId ? '{{ __('Mettre à jour') }}' : '{{ __('Sauvegarder') }}')"></button>
                            </div>
                            {{-- Round 65 (2026-07-27) : annonce ARIA du chargement initial de l'historique
                                 - sans ça, un utilisateur de lecteur d'écran entendait seulement "bouton,
                                 désactivé" pendant la brève fenêtre de chargement, sans savoir pourquoi. --}}
                            <span class="visually-hidden" role="status" x-text="!historyLoaded ? '{{ __('Chargement de votre historique de prompts en cours...') }}' : ''"></span>
                            <div class="small mt-2 mb-0" style="font-size: 0.8rem; color: var(--c-text-muted);">
                                {{-- Round 134 (2026-07-30, passe adversariale) : ce lien menait vers /user/saved, la
                                     page GÉNÉRIQUE tous-outils, qui n'expose qu'un nom, un aperçu de 80 caractères et
                                     une date. C'est la seule invitation contextuelle affichée après une sauvegarde, et
                                     elle contournait /user/prompts - la bibliothèque dédiée construite par cette
                                     refonte (recherche, étiquettes, favoris, duplication, panneau « Mon profil »).
                                     Le commentaire de l'historique plus bas dans ce fichier disait déjà « les
                                     connectés ont Mes prompts » : l'intention existait, le lien ne l'avait pas suivie.
                                     Le libellé nomme désormais la page d'arrivée, pour qu'on sache où l'on va. --}}
                                {{ __('Retrouvez-les dans') }} <a href="{{ route('user.prompts.index') }}" style="color: #0A3A42; font-weight: 600; text-decoration: underline;">{{ __('Mes prompts') }}</a>.
                            </div>
                            <template x-if="hasLocalData">
                                <div class="small mt-2 mb-0" style="font-size: 0.8rem; color: var(--c-text-muted);">
                                    {{ __('Des prompts de votre navigateur ont été trouvés.') }}
                                    <button class="ct-btn ct-btn-outline ct-btn-xs ms-1" style="min-height:44px;" @click="importLocalStorage()" :disabled="importing || !historyLoaded" :aria-busy="importing || !historyLoaded ? 'true' : 'false'" :title="!historyLoaded ? '{{ __('Chargement de votre historique en cours...') }}' : null">{{ __('Importer') }}</button>
                                </div>
                            </template>
                        </div>
                        <template x-if="!isAuthenticated">
                            <div class="mt-3 mb-3 p-2 rounded" style="background: rgba(11,114,133,0.06); border: 1px solid rgba(11,114,133,0.15); border-radius: 10px; font-size: 0.85rem;">
                                <strong style="color: var(--c-primary);">{{ __('Connectez-vous') }}</strong> {{ __('pour sauvegarder vos prompts et les retrouver sur tous vos appareils.') }}
                                <button class="ct-btn ct-btn-primary ct-btn-xs ms-1" style="min-height:44px;" @click="$dispatch('open-auth-modal')">{{ __('Se connecter') }}</button>
                            </div>
                        </template>
                        {{-- Round 20 (2026-07-26) : ce message doit rester visible même déconnecté - exportPrompt()
                             est atteignable par un invité (bouton "Exporter .txt" sans gate isAuthenticated), donc
                             ce template ne peut PAS vivre dans le conteneur x-show="isAuthenticated" ci-dessus
                             (round 10-19 l'y avaient laissé, rendant tout échec d'export silencieux pour un invité). --}}
                        {{-- Round 128 : notice NEUTRE (role=status, aria-live=polite), distincte de
                             l'alerte rouge saveError juste en dessous - conserver le texte de
                             l'utilisateur n'est pas une erreur, c'est une protection. --}}
                        <template x-if="taskNotice">
                            <div class="alert alert-info small p-1 mt-2 mb-0" style="font-size: 0.8rem; border-radius: 6px;" x-text="taskNotice" role="status" aria-live="polite"></div>
                        </template>

                        <template x-if="saveError">
                            <div class="alert alert-danger small p-1 mt-2 mb-0" style="font-size: 0.8rem; border-radius: 6px;" x-text="saveError" role="alert" aria-live="assertive"></div>
                        </template>

                        <style>
                        .ct-pill{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0.5rem 1rem;border:2px solid #d1d5db;border-radius:9999px;background:#fff;color:#374151;font-size:0.875rem;font-weight:500;cursor:pointer;transition:all .15s ease;position:relative;}
                        .ct-pill:hover{border-color:var(--c-primary);color:var(--c-primary);}
                        .ct-pill--on{background:var(--c-primary);border-color:var(--c-primary);color:#fff;}
                        .ct-pill--on:hover{color:#fff;}
                        .ct-pill__input{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;}
                        .ct-pill:focus-within{outline:2px solid var(--c-primary);outline-offset:2px;z-index:1;}
                        .ct-task-card{display:flex;flex-direction:column;gap:2px;text-align:left;width:100%;min-height:76px;padding:0.85rem 1rem;border:2px solid #d1d5db;border-radius:12px;background:#fff;color:var(--c-dark);cursor:pointer;transition:all .15s ease;}
                        .ct-task-card:hover{border-color:var(--c-primary);}
                        .ct-task-card:focus-visible{outline:2px solid var(--c-primary);outline-offset:2px;}
                        .ct-task-card--on{background:var(--c-primary);border-color:var(--c-primary);color:#fff;}
                        .ct-task-card--on .ct-task-card__desc{color:rgba(255,255,255,0.85);}
                        .ct-task-card__title{font-weight:700;font-size:0.95rem;}
                        .ct-task-card__desc{font-size:0.78rem;color:var(--c-text-muted);}
                        .ct-advanced-toggle{display:flex;align-items:center;justify-content:space-between;gap:8px;width:100%;padding:0.75rem 1rem;border:1px dashed rgba(11,114,133,0.35);border-radius:10px;background:var(--c-primary-light);color:var(--c-primary);font-weight:600;font-size:0.9rem;cursor:pointer;min-height:44px;}
                        .ct-advanced-toggle .ct-chevron{transition:transform .2s ease;}
                        .ct-advanced-toggle[aria-expanded="true"] .ct-chevron{transform:rotate(180deg);}
                        /* Cartes personnalisées (Option D, 2026-07-26) */
                        .ct-task-card--custom{cursor:default;position:relative;}
                        .ct-task-card--custom.ct-task-card--hidden-card{opacity:0.5;}
                        .ct-custom-card__head{display:flex;align-items:flex-start;justify-content:space-between;gap:4px;width:100%;flex-wrap:wrap;}
                        .ct-custom-card__icon-btn{flex-shrink:0;width:44px;height:44px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #d1d5db;border-radius:8px;background:#fff;cursor:pointer;padding:0;}
                        .ct-task-card--on .ct-custom-card__icon-btn{background:rgba(255,255,255,0.15);border-color:rgba(255,255,255,0.5);}
                        {{-- WCAG 2.2 AAA (SC 2.5.5) : cibles tactiles >= 44x44 CSS px, y compris les icônes
                             d'action compactes (déplacer/masquer/éditer/supprimer) - jamais de cible < 44px
                             même quand l'icône visuelle est petite. --}}
                        .ct-custom-card__actions{display:flex;flex-wrap:wrap;gap:2px;justify-content:flex-end;}
                        .ct-custom-card__icon-action{width:44px;height:44px;min-width:44px;min-height:44px;display:inline-flex;align-items:center;justify-content:center;border:none;background:transparent;border-radius:6px;cursor:pointer;color:inherit;opacity:0.75;font-size:0.85rem;padding:0;}
                        .ct-custom-card__icon-action:hover{opacity:1;background:rgba(0,0,0,0.06);}
                        .ct-task-card--on .ct-custom-card__icon-action:hover{background:rgba(255,255,255,0.15);}
                        .ct-custom-card__icon-action:focus-visible{outline:2px solid var(--c-primary);outline-offset:1px;}
                        {{-- Round 87 (2026-07-27, passe adversariale) : min-height:28px/32px échouaient
                             la cible tactile WCAG 2.2 AAA SC 2.5.5 (44x44) - remontés à 44px, cohérent
                             avec le fix mécanique des rounds 83-86 sur les autres boutons du fichier. --}}
                        .ct-custom-card__title-btn{background:none;border:none;padding:0;margin:0;text-align:left;font-weight:700;font-size:0.95rem;color:inherit;cursor:text;min-height:44px;display:flex;align-items:center;}
                        .ct-custom-card__title-btn:focus-visible{outline:2px solid var(--c-primary);outline-offset:2px;}
                        .ct-custom-card__title-input{width:100%;font-weight:700;font-size:0.9rem;padding:2px 4px;border-radius:6px;border:1px solid var(--c-primary);}
                        .ct-custom-card__select{display:flex;align-items:center;width:100%;text-align:left;background:none;border:none;padding:0;margin-top:2px;font-size:0.78rem;color:var(--c-text-muted);cursor:pointer;min-height:44px;}
                        .ct-task-card--on .ct-custom-card__select{color:rgba(255,255,255,0.85);}
                        {{-- Enrichissement 2026-07-31 (accès à plus d'icônes + recherche) : la popover
                             .ct-icon-picker (recherche + catégories, scrollable) enveloppe désormais un
                             ou plusieurs .ct-emoji-grid (la grille de boutons proprement dite, un par
                             catégorie affichée, ou un seul groupe à plat pendant une recherche). Les
                             règles .ct-emoji-grid ci-dessous restent VOLONTAIREMENT identiques à
                             l'existant (cibles 44×44px, 5 colonnes bureau / 4 mobile - round 16 et round
                             91) : seule la positionnement/le fond/l'ombre de la popover ont été déplacés
                             vers .ct-icon-picker pour laisser la place au champ de recherche et aux
                             en-têtes de catégorie. --}}
                        .ct-icon-picker{position:absolute;z-index:5;top:40px;left:50%;transform:translateX(-50%);display:flex;flex-direction:column;gap:6px;width:280px;max-width:calc(100vw - 24px);max-height:340px;overflow-y:auto;background:#fff;border:1px solid #d1d5db;border-radius:10px;padding:8px;box-shadow:0 6px 18px rgba(0,0,0,0.15);}
                        .ct-icon-picker__search{width:100%;box-sizing:border-box;padding:0.4rem 0.6rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.85rem;min-height:44px;}
                        .ct-icon-picker__search:focus-visible{outline:2px solid var(--c-primary);outline-offset:1px;}
                        .ct-icon-picker__category{font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.03em;color:var(--c-text-muted);margin:6px 2px 2px;}
                        .ct-icon-picker__empty{font-size:0.8rem;color:var(--c-text-muted);padding:8px 4px;margin:0;}
                        .ct-emoji-grid{display:grid;grid-template-columns:repeat(5,44px);gap:4px;}
                        .ct-emoji-grid button{width:44px;height:44px;display:inline-flex;align-items:center;justify-content:center;border:1px solid transparent;border-radius:8px;background:#fff;font-size:1.2rem;cursor:pointer;}
                        @media (max-width: 575.98px) {
                            /* Round 16 (2026-07-27) : left:0 débordait hors viewport pour les cartes de la
                               colonne de droite (grille col-6 mobile) - centré sous l'ancre + rétréci.
                               Round 91 (2026-07-27, passe adversariale) : le rétrécissement à 40px faisait
                               échouer la cible tactile WCAG 2.2 AAA SC 2.5.5 (44×44). La réduction à 4
                               colonnes suffit déjà à éviter le débordement (188px de large + padding) -
                               les boutons restent à 44px. */
                            .ct-icon-picker{width:252px;}
                            .ct-emoji-grid{grid-template-columns:repeat(4,44px);}
                        }
                        .ct-emoji-grid button:hover,.ct-emoji-grid button:focus-visible{border-color:var(--c-primary);outline:2px solid var(--c-primary);outline-offset:1px;}
                        .ct-custom-card__panel{border:1px solid #e5e7eb;border-radius:10px;padding:0.6rem;margin-top:0.5rem;background:#f8fafb;color:var(--c-dark);}
                        .ct-add-card-btn{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;width:100%;min-height:76px;padding:0.85rem 1rem;border:2px dashed var(--c-text-muted, #52586A);border-radius:12px;background:transparent;color:var(--c-text-muted);cursor:pointer;font-weight:600;}
                        .ct-add-card-btn:hover:not(:disabled){border-color:var(--c-primary);color:var(--c-primary);}
                        .ct-add-card-btn:disabled{opacity:0.5;cursor:not-allowed;}
                        .ct-add-card-btn:focus-visible{outline:2px solid var(--c-primary);outline-offset:2px;}
                        </style>

                        {{-- Indicateur d'étapes (2 étapes : objectif d'abord, puis la demande) --}}
                        {{-- Round 77 (2026-07-27, passe adversariale) : cercles cliquables à la souris
                             mais inatteignables au clavier (échec WCAG 2.1.1) - ajout role/tabindex/
                             gestionnaires clavier/aria-current/aria-label + cible 44px (cohérent avec
                             la règle WCAG 2.2 AAA SC 2.5.5 déjà appliquée ailleurs dans ce fichier). --}}
                        <style>
                        .ct-step-circle{cursor:pointer;margin:0 auto;width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.9rem;transition:all 0.2s;}
                        .ct-step-circle:focus-visible{outline:2px solid var(--c-primary);outline-offset:2px;}
                        </style>
                        <div class="d-flex justify-content-between mb-4" style="position: relative;">
                            <template x-for="s in [1,2]" :key="s">
                                <div class="text-center" style="flex: 1; position: relative; z-index: 1;">
                                    {{-- Round 85 (2026-07-27, passe adversariale) : #6c757d/#e9ecef (3,95:1)
                                         et #adb5bd/blanc (2,07:1) échouaient même le seuil AA (4,5:1),
                                         a fortiori l'AAA 7:1 de la charte. Remplacés par les tokens de
                                         charte déjà utilisés dans ce fichier : var(--c-dark) sur
                                         #e9ecef = 14,24:1 AAA ; var(--c-text-muted) sur blanc =
                                         7,09:1 AAA (déjà confirmé round 82). --}}
                                    <div class="ct-step-circle" @click="goToStep(s)" @keydown.enter.prevent="goToStep(s)" @keydown.space.prevent="goToStep(s)" role="button" tabindex="0" :aria-current="step === s ? 'step' : false" :aria-label="'{{ __('Étape') }} ' + s + ' : ' + (s === 1 ? '{{ __('Votre objectif') }}' : '{{ __('Votre demande') }}')" :style="step >= s ? 'background: var(--c-primary); color: #fff;' : 'background: #e9ecef; color: var(--c-dark, #1A1D23);'" x-text="s"></div>
                                    <small class="d-block mt-1" style="font-size: 0.7rem;" :style="step >= s ? 'color: var(--c-primary); font-weight: 600;' : 'color: var(--c-text-muted, #52586A);'" x-text="s === 1 ? '{{ __('Votre objectif') }}' : '{{ __('Votre demande') }}'"></small>
                                </div>
                            </template>
                        </div>

                        {{-- Étape 1 : Objectif (entrée par l'intention, pas par la persona) --}}
                        <div x-show="step === 1" x-transition>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h2 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); font-size: 1.1rem; margin: 0;">{{ __('Que voulez-vous faire ?') }}</h2>
                                <button class="ct-btn ct-btn-ghost ct-btn-xs" @click="showHelp.persona = !showHelp.persona" style="border-radius:50%;width:22px;height:22px;padding:0;line-height:22px;margin-left:4px;flex-shrink:0;">?</button>
                            </div>
                            <p class="text-muted small mb-3">{{ __('Choisissez la carte la plus proche de votre besoin. On pré-remplit le reste pour vous, tout reste modifiable ensuite.') }}</p>
                            <div x-show="showHelp.persona" x-transition class="alert alert-info small mb-3 p-2" style="font-size: 0.8rem;" x-text="helps.persona"></div>
                            {{-- Round 65 (2026-07-27) : annonce ARIA pendant le chargement d'un prompt en
                                 édition (?edit=ID) - sans ça, les cartes désactivées par editLoading ne
                                 s'expliquaient pas aux technologies d'assistance. --}}
                            <span class="visually-hidden" role="status" x-text="editLoading ? '{{ __('Chargement du prompt en édition en cours...') }}' : ''"></span>
                            <div class="row g-2 mb-2" role="group" aria-label="{{ __('Choisir un objectif') }}" :aria-busy="editLoading ? 'true' : 'false'">
                                <template x-for="c in taskCards" :key="c.id">
                                    <div class="col-6 col-md-4">
                                        <button type="button" class="ct-task-card" :class="{ 'ct-task-card--on': selectedTask === c.id }" :disabled="editLoading" :title="editLoading ? '{{ __('Chargement du prompt en édition en cours...') }}' : null" @click="selectTask(c)" :aria-pressed="selectedTask === c.id">
                                            <span aria-hidden="true" style="font-size:1.3rem;" x-text="c.icon"></span>
                                            <span class="ct-task-card__title" x-text="c.label"></span>
                                            <span class="ct-task-card__desc" x-text="c.description"></span>
                                        </button>
                                    </div>
                                </template>

                                {{-- Cartes personnalisées (Option D, 2026-07-26) : ajoutées par le membre,
                                     réordonnables par glisser-déposer OU par les boutons ↑/↓ (alternative
                                     clavier obligatoire WCAG 2.2). Les 9 cartes système ci-dessus restent
                                     intactes, non éditables, non réordonnables. --}}
                                <template x-for="(c, cIdx) in customCards" :key="c.id">
                                    <div class="col-6 col-md-4">
                                        <div class="ct-task-card ct-task-card--custom" :class="{ 'ct-task-card--on': selectedTask === c.id, 'ct-task-card--hidden-card': c.hidden }"
                                             role="group" :aria-label="c.title"
                                             draggable="true" @dragstart="dragStartCustomCard($event, c)" @dragover.prevent @drop="dropOnCustomCard($event, c)">
                                            <div class="ct-custom-card__head">
                                                <button type="button" :id="'cpCardIconBtn-' + c.id" class="ct-custom-card__icon-btn" @click="toggleIconPicker(c.id)" aria-haspopup="true" :aria-expanded="iconPickerOpenId === c.id ? 'true' : 'false'" :aria-label="'{{ __('Icône de la carte') }} : ' + c.title">
                                                    <span aria-hidden="true" style="font-size:1.2rem;" x-text="c.icon"></span>
                                                </button>
                                                <div class="ct-custom-card__actions">
                                                    <button type="button" class="ct-custom-card__icon-action" @click="moveCustomCard(c, -1)" :disabled="cIdx === 0" aria-label="{{ __('Déplacer plus tôt') }}" title="{{ __('Déplacer plus tôt') }}">↑</button>
                                                    <button type="button" class="ct-custom-card__icon-action" @click="moveCustomCard(c, 1)" :disabled="cIdx === customCards.length - 1" aria-label="{{ __('Déplacer plus tard') }}" title="{{ __('Déplacer plus tard') }}">↓</button>
                                                    <button type="button" class="ct-custom-card__icon-action" @click="toggleCardHidden(c)" :aria-label="c.hidden ? '{{ __('Afficher cette carte') }}' : '{{ __('Masquer cette carte') }}'" :title="c.hidden ? '{{ __('Afficher cette carte') }}' : '{{ __('Masquer cette carte') }}'" x-text="c.hidden ? '🚫' : '👁️'"></button>
                                                    <button type="button" :id="'cpCardPanelBtn-' + c.id" class="ct-custom-card__icon-action" @click="toggleCardPanel(c)" aria-label="{{ __('Éditer le gabarit de requête') }}" title="{{ __('Éditer') }}" :aria-expanded="editingCardPanelId === c.id ? 'true' : 'false'">✏️</button>
                                                    <button type="button" class="ct-custom-card__icon-action" @click="confirmDeleteCard(c)" aria-label="{{ __('Supprimer cette carte') }}" title="{{ __('Supprimer') }}">🗑️</button>
                                                </div>
                                            </div>
                                            <template x-if="editingCardId === c.id">
                                                <input type="text" :id="'cpCardTitleInput-' + c.id" class="ct-custom-card__title-input" x-model="c.title" maxlength="60" @blur="commitCardTitle(c)" @keydown.enter.prevent="commitCardTitle(c)" @keydown.escape="cancelEditCardTitle(c)" aria-label="{{ __('Titre de la carte') }}">
                                            </template>
                                            <template x-if="editingCardId !== c.id">
                                                <button type="button" :id="'cpCardTitleBtn-' + c.id" class="ct-custom-card__title-btn" @click="startEditCardTitle(c)" :aria-label="'{{ __('Modifier le titre') }} : ' + c.title">
                                                    <span x-text="c.title"></span>
                                                </button>
                                            </template>
                                            <button type="button" class="ct-custom-card__select" :disabled="c.hidden || editLoading" @click="selectTask(c)">{{ __('Utiliser cette carte') }} →</button>

                                            {{-- Enrichissement 2026-07-31 : catalogue classé (12 catégories, ~200 icônes) +
                                                 recherche par mot-clé français insensible aux accents/casse (factorisée
                                                 dans _normalizeIconText côté JS). Regroupé par catégorie sans recherche,
                                                 à plat pendant une recherche (iconSearchGroups). Navigation clavier réelle
                                                 (flèches/Début/Fin) via handleIconGridKeydown ; Entrée/Espace = natif au
                                                 <button> ; Échap = @keydown.escape.window déjà branché sur closeIconPicker()
                                                 au niveau du conteneur racine. --}}
                                            <template x-if="iconPickerOpenId === c.id">
                                                <div class="ct-icon-picker" role="group" aria-label="{{ __('Choisir une icône') }}">
                                                    <label class="visually-hidden" :for="'cpIconSearch-' + c.id">{{ __('Rechercher une icône') }}</label>
                                                    <input type="text" :id="'cpIconSearch-' + c.id" class="ct-icon-picker__search" x-model="iconSearchQuery" autocomplete="off" placeholder="{{ __('Rechercher une icône...') }}">
                                                    <div class="visually-hidden" role="status" aria-live="polite" x-text="iconResultsAnnouncement"></div>
                                                    <template x-if="iconSearchQuery && iconSearchResultsCount === 0">
                                                        <p class="ct-icon-picker__empty">{{ __('Aucune icône ne correspond à cette recherche.') }}</p>
                                                    </template>
                                                    <div :id="'cpEmojiGrid-' + c.id" @keydown="handleIconGridKeydown($event, c.id)">
                                                        <template x-for="group in iconSearchGroups" :key="group.category || 'resultats'">
                                                            <div>
                                                                <template x-if="group.category">
                                                                    <p class="ct-icon-picker__category" x-text="group.category"></p>
                                                                </template>
                                                                <div class="ct-emoji-grid" role="group" :aria-label="group.category || '{{ __('Résultats de recherche') }}'">
                                                                    <template x-for="icon in group.icons" :key="icon.c">
                                                                        <button type="button" data-icon-idx @click="setCardIcon(c, icon.c)" :aria-label="iconAriaLabel(icon)" x-text="icon.c"></button>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>

                                            <template x-if="editingCardPanelId === c.id">
                                                <div class="ct-custom-card__panel">
                                                    <label class="form-label fw-medium mb-1" style="font-size:0.78rem;">{{ __('Gabarit de requête') }}</label>
                                                    {{-- Round 119 (2026-07-27, passe adversariale) : ce textarea n'avait AUCUN id, donc
                                                         le garde-fou anti-PII (qui résout ses champs par getElementById) ne pouvait
                                                         structurellement pas le surveiller - alors que son contenu est persisté en base
                                                         et réinjecté dans de futurs prompts. Id dynamique (même convention que
                                                         cpCardTitleInput-<id>) + écoute déléguée côté JS, car ces cartes sont créées et
                                                         détruites dynamiquement (x-if dans x-for). --}}
                                                    {{-- @cp-card-masked : signal dédié envoyé par prompt-anon-panel.js juste après avoir
                                                         réinjecté un gabarit masqué dans ce champ (événement input, pas de blur). Sans lui,
                                                         la copie en clair déjà écrite dans localStorage au blur précédent survivrait tant
                                                         qu'aucun nouveau blur ne se produit - purgerCopieLocaleDesCartes() réécrit
                                                         immédiatement la copie locale avec l'état courant (déjà masqué). --}}
                                                    <textarea :id="'cpCardTemplate-' + c.id" class="form-control form-control-sm" rows="3" x-model="c.query_template" @blur="commitCardPanelBlur(c)" @keydown.escape="cancelEditCardPanel(c)" @cp-card-masked="purgerCopieLocaleDesCartes()" maxlength="500" placeholder="{{ __('Ex: Corrige les fautes et améliore la clarté de ce texte...') }}" aria-label="{{ __('Gabarit de requête de cette carte') }}"></textarea>
                                                    <small class="text-muted d-block mt-1" style="font-size:0.7rem;">{{ __('Ce texte remplira votre demande si elle est encore vide. Si vous avez déjà écrit quelque chose, rien ne sera écrasé.') }}</small>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <div class="col-6 col-md-4">
                                    <button type="button" class="ct-add-card-btn" @click="addCustomCard()" :disabled="!customCardsLoaded || customCards.length >= 10">
                                        <span aria-hidden="true" style="font-size:1.3rem;">＋</span>
                                        <span>{{ __('Ajouter une carte') }}</span>
                                    </button>
                                </div>
                            </div>
                            {{-- Round 118 (2026-07-27, passe adversariale) : le chargement des cartes
                                 serveur échouait en SILENCE (customCards vidé, bouton d'ajout resté
                                 actif) et la première carte ajoutée écrasait toutes les cartes déjà
                                 enregistrées. L'ajout est maintenant bloqué tant que le chargement
                                 n'a pas réussi ; cet avertissement persistant explique pourquoi et
                                 offre le réessai (un toast à 4 s ne suffisait pas : le bouton reste
                                 désactivé bien après sa disparition). --}}
                            <template x-if="isAuthenticated && customCardsLoadFailed">
                                <div role="alert" class="small mb-2 p-2 rounded" style="font-size:0.78rem; background: #FEF3C7; border: 1px solid #B7791F; color: #5b4a1f; border-radius: 8px;">
                                    {{ __('Impossible de charger vos cartes personnalisées pour le moment. L\'ajout est désactivé afin de ne pas écraser celles déjà enregistrées.') }}
                                    <button type="button" class="ct-btn ct-btn-outline ct-btn-xs ms-1" style="min-height:44px;" @click="retryLoadCustomCards()">{{ __('Réessayer') }}</button>
                                </div>
                            </template>
                            <template x-if="customCards.length >= 10">
                                <p class="small mb-2" style="font-size:0.78rem;color:var(--c-text-muted);" aria-live="polite">{{ __('10/10 cartes - supprimez-en une pour en ajouter une autre.') }}</p>
                            </template>
                            <template x-if="!isAuthenticated && customCards.length > 0">
                                <div class="small mb-2 p-2 rounded" style="font-size:0.78rem; background: rgba(11,114,133,0.06); border: 1px solid rgba(11,114,133,0.15); border-radius: 8px;">
                                    {{ __('Connectez-vous pour sauvegarder vos cartes en permanence.') }}
                                    <button type="button" class="ct-btn ct-btn-primary ct-btn-xs ms-1" style="min-height:44px;" @click="$dispatch('open-auth-modal')">{{ __('Se connecter') }}</button>
                                </div>
                            </template>
                            <template x-if="isAuthenticated && customCardsImportAvailable">
                                <div class="small mb-2 p-2 rounded" style="font-size:0.78rem; background: var(--c-primary-light); border: 1px solid rgba(11,114,133,0.2); border-radius: 8px;">
                                    {{ __('Des cartes personnalisées enregistrées dans ce navigateur ont été trouvées.') }}
                                    {{-- Round 97 (2026-07-27, passe adversariale) : :disabled empêche le
                                         double-clic qui créait de vrais doublons persistés en base
                                         (voir importingCards, constructeur-prompts-core.js).
                                         Round 104 (2026-07-27, passe adversariale) : accord singulier/pluriel
                                         manquant - affichait "Importer mes 1 cartes locales" quand une seule
                                         carte invité existait. Mirroir du pattern déjà établi au round 25
                                         pour le toast de succès (customCardsImportedOne/Many, lignes 887-888). --}}
                                    <button type="button" class="ct-btn ct-btn-outline ct-btn-xs ms-1" style="min-height:44px;" @click="importLocalCustomCards()" :disabled="importingCards" x-text="(_localCardsToImport && _localCardsToImport.length === 1) ? '{{ __('Importer 1 carte locale') }}' : '{{ __('Importer mes') }} ' + (_localCardsToImport ? _localCardsToImport.length : 0) + ' {{ __('cartes locales') }}'"></button>
                                </div>
                            </template>
                        </div>

                        {{-- Étape 2 : Votre demande (essentiel visible, réglages avancés repliés) --}}
                        <div x-show="step === 2" x-transition>
                            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap" x-show="selectedTask">
                                <span class="small p-2 rounded" style="background: var(--c-primary-light); border-left: 3px solid var(--c-primary); color: var(--c-dark);">
                                    {{ __('Objectif choisi :') }} <strong x-text="selectedTaskLabel"></strong>
                                </span>
                                <button type="button" class="ct-btn ct-btn-ghost ct-btn-xs" @click="goToStep(1)">{{ __('Changer d\'objectif') }}</button>
                            </div>

                            {{-- Round 89 (2026-07-27, passe adversariale) : les 3 astérisques de champ
                                 requis utilisaient #DC2626 (contraste ~4,55-4,83:1 selon le fond, AA
                                 seulement) - portés à #991B1B comme au round 88 (~8,3:1 AAA). --}}
                            {{-- Round 148 (2026-07-31, refonte « anonymisation en place ») : ce champ ne
                                 doit plus JAMAIS être masqué, même pendant une anonymisation - décision
                                 tranchée (panel Perplexity/Gemini 95/100, Codex 82/100). L'`id` reste utile
                                 comme point d'ancrage stable pour le récapitulatif de masquage ci-dessous. --}}
                            <div class="form-group mb-3" id="cpTaskField">
                                <label class="form-label fw-medium">{{ __('Sur quoi porte votre demande ?') }} <span style="color: #991B1B;">*</span></label>
                                <p class="small mb-2 p-2 rounded" style="font-size: 0.82rem; color: var(--c-dark); background: var(--c-primary-light); border-left: 3px solid var(--c-primary); border-radius: 8px;">🔒 {{ __('Il y a un vrai nom, un courriel, un numéro de téléphone ou une adresse dans votre texte ? Cachez-les d\'abord avec le bouton ci-dessous. Tout se passe directement sur votre ordinateur : rien n\'est envoyé ni enregistré nulle part.') }}</p>
                                <textarea id="cpTaskObject" class="form-control" rows="3" x-model="taskObject" autocomplete="off" aria-required="true" placeholder="{{ __('Ex: un plan marketing pour le lancement d\'une application mobile au Québec') }}" aria-label="{{ __('Description de la demande') }}"></textarea>
                                <small class="text-muted">{{ __('Décrivez précisément ce que vous voulez obtenir.') }}</small>
                            </div>

                            {{-- Round 148 (2026-07-31) : MASQUAGE EN PLACE. Ce bouton n'ouvre plus le
                                 panneau d'anonymisation - il détecte et remplace directement le contenu du
                                 champ ci-dessus (voir maskFieldInPlace() dans prompt-anon-panel.js).
                                 aria-expanded/aria-controls retirés : ce bouton ne pilote plus #cpAnonPanel.
                                 Round 149 (2026-07-31) : le même mécanisme (récapitulatif + retour) sert
                                 maintenant aussi les 5 autres champs surveillés du wizard (Exemples, Rôle,
                                 Audience, Verbe, Contraintes personnalisés) via le bandeau anti-PII.
                                 Round 150 (2026-07-31, PERTE DE DONNÉES corrigée) : #cpAnonRecap/#cpAnonUndo
                                 ci-dessous ne servent plus QUE le champ Tâche - un bloc PARTAGÉ entre les 6
                                 champs faisait perdre l'accès au texte d'origine d'un champ dès qu'un AUTRE
                                 champ était masqué ensuite (le bloc unique se déplaçait vers le dernier
                                 champ masqué). Les 5 autres champs obtiennent désormais chacun leur PROPRE
                                 bloc récapitulatif, bâti dynamiquement par la même fabrique JS
                                 (getOrCreateRecapController() dans prompt-anon-panel.js) sur le même
                                 gabarit visuel que celui-ci - DRY strict, toujours aucun bloc dupliqué dans
                                 CE fichier Blade. --}}
                            <div class="form-group mb-3">
                                <button id="cpAnonToggle" type="button" class="ct-btn ct-btn-outline ct-btn-sm" style="min-height:44px;">🛡️ {{ __('Masquer mes informations personnelles') }}</button>
                                <a href="/outils/anonymiseur" class="ct-btn ct-btn-ghost ct-btn-sm ms-1" style="min-height:44px;" title="{{ __('Ouvrir l\'anonymiseur complet (restauration des réponses IA)') }}">↗ {{ __('Anonymiseur complet') }}</a>

                                {{-- Récapitulatif du masquage en place (champ Tâche uniquement, voir la
                                     note round 150 ci-dessus) + annulation. aria-live="polite" +
                                     role="status" : annoncé aux lecteurs d'écran sans interrompre leur
                                     lecture en cours. Masqué par défaut, affiché par JS après un clic sur
                                     le bouton ci-dessus. --}}
                                <div id="cpAnonRecap" class="mt-2" style="display:none;" role="status" aria-live="polite">
                                    <p id="cpAnonRecapText" class="small mb-2 p-2 rounded" style="font-size: 0.82rem; color: var(--c-dark); background: var(--c-primary-light); border-left: 3px solid var(--c-primary); border-radius: 8px;"></p>
                                    <button type="button" id="cpAnonUndo" class="ct-btn ct-btn-outline ct-btn-sm" style="display:none; min-height:44px;">↺ {{ __('Revenir à mon texte de départ') }}</button>
                                </div>

                                <div id="cpAnonPanel" class="anon-wrap" style="display:none; border:1px solid var(--anon-line,#e2e6ea); border-radius:12px; padding:1rem; margin-top:.75rem; background:#f8fafb;" aria-hidden="true">
                                    <p style="font-size:.85rem; color:#52586a; margin:0 0 .5rem;">🔒 {{ __('100 % local : aucune donnée ne quitte votre navigateur. Sélectionnez un passage, surlignez, anonymisez, puis insérez le texte masqué dans votre tâche.') }}</p>
                                    {{-- Éditeur d'anonymisation RÉUTILISABLE (même UX que /outils/anonymiseur) --}}
                                    <x-tools::anonymizer-editor>
                                        <x-slot:previewActions>
                                            <button type="button" id="btnCopyAnon" class="anon-btn secondary">📋 {{ __('Copier') }}</button>
                                            <button type="button" id="cpAnonInsert" class="anon-btn"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true" focusable="false" style="vertical-align:-2px;margin-right:5px;flex-shrink:0;"><path d="M12 5v14M5 12h14"/></svg><span id="cpAnonInsertLabel">{{ __('Insérer dans la tâche') }}</span></button>
                                        </x-slot:previewActions>
                                    </x-tools::anonymizer-editor>
                                </div>
                            </div>

                            <div class="row mb-3" id="cpAudienceBlock">
                                {{-- Round 60 (2026-07-27) : le toggle preset/custom avait disparu lors de la
                                     refonte v1.132.0 « objectif d'abord » - personaType et verbType ont
                                     chacun conservé le leur, mais audienceType n'a jamais été réintroduit,
                                     rendant audienceCustom (champ + logique JS/serveur toujours en place)
                                     définitivement inatteignable pour tout nouveau prompt (sauf via ?edit=ID
                                     d'un ancien prompt déjà en mode custom). --}}
                                <div class="col-12 mb-1">
                                    <div class="d-flex gap-3" role="radiogroup" aria-label="{{ __('Mode de sélection de l\'audience') }}">
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem; min-height: 44px; padding: 4px 6px;">
                                            <input type="radio" name="audienceType" value="preset" x-model="audienceType" style="width:24px;height:24px;accent-color:var(--c-primary);margin:0;flex-shrink:0;cursor:pointer;"> {{ __('Prédéfinie') }}
                                        </label>
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem; min-height: 44px; padding: 4px 6px;">
                                            <input type="radio" name="audienceType" value="custom" x-model="audienceType" style="width:24px;height:24px;accent-color:var(--c-primary);margin:0;flex-shrink:0;cursor:pointer;"> {{ __('Personnalisée') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2" x-show="audienceType === 'preset'" role="group" aria-label="{{ __('Choisir une ou plusieurs audiences') }}">
                                    <label class="form-label fw-medium" style="font-size: 0.85rem;">{{ __('Pour qui ?') }} <button class="ct-btn ct-btn-ghost ct-btn-xs" @click="showHelp.audience = !showHelp.audience" style="border-radius:50%;width:20px;height:20px;padding:0;line-height:20px;">?</button></label>
                                    <div x-show="showHelp.audience" x-transition class="alert alert-info small mb-2 p-2" style="font-size: 0.78rem;" x-text="helps.audience"></div>
                                    <div class="d-flex flex-wrap gap-2" style="gap:0.5rem;">
                                        <template x-for="a in audiences" :key="a.value">
                                            <label class="ct-pill" :class="{ 'ct-pill--on': audiencePresets.includes(a.value) }">
                                                <input type="checkbox" class="ct-pill__input" :value="a.value" x-model="audiencePresets">
                                                <span x-text="a.label"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2" x-show="audienceType === 'custom'">
                                    <label class="form-label fw-medium" style="font-size: 0.85rem;">{{ __('Pour qui ?') }}</label>
                                    <input type="text" id="cpAudienceCustom" class="form-control" x-model="audienceCustom" autocomplete="off" placeholder="{{ __('Ex: enseignants du secondaire au Québec') }}" aria-label="{{ __('Audience personnalisée') }}">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-medium" style="font-size: 0.85rem;">{{ __('Ton général souhaité') }}</label>
                                    <select class="form-control form-control-sm" x-model="tone" aria-label="{{ __('Ton de la réponse') }}">
                                        <option value="">{{ __('-- Aucun --') }}</option>
                                        <option value="Professionnel">{{ __('Professionnel') }}</option>
                                        <option value="Accessible et pédagogique">{{ __('Accessible et pédagogique') }}</option>
                                        <option value="Technique et précis">{{ __('Technique et précis') }}</option>
                                        <option value="Chaleureux et engageant">{{ __('Chaleureux et engageant') }}</option>
                                        <option value="Académique">{{ __('Académique') }}</option>
                                        <option value="Créatif et dynamique">{{ __('Créatif et dynamique') }}</option>
                                        <option value="Conversationnel">{{ __('Conversationnel') }}</option>
                                        <option value="Persuasif">{{ __('Persuasif') }}</option>
                                        <option value="Neutre et factuel">{{ __('Neutre et factuel') }}</option>
                                        <option value="Empathique et bienveillant">{{ __('Empathique et bienveillant') }}</option>
                                        <option value="Motivant et inspirant">{{ __('Motivant et inspirant') }}</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Divulgation progressive CONTEXTUELLE (Phase 2, recherche validée juillet 2026) :
                                 chaque section a sa PROPRE petite divulgation locale repliée par défaut,
                                 au lieu d'un panneau global unique. Pattern DRY réutilisé 5x : x-data="{ open:false }"
                                 imbriqué (accède aux données du parent promptBuilder()) + bouton .ct-advanced-toggle. --}}

                            {{-- Section : Rôle (requis) --}}
                            <div class="mb-3">
                                <button type="button" class="ct-advanced-toggle" @click="openSections.role = !openSections.role" :aria-expanded="openSections.role.toString()" aria-controls="cpSectionRole">
                                    <span><span x-text="openSections.role ? '{{ __('Masquer') }}' : '+'"></span> {{ __('Réglages avancés : rôle de l\'IA') }} <span style="color: #991B1B;">*</span></span>
                                    <span class="ct-chevron" aria-hidden="true">▾</span>
                                </button>
                                <div id="cpSectionRole" x-show="openSections.role" x-transition x-cloak class="p-3 mt-2 rounded" style="border: 1px solid #e5e7eb; border-radius: 10px;">
                                    <div class="d-flex gap-3 mb-2">
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; min-height: 44px; padding: 4px 6px;">
                                            <input type="radio" name="personaType" value="preset" x-model="personaType" style="width:24px;height:24px;accent-color:var(--c-primary);margin:0;flex-shrink:0;cursor:pointer;"> {{ __('Prédéfini') }}
                                        </label>
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; min-height: 44px; padding: 4px 6px;">
                                            <input type="radio" name="personaType" value="custom" x-model="personaType" style="width:24px;height:24px;accent-color:var(--c-primary);margin:0;flex-shrink:0;cursor:pointer;"> {{ __('Personnalisé') }}
                                        </label>
                                    </div>
                                    <div x-show="personaType === 'preset'" class="form-group mb-0">
                                        <select class="form-control" x-model="personaPreset" :aria-required="personaType === 'preset'" aria-label="{{ __('Choisir un rôle') }}">
                                            <option value="">{{ __('-- Sélectionnez un rôle --') }}</option>
                                            <template x-for="p in personas" :key="p.value">
                                                <option :value="p.value" x-text="p.label"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div x-show="personaType === 'custom'" class="form-group mb-0">
                                        <input type="text" id="cpPersonaCustom" class="form-control" x-model="personaCustom" :aria-required="personaType === 'custom'" autocomplete="off" placeholder="{{ __('Ex: un expert en cybersécurité spécialisé en PME québécoises') }}" aria-label="{{ __('Rôle personnalisé') }}">
                                    </div>
                                </div>
                            </div>

                            {{-- Section : Verbe d'action (requis) --}}
                            <div class="mb-3">
                                <button type="button" class="ct-advanced-toggle" @click="openSections.verb = !openSections.verb" :aria-expanded="openSections.verb.toString()" aria-controls="cpSectionVerb">
                                    <span><span x-text="openSections.verb ? '{{ __('Masquer') }}' : '+'"></span> {{ __('Réglages avancés : verbe d\'action') }} <span style="color: #991B1B;">*</span></span>
                                    <span class="ct-chevron" aria-hidden="true">▾</span>
                                </button>
                                <div id="cpSectionVerb" x-show="openSections.verb" x-transition x-cloak class="p-3 mt-2 rounded" style="border: 1px solid #e5e7eb; border-radius: 10px;">
                                    <div class="d-flex gap-3 mb-2">
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; min-height: 44px; padding: 4px 6px;">
                                            <input type="radio" name="verbType" value="preset" x-model="verbType" style="width:24px;height:24px;accent-color:var(--c-primary);margin:0;flex-shrink:0;cursor:pointer;"> {{ __('Prédéfini') }}
                                        </label>
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; min-height: 44px; padding: 4px 6px;">
                                            <input type="radio" name="verbType" value="custom" x-model="verbType" style="width:24px;height:24px;accent-color:var(--c-primary);margin:0;flex-shrink:0;cursor:pointer;"> {{ __('Personnalisé') }}
                                        </label>
                                    </div>
                                    <select class="form-control" x-show="verbType === 'preset'" x-model="verb" :aria-required="verbType === 'preset'" aria-label="{{ __('Verbe d\'action') }}">
                                        <option value="">{{ __('-- Sélectionnez un verbe --') }}</option>
                                        <template x-for="v in verbs" :key="v">
                                            <option :value="v" x-text="v"></option>
                                        </template>
                                    </select>
                                    <input type="text" id="cpVerbCustom" class="form-control" x-show="verbType === 'custom'" x-model="verbCustom" autocomplete="off" :aria-required="verbType === 'custom'" placeholder="{{ __('Ex: Reformule, Synthétise, Décortique...') }}" aria-label="{{ __('Verbe personnalisé') }}">
                                </div>
                            </div>

                            {{-- Section : Format, longueur et langue --}}
                            <div class="mb-3">
                                <button type="button" class="ct-advanced-toggle" @click="openSections.format = !openSections.format" :aria-expanded="openSections.format.toString()" aria-controls="cpSectionFormat">
                                    <span><span x-text="openSections.format ? '{{ __('Masquer') }}' : '+'"></span> {{ __('Réglages avancés : format, longueur et langue') }}</span>
                                    <span class="ct-chevron" aria-hidden="true">▾</span>
                                </button>
                                <div id="cpSectionFormat" x-show="openSections.format" x-transition x-cloak class="p-3 mt-2 rounded" style="border: 1px solid #e5e7eb; border-radius: 10px;">
                                    <div class="row mb-0">
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label fw-medium" style="font-size: 0.85rem;">{{ __('Format de sortie') }}</label>
                                            <select class="form-control form-control-sm" x-model="format" aria-label="{{ __('Format de sortie') }}">
                                                <option value="">{{ __('-- Aucun --') }}</option>
                                                <option value="Liste à puces">{{ __('Liste à puces') }}</option>
                                                <option value="Paragraphes détaillés">{{ __('Paragraphes détaillés') }}</option>
                                                <option value="Tableau structuré">{{ __('Tableau structuré') }}</option>
                                                <option value="Plan hiérarchisé">{{ __('Plan hiérarchisé') }}</option>
                                                <option value="Étapes numérotées">{{ __('Étapes numérotées') }}</option>
                                                <option value="Format JSON">{{ __('Format JSON') }}</option>
                                                <option value="Diagramme Mermaid">{{ __('Diagramme Mermaid') }}</option>
                                                <option value="Questionnaire / QCM avec corrigé">{{ __('Questionnaire / QCM avec corrigé') }}</option>
                                                <option value="Grille d'évaluation (rubrique)">{{ __('Grille d\'évaluation (rubrique)') }}</option>
                                                <option value="Fiche pratique (1 page)">{{ __('Fiche pratique (1 page)') }}</option>
                                                <option value="Modèle réutilisable (gabarit)">{{ __('Modèle réutilisable (gabarit)') }}</option>
                                                <option value="FAQ structurée">{{ __('FAQ structurée') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label fw-medium" style="font-size: 0.85rem;">{{ __('Longueur précise') }}</label>
                                            <select class="form-control form-control-sm" x-model="length" aria-label="{{ __('Longueur souhaitée') }}">
                                                <option value="">{{ __('-- Aucune --') }}</option>
                                                <option value="Concis (100-200 mots)">{{ __('Concis (100-200 mots)') }}</option>
                                                <option value="Modéré (300-500 mots)">{{ __('Modéré (300-500 mots)') }}</option>
                                                <option value="Détaillé (500-800 mots)">{{ __('Détaillé (500-800 mots)') }}</option>
                                                <option value="Exhaustif (800+ mots)">{{ __('Exhaustif (800+ mots)') }}</option>
                                                <option value="3 à 5 points clés">{{ __('3 à 5 points clés') }}</option>
                                                <option value="5 à 10 points clés">{{ __('5 à 10 points clés') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label fw-medium" style="font-size: 0.85rem;">{{ __('Langue de réponse') }}</label>
                                            <select class="form-control form-control-sm" x-model="language" aria-label="{{ __('Langue') }}">
                                                <option value="fr">{{ __('Français') }}</option>
                                                <option value="en">English</option>
                                                <option value="es">Español</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Section : Technique de réflexion --}}
                            <div class="mb-3">
                                <button type="button" class="ct-advanced-toggle" @click="openSections.technique = !openSections.technique" :aria-expanded="openSections.technique.toString()" aria-controls="cpSectionTechnique">
                                    <span><span x-text="openSections.technique ? '{{ __('Masquer') }}' : '+'"></span> {{ __('Réglages avancés : comment l\'IA doit réfléchir') }}</span>
                                    <span class="ct-chevron" aria-hidden="true">▾</span>
                                </button>
                                <div id="cpSectionTechnique" x-show="openSections.technique" x-transition x-cloak class="p-3 mt-2 rounded" style="border: 1px solid #e5e7eb; border-radius: 10px;">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <button class="ct-btn ct-btn-ghost ct-btn-xs" @click="showHelp.technique = !showHelp.technique" style="border-radius:50%;width:22px;height:22px;padding:0;line-height:22px;flex-shrink:0;">?</button>
                                        <span class="small text-muted">{{ __('Une micro-explication apparaît pour chaque méthode.') }}</span>
                                    </div>
                                    <div x-show="showHelp.technique" x-transition class="alert alert-info small mb-2 p-2" style="font-size: 0.8rem;" x-text="helps.technique"></div>
                                    <div class="row mb-0">
                                        <div class="col-md-6 mb-2">
                                            <select class="form-control form-control-sm" x-model="technique" aria-label="{{ __('Méthode de réflexion de l\'IA') }}">
                                                <option value="zero-shot">{{ __('Réponse directe (par défaut)') }}</option>
                                                <option value="zero-shot-cot">{{ __('Réponse directe + réflexion étape par étape') }}</option>
                                                <option value="few-shot">{{ __('Avec des exemples') }}</option>
                                                <option value="few-shot-cot">{{ __('Avec des exemples + réflexion étape par étape') }}</option>
                                                <option value="iterative">{{ __('Par étapes, avec votre validation à chaque fois') }}</option>
                                            </select>
                                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;" x-text="techniqueHints[technique] || ''"></small>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            {{-- Round 88 (2026-07-27, passe adversariale) : min-height:44px + padding ajoutés
                                                 - cible tactile WCAG 2.2 AAA SC 2.5.5, cohérent avec les labels radio du même
                                                 fichier (déjà à min-height: 44px depuis les rounds précédents). --}}
                                            <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem; min-height: 44px; padding: 4px 6px;">
                                                <input type="checkbox" x-model="useDelimiters" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                                <span>{{ __('Séparer clairement les données du reste (délimiteurs ###)') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div x-show="technique === 'few-shot' || technique === 'few-shot-cot'" class="form-group mb-0">
                                        <label class="form-label fw-medium" style="font-size: 0.85rem;">{{ __('Exemples (2-3 recommandés)') }}</label>
                                        <textarea id="cpExamples" class="form-control form-control-sm" rows="4" x-model="examples" autocomplete="off" placeholder="{{ __('Exemple 1 :\nEntrée : ...\nSortie : ...\n\nExemple 2 :\nEntrée : ...\nSortie : ...') }}" aria-label="{{ __('Exemples à donner à l\'IA') }}"></textarea>
                                        <small class="text-muted">{{ __('Donnez 2-3 exemples du résultat attendu pour guider l\'IA.') }}</small>
                                    </div>
                                </div>
                            </div>

                            {{-- Section : Contraintes (inclut Destination + Format attendu, séparés visuellement) --}}
                            <div class="mb-3">
                                <button type="button" class="ct-advanced-toggle" @click="openSections.contraintes = !openSections.contraintes" :aria-expanded="openSections.contraintes.toString()" aria-controls="cpSectionContraintes">
                                    <span><span x-text="openSections.contraintes ? '{{ __('Masquer') }}' : '+'"></span> {{ __('Réglages avancés : contraintes et destination') }}</span>
                                    <span class="ct-chevron" aria-hidden="true">▾</span>
                                </button>
                                <div id="cpSectionContraintes" x-show="openSections.contraintes" x-transition x-cloak class="p-3 mt-2 rounded" style="border: 1px solid #e5e7eb; border-radius: 10px;">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <button class="ct-btn ct-btn-ghost ct-btn-xs" @click="showHelp.constraints = !showHelp.constraints" style="border-radius:50%;width:22px;height:22px;padding:0;line-height:22px;flex-shrink:0;">?</button>
                                        <span class="small text-muted">{{ __('Cochez celles qui correspondent à votre besoin.') }}</span>
                                    </div>
                                    <div x-show="showHelp.constraints" x-transition class="alert alert-info small mb-2 p-2" style="font-size: 0.8rem;">
                                        {{ __('Les contraintes limitent ou orientent le comportement de l\'IA. Elles seront ajoutées automatiquement au prompt.') }}
                                    </div>
                                    <div class="d-flex flex-column gap-2 mb-3">
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem; min-height: 44px; padding: 4px 6px;">
                                            <input type="checkbox" x-model="constraintAntiAI" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                            <span><strong>{{ __('Écriture naturelle (anti-IA)') }}</strong> : {{ __('style humain, phrases variées, pas de formulations génériques') }}</span>
                                        </label>
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem; min-height: 44px; padding: 4px 6px;">
                                            <input type="checkbox" x-model="constraintTypo" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                            <span><strong>{{ __('Règles typographiques') }}</strong> : {{ __('majuscules en début de phrase, pas de tiret cadratin') }}</span>
                                        </label>

                                        {{-- Destination (OÙ) et Format attendu (QUOI) : 2 champs distincts mais liés
                                             (décision d'architecture d'info validée Codex/claude.ai/Gemini, juillet 2026).
                                             État interne inchangé : constraintCanvas + canvasAI pilotés par le getter/setter
                                             `destination` (voir constructeur-prompts-core.js) pour zéro régression au reload. --}}
                                        <div class="p-2 rounded" style="background: #f0f9ff; border: 1px solid rgba(11,114,133,0.18); border-radius: 10px;">
                                            <label class="form-label fw-medium mb-1" style="font-size: 0.85rem;">{{ __('Destination') }}</label>
                                            <p class="small text-muted mb-2" style="font-size: 0.75rem;">{{ __('Où la réponse doit-elle apparaître : la conversation normale, ou un espace de travail dédié d\'une IA précise ?') }}</p>
                                            <select class="form-control form-control-sm" x-model="destination" style="max-width: 280px;" aria-label="{{ __('Destination de la réponse') }}">
                                                <option value="">{{ __('Conversation standard') }}</option>
                                                <option value="chatgpt">{{ __('Canvas ChatGPT') }}</option>
                                                <option value="claude">{{ __('Artefact Claude') }}</option>
                                                <option value="gemini">{{ __('Canvas Gemini') }}</option>
                                                <option value="mistral">{{ __('Espace de travail Mistral') }}</option>
                                            </select>

                                            <div x-show="destination" x-transition x-cloak class="mt-2 pt-2" style="border-top: 1px dashed rgba(11,114,133,0.3);">
                                                <label class="form-label fw-medium mb-1" style="font-size: 0.8rem;">{{ __('Format attendu') }}</label>
                                                <p class="small text-muted mb-2" style="font-size: 0.72rem;">{{ __('La structure du contenu généré à l\'intérieur de cet espace de travail.') }}</p>
                                                <div class="d-flex gap-3 mb-2" role="radiogroup" aria-label="{{ __('Mode de sélection du format') }}">
                                                    <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.8rem; min-height: 44px; padding: 4px 6px;">
                                                        <input type="radio" name="formatMode" value="preset" x-model="formatMode" style="width:24px;height:24px;accent-color:var(--c-primary);margin:0;flex-shrink:0;cursor:pointer;">
                                                        <span>{{ __('Format prédéfini') }}</span>
                                                    </label>
                                                    <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.8rem; min-height: 44px; padding: 4px 6px;">
                                                        <input type="radio" name="formatMode" value="custom" x-model="formatMode" style="width:24px;height:24px;accent-color:var(--c-primary);margin:0;flex-shrink:0;cursor:pointer;">
                                                        <span>{{ __('Format personnalisé') }}</span>
                                                    </label>
                                                </div>
                                                <div x-show="formatMode === 'preset'">
                                                    <select class="form-control form-control-sm" x-model="canvasFormat" style="max-width: 280px;" aria-label="{{ __('Format attendu prédéfini') }}">
                                                        <option value="">{{ __('-- Aucun --') }}</option>
                                                        <template x-for="f in canvasFormats" :key="f">
                                                            <option :value="f" x-text="f"></option>
                                                        </template>
                                                    </select>
                                                </div>
                                                <div x-show="formatMode === 'custom'">
                                                    <input type="text" class="form-control form-control-sm" x-model="canvasCustomFormat" placeholder="{{ __('Ex: LaTeX, YAML, BibTeX, AsciiDoc, Apache config, MJML email...') }}" style="max-width: 380px;" aria-label="{{ __('Format personnalisé libre') }}">
                                                    <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">{{ __('Décrivez le format souhaité dans vos propres mots : disponible pour les 4 IA.') }}</small>
                                                </div>
                                            </div>
                                        </div>

                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem; min-height: 44px; padding: 4px 6px;">
                                            <input type="checkbox" x-model="constraintChainOfThought" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                            <span><strong>{{ __('Réflexion étape par étape') }}</strong> : {{ __('utile pour les calculs et les problèmes complexes') }}</span>
                                        </label>
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem; min-height: 44px; padding: 4px 6px;">
                                            <input type="checkbox" x-model="constraintAskIfUnclear" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                            <span><strong>{{ __('Poser des questions') }}</strong> : {{ __('demander des précisions si nécessaire') }}</span>
                                        </label>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="form-label fw-medium" style="font-size: 0.85rem;">{{ __('Contraintes spécifiques') }}</label>
                                        <textarea id="cpConstraintCustom" class="form-control form-control-sm" rows="2" x-model="constraintCustom" autocomplete="off" placeholder="{{ __('Ex: éviter le jargon technique, inclure des exemples concrets') }}" aria-label="{{ __('Contraintes personnalisées') }}"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Navigation --}}
                        {{-- Round 85 (2026-07-27, passe adversariale) : les spans spécifiques à l'étape 2
                             (verbe/demande manquants) étaient du code mort - showValidation ne devient
                             JAMAIS true tant que step===2 (nextStep()/canGoToStep() ne testent que
                             selectedTask, jamais taskObject/verb). Conforme à la décision déjà
                             documentée au Round 14 (constructeur-prompts-core.js:342-344) : le cas
                             "verbe manquant" est intentionnellement couvert par l'alerte générique
                             x-show="!isValid" plus bas, pas ici. Retrait des spans inatteignables. --}}
                        <div x-show="showValidation && step === 1 && !selectedTask" x-transition class="alert alert-danger small p-2 mb-2" style="font-size: 0.85rem;" role="alert" aria-live="assertive">
                            {{ __('Veuillez choisir une carte avant de continuer.') }}
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <button class="ct-btn ct-btn-outline" @click="prevStep()" x-show="step > 1" style="min-height:44px;">{{ __('Précédent') }}</button>
                            <div x-show="step === 1"></div>
                            <button class="ct-btn ct-btn-primary" @click="nextStep()" x-show="step < 2" style="min-height:44px;">
{{ __('Suivant') }}</button>
                        </div>

                        {{-- Prévisualisation en langage courant (Phase 2 : toujours avant la vue technique) --}}
                        <div x-show="promptSummary" class="p-3 rounded mb-2" style="background: #fff; border: 1.5px solid var(--c-primary); font-size: 0.92rem; line-height: 1.6; color: var(--c-dark);">
                            <strong style="color: var(--c-primary);">{{ __('Voici ce qui sera envoyé à l\'IA :') }}</strong>
                            <p class="mb-0 mt-1" x-text="promptSummary"></p>
                        </div>
                        <details class="mb-3">
                            <summary class="small" style="cursor:pointer; color: var(--c-text-muted); user-select:none;">{{ __('Voir le texte exact envoyé à l\'IA (technique)') }}</summary>
                            <div class="p-3 rounded mt-2" style="background: var(--c-primary-light); white-space: pre-wrap; font-family: monospace; font-size: 0.9rem; min-height: 60px; line-height: 1.6;" x-text="prompt || '{{ __('Remplissez les étapes ci-dessus...') }}'"></div>
                            <div class="d-flex justify-content-end gap-3 mt-1" style="font-size: 0.8rem;">
                                <span class="text-muted" x-text="prompt.length + ' {{ __('caractères') }}'"></span>
                                <span class="text-muted" x-text="'~' + Math.ceil(prompt.length / 4) + ' {{ __('unités de traitement IA (tokens)') }}'"></span>
                                <span class="text-muted" x-text="prompt.split(/\s+/).filter(function(w){ return w; }).length + ' {{ __('mots') }}'"></span>
                            </div>
                        </details>

                        {{-- Diagnostic rapide (Option 3 hybride, Partie A — 2026-07-26) : détection
                             par règles simples, ZÉRO IA, ZÉRO appel réseau. Chaque manque pointe
                             vers la section « Réglages avancés » correspondante en un clic. --}}
                        <div x-show="isValid" x-cloak class="p-3 rounded mb-3" style="background:#fff; border: 1.5px solid var(--c-primary); border-radius: var(--r-base);">
                            <strong style="color: var(--c-primary); font-size:0.9rem;">🔎 {{ __('Diagnostic rapide') }}</strong>
                            <div aria-live="polite">
                                <template x-if="diagnostic.ok">
                                    <p class="mb-0 mt-1 small" style="color: var(--c-dark);">✓ {{ __('Votre prompt contient les éléments essentiels.') }}</p>
                                </template>
                                <template x-if="!diagnostic.ok">
                                    <ul class="mb-0 mt-2 ps-0" style="list-style:none;">
                                        <template x-for="issue in diagnostic.issues" :key="issue.key">
                                            <li class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2 small" style="color: var(--c-dark);">
                                                <span x-text="issue.message"></span>
                                                <button type="button" class="ct-btn ct-btn-outline ct-btn-xs" style="min-height:44px;" @click="openDiagnosticSection(issue.key)">{{ __('Compléter') }}</button>
                                            </li>
                                        </template>
                                    </ul>
                                </template>
                            </div>
                        </div>

                        {{-- Actions --}}
                        {{-- Round 131 (2026-07-30, passe adversariale) : cette alerte est la SEULE explication du
                             blocage des 3 boutons ci-dessous, car le panneau « Diagnostic rapide » est en
                             x-show="isValid" - donc caché précisément quand l'utilisateur aurait besoin de
                             savoir ce qui manque. Sans role/aria-live, elle n'était jamais annoncée : les 4
                             autres alertes du même fichier (lignes ~67, 71, 250, 608) en ont toutes.
                             « polite » et non « assertive » : elle apparaît et disparaît au fil de la frappe,
                             une annonce assertive interromprait le lecteur d'écran à chaque bascule. --}}
                        <div x-show="!isValid" id="cpValidityHint" role="status" aria-live="polite" class="alert alert-warning small p-2 mb-2" style="font-size: 0.8rem;">
                            {{ __('Choisissez un objectif (étape 1) et décrivez votre demande (étape 2) pour générer votre prompt.') }}
                        </div>
                        <div class="d-flex gap-2 mb-4 flex-wrap">
                            <button class="ct-btn ct-btn-accent flex-fill" @click="copy()" :disabled="!isValid" aria-describedby="cpValidityHint" :style="!isValid && 'opacity:0.5;cursor:not-allowed;'"
                                    x-text="copied ? '{{ __('Copié !') }}' : '{{ __('Copier le prompt') }}'"></button>
                            {{-- "Améliorer avec mon IA" (Option 3 hybride, Partie B — 2026-07-26,
                                 validation croisée Codex/Gemini/claude.ai) : modèle BYOA strict.
                                 AUCUN appel réseau backend — génère un méta-prompt 100% côté client
                                 (get metaPrompt()) et réutilise le mécanisme "Ouvrir dans"/"Copier"
                                 déjà existant pour le pousser vers l'IA déjà connectée de
                                 l'utilisateur. Disponible aux invités comme aux connectés. --}}
                            <button class="ct-btn ct-btn-outline" @click="toggleMetaPrompt()" :disabled="!isValid" aria-describedby="cpValidityHint" :style="'min-height:44px;' + (!isValid ? 'opacity:0.5;cursor:not-allowed;' : '')"
                                    :aria-expanded="metaPromptShown.toString()" aria-controls="cpMetaPromptPanel">✨ {{ __('Améliorer avec mon IA') }}</button>
                            <button class="ct-btn ct-btn-outline" @click="exportPrompt()" :disabled="!isValid" aria-describedby="cpValidityHint" :style="'min-height:44px;' + (!isValid ? 'opacity:0.5;cursor:not-allowed;' : '')">{{ __('Exporter .txt') }}</button>
                            <button class="ct-btn ct-btn-outline-danger" @click="armReset()" aria-live="assertive" style="min-height:44px;" x-text="resetArmed ? '{{ __('⚠️ Confirmer la réinitialisation') }}' : '{{ __('🔄 Recommencer') }}'"></button>
                        </div>
                        <template x-if="metaPromptShown && isValid">
                            <div id="cpMetaPromptPanel" class="mb-4 p-3 rounded" style="border: 1.5px solid var(--c-primary); border-radius: var(--r-base); background:#fff;" aria-live="polite">
                                <h3 style="font-family: var(--f-heading); font-weight: 700; font-size: 1rem; color: var(--c-dark); margin:0 0 0.5rem;">✨ {{ __('Améliorer avec mon IA') }}</h3>
                                <p class="small mb-2" style="color: var(--c-text-muted);">{{ __('Un méta-prompt est préparé dans votre navigateur (aucune donnée envoyée à nos serveurs). Choisissez où l\'envoyer, ou copiez-le pour le coller dans l\'IA de votre choix.') }}</p>
                                <details class="mb-3">
                                    <summary class="small" style="cursor:pointer; color: var(--c-text-muted); user-select:none;">{{ __('Voir le méta-prompt') }}</summary>
                                    <div class="p-2 rounded mt-2" style="background: var(--c-primary-light); white-space:pre-wrap; font-size:0.82rem; max-height:260px; overflow-y:auto; border:1px solid rgba(11,114,133,0.25); border-radius:8px;" x-text="metaPrompt"></div>
                                </details>
                                <div class="d-flex gap-2 flex-wrap align-items-center mb-2">
                                    <span class="text-muted small">{{ __('Ouvrir dans') }} :</span>
                                    <button class="ct-btn ct-btn-outline ct-btn-sm" style="min-height:44px;" @click="openIn('chatgpt', metaPrompt)">ChatGPT</button>
                                    <button class="ct-btn ct-btn-outline ct-btn-sm" style="min-height:44px;" @click="openIn('claude', metaPrompt)">Claude</button>
                                    <button class="ct-btn ct-btn-outline ct-btn-sm" style="min-height:44px;" @click="openIn('perplexity', metaPrompt)">Perplexity</button>
                                    <button class="ct-btn ct-btn-outline ct-btn-sm" style="min-height:44px;" @click="openIn('gemini', metaPrompt)">Gemini</button>
                                    <button class="ct-btn ct-btn-outline ct-btn-sm" style="min-height:44px;" @click="openIn('mistral', metaPrompt)">Mistral</button>
                                </div>
                                <button type="button" class="ct-btn ct-btn-accent" style="min-height:44px;" @click="copyText(metaPrompt)">{{ __('Copier le méta-prompt') }}</button>
                            </div>
                        </template>
                        {{-- #166 GEO/UX : ouvrir le prompt directement dans une IA (le prompt est aussi copié) --}}
                        <div class="d-flex gap-2 mb-4 flex-wrap align-items-center">
                            <span class="text-muted small">{{ __('Ouvrir dans') }} :</span>
                            <button class="ct-btn ct-btn-outline ct-btn-sm" :disabled="!isValid" :style="'min-height:44px;' + (!isValid ? 'opacity:0.5;cursor:not-allowed;' : '')" @click="openIn('chatgpt')">ChatGPT</button>
                            <button class="ct-btn ct-btn-outline ct-btn-sm" :disabled="!isValid" :style="'min-height:44px;' + (!isValid ? 'opacity:0.5;cursor:not-allowed;' : '')" @click="openIn('claude')">Claude</button>
                            <button class="ct-btn ct-btn-outline ct-btn-sm" :disabled="!isValid" :style="'min-height:44px;' + (!isValid ? 'opacity:0.5;cursor:not-allowed;' : '')" @click="openIn('perplexity')">Perplexity</button>
                            <button class="ct-btn ct-btn-outline ct-btn-sm" :disabled="!isValid" :style="'min-height:44px;' + (!isValid ? 'opacity:0.5;cursor:not-allowed;' : '')" @click="openIn('gemini')">Gemini</button>
                            <button class="ct-btn ct-btn-outline ct-btn-sm" :disabled="!isValid" :style="'min-height:44px;' + (!isValid ? 'opacity:0.5;cursor:not-allowed;' : '')" @click="openIn('mistral')">Mistral</button>
                        </div>

                        {{-- Historique (visible seulement pour les non-connectes, les connectes ont "Mes prompts") --}}
                        <template x-if="!isAuthenticated && history.length > 0">
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h3 style="font-family: var(--f-heading); font-weight: 700; margin: 0; font-size: 1rem;">{{ __('Historique') }} (<span x-text="history.length"></span>)</h3>
                                    <button class="ct-btn ct-btn-outline-danger ct-btn-xs" style="min-height:44px;" @click="clearHistory()">{{ __('Effacer') }}</button>
                                </div>
                                <template x-for="(h, i) in history" :key="i">
                                    <div class="d-flex justify-content-between align-items-center p-2 mb-1 rounded" style="background: #f8f9fa; font-size: 0.8rem;">
                                        <div class="flex-fill" style="cursor: pointer;" @click="copyText(h.prompt)">
                                            <strong x-text="h.name"></strong>
                                            <div class="text-muted" x-text="h.prompt.substring(0, 80) + '...'"></div>
                                        </div>
                                        <div class="d-flex gap-1 ms-2">
                                            <button class="ct-btn ct-btn-outline ct-btn-xs" style="min-height:44px;" @click="copyText(h.prompt)">{{ __('Copier') }}</button>
                                            <button class="ct-btn ct-btn-outline-danger ct-btn-xs" @click.stop="deletePrompt(h.id, i)" :disabled="_deletingIds.includes(h.id)" style="min-height:44px; min-width:44px; padding:1px 5px;">✕</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Modale aide --}}
<div class="modal fade" id="promptHelpModal" tabindex="-1" role="dialog" aria-labelledby="promptHelpModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: var(--r-base);">
            <div class="modal-header" style="background: var(--c-primary); border-radius: var(--r-base) var(--r-base) 0 0;">
                <h4 class="modal-title" id="promptHelpModalLabel" style="color: #fff; font-family: var(--f-heading); font-weight: 700;">{{ __('Comment créer un bon prompt') }}</h4>
                {{-- Round 92 (2026-07-27, passe adversariale) : ce bouton × créé sans classe .ct-btn
                     n'avait ni min-height ni min-width (~24×24px effectif) - même défaut que le
                     bouton "Compris !" du footer ci-dessous (explicitement signalé), découvert en
                     inspectant le reste de cette modale. Cible tactile WCAG 2.2 AAA SC 2.5.5. --}}
                <button type="button" onclick="jQuery('#promptHelpModal').modal('hide')" style="background: none; border: none; color: #fff !important; opacity: 1; font-size: 1.5rem; font-weight: 700; cursor: pointer; float: right; min-width:44px; min-height:44px; display:flex; align-items:center; justify-content:center;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <h4 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); border-bottom: 2px solid var(--c-primary); padding-bottom: 0.5rem;">{{ __('La méthode en 2 étapes') }}</h4>
                <ul>
                    <li><strong>{{ __('Votre objectif') }}</strong> : {{ __('choisissez la carte qui correspond à ce que vous voulez faire (rédiger, résumer, apprendre...)') }}</li>
                    <li><strong>{{ __('Votre demande') }}</strong> : {{ __('précisez le sujet, à qui ça s\'adresse et le ton ; chaque réglage avancé (rôle de l\'IA, verbe, format, technique, contraintes) reste replié par défaut, à un clic via son propre bouton « + Réglages avancés »') }}</li>
                </ul>
                <h4 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); border-bottom: 2px solid var(--c-primary); padding-bottom: 0.5rem; margin-top: 1.5rem;">{{ __('Les contraintes utiles (réglages avancés)') }}</h4>
                <ul>
                    <li><strong>{{ __('Écriture naturelle') }}</strong> : {{ __('évite le style « robotique » typique de l\'IA') }}</li>
                    <li><strong>{{ __('Destination') }}</strong> : {{ __('choisissez si la réponse doit s\'afficher dans la conversation normale ou dans un espace de travail dédié (Canvas ChatGPT, Artefact Claude, Canvas Gemini, Mistral) ; le « Format attendu » qui suit précise alors la structure du contenu généré dans cet espace') }}</li>
                    <li><strong>{{ __('Réflexion étape par étape') }}</strong> : {{ __('meilleur pour les problèmes complexes') }}</li>
                    <li><strong>{{ __('Poser des questions') }}</strong> : {{ __('l\'IA clarifie avant de répondre = meilleur résultat') }}</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="ct-btn ct-btn-primary" onclick="jQuery('#promptHelpModal').modal('hide')" style="min-height:44px;">{{ __('Compris !') }}</button>
            </div>
        </div>
    </div>
</div>

@include('fronttheme::partials.tools-newsletter-cta', ['toolSource' => 'constructeur-prompts'])
@endsection

@push('head')
{{-- CSS de l'éditeur d'anonymisation réutilisable (panneau « Anonymiser un texte ») --}}
<link rel="stylesheet" href="{{ asset('assets/tools/anonymiseur/anon-v2.css') }}?v={{ config('version.semver') }}">
{{-- 2026-05-27 #310 : Schema.org SoftwareApplication pour AEO/GEO. Outil top page 403 PV /30j. --}}
@php
    $_swApp = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        '@id' => url()->current().'#software',
        // Round 78 (2026-07-27, passe adversariale) : name/description/featureList traduits via
        // __() (repli sur la valeur FR quand la clé lang/en.json existe déjà, comme partout ailleurs
        // dans ce fichier). 'inLanguage' reste 'fr-CA' EN DUR par décision délibérée : c'est la
        // convention appliquée telle quelle sur TOUS les blocs JSON-LD du site (Books, Dictionary,
        // SEO, Academy - vérifié par grep, aucune exception) ; la rendre dynamique uniquement ici
        // créerait une incohérence avec le reste du site plutôt que la résoudre - hors périmètre
        // d'une passe ciblée sur constructeur-prompts (changement de politique site-wide à trancher
        // séparément si souhaité).
        'name' => __('Constructeur de prompts IA'),
        'alternateName' => ['Prompt Builder', 'Générateur de prompts ChatGPT', 'Constructeur de prompts'],
        'description' => __('Outil gratuit et interactif pour créer des prompts optimisés en partant de votre objectif (rédiger, résumer, analyser, apprendre...), avec réglages avancés repliés par défaut (rôle de l\'IA, audience, format de sortie). Compatible ChatGPT, Claude, Gemini, Mistral et tous les LLMs. Sauvegarde compte ou navigateur, partage natif, mode plein écran.'),
        'url' => url()->current(),
        'applicationCategory' => 'BusinessApplication',
        'applicationSubCategory' => 'ProductivityApplication',
        'operatingSystem' => 'Web (any modern browser)',
        'browserRequirements' => 'Requires JavaScript. Best on Chrome, Firefox, Safari, Edge.',
        'inLanguage' => 'fr-CA',
        'isAccessibleForFree' => true,
        'offers' => [
            '@type' => 'Offer',
            'price' => '0',
            'priceCurrency' => 'CAD',
            'availability' => 'https://schema.org/OnlineOnly',
        ],
        'featureList' => [
            __('Cartes d\'objectifs cliquables (rédiger, résumer, analyser, apprendre...) pour démarrer sans jargon'),
            __('Réglages avancés repliés par défaut (rôle de l\'IA, verbe, format, exemples, contraintes)'),
            __('Sauvegarde locale (navigateur) ou compte utilisateur'),
            __('Partage natif (Web Share API) et copier-coller'),
            __('Mode plein écran sans distraction'),
            __('Compatible ChatGPT, Claude, Gemini, Mistral, DeepSeek, Qwen, Llama'),
        ],
        'author' => function_exists('lv_jsonld_author_stephane') ? lv_jsonld_author_stephane() : ['@type' => 'Person', 'name' => 'Stéphane Lapointe'],
        'publisher' => function_exists('lv_jsonld_publisher') ? lv_jsonld_publisher() : ['@type' => 'Organization', 'name' => 'La veille'],
        'softwareVersion' => '1.0',
        'dateModified' => now()->toIso8601String(),
    ];
@endphp
<script type="application/ld+json">{!! json_encode($_swApp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@push('scripts')
{{-- Liaison anonymiseur : éditeur RÉUTILISABLE partagé (100% local, même UX que /outils/anonymiseur) --}}
@include('tools::partials.anonymizer-scripts')
{{-- Pont spécifique au constructeur : toggle du panneau + insertion du texte anonymisé dans la tâche --}}
<script src="{{ asset('assets/tools/constructeur-prompts/prompt-anon-panel.js') }}?v={{ config('version.semver') }}" defer></script>
@php
$defaultPersonas = [['value'=>'expert_marketing','label'=>'Expert en marketing digital'],['value'=>'redacteur_web','label'=>'Rédacteur web professionnel'],['value'=>'enseignant','label'=>'Enseignant pédagogue'],['value'=>'developpeur','label'=>'Développeur senior'],['value'=>'consultant','label'=>'Consultant en stratégie'],['value'=>'graphiste','label'=>'Graphiste créatif'],['value'=>'analyste','label'=>'Analyste de données'],['value'=>'gestionnaire','label'=>'Gestionnaire de projet'],['value'=>'coach','label'=>'Coach professionnel'],['value'=>'journaliste','label'=>'Journaliste d\'investigation'],['value'=>'chercheur','label'=>'Chercheur scientifique'],['value'=>'rh','label'=>'Spécialiste en ressources humaines'],['value'=>'concepteur_pedagogique','label'=>'Concepteur pédagogique'],['value'=>'community_manager','label'=>'Gestionnaire de médias sociaux'],['value'=>'copywriter','label'=>'Rédacteur publicitaire (copywriter)'],['value'=>'formateur','label'=>'Formateur en entreprise'],['value'=>'adjoint_admin','label'=>'Adjoint administratif']];
$defaultVerbs = ['Rédige','Analyse','Crée','Génère','Explique','Compare','Résume','Traduis','Optimise','Évalue','Développe','Conçois','Planifie','Diagnostique'];
$defaultAudiences = [['value'=>'pro','label'=>'Professionnels du secteur'],['value'=>'debutants','label'=>'Débutants'],['value'=>'entrepreneurs','label'=>'Entrepreneurs et dirigeants'],['value'=>'etudiants','label'=>'Étudiants universitaires'],['value'=>'grand_public','label'=>'Grand public'],['value'=>'techniques','label'=>'Collègues techniques'],['value'=>'direction','label'=>'Direction générale']];
$pbPersonas = class_exists(\Modules\Settings\Facades\Settings::class) ? \Modules\Settings\Facades\Settings::get('tools.prompt_builder.personas', $defaultPersonas) : $defaultPersonas;
$pbVerbs = class_exists(\Modules\Settings\Facades\Settings::class) ? \Modules\Settings\Facades\Settings::get('tools.prompt_builder.verbs', $defaultVerbs) : $defaultVerbs;
$pbAudiences = class_exists(\Modules\Settings\Facades\Settings::class) ? \Modules\Settings\Facades\Settings::get('tools.prompt_builder.audiences', $defaultAudiences) : $defaultAudiences;
// Garde-fou : Settings peut renvoyer une chaîne JSON (ou une valeur invalide) → on normalise en tableau, sinon défaut.
$pbNormalize = function ($v, $default) {
    if (is_string($v)) { $decoded = json_decode($v, true); $v = is_array($decoded) ? $decoded : null; }
    return (is_array($v) && ! empty($v)) ? $v : $default;
};
$pbPersonas = $pbNormalize($pbPersonas, $defaultPersonas);
$pbVerbs = $pbNormalize($pbVerbs, $defaultVerbs);
$pbAudiences = $pbNormalize($pbAudiences, $defaultAudiences);
// Round 77 (2026-07-27, passe adversariale) : texte d'aide contextuelle pur (jamais injecté dans le
// prompt généré) - construit en variables PHP (pas inline dans @json() plus bas) car le compilateur
// Blade tronque silencieusement/plante sur un @json(__(...)) contenant plusieurs parenthèses et
// virgules imbriquées (piège documenté depuis le round 69 de cette passe adversariale).
$pbHelps = [
    'persona' => __('Donner un rôle à l\'IA aide à orienter ses réponses selon une expertise ou un style spécifique. Ex: « Tu es un expert marketing » donnera des réponses plus stratégiques.'),
    'verb' => __('Choisir un verbe d\'action précise ce que l\'IA doit faire : rédiger, analyser, résumer, créer... Le verbe détermine le type de résultat.'),
    'taskObject' => __('Décrivez clairement et précisément ce que l\'IA doit produire. Plus vous donnez de contexte et de détails, meilleur sera le résultat.'),
    'audience' => __('Spécifier le public aide l\'IA à adapter son langage. Un texte pour des débutants sera différent d\'un texte pour des experts.'),
    'format' => __('Le format guide la structure de la réponse. Une liste à puces est facile à lire, un tableau est bon pour comparer, un plan est idéal pour organiser.'),
    'length' => __('Indiquer une longueur permet de contrôler si la réponse est concise (pour un résumé) ou détaillée (pour un article complet).'),
    'tone' => __('Le ton change le style : professionnel pour un rapport, chaleureux pour un courriel client, académique pour un mémoire.'),
    'technique' => __('Réponse directe : l\'IA répond tout de suite sans exemple. Avec des exemples : vous lui donnez 2-3 modèles à suivre. Réflexion étape par étape : l\'IA détaille son raisonnement avant de conclure (meilleur pour la logique et les calculs). Par étapes : l\'IA valide chaque étape avec vous avant de continuer.'),
    'delimiters' => __('Les délimiteurs (###) séparent vos instructions de vos données. Utile quand vous analysez un texte spécifique : l\'IA sait où commence le texte à analyser.'),
    'constraintAntiAI' => __('L\'IA a tendance à produire des textes génériques reconnaissables. Cette option force un style plus naturel, varié et authentiquement humain.'),
    'constraintCanvas' => __('Canvas (ChatGPT) et artefact (Claude) sont des espaces de travail dédiés où l\'IA crée du contenu que vous pouvez modifier directement.'),
    'constraintChainOfThought' => __('La chaîne de pensée force l\'IA à montrer son raisonnement, pas juste le résultat. Très utile pour les problèmes complexes, les mathématiques ou la logique.'),
    'constraintAskIfUnclear' => __('Au lieu de deviner, l\'IA vous posera des questions de clarification. Résultat : des réponses beaucoup plus pertinentes dès le premier essai.'),
];
$pbTechniqueHints = [
    'zero-shot' => __("L'IA répond directement, sans exemple ni étape intermédiaire."),
    'zero-shot-cot' => __("L'IA réfléchit en interne avant de répondre, sans montrer ce raisonnement."),
    'few-shot' => __("Vous donnez 2-3 exemples du résultat attendu pour guider l'IA."),
    'few-shot-cot' => __("Exemples fournis, puis raisonnement détaillé appliqué au même modèle."),
    'iterative' => __("L'IA avance étape par étape et attend votre accord avant de continuer."),
];
// Phase 1 (audit 2026-07-26) : taxonomie de tâches concrètes pour l'étape 1 « objectif d'abord ».
// Mapping simple (pas d'IA) vers les personas/verbes existants ci-dessus — dérivé des mêmes valeurs,
// jamais une taxonomie parallèle. « autre » = échappatoire vers la sélection manuelle (réglages avancés).
//
// Round 74 (2026-07-27, passe adversariale) : 'label'/'description' passés par __() (texte d'affichage
// PUR - jamais injecté dans le prompt généré, cf. selectedTaskLabel et c.description côté JS/Blade).
// 'personaValue'/'verb' restent VOLONTAIREMENT non traduits : ce sont des valeurs injectées brutes
// dans le gabarit du prompt généré (this.verb → "Ta tâche : Rédige ...", this.personaText via
// $defaultPersonas[].label → "Tu es un(e) Rédacteur web professionnel avec une expertise...") -
// l'outil génère TOUJOURS un méta-prompt en français, quel que soit le locale du site (le champ
// « language » du wizard ajoute une instruction de langue de RÉPONSE, il ne traduit pas le gabarit).
// Traduire personas/verbes/audiences casserait donc le prompt généré (grammaire mixte FR/EN) - ne
// JAMAIS les envelopper de __() sans repenser en profondeur la séparation valeur/étiquette.
$defaultTaskCards = [
    ['id' => 'redaction', 'icon' => '✍️', 'label' => __('Rédiger un texte'), 'description' => __('Un article, un courriel, une publication...'), 'personaValue' => 'redacteur_web', 'verb' => 'Rédige'],
    ['id' => 'resume', 'icon' => '📝', 'label' => __('Résumer un contenu'), 'description' => __('Condenser un texte, un rapport, une réunion...'), 'personaValue' => 'analyste', 'verb' => 'Résume'],
    ['id' => 'idees', 'icon' => '💡', 'label' => __('Trouver des idées'), 'description' => __('Brainstormer des angles, des options, des titres...'), 'personaValue' => 'consultant', 'verb' => 'Génère'],
    ['id' => 'analyse', 'icon' => '🔍', 'label' => __('Analyser ou comparer'), 'description' => __('Étudier des données, comparer des options...'), 'personaValue' => 'analyste', 'verb' => 'Analyse'],
    ['id' => 'apprendre', 'icon' => '🎓', 'label' => __('Apprendre ou comprendre'), 'description' => __('Faire expliquer un sujet clairement, étape par étape...'), 'personaValue' => 'enseignant', 'verb' => 'Explique'],
    ['id' => 'traduire', 'icon' => '🌐', 'label' => __('Traduire un texte'), 'description' => __('Passer d\'une langue à une autre...'), 'personaValue' => 'redacteur_web', 'verb' => 'Traduis'],
    ['id' => 'planifier', 'icon' => '🗂️', 'label' => __('Planifier ou organiser'), 'description' => __('Un projet, une stratégie, un horaire...'), 'personaValue' => 'gestionnaire', 'verb' => 'Planifie'],
    ['id' => 'coder', 'icon' => '💻', 'label' => __('Écrire ou déboguer du code'), 'description' => __('Créer, corriger ou expliquer du code...'), 'personaValue' => 'developpeur', 'verb' => 'Développe'],
    ['id' => 'autre', 'icon' => '✨', 'label' => __('Autre chose'), 'description' => __('Je préfère tout choisir moi-même'), 'personaValue' => '', 'verb' => ''],
];
// Round 148 (2026-07-31) : formes singulier/pluriel des catégories RÉELLES retournées par
// AnonymizerCore.detectEntities() (entity.label), pour le récapitulatif humain du masquage en
// place (« 2 noms et 1 numéro de téléphone ont été masqués. »). Construit en variable PHP plutôt
// qu'en tableau littéral inline dans @json() : le directive @json() de cette version de Blade ne
// gère pas correctement plusieurs sous-tableaux successifs au même niveau d'imbrication (bug de
// comptage de parenthèses du compilateur - vérifié : @json(['a'=>[...],'b'=>[...]]) tronque la
// sortie). Les clés reprennent les libellés EXACTS du moteur (anonymizer-core.js) : ce ne sont que
// des clés de correspondance, jamais affichées telles quelles - seules leurs VALEURS (traduisibles
// via __()) le sont.
// Round 149 (2026-07-31, défaut #3) : 3e élément de chaque entrée = accord FÉMININ (bool). Le
// récapitulatif disait « une adresse a été masqué » (masculin figé) même pour des catégories
// féminines - voir resumerMasquage() dans prompt-anon-panel.js. Catégories réellement féminines au
// singulier : Adresse, Adresse IP, Date. Toutes les autres commencent par « un/le » (numéro de...,
// nom, prénom, courriel, montant...) et restent masculines.
$anonPluralLabels = [
    'Nom complet' => [__('nom complet'), __('noms complets'), false],
    'Nom de famille' => [__('nom de famille'), __('noms de famille'), false],
    'Prénom' => [__('prénom'), __('prénoms'), false],
    'RAMQ' => [__('numéro d\'assurance maladie (RAMQ)'), __('numéros d\'assurance maladie (RAMQ)'), false],
    'Numéro de permis' => [__('numéro de permis'), __('numéros de permis'), false],
    'Adresse' => [__('adresse'), __('adresses'), true],
    'Code postal' => [__('code postal'), __('codes postaux'), false],
    'Courriel' => [__('courriel'), __('courriels'), false],
    'Carte bancaire' => [__('numéro de carte bancaire'), __('numéros de carte bancaire'), false],
    'IBAN' => [__('numéro IBAN'), __('numéros IBAN'), false],
    'Adresse IP' => [__('adresse IP'), __('adresses IP'), true],
    'Téléphone' => [__('numéro de téléphone'), __('numéros de téléphone'), false],
    'Numéro de dossier' => [__('numéro de dossier'), __('numéros de dossier'), false],
    'Montant' => [__('montant'), __('montants'), false],
    'Date' => [__('date'), __('dates'), true],
    'NAS' => [__('numéro d\'assurance sociale (NAS)'), __('numéros d\'assurance sociale (NAS)'), false],
];
@endphp
<script>
// Injection des données dynamiques (personas/verbes/audiences configurables via Settings + i18n)
// pour le fichier JS externe ci-dessous — même pattern que window.taxConfig (calculatrice-taxes).
// Le JS de logique du wizard est désormais un fichier externe mis en cache par le navigateur
// (Phase 0 audit 2026-07-26 : ~430 lignes de JS inline jamais cachées auparavant).
window.promptBuilderConfig = {
    personas: @json($pbPersonas),
    verbs: @json($pbVerbs),
    audiences: @json($pbAudiences),
    taskCards: @json($defaultTaskCards),
    isAuthenticated: {{ auth()->check() ? 'true' : 'false' }},
    // Round 77 (2026-07-27, passe adversariale) : texte d'aide contextuelle pur (jamais injecté
    // dans le prompt généré) resté en dur en français dans constructeur-prompts-core.js malgré
    // l'extraction du JS en fichier externe cachable (contrairement à i18n.* déjà pontés).
    // Valeurs construites en variables PHP ($pbHelps/$pbTechniqueHints) plus haut - voir le
    // commentaire à leur définition pour le piège de compilation Blade évité.
    helps: @json($pbHelps),
    techniqueHints: @json($pbTechniqueHints),
    i18n: {
        promptCopied: @json(__('Prompt copié')),
        promptSaved: @json(__('Prompt sauvegardé')),
        saveError: @json(__('Erreur de sauvegarde. Réessayez.')),
        taskTextKept: @json(__('Votre texte a été conservé. Effacez-le si vous voulez repartir du gabarit de cette carte.')),
        loadError: @json(__('Impossible de charger ce prompt pour édition.')),
        diagnosticFormat: @json(__('Aucun format de sortie ni longueur précisée pour la réponse.')),
        diagnosticAudience: @json(__('Aucun contexte ni audience précisé(e) pour qui recevra la réponse.')),
        diagnosticContraintes: @json(__('Aucune contrainte cochée dans la section « Contraintes et destination ».')),
        exportError: @json(__('Impossible d\'exporter le fichier. Réessayez.')),
        deleteError: @json(__('Erreur lors de la suppression. Réessayez.')),
        importError: @json(__('Erreur lors de l\'importation. Réessayez.')),
        openInGeneric: @json(__('Prompt copié : ouverture de la conversation…')),
        openInGemini: @json(__('Prompt copié : colle-le dans Gemini (Ctrl/Cmd + V).')),
        openInMistral: @json(__('Prompt copié : colle-le dans Mistral (Ctrl/Cmd + V).')),
        taskCardDeleted: @json(__('Objectif supprimé')),
        openInTooLong: @json(__('Prompt trop long pour le lien : il est copié, colle-le (Ctrl/Cmd + V).')),
        customCardsImportedOne: @json(__('1 carte importée')),
        customCardsImportedMany: @json(__('{count} cartes importées')),
        customCardsImportLimitReached: @json(__('Limite de 10 cartes atteinte - aucune carte importée. Supprimez-en une puis réessayez.')),
        customCardsImportedPartial: @json(__('{imported} carte(s) importée(s) - {remaining} en attente (limite de 10 atteinte).')),
        anonImported: @json(__('Texte anonymisé importé de l\'anonymiseur.')),
        anonNeedTextFirst: @json(__('Anonymisez d\'abord un texte (bouton « Détecter et anonymiser »).')),
        anonInserted: @json(__('Texte anonymisé inséré dans la tâche.')),
        anonInsertedInField: @json(__('Texte anonymisé inséré dans « %s ».')),
        // Round 141 : libellé du BOUTON d'insertion, recalculé selon le champ réellement visé.
        // Le round 138 n'avait corrigé que le message affiché APRÈS le clic ; le bouton, lui,
        // promettait toujours « Insérer dans la tâche » avant même que la personne clique.
        anonInsertInTask: @json(__('Insérer dans la tâche')),
        anonInsertInField: @json(__('Insérer dans « %s »')),
        // Round 142 : le champ visé a été démonté entre la mémorisation et le clic (panneau de carte
        // refermé). On le dit au lieu d'écrire dans un noeud détaché ou de rediriger vers la tâche.
        anonTargetGone: @json(__('Ce champ n\'est plus affiché. Rouvrez-le, puis réessayez : rien n\'a été inséré.')),
        anonPiiWarningField: @json(__('On dirait qu\'il y a des infos personnelles dans « %s ». Pour ta sécurité, masque-les avant de copier ton prompt.')),
        anonFieldCardTemplate: @json(__('Gabarit de requête de cette carte')),
        anonFieldExamples: @json(__('Exemples pour guider l\'IA')),
        anonMaskButton: @json(__('Masquer mes infos →')),
        // Round 148 (2026-07-31, refonte « anonymisation en place ») : messages du masquage EN
        // PLACE, généralisé au round 149 aux 6 champs surveillés (voir maskFieldInPlace() dans
        // prompt-anon-panel.js).
        anonEmptyField: @json(__('Écrivez d\'abord votre demande dans le champ ci-dessus, puis cliquez de nouveau sur ce bouton pour masquer vos informations personnelles.')),
        anonNoneDetected: @json(__('Aucune information personnelle trouvée dans votre texte. Vous pouvez continuer.')),
        anonMaskedInField: @json(__('Vos informations personnelles ont été masquées, directement sur votre ordinateur.')),
        anonUndone: @json(__('Votre texte de départ est revenu, tel que vous l\'aviez écrit.')),
        // Round 150 (2026-07-31) : libellé du bouton « Revenir à mon texte de départ » construit
        // dynamiquement par prompt-anon-panel.js pour les récapitulatifs créés en JS (5 champs sans
        // markup Blade dédié - voir getOrCreateRecapController()). Réutilise la MÊME clé de
        // traduction que le bouton statique rendu ci-dessus pour le champ Tâche : zéro nouvelle
        // chaîne, zéro divergence possible entre les deux boutons.
        anonUndoLabel: @json(__('Revenir à mon texte de départ')),
        // Round 149 (2026-07-31, défaut #1 - PERTE DE DONNÉES prouvée) : le clic sur « Revenir à
        // mon texte de départ » réécrivait toujours la valeur d'AVANT masquage sans jamais vérifier
        // si la personne avait modifié le champ depuis (ex. complété sa phrase après le masquage) -
        // l'ajout disparaissait sans avertissement. Ce message n'apparaît QUE dans ce cas précis,
        // via la modale de confirmation du thème (jamais confirm() natif).
        anonUndoConfirm: @json(__('Vous avez modifié ce texte depuis le masquage. Revenir en arrière effacera ce que vous avez écrit depuis. Continuer quand même ?')),
        anonUnavailable: @json(__('Le masquage automatique n\'est pas disponible pour le moment.')),
        anonAnd: @json(__('et')),
        // Round 149 (2026-07-31, défaut #3) : accord du verbe selon le GENRE réel de la catégorie
        // masquée (voir resumerMasquage() dans prompt-anon-panel.js et le 3e élément de chaque
        // entrée de $anonPluralLabels ci-dessus). « a été masqué »/« ont été masqués » restent les
        // formes MASCULINES (catégories masculines, ou plusieurs catégories mixtes jointes par
        // « et » - règle du masculin générique) ; les formes *Feminine ci-dessous ne s'appliquent
        // qu'à une SEULE catégorie féminine (adresse, adresse IP, date).
        anonMaskedSingular: @json(__('a été masqué')),
        anonMaskedSingularFeminine: @json(__('a été masquée')),
        anonMaskedPlural: @json(__('ont été masqués')),
        anonMaskedPluralFeminine: @json(__('ont été masquées')),
        // Formes singulier/pluriel des catégories RÉELLES retournées par AnonymizerCore.detectEntities()
        // (entity.label) - sert à construire le récapitulatif humain (« 2 noms et 1 numéro de
        // téléphone ont été masqués. »). Les clés reprennent les libellés EXACTS du moteur
        // (anonymizer-core.js) : ce ne sont que des clés de correspondance, jamais affichées telles
        // quelles - seules leurs VALEURS (traduisibles) le sont.
        anonPluralLabels: @json($anonPluralLabels),
        close: @json(__('Fermer')),
        newCardTitle: @json(__('Nouvelle carte')),
        untitledCard: @json(__('Carte sans titre')),
        deleteCardConfirm: @json(__('Supprimer cette carte ?')),
        importedPromptName: @json(__('Prompt importé')),
        summaryRole: @json(__('L\'IA va se comporter comme ')),
        summaryRoleArticle: @json(__('un(e) ')),
        summaryAction: @json(__('Elle va ')),
        summarySubject: @json(__('Sujet : ')),
        summaryAudience: @json(__('Le résultat sera adapté pour : ')),
        summaryTone: @json(__('Ton : ')),
        summaryFormat: @json(__('Présenté sous forme de : ')),
        summaryLength: @json(__('Longueur visée : ')),
        // Enrichissement 2026-07-31 : sélecteur d'icônes classé + recherche (accès à plus d'icônes,
        // bien classifiées, recherche par mot-clé français).
        iconSearchEmpty: @json(__('Aucune icône ne correspond à cette recherche.')),
        iconSearchResultOne: @json(__('1 icône trouvée')),
        iconSearchResultMany: @json(__('{count} icônes trouvées')),
        iconLabelPrefix: @json(__('Icône : '))
    }
};
</script>
<script src="{{ asset('assets/tools/constructeur-prompts/constructeur-prompts-core.js') }}?v={{ config('version.semver') }}" defer></script>
@endpush
