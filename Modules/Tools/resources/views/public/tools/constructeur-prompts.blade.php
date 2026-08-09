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
                                <x-tools::help-btn click="jQuery('#promptHelpModal').modal('show')" :size="32" />
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
                        {{-- Round 151 (2026-08-01, écran 2 « Votre prompt est prêt ») : colorisation de
                             l'aperçu. Réutilise 2 paires de couleurs DÉJÀ établies (AAA) ailleurs dans ce
                             même fichier plutôt que d'en inventer de nouvelles : #5b4a1f sur #FEF3C7 (bandeau
                             d'avertissement round 118, ligne ~302 de ce fichier) pour « votre texte » (plus
                             visible = ce qui vient de la personne) ; var(--c-dark) sur var(--c-primary-light)
                             (badge « Objectif choisi », déjà utilisé partout dans ce fichier) pour ce que
                             l'outil a ajouté (plus discret = du gabarit, pas le propos de la personne). --}}
                        .ct-seg-user{background:#FEF3C7;color:#5b4a1f;border-radius:4px;padding:0 2px;font-weight:600;}
                        .ct-seg-tool{background:var(--c-primary-light);color:var(--c-dark);border-radius:4px;}
                        {{-- Round 152 (2026-08-01, écran 3) : 5 blocs TOUJOURS visibles, zéro accordéon
                             interne (x-tools::prompt-block). Les grilles de cartes cliquables
                             (x-tools::prompt-card) des multi-sélections ont été remplacées le 2026-08-06
                             (demande explicite : « garder l'ancien menu déroulant, mais chaque sélection
                             devient une pastille ») par le pattern select + pastilles amovibles ci-dessous
                             (.ct-chip) - les garde-fous formats (max 3, exclusivité JSON/Mermaid) restent
                             appliqués via :disabled sur les <option>. --}}
                        .ct-block{margin-bottom:1.5rem;padding:1rem 1.1rem;border:1px solid #e5e7eb;border-radius:12px;background:#fff;}
                        .ct-block__head{display:flex;align-items:baseline;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:0.25rem;}
                        .ct-block__title{font-family:var(--f-heading);font-weight:700;color:var(--c-dark);font-size:1rem;margin:0;}
                        .ct-block__optional{font-size:0.72rem;font-weight:600;color:var(--c-text-muted);background:#f3f4f6;border-radius:999px;padding:2px 8px;white-space:nowrap;}
                        .ct-block__example{font-size:0.78rem;color:var(--c-text-muted);margin:0 0 0.75rem;font-style:italic;}
                        .ct-block__added{font-size:0.8rem;color:var(--c-dark);background:var(--c-primary-light);border-left:3px solid var(--c-primary);border-radius:6px;padding:0.45rem 0.65rem;margin:0.75rem 0 0;font-weight:600;}
                        .ct-block__field{margin-bottom:0.75rem;}
                        .ct-block__field:last-child{margin-bottom:0;}
                        .ct-chip-row{display:flex;flex-wrap:wrap;gap:0.4rem;margin-top:0.5rem;}
                        .ct-chip{display:inline-flex;align-items:center;gap:4px;border:2px solid var(--c-primary);border-radius:9999px;background:var(--c-primary);color:#fff;padding:0.25rem 0.5rem 0.25rem 0.75rem;font-size:0.8rem;font-weight:500;}
                        .ct-chip__x{background:transparent;border:0;color:inherit;font-size:1rem;line-height:1;cursor:pointer;min-width:24px;min-height:24px;padding:0;}
                        .ct-chip__x:focus-visible{outline:2px solid #fff;outline-offset:1px;border-radius:50%;}
                        {{-- Espaces à remplir (tâches 1660-1665, 2026-08-07) : pastille pending en
                             gris #4b5563 (contraste AAA mesuré à 7,57:1 sur blanc, valeur déjà
                             établie ailleurs dans ce fichier - jamais une nouvelle teinte inventée)
                             au lieu du teal vif des pastilles confirmées (.ct-chip), pour signaler
                             visuellement un état transitoire/à corriger. --}}
                        .ct-chip--pending{background:#4b5563;border-color:#4b5563;}
                        {{-- Correctif UX « orpheline visible » (2026-08-09, capture fondateur) :
                             .ct-chip--missing réutilise la paire ambre #5b4a1f sur #FEF3C7 déjà
                             établie (AAA, 7,72:1 - voir round 151 ligne ~155) plutôt qu'une nouvelle
                             teinte, pour distinguer visuellement « à préciser » (gris, transitoire)
                             de « introuvable » (ambre, à corriger). Le focus visible des boutons
                             internes (× et libellé) passait par un contour blanc conçu pour un fond
                             sombre - invisible sur ce fond clair (1,11:1), d'où l'override ciblé. --}}
                        .ct-chip--missing{background:#FEF3C7;border-color:#5b4a1f;color:#5b4a1f;}
                        .ct-chip--missing .ct-chip__x:focus-visible,.ct-chip--missing .ct-chip__label:focus-visible{outline-color:#5b4a1f;}
                        .ct-chip__label{background:transparent;border:0;color:inherit;font:inherit;font-weight:500;cursor:pointer;padding:0;min-height:24px;}
                        .ct-chip__label:focus-visible{outline:2px solid #fff;outline-offset:1px;border-radius:4px;}
                        .ct-chip__input{background:#fff;border:1px solid var(--c-primary);border-radius:6px;color:var(--c-dark);font-size:0.8rem;padding:2px 6px;min-width:90px;max-width:180px;min-height:24px;}
                        .ct-chip__badge{font-size:0.7rem;opacity:0.9;white-space:nowrap;}
                        {{-- Correctif UX « lien persistant pendant le nommage » (2026-08-09) : flèche
                             purement décorative en ::before (ignorée par les lecteurs d'écran),
                             jamais dans x-text pour ne pas polluer l'annonce aria-live. --}}
                        .ct-space-pending-hint::before{content:'↑ ';}
                        {{-- Aperçu colorisé des espaces (Intégration au moteur, point 3) : réutilise
                             la paire déjà établie #5b4a1f sur #FEF3C7 (round 151, « votre texte »)
                             plutôt que d'inventer une nouvelle teinte - pointillé = non rempli, plein
                             = rempli, halo = champ de remplissage actuellement focalisé. --}}
                        .ct-seg-space-empty{background:#FEF3C7;color:#5b4a1f;border-radius:4px;padding:0 2px;font-weight:600;border-bottom:2px dashed #5b4a1f;}
                        .ct-seg-space-filled{background:#FEF3C7;color:#5b4a1f;border-radius:4px;padding:0 2px;font-weight:600;border-bottom:2px solid #5b4a1f;}
                        .ct-seg-space-active{outline:2px solid var(--c-primary);outline-offset:1px;}
                        {{-- LOT 2 (2026-08-06, champs conditionnels invisibles) : conteneur accentué
                             qui enveloppe une case "Autre (...)" et son champ de saisie révélé - rend
                             visible que ces cases ouvrent une zone de saisie secondaire, cohérent avec
                             la teinte déjà utilisée ailleurs (var(--c-primary-light), voir round 151). --}}
                        .ct-conditional-block{margin-top:0.5rem;padding:0.5rem 0.65rem;background:var(--c-primary-light);border-left:3px solid var(--c-primary);border-radius:8px;}
                        {{-- Correctifs UX incrémentaux wizard 4 étapes (2026-08-05, club des sages) :
                             6 évolutions ciblées du gabarit existant, aucune refonte d'écrans - voir
                             CLAUDE.md « pas de big-bang » (3 refontes annulées par l'utilisateur en 2026). --}}

                        {{-- #1 espacement bouton "Suivant"/nav collé au champ au-dessus. --}}
                        .ct-step-nav{margin-top:1.5rem;}

                        {{-- #2 + #4 stepper visuel 2026 : cercles numérotés + connecteur + coche de
                             complétion. Conserve exactement role="tablist"/role="tab" (test
                             PrompteurToolTest.php::assertSee('role="tablist"')) et goToStep()/canGoToStep(). --}}
                        .ct-stepper{display:flex;align-items:flex-start;width:100%;margin:0;padding:0;}
                        .ct-stepper__item{position:relative;flex:1 1 0;min-width:0;display:flex;justify-content:center;}
                        .ct-stepper__item:not(:last-child)::after{content:'';position:absolute;top:17px;left:calc(50% + 21px);right:calc(-50% + 21px);height:2px;background:var(--sys-border-default,#D1D5DB);z-index:0;}
                        .ct-stepper__item--done:not(:last-child)::after{background:var(--sys-status-success-text,#054F3A);}
                        .ct-stepper__btn{position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;gap:4px;min-height:44px;min-width:44px;padding:2px 6px;border:0;background:transparent;cursor:pointer;color:var(--c-text-secondary);width:100%;border-radius:8px;}
                        .ct-stepper__circle{display:flex;align-items:center;justify-content:center;flex-shrink:0;width:34px;height:34px;border-radius:50%;border:2px solid var(--sys-border-default,#D1D5DB);background:#fff;color:var(--c-text-secondary);font-weight:700;font-size:0.85rem;line-height:1;transition:background .15s ease,border-color .15s ease,color .15s ease;}
                        .ct-stepper__label{font-size:0.72rem;font-weight:600;max-width:92px;text-align:center;line-height:1.2;}
                        .ct-stepper__btn--on .ct-stepper__circle{background:var(--c-primary);border-color:var(--c-primary);color:#fff;}
                        .ct-stepper__btn--on .ct-stepper__label{color:var(--c-primary);font-weight:800;}
                        .ct-stepper__btn--done .ct-stepper__circle{background:var(--sys-status-success-bg,#D1FAE5);border-color:var(--sys-status-success-text,#054F3A);color:var(--sys-status-success-text,#054F3A);}
                        .ct-stepper__btn--on.ct-stepper__btn--done .ct-stepper__circle{background:var(--c-primary);border-color:var(--c-primary);color:#fff;}
                        .ct-stepper__btn:hover .ct-stepper__circle{border-color:var(--c-primary);}
                        .ct-stepper__btn:focus-visible{outline:2px solid var(--c-primary);outline-offset:2px;}
                        @media (max-width:520px){
                            .ct-stepper__label{display:none;}
                            .ct-stepper__btn--on .ct-stepper__label{display:block;position:absolute;top:100%;left:50%;transform:translateX(-50%);white-space:nowrap;max-width:none;margin-top:2px;}
                        }
                        @media (prefers-reduced-motion:reduce){
                            .ct-stepper__circle{transition:none;}
                        }

                        {{-- #3 allègement charge cognitive : disclosures repliées par défaut pour l'aperçu
                             et les vérifications, section actions/"Ouvrir dans" regroupée et atténuée tant
                             qu'aucun prompt valide n'existe. --}}
                        .ct-disclosure{border:1px solid #e5e7eb;border-radius:10px;background:#fff;margin-bottom:0.75rem;overflow:hidden;}
                        .ct-disclosure__toggle{display:flex;align-items:center;gap:8px;width:100%;min-height:44px;padding:0.65rem 0.9rem;border:0;background:transparent;color:var(--c-dark);font-family:var(--f-heading);font-weight:700;font-size:0.9rem;text-align:left;cursor:pointer;}
                        .ct-disclosure__toggle:focus-visible{outline:2px solid var(--c-primary);outline-offset:-2px;}
                        .ct-disclosure__chevron{display:inline-block;transition:transform .2s ease;flex-shrink:0;}
                        .ct-disclosure__chevron--open{transform:rotate(90deg);}
                        @media (prefers-reduced-motion:reduce){
                            .ct-disclosure__chevron{transition:none;}
                        }
                        .ct-disclosure__panel{padding:0 0.9rem 0.9rem;}
                        .ct-actions-panel{border-radius:12px;padding:0.85rem;margin-bottom:1rem;transition:background .2s ease,border-color .2s ease,box-shadow .2s ease;border:1px solid #e5e7eb;background:var(--c-surface,#F8FAFB);}
                        .ct-actions-panel--active{border-color:var(--c-primary);background:#fff;box-shadow:0 2px 10px rgba(6,78,90,0.08);}
                        @media (prefers-reduced-motion:reduce){
                            .ct-actions-panel{transition:none;}
                        }

                        {{-- #6 étape 4 regroupée en 3 intentions, sans déplacer ni retirer un champ. --}}
                        .ct-group{border:0;padding:0;margin:0 0 1.75rem;min-width:0;}
                        .ct-group:last-child{margin-bottom:0;}
                        .ct-group__legend{width:100%;font-family:var(--f-heading);font-weight:700;color:var(--c-dark);font-size:0.95rem;padding:0 0 0.4rem;margin:0 0 0.85rem;border-bottom:2px solid var(--c-primary);}
                        </style>
                        {{-- .ct-profile-strip / .ct-profile-strip__title retirés le 2026-08-04 :
                             classes jamais utilisées dans ce fichier (CSS orphelin), aucun effet
                             visuel à leur suppression. --}}

                        {{-- Round 151 (2026-08-01, refonte écrans 1-2, PLAN-FINAL-constructeur-2026-07-31.md) :
                             l'indicateur d'étapes numéroté (cercles « 1 »/« 2 » cliquables) a été retiré.
                             Restauré en assistant séquentiel à 4 étapes (2026-08-03, sur demande explicite
                             de l'utilisateur : « je veux l'ancien outil, celui où on pouvait aussi choisir
                             zero-shot, few-shot, etc. » - fidélité à la version pré-refonte du 26 juillet
                             2026, commit bf422878). Tous les champs et TOUTE la logique de confidentialité
                             (anti-PII), de sauvegarde et de partage restent ceux du code actuel - seule la
                             RÉPARTITION en 4 étapes change. Les 9 cartes de démarrage (n'existaient pas
                             dans la version restaurée) sont retirées de cette page. --}}

                        {{-- Indicateur d'étapes, cliquable (retour à une étape déjà validée). Stepper
                             visuel 2026 (correctif #2, 2026-08-05) : cercles numérotés + connecteur +
                             coche de complétion (correctif #4, via stepComplete()). Mécanisme de
                             navigation inchangé (goToStep/canGoToStep), role="tablist"/role="tab"
                             conservés tels quels (test PrompteurToolTest.php). --}}
                        <div class="ct-stepper mb-3" role="tablist" aria-label="{{ __('Étapes du constructeur') }}">
                            <template x-for="(s, sIdx) in [[1,'{{ __('Persona') }}'],[2,'{{ __('Tâche') }}'],[3,'{{ __('Audience') }}'],[4,'{{ __('Options') }}']]" :key="s[0]">
                                <div class="ct-stepper__item" :class="{ 'ct-stepper__item--done': stepComplete(s[0]) }">
                                    <button type="button" class="ct-stepper__btn" :class="{ 'ct-stepper__btn--on': step === s[0], 'ct-stepper__btn--done': stepComplete(s[0]) }" @click="goToStep(s[0])" role="tab" :aria-selected="(step === s[0]).toString()" :aria-current="step === s[0] ? 'step' : null">
                                        <span class="ct-stepper__circle" aria-hidden="true" x-text="stepComplete(s[0]) ? '✓' : s[0]"></span>
                                        <span class="ct-stepper__label" x-text="s[1]"></span>
                                        <span class="visually-hidden" x-show="stepComplete(s[0])">{{ __('complétée') }}</span>
                                    </button>
                                </div>
                            </template>
                        </div>

                        {{-- Étape 1 : Persona --}}
                        <div x-show="step === 1" x-transition>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h2 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); font-size: 1.1rem; margin: 0;">{{ __('Sur quel ton l\'IA doit-elle répondre ?') }}</h2>
                                <x-tools::help-btn toggle="showHelp.persona" style="margin-left:4px;" />
                            </div>
                            <div x-show="showHelp.persona" x-transition class="alert alert-info small mb-3 p-2" style="font-size: 0.8rem;" x-text="helps.persona"></div>
                            <div class="ct-block__field">
                                <label class="form-label fw-medium mb-1" style="font-size: 0.85rem;">{{ __('Rôle de l\'IA') }} <span style="color: #991B1B;">*</span></label>
                                <div class="d-flex gap-3 mb-2">
                                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; min-height: 44px; padding: 4px 6px;">
                                        <input type="radio" name="personaType" value="preset" x-model="personaType" style="width:24px;height:24px;accent-color:var(--c-primary);margin:0;flex-shrink:0;cursor:pointer;"> {{ __('Prédéfini') }}
                                    </label>
                                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; min-height: 44px; padding: 4px 6px;">
                                        <input type="radio" name="personaType" value="custom" x-model="personaType" style="width:24px;height:24px;accent-color:var(--c-primary);margin:0;flex-shrink:0;cursor:pointer;"> {{ __('Personnalisé') }}
                                    </label>
                                </div>
                                <div class="form-group mb-0" x-show="personaType === 'preset'">
                                    <select class="form-control" x-model="personaPreset" :aria-required="personaType === 'preset'" aria-label="{{ __('Choisir un rôle') }}">
                                        <option value="">{{ __('-- Sélectionnez un rôle --') }}</option>
                                        <template x-for="p in personas" :key="p.value">
                                            <option :value="p.value" x-text="p.label"></option>
                                        </template>
                                    </select>
                                </div>
                                <input type="text" id="cpPersonaCustom" class="form-control" x-show="personaType === 'custom'" x-model="personaCustom" :aria-required="personaType === 'custom'" autocomplete="off" placeholder="{{ __('Ex: un expert en cybersécurité spécialisé en PME québécoises') }}" aria-label="{{ __('Rôle personnalisé') }}">
                            </div>
                        </div>

                        {{-- Étape 2 : Tâche (verbe d'action + description) --}}
                        <div x-show="step === 2" x-transition>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h2 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); font-size: 1.1rem; margin: 0;">{{ __('Que voulez-vous demander à l\'IA ?') }}</h2>
                            </div>
                            <div class="ct-block__field mb-3" :style="secondTaskEnabled ? 'border: 1px solid var(--c-primary); border-radius: 10px; padding: 10px;' : ''">
                                <template x-if="secondTaskEnabled">
                                    <span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; margin-right: 6px; border-radius: 50%; background: var(--c-primary); color: #FFFFFF; font-size: 0.75rem; font-weight: 700; line-height: 1;">1</span>
                                </template>
                                <label class="form-label fw-medium mb-1" style="font-size: 0.85rem;">{{ __('Verbe d\'action') }} <span style="color: #991B1B;">*</span></label>
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

                            {{-- Deuxième tâche optionnelle (2026-08-04, club des sages 5/5 unanime) :
                                 bornée à 2 tâches maximum, séquence explicite dans le prompt généré -
                                 jamais un multi-select libre (déjà essayé et rejeté 2 fois cette année).
                                 Badges numérotés + bouton "Inverser l'ordre" (2026-08-04, round 2 du
                                 club des sages 5/5) : alternative 100% accessible aux pills réordonnables
                                 par glisser-déposer, rejetées car non conformes WCAG AAA (critères 2.1.1
                                 et 2.5.7 - le drag-and-drop pur exige un équivalent clavier/pointeur simple
                                 que 2 select natifs + 1 bouton natif offrent déjà nativement). --}}
                            <div class="ct-block__field mb-3">
                                <button type="button" x-show="!secondTaskEnabled" @click="secondTaskEnabled = true" style="display: inline-flex; align-items: center; min-height: 44px; padding: 4px 6px; border: 0; background: transparent; color: var(--c-primary); font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                                    {{ __('+ Ajouter une deuxième tâche (optionnel)') }}
                                </button>
                                <div x-show="secondTaskEnabled" x-transition style="border: 1px solid var(--c-primary); border-radius: 10px; padding: 10px;">
                                    <p class="small mb-2" style="font-size: 0.8rem; color: var(--c-dark);">{{ __('Cette deuxième tâche sera réalisée à partir du résultat de la première.') }}</p>
                                    <span aria-hidden="true" style="display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; margin-right: 6px; border-radius: 50%; background: var(--c-primary); color: #FFFFFF; font-size: 0.75rem; font-weight: 700; line-height: 1;">2</span>
                                    <label class="form-label fw-medium mb-1" style="font-size: 0.85rem;">{{ __('Verbe d\'action de la deuxième tâche') }}</label>
                                    <div class="d-flex gap-3 mb-2">
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; min-height: 44px; padding: 4px 6px;">
                                            <input type="radio" name="verbType2" value="preset" x-model="verbType2" style="width:24px;height:24px;accent-color:var(--c-primary);margin:0;flex-shrink:0;cursor:pointer;"> {{ __('Prédéfini') }}
                                        </label>
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; min-height: 44px; padding: 4px 6px;">
                                            <input type="radio" name="verbType2" value="custom" x-model="verbType2" style="width:24px;height:24px;accent-color:var(--c-primary);margin:0;flex-shrink:0;cursor:pointer;"> {{ __('Personnalisé') }}
                                        </label>
                                    </div>
                                    <select class="form-control" x-show="verbType2 === 'preset'" x-model="verb2" aria-label="{{ __('Verbe d\'action de la deuxième tâche') }}">
                                        <option value="">{{ __('-- Sélectionnez un verbe --') }}</option>
                                        <template x-for="v in verbs" :key="'second-' + v">
                                            <option :value="v" x-text="v"></option>
                                        </template>
                                    </select>
                                    <input type="text" id="cpVerbCustom2" class="form-control" x-show="verbType2 === 'custom'" x-model="verbCustom2" autocomplete="off" placeholder="{{ __('Ex: Reformule, Synthétise, Décortique...') }}" aria-label="{{ __('Verbe personnalisé de la deuxième tâche') }}">
                                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 4px; margin-top: 6px;">
                                        <button type="button" @click="swapTaskOrder()" style="display: inline-flex; align-items: center; min-height: 44px; padding: 4px 6px; border: 0; background: transparent; color: var(--c-primary); font-size: 0.8rem; font-weight: 600; cursor: pointer;">
                                            {{ __('⇅ Inverser l\'ordre') }}
                                        </button>
                                        <button type="button" @click="secondTaskEnabled = false; verbType2 = 'preset'; verb2 = ''; verbCustom2 = '';" style="display: inline-flex; align-items: center; min-height: 44px; padding: 4px 6px; border: 0; background: transparent; color: #991B1B; font-size: 0.8rem; font-weight: 600; cursor: pointer;">
                                            {{ __('✕ Retirer la deuxième tâche') }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Retrait du panneau de masquage intégré (2026-08-04, demande explicite de
                                 l'utilisateur) : l'anonymisation vit désormais UNIQUEMENT à l'outil séparé
                                 /outils/anonymiseur (jamais touché par ce retrait). L'`id` de ce champ est
                                 conservé comme ancre stable pour openDiagnosticSection() et le futur. --}}
                            <div class="form-group mb-3" id="cpTaskField">
                                {{-- C2-4 (couche 2, tâches 1660-1665, 2026-08-09) : promesse en une ligne
                                     au-dessus du champ principal - discrète, même classe de note que le
                                     reste du formulaire (voir <small class="text-muted"> ligne ~408 juste
                                     en dessous). Fait le pont entre ce champ et les espaces à remplir
                                     décrits plus bas dans cette même étape. --}}
                                <small class="text-muted d-block mb-1">{{ __('Écris ta demande une fois - réutilise-la en changeant seulement quelques mots.') }}</small>
                                <label class="form-label fw-medium">{{ __('Sur quoi porte votre demande ?') }} <span style="color: #991B1B;">*</span></label>
                                <p class="small mb-2 p-2 rounded" style="font-size: 0.82rem; color: var(--c-dark); background: var(--c-primary-light); border-left: 3px solid var(--c-primary); border-radius: 8px;">🔒 {{ __('Il y a un vrai nom, un courriel, un numéro de téléphone ou une adresse dans votre texte ? Cachez-les d\'abord avec l\'') }}<a href="/outils/anonymiseur" style="color: #0A3A42; font-weight: 600; text-decoration: underline;">{{ __('Anonymiseur') }}</a>{{ __(', puis collez le résultat ici. Rien n\'est envoyé ni enregistré nulle part.') }}</p>
                                <textarea id="cpTaskObject" class="form-control" rows="3" x-model="taskObject" autocomplete="off" aria-required="true" placeholder="{{ __('Ex: un plan marketing pour le lancement d\'une application mobile au Québec') }}" aria-label="{{ __('Description de la demande') }}"
                                    @focus="handleSpaceFieldFocus('cpTaskObject')" @select="handleSpaceFieldSelect($event)" @mouseup="handleSpaceFieldSelect($event)" @keyup="handleSpaceFieldSelect($event)" @blur="handleSpaceFieldBlur()"></textarea>
                                <small class="text-muted">{{ __('Décrivez précisément ce que vous voulez obtenir.') }}</small>
                                {{-- Espaces à remplir (tâches 1660-1665, geste A) : bulle inline sous le
                                     champ dès qu'une sélection valide existe - « près du champ », pas une
                                     bulle positionnée en pixels (plus robuste, même esprit que la spec).
                                     @mousedown.prevent évite la course avec le blur du textarea (le clic
                                     sur ce bouton ne doit jamais faire perdre la sélection avant l'action). --}}
                                <div x-show="spaceBubble.show && spaceBubble.fieldId === 'cpTaskObject'" x-cloak class="mt-2">
                                    <button type="button" role="button" class="ct-btn ct-btn-outline ct-btn-xs" style="min-height:44px;" @mousedown.prevent="createSpaceFromSelection()">
                                        <span x-text="'{{ __('En faire un espace à remplir') }} : « ' + spaceBubble.text + ' »'"></span>
                                    </button>
                                </div>
                            </div>

                            {{-- Contexte additionnel (#1593a, 2026-08-07) : champ facultatif DISTINCT
                                 de la tâche ci-dessus - sur le même modèle que le champ "Exemples"
                                 (few-shot) plus bas (bouton d'aide « ? », x-model direct, textarea).
                                 Apparaît dans le prompt final sous « Contexte : » (voir la section
                                 CONTEXTE ADDITIONNEL de get promptSegments(), constructeur-prompts-core.js). --}}
                            <div class="form-group mb-3" id="cpContextField">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <label class="form-label fw-medium mb-0" for="cpContextInfo">{{ __('Contexte additionnel (facultatif)') }}</label>
                                    <x-tools::help-btn toggle="showHelp.contextInfo" style="margin-left:4px;" />
                                </div>
                                <div x-show="showHelp.contextInfo" x-transition class="alert alert-info small mb-2 p-2" style="font-size: 0.8rem;" x-text="helps.contextInfo"></div>
                                <textarea id="cpContextInfo" class="form-control" rows="3" x-model="contextInfo" autocomplete="off" placeholder="{{ __('Ex: on a déjà essayé une version plus formelle qui n\'a pas fonctionné ; le budget est limité à 500$...') }}" aria-label="{{ __('Contexte additionnel') }}"
                                    @focus="handleSpaceFieldFocus('cpContextInfo')" @select="handleSpaceFieldSelect($event)" @mouseup="handleSpaceFieldSelect($event)" @keyup="handleSpaceFieldSelect($event)" @blur="handleSpaceFieldBlur()"></textarea>
                                <small class="text-muted">{{ __('Informations de fond utiles : ce qui a déjà été essayé, des contraintes, le contexte du projet...') }}</small>
                                {{-- Espaces à remplir (tâches 1660-1665, geste A) : même bulle que le
                                     champ ci-dessus, filtrée sur ce champ (fieldId). --}}
                                <div x-show="spaceBubble.show && spaceBubble.fieldId === 'cpContextInfo'" x-cloak class="mt-2">
                                    <button type="button" role="button" class="ct-btn ct-btn-outline ct-btn-xs" style="min-height:44px;" @mousedown.prevent="createSpaceFromSelection()">
                                        <span x-text="'{{ __('En faire un espace à remplir') }} : « ' + spaceBubble.text + ' »'"></span>
                                    </button>
                                </div>
                            </div>

                            {{-- Bande de repérage (UI - repérage, tâches 1660-1665) : une pastille par
                                 espace créé, visible en fin d'étape 2 (spec : « sinon étape 2 seulement »
                                 - choix retenu pour éviter de dupliquer ce bloc à l'étape 4). Pastille
                                 pending : champ de renommage ouvert nativement (sp.draftText) + suffixe
                                 « à préciser ». Pastille confirmée : clic sur le nom → renommage inline
                                 (remplace TOUTES les occurrences dans les 2 textareas). Pastille
                                 "non retrouvée" : grise, infobulle, × la retire - AUCUNE corruption
                                 possible (rien n'est jamais retiré du texte lui-même). --}}
                            {{-- Geste B (spec §UI - création, point B) : bouton d'insertion au curseur.
                                 Raccord ajouté au QC visuel (la méthode addSpaceAtCursor existait au
                                 moteur mais aucun bouton ne l'appelait). Toujours visible à l'étape 2 :
                                 c'est LA porte d'entrée découvrable de la fonctionnalité. mb-1 (panel
                                 round 2, Gemini) : resserre le groupe visuel bouton → bande. --}}
                            <div class="mb-1">
                                <button type="button" class="ct-btn ct-btn-outline ct-btn-xs" style="min-height:44px;" @click="addSpaceAtCursor()">
                                    {{ __('+ Ajouter un espace à remplir') }}
                                </button>
                                {{-- Correctif UX « note accents » (2026-08-09, capture fondateur) :
                                     l'utilisateur s'attendait à des noms de variables sans accents -
                                     lever l'ambiguïté avant le clic, même style discret que les autres
                                     sous-textes du formulaire (voir <small class="text-muted"> ligne
                                     ~421 de ce fichier, champ Contexte additionnel). --}}
                                <div><small class="text-muted">{{ __('Un espace est un bout de phrase normal - accents et espaces bienvenus.') }}</small></div>
                            </div>
                            <div class="mb-2" x-show="spaces.length > 0" x-cloak>
                                {{-- Correctif UX « libellé clair » (2026-08-09, capture fondateur) :
                                     « Tu pourras changer : » ne disait pas QUOI ni son lien avec le
                                     texte - reformulé pour nommer explicitement le lien pastille ↔
                                     texte de la demande. --}}
                                <p class="small mb-1" style="font-size:0.8rem; color:var(--c-text-muted);">{{ __("Tes espaces à remplir - chaque pastille est un bout de texte de ta demande :") }}</p>
                                <div class="ct-chip-row" role="list" aria-label="{{ __('Espaces à remplir créés') }}">
                                    <template x-for="(sp, spIdx) in spaces" :key="spIdx">
                                        {{-- Couche 2 (canonKey, 2026-08-09) : spaceMissing(sp) remplace l'ancien
                                             accès direct spaceMissingCache[sp.text] partout dans ce bloc -
                                             même résultat visible, mais résout la pastille même si son texte
                                             contient une apostrophe courbe ou un espace insécable (voir
                                             spaceMissing() dans constructeur-prompts-core.js). --}}
                                        <span class="ct-chip" :class="{ 'ct-chip--pending': sp.pending, 'ct-chip--missing': spaceMissing(sp) }" role="listitem">
                                            <template x-if="sp.pending">
                                                <input type="text" :id="'cpSpacePendingInput-' + spIdx" x-model="sp.draftText" autocomplete="off" class="ct-chip__input"
                                                    :aria-label="'{{ __('Nommer cet espace à remplir') }}'"
                                                    @keydown.enter.prevent="commitPendingSpaceRename(spIdx)" @keydown.escape.prevent="removeSpace(spIdx)" @blur="commitPendingSpaceRename(spIdx)">
                                            </template>
                                            <template x-if="sp.pending">
                                                <span class="ct-chip__badge">{{ __('à préciser') }}</span>
                                            </template>
                                            <template x-if="!sp.pending && spaceEditingIndex === spIdx">
                                                <input type="text" :id="'cpSpaceRename-' + spIdx" x-model="spaceEditingText" autocomplete="off" class="ct-chip__input"
                                                    :aria-label="'{{ __('Renommer') }} ' + sp.text"
                                                    @keydown.enter.prevent="commitRenameSpace(spIdx)" @keydown.escape.prevent="cancelRenameSpace()" @blur="commitRenameSpace(spIdx)">
                                            </template>
                                            <template x-if="!sp.pending && spaceEditingIndex !== spIdx">
                                                <button type="button" class="ct-chip__label" @click="startRenameSpace(spIdx)"
                                                    {{-- @js() : l'apostrophe de « n'a » cassait l'expression Alpine en quotes simples
                                                         (SyntaxError console, title/aria jamais rendus - trouvé au QC visuel). --}}
                                                    :aria-label="(spaceMissing(sp) ? @js(__('ce mot n\'a pas été retrouvé dans ton texte : ')) : '') + sp.text"
                                                    :title="spaceMissing(sp) ? @js(__('Ce mot n\'a pas été retrouvé dans ton texte.')) : sp.text">
                                                    <span x-text="sp.text"></span>
                                                </button>
                                            </template>
                                            {{-- Correctif UX « orpheline visible » (2026-08-09) : le
                                                 title/aria "non retrouvé" existant n'était visible qu'au
                                                 survol/lecteur d'écran - libellé EN CLAIR dans la
                                                 pastille pour les personnes qui ne survolent jamais. --}}
                                            <template x-if="!sp.pending && spaceMissing(sp)">
                                                <span class="ct-chip__badge">{{ __('introuvable dans le texte') }}</span>
                                            </template>
                                            <button type="button" class="ct-chip__x" :aria-label="'{{ __('Retirer') }} ' + sp.text" @click="removeSpace(spIdx)">&times;</button>
                                        </span>
                                    </template>
                                </div>
                                {{-- Correctif UX « lien persistant pendant le nommage » (2026-08-09,
                                     capture fondateur) : un oracle UX a réfuté le toast (éphémère) -
                                     cette ligne reste affichée tant qu'au moins une pastille est
                                     `pending`, et pointe vers le texte qui vient d'être inséré
                                     au-dessus. Une seule ligne même si plusieurs pending coexistent
                                     (la plus récente, voir mostRecentPendingSpaceText). Même patron
                                     que unfilledSpacesMessage (role="status" aria-live="polite") -
                                     voir ligne ~944 de ce fichier. --}}
                                <p class="ct-space-pending-hint small mb-0 mt-1" x-show="mostRecentPendingSpaceText" x-cloak role="status" aria-live="polite" style="color: var(--c-text-muted); font-size: 0.78rem;" x-text="spacePendingHintMessage"></p>
                            </div>
                        </div>

                        {{-- Étape 3 : Audience (optionnelle) --}}
                        <div id="cpAudienceBlock" x-show="step === 3" x-transition>
                            <h2 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); font-size: 1.1rem; margin: 0 0 0.75rem;">{{ __('Qui va lire ça ?') }}</h2>
                            <div class="d-flex gap-3 mb-2" role="radiogroup" aria-label="{{ __('Mode de sélection de l\'audience') }}">
                                <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem; min-height: 44px; padding: 4px 6px;">
                                    <input type="radio" name="audienceType" value="preset" x-model="audienceType" style="width:24px;height:24px;accent-color:var(--c-primary);margin:0;flex-shrink:0;cursor:pointer;"> {{ __('Prédéfinie') }}
                                </label>
                                <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem; min-height: 44px; padding: 4px 6px;">
                                    <input type="radio" name="audienceType" value="custom" x-model="audienceType" style="width:24px;height:24px;accent-color:var(--c-primary);margin:0;flex-shrink:0;cursor:pointer;"> {{ __('Personnalisée') }}
                                </label>
                            </div>
                            <div class="ct-block__field" x-show="audienceType === 'preset'">
                                <select id="cpAudienceSelect" class="form-control form-control-sm" @change="addAudienceFromSelect($event.target.value); $event.target.value=''" aria-label="{{ __('Ajouter un lecteur prédéfini') }}">
                                    <option value="">{{ __('-- Ajouter un lecteur --') }}</option>
                                    <template x-for="a in audiences" :key="a.value">
                                        <option :value="a.value" x-text="a.label" :disabled="audiencePresets.includes(a.value)"></option>
                                    </template>
                                </select>
                                <div class="ct-chip-row" role="list" aria-label="{{ __('Lecteurs choisis') }}">
                                    <template x-for="v in audiencePresets" :key="v">
                                        <span class="ct-chip" role="listitem">
                                            <span x-text="audienceLabel(v)"></span>
                                            <button type="button" class="ct-chip__x" :aria-label="'{{ __('Retirer') }} ' + audienceLabel(v)" @click="removeAudience(v)">&times;</button>
                                        </span>
                                    </template>
                                </div>
                            </div>
                            <div class="ct-block__field" x-show="audienceType === 'custom'">
                                <input type="text" id="cpAudienceCustom" class="form-control" x-model="audienceCustom" autocomplete="off" placeholder="{{ __('Ex : mes élèves de 5e année, leurs parents') }}" aria-label="{{ __('Audience personnalisée') }}" aria-describedby="cpAudienceCustomHelp">
                                <small id="cpAudienceCustomHelp" style="display:block; margin-top:4px; color:var(--c-muted, #5a6b6f); font-size:0.8rem;">{{ __('Tu peux nommer plusieurs lecteurs, séparés par des virgules.') }}</small>
                            </div>
                        </div>

                        {{-- Étape 4 : Options avancées (tout optionnel). Correctif #6 (2026-08-05) :
                             les 5 blocs existants sont regroupés visuellement sous 3 fieldsets nommés
                             par intention (Apparence / Voix / Règles), SANS déplacer ni retirer un seul
                             champ - chaque bloc x-tools::prompt-block garde son id, son contenu et ses
                             bindings Alpine tels quels ; seul l'emplacement du bloc « Un exemple à
                             imiter ? » remonte pour rejoindre le bloc Format/Longueur sous « Apparence
                             de la réponse » (aucun test n'assertait l'ordre de ces blocs - vérifié par
                             grep avant ce changement). --}}
                        <div x-show="step === 4" x-transition>
                            <h2 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); font-size: 1.1rem; margin: 0 0 0.75rem;">{{ __('Options avancées') }}</h2>

                            <fieldset class="ct-group">
                                <legend class="ct-group__legend">{{ __('Apparence de la réponse') }}</legend>
                                <x-tools::prompt-block id="cpSectionFormat" :question="__('Vous voulez quoi au juste ?')" :example="__('Ex. : un texte de 300 mots avec une liste à puces, un plan détaillé, un tableau comparatif.')" added="feedbackResultat">
                                    <div class="ct-block__field">
                                        <label class="form-label fw-medium mb-1" style="font-size: 0.85rem;">{{ __('Format de sortie') }}</label>
                                        {{-- LOT 1 (2026-08-06) : cartes-checkbox multi-sélection (remplace l'ancien
                                             <select> à valeur unique). État séparé formatsSelected[] + formatCustom
                                             (auparavant partagés dans une seule chaîne `format`, voir migration de
                                             lecture dans init()). Garde-fous appliqués via isFormatDisabled()/
                                             handleFormatChange() (constructeur-prompts-core.js) : maximum 3
                                             sélections, et « Format JSON »/« Diagramme Mermaid » exclusifs entre eux
                                             et avec tout le reste. --}}
                                        <p class="small text-muted mb-2" style="font-size: 0.75rem;">{{ __('Choisis 1 à 3 formats compatibles.') }}</p>
                                        <select id="cpFormatSelect" class="form-control form-control-sm" @change="addFormatFromSelect($event.target.value); $event.target.value=''" aria-label="{{ __('Ajouter un format de sortie') }}">
                                            <option value="">{{ __('-- Ajouter un format --') }}</option>
                                            <template x-for="fmt in formats" :key="fmt.value">
                                                <option :value="fmt.value" x-text="fmt.label" :disabled="isFormatDisabled(fmt.value) || formatsSelected.includes(fmt.value)"></option>
                                            </template>
                                        </select>
                                        <div class="ct-chip-row" role="list" aria-label="{{ __('Formats choisis') }}">
                                            <template x-for="v in formatsSelected" :key="v">
                                                <span class="ct-chip" role="listitem">
                                                    <span x-text="formatLabel(v)"></span>
                                                    <button type="button" class="ct-chip__x" :aria-label="'{{ __('Retirer') }} ' + formatLabel(v)" @click="removeFormat(v)">&times;</button>
                                                </span>
                                            </template>
                                        </div>
                                        {{-- Tâche 1637 : contrairement à Longueur/Ton, le format personnalisé
                                             (formatCustom) S'AJOUTE aux formats choisis (multi-sélection voulue,
                                             tâche 1618) - le menu reste donc visible ici, c'est intentionnel. --}}
                                        <div class="ct-conditional-block">
                                            <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.8rem; min-height: 44px; padding: 4px 6px;">
                                                <input type="checkbox" x-model="customOpen.format" style="width:18px;height:18px;accent-color:var(--c-primary);margin:0;flex-shrink:0;"> {{ __('Autre (format personnalisé)') }}
                                            </label>
                                            <div x-show="customOpen.format" x-transition>
                                                <input type="text" x-model="formatCustom" class="form-control form-control-sm mt-1" autocomplete="off" placeholder="{{ __('Ex: Sonnet, script vidéo, fiche produit...') }}" aria-label="{{ __('Format personnalisé') }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ct-block__field">
                                        <label class="form-label fw-medium mb-1" style="font-size: 0.85rem;">{{ __('Longueur précise') }}</label>
                                        {{-- Tâche 1637 (2026-08-06) : le select et le champ « Autre » pilotent la MÊME
                                             valeur `length` - affichés ensemble, ils laissaient croire qu'on peut en
                                             choisir deux. Cocher « Autre » masque le menu et remet la valeur à zéro. --}}
                                        <select class="form-control form-control-sm" x-show="!customOpen.length" x-transition x-model="length" aria-label="{{ __('Longueur souhaitée') }}">
                                            <option value="">{{ __('-- Aucune --') }}</option>
                                            <template x-for="len in lengths" :key="len.value">
                                                <option :value="len.value" x-text="len.label"></option>
                                            </template>
                                        </select>
                                        {{-- LOT 2 (2026-08-06) : conteneur accentué autour de la case "Autre" +
                                             son champ - voir .ct-conditional-block (style ci-dessus). --}}
                                        <div class="ct-conditional-block">
                                            <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.8rem; min-height: 44px; padding: 4px 6px;">
                                                <input type="checkbox" x-model="customOpen.length" @change="length = ''" style="width:18px;height:18px;accent-color:var(--c-primary);margin:0;flex-shrink:0;"> {{ __('Autre (longueur personnalisée)') }}
                                            </label>
                                            <div x-show="customOpen.length" x-transition>
                                                <input type="text" x-model="length" class="form-control form-control-sm mt-1" autocomplete="off" placeholder="{{ __('Ex: 1000 mots exactement') }}" aria-label="{{ __('Longueur personnalisée') }}">
                                            </div>
                                        </div>
                                    </div>
                                </x-tools::prompt-block>

                                {{-- Bloc « Un modèle » : comment l'IA doit s'y prendre (technique) + exemples
                                     (few-shot) + délimiteurs. LOT 3 (2026-08-06) : le nom technique de chaque
                                     méthode (zero-shot, chaîne de pensée, few-shot...) apparaît désormais en
                                     petit texte gris SOUS le sélecteur (techniqueHints, déjà l'emplacement de
                                     l'explication d'une ligne) plutôt que dans le libellé principal - un <select>
                                     natif ne permet pas de styliser une partie du texte d'une <option>. --}}
                                <x-tools::prompt-block :question="__('Un exemple à imiter ?')" :example="__('Ex. : donnez 2-3 exemples du résultat attendu, ou laissez l\'IA répondre directement.')" added="feedbackModele">
                                    <div class="ct-block__field">
                                        <label class="form-label fw-medium mb-1" style="font-size: 0.85rem;">{{ __('Comment l\'IA doit-elle s\'y prendre ?') }}</label>
                                        <select class="form-control form-control-sm" x-model="technique" aria-label="{{ __('Méthode de réflexion de l\'IA') }}">
                                            <template x-for="tq in techniques" :key="tq.value">
                                                <option :value="tq.value" x-text="tq.label"></option>
                                            </template>
                                        </select>
                                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;" x-text="techniqueHints[technique] || ''"></small>
                                    </div>
                                    <div class="ct-block__field" x-show="technique === 'few-shot' || technique === 'few-shot-cot'">
                                        <label class="form-label fw-medium" style="font-size: 0.85rem;">{{ __('Exemples (2-3 recommandés)') }}</label>
                                        <textarea id="cpExamples" class="form-control form-control-sm" rows="4" x-model="examples" autocomplete="off" placeholder="{{ __('Exemple 1 :\nEntrée : ...\nSortie : ...\n\nExemple 2 :\nEntrée : ...\nSortie : ...') }}" aria-label="{{ __('Exemples à donner à l\'IA') }}"></textarea>
                                        <small class="text-muted">{{ __('Donnez 2-3 exemples du résultat attendu pour guider l\'IA.') }}</small>
                                    </div>
                                    <div class="ct-block__field">
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem; min-height: 44px; padding: 4px 6px;">
                                            <input type="checkbox" x-model="useDelimiters" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                            {{-- LOT 4 (2026-08-06, trouvaille Codex) : l'ancien libellé « Séparer
                                                 clairement les données du reste » laissait croire que l'option
                                                 séparait les DONNÉES fournies par l'utilisateur, alors qu'elle
                                                 sépare les PARTIES de la réponse générée (voir l'instruction
                                                 réellement injectée, inchangée, constructeur-prompts-core.js
                                                 ~944-948 : "Utilise des délimiteurs ### pour séparer clairement
                                                 chaque section de ta réponse."). --}}
                                            <span>{{ __('Séparer clairement les parties de la réponse') }}</span>
                                        </label>
                                        {{-- Signalement 2026-08-06 (tâche 1636) : « ### » ne parle qu'à qui connaît le
                                             Markdown - l'explication décrit maintenant l'EFFET concret, sans jargon.
                                             L'instruction réellement injectée dans le prompt reste inchangée. --}}
                                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">{{ __('Chaque partie de la réponse sera précédée d\'une ligne de séparation bien visible, pour voir d\'un coup d\'oeil où une section commence et où elle finit.') }}</small>
                                    </div>
                                </x-tools::prompt-block>
                            </fieldset>

                            <fieldset class="ct-group">
                                <legend class="ct-group__legend">{{ __('Voix et niveau de langage') }}</legend>
                                <x-tools::prompt-block :question="__('Sur quel ton l\'IA doit-elle répondre ?')" :example="__('Ex. : professionnel, chaleureux et engageant, académique.')" added="feedbackTon">
                                    <div class="ct-block__field">
                                        <label class="form-label fw-medium mb-1" style="font-size: 0.85rem;">{{ __('Ton général souhaité') }}</label>
                                        {{-- Tâche 1637 (2026-08-06) : même logique que la longueur - select et champ
                                             « Autre » partagent la valeur `tone`, donc un seul des deux visible. --}}
                                        <select class="form-control form-control-sm" x-show="!customOpen.tone" x-transition x-model="tone" aria-label="{{ __('Ton de la réponse') }}">
                                            <option value="">{{ __('-- Aucun --') }}</option>
                                            <template x-for="t in tones" :key="t.value">
                                                <option :value="t.value" x-text="t.label"></option>
                                            </template>
                                        </select>
                                        {{-- LOT 2 (2026-08-06) : conteneur accentué autour de la case "Autre" +
                                             son champ - voir .ct-conditional-block (style ci-dessus). --}}
                                        <div class="ct-conditional-block">
                                            <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.8rem; min-height: 44px; padding: 4px 6px;">
                                                <input type="checkbox" x-model="customOpen.tone" @change="tone = ''" style="width:18px;height:18px;accent-color:var(--c-primary);margin:0;flex-shrink:0;"> {{ __('Autre (ton personnalisé)') }}
                                            </label>
                                            <div x-show="customOpen.tone" x-transition>
                                                <input type="text" x-model="tone" class="form-control form-control-sm mt-1" autocomplete="off" placeholder="{{ __('Ex: Ironique et léger') }}" aria-label="{{ __('Ton personnalisé') }}">
                                            </div>
                                        </div>
                                    </div>
                                </x-tools::prompt-block>
                            </fieldset>

                            <fieldset class="ct-group">
                                <legend class="ct-group__legend">{{ __('Règles à respecter') }}</legend>
                                {{-- Interrupteur « Cadre strict » : coupe les règles automatiquement injectées
                                     aux choix explicites de la personne (voir cadreStrict, constructeur-prompts-core.js). --}}
                                <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem; min-height: 44px; padding: 4px 6px;">
                                        <input type="checkbox" x-model="cadreStrict" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                        <span><strong>{{ __('Cadre strict') }}</strong> : <span x-text="cadreStrict ? '{{ __('activé') }}' : '{{ __('désactivé') }}'"></span></span>
                                    </label>
                                    <x-tools::help-btn toggle="showHelp.cadreStrict" />
                                </div>
                                <div x-show="showHelp.cadreStrict" x-transition class="alert alert-info small mb-3 p-2" style="font-size: 0.8rem;">{{ __('Activé (par défaut), l\'outil ajoute l\'écriture naturelle anti-IA, la typographie française et un rappel de qualité. Désactivé, votre prompt reste au plus près de ce que vous avez écrit.') }}</div>

                                {{-- Bloc « Les limites » : contraintes, typographie, écriture anti-IA,
                                     réflexion affichée, questions de clarification, document modifiable
                                     (ex-Destination/Canvas), langue de réponse. id="cpSectionContraintes"
                                     CONSERVÉ - c'est la cible de openDiagnosticSection('contraintes'). --}}
                                <x-tools::prompt-block id="cpSectionContraintes" :question="__('Quelque chose à respecter ?')" :example="__('Ex. : pas de jargon technique, respecter la typographie française, garder un raisonnement visible.')" added="feedbackLimites">
                                    <div class="ct-block__field">
                                        <label class="form-label fw-medium" style="font-size: 0.85rem;">{{ __('Contraintes spécifiques') }}</label>
                                        <textarea id="cpConstraintCustom" class="form-control form-control-sm" rows="2" x-model="constraintCustom" autocomplete="off" placeholder="{{ __('Ex: éviter le jargon technique, inclure des exemples concrets') }}" aria-label="{{ __('Contraintes personnalisées') }}"></textarea>
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
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem; min-height: 44px; padding: 4px 6px;">
                                            <input type="checkbox" x-model="constraintChainOfThought" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                            <span><strong>{{ __('Réflexion étape par étape') }}</strong> : {{ __('utile pour les calculs et les problèmes complexes') }}</span>
                                        </label>
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem; min-height: 44px; padding: 4px 6px;">
                                            <input type="checkbox" x-model="constraintAskIfUnclear" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                            <span><strong>{{ __('Poser des questions') }}</strong> : {{ __('demander des précisions si nécessaire') }}</span>
                                        </label>
                                        {{-- Bonification « QCM forcé » (2026-08-07) : ajoute UN segment fixe en
                                             toute fin du prompt généré (voir get promptSegments(),
                                             constructeur-prompts-core.js) qui fait attendre l'IA avant sa
                                             réponse finale - jamais d'appel réseau, texte statique. --}}
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem; min-height: 44px; padding: 4px 6px;">
                                            <input type="checkbox" x-model="constraintForceQcm" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                            <span><strong>{{ __('Laisser l\'IA me proposer des choix avant de répondre') }}</strong> : {{ __('L\'IA présentera d\'abord 3 pistes numérotées ; vous répondez 1, 2 ou 3.') }}</span>
                                        </label>
                                        {{-- Bonification « Répéter pour ma liste » (2026-08-07) : même mécanisme,
                                             segment fixe placé avant celui du QCM forcé si les deux sont cochées. --}}
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem; min-height: 44px; padding: 4px 6px;">
                                            <input type="checkbox" x-model="constraintRepeatList" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                            <span><strong>{{ __('Répéter pour chaque élément de ma liste') }}</strong> : {{ __('Collez votre liste dans le champ de contexte ; l\'IA traitera chaque élément séparément.') }}</span>
                                        </label>
                                    </div>
                                    {{-- Destination (OÙ) et Format attendu (QUOI) : 2 champs distincts mais liés
                                         (décision d'architecture d'info validée Codex/claude.ai/Gemini, juillet 2026).
                                         État interne inchangé : constraintCanvas + canvasAI pilotés par le getter/setter
                                         `destination` (voir constructeur-prompts-core.js) pour zéro régression au reload.
                                         Renommé « Créer un document modifiable » (section 6 du plan). --}}
                                    <div class="ct-block__field p-2 rounded" style="background: #f0f9ff; border: 1px solid rgba(11,114,133,0.18); border-radius: 10px;">
                                        <label class="form-label fw-medium mb-1" style="font-size: 0.85rem;">{{ __('Créer un document modifiable') }}</label>
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
                                    <div class="ct-block__field">
                                        <label class="form-label fw-medium mb-1" style="font-size: 0.85rem;">{{ __('Langue de réponse') }}</label>
                                        <select class="form-control form-control-sm" x-model="language" aria-label="{{ __('Langue') }}">
                                            <template x-for="l in languages" :key="l.value">
                                                <option :value="l.value" x-text="l.label"></option>
                                            </template>
                                        </select>
                                    </div>
                                </x-tools::prompt-block>
                            </fieldset>
                        </div>

                        {{-- Navigation du wizard 4 étapes (restauré 2026-08-03). Correctif #1
                             (2026-08-05) : .ct-step-nav ajoute l'espacement vertical manquant entre le
                             dernier champ de l'étape active et cette zone (0px mesuré avant fix). --}}
                        <div class="ct-step-nav">
                        <div x-show="showValidation && step === 1 && !personaText" x-transition class="alert alert-danger small p-2 mb-2" style="font-size: 0.85rem;" role="alert" aria-live="assertive">
                            {{ __('Choisissez un rôle (ou saisissez-en un personnalisé) avant de continuer.') }}
                        </div>
                        <div x-show="showValidation && step === 2 && (!(verbType === 'custom' ? verbCustom : verb) || !taskObject)" x-transition class="alert alert-danger small p-2 mb-2" style="font-size: 0.85rem;" role="alert" aria-live="assertive">
                            {{ __('Le verbe d\'action et la description de votre demande sont requis avant de continuer.') }}
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <button class="ct-btn ct-btn-outline" @click="prevStep()" x-show="step > 1" style="min-height:44px;">{{ __('Précédent') }}</button>
                            <div x-show="step === 1"></div>
                            <button class="ct-btn ct-btn-primary" @click="nextStep()" x-show="step < 4" style="min-height:44px;">{{ __('Suivant') }}</button>
                        </div>
                        </div>

                        {{-- Aperçu (correctif #3, 2026-08-05, allègement charge cognitive) : disclosure
                             repliée par défaut regroupant la prévisualisation en langage courant ET
                             l'accordéon technique existant (auparavant empilés à plat sous chaque
                             étape). Rouvrable en 1 clic ; état conservé pendant la session Alpine
                             (previewOpen), rien n'est supprimé. --}}
                        <div class="ct-disclosure" x-show="promptSummary" x-cloak>
                            <button type="button" class="ct-disclosure__toggle" @click="previewOpen = !previewOpen" :aria-expanded="previewOpen.toString()" aria-controls="cpPreviewPanel">
                                <span class="ct-disclosure__chevron" :class="{ 'ct-disclosure__chevron--open': previewOpen }" aria-hidden="true">▸</span>
                                {{ __('Aperçu du prompt') }}
                            </button>
                            <div id="cpPreviewPanel" class="ct-disclosure__panel" x-show="previewOpen" x-transition>
                                {{-- Prévisualisation en langage courant (Phase 2 : toujours avant la vue technique) --}}
                                <div class="p-3 rounded mb-2" style="background: #fff; border: 1.5px solid var(--c-primary); font-size: 0.92rem; line-height: 1.6; color: var(--c-dark);">
                                    <strong style="color: var(--c-primary);">{{ __('Voici ce qui sera envoyé à l\'IA :') }}</strong>
                                    <p class="mb-0 mt-1" x-text="promptSummary"></p>
                                </div>
                                <details class="mb-0">
                                    <summary class="small" style="cursor:pointer; color: var(--c-text-muted); user-select:none;">{{ __('Voir le texte exact envoyé à l\'IA (technique)') }}</summary>
                                    {{-- Aperçu colorisé = MIROIR (Intégration au moteur, point 3, tâches
                                         1660-1665) : rendu segment par segment de get promptSegments() -
                                         seuls les segments 'space' reçoivent un traitement visuel
                                         (pointillé = non rempli, plein = rempli, halo = champ de
                                         remplissage actuellement focalisé) ; 'tool'/'user' restent du
                                         texte brut, comportement visuel IDENTIQUE à avant cette
                                         fonctionnalité (zéro régression sur l'existant). --}}
                                    {{-- Couche 2 (canonKey, 2026-08-09) : spaceValueForText(seg.spaceRef)
                                         remplace l'accès direct spaceValues[seg.spaceRef] - la clé de
                                         recherche est canonique (voir constructeur-prompts-core.js),
                                         jamais implémentée ici dans la vue. --}}
                                    {{-- Balisage volontairement COMPACT à l'intérieur de ce div : il est en
                                         white-space pre-wrap, donc toute indentation du gabarit deviendrait des
                                         espaces visibles dans l'aperçu du prompt (capture 2026-08-09_13-48-08 :
                                         grand vide avant « Tu es... » + décalages entre segments). --}}
                                    <div class="p-3 rounded mt-2" style="background: var(--c-primary-light); white-space: pre-wrap; font-family: monospace; font-size: 0.9rem; min-height: 60px; line-height: 1.6;"><template x-if="!prompt"><span>{{ __('Remplissez les étapes ci-dessus...') }}</span></template><template x-if="prompt"><template x-for="(seg, segIdx) in promptSegments" :key="segIdx"><span x-text="seg.kind === 'space' ? (spaceValueForText(seg.spaceRef) || seg.text) : seg.text" :class="seg.kind === 'space' ? ((spaceValueForText(seg.spaceRef) ? 'ct-seg-space-filled' : 'ct-seg-space-empty') + (focusedSpaceText === seg.spaceRef ? ' ct-seg-space-active' : '')) : ''"></span></template></template></div>
                                    <div class="d-flex justify-content-end gap-3 mt-1" style="font-size: 0.8rem;">
                                        <span class="text-muted" x-text="prompt.length + ' {{ __('caractères') }}'"></span>
                                        <span class="text-muted" x-text="'~' + Math.ceil(prompt.length / 4) + ' {{ __('unités de traitement IA (tokens)') }}'"></span>
                                        <span class="text-muted" x-text="prompt.split(/\s+/).filter(function(w){ return w; }).length + ' {{ __('mots') }}'"></span>
                                    </div>
                                </details>
                            </div>
                        </div>

                        {{-- Remplis tes espaces (ex-« Remplis tes variables », étendu tâches 1660-1665,
                             2026-08-07) : zone de remplissage affichée SOUS l'aperçu, listant D'ABORD
                             les espaces créés par sélection/bouton (fillableSpaces - les "non
                             retrouvés" en sont exclus, seule action possible pour eux = le × de la
                             bande), PUIS les variables {{...}} historiques (promptVariables,
                             mécanique {{}} totalement inchangée - #1593b). La copie et « Ouvrir dans »
                             utilisent automatiquement les valeurs saisies (get promptFilled()) ; un
                             champ laissé vide garde le mot de départ - jamais bloquant. --}}
                        <div class="mb-3" x-show="fillableSpaces.length > 0 || promptVariables.length > 0" x-cloak>
                            <div class="p-3 rounded" style="background: var(--c-primary-light); border: 1px solid rgba(11,114,133,0.12); border-radius: 10px;">
                                <h3 style="font-family: var(--f-heading); font-weight: 700; font-size: 0.95rem; color: var(--c-dark); margin: 0 0 0.35rem;">{{ __('Remplis tes espaces') }}</h3>
                                <p class="small mb-2" style="color: var(--c-text-muted); font-size: 0.8rem;">{{ __('Ton prompt contient des espaces à remplir. Complète-les ici : la copie et « Ouvrir dans » utiliseront automatiquement tes réponses (les champs laissés vides gardent le mot de départ).') }}</p>
                                <template x-for="(sp, spIdx) in fillableSpaces" :key="sp.text">
                                    <div class="mb-2">
                                        {{-- Nom en évidence (panel round 2, Gemini) : l'étiquette porte le
                                             concept « texte à trous » pour un néophyte - graisse forte +
                                             couleur charte, plus proéminente que le champ. --}}
                                        <label class="form-label mb-1 d-block" style="font-size: 0.86rem; font-weight: 700; color: var(--c-dark);" :for="'cpSpaceFill-' + spIdx">
                                            <span x-text="sp.text.length > 24 ? sp.text.slice(0, 24) + '…' : sp.text" :title="sp.text"></span>
                                        </label>
                                        {{-- Couche 2 (canonKey, 2026-08-09) : :value/@input remplace
                                             x-model="spaceValues[sp.text]" - un renommage fusionné peut écrire la
                                             valeur sous une clé canonique différente de la forme littérale exacte
                                             de sp.text (voir spaceValueFor()/setSpaceValue() dans
                                             constructeur-prompts-core.js), jamais implémenté ici dans la vue. --}}
                                        <input type="text" :id="'cpSpaceFill-' + spIdx" class="form-control form-control-sm" :value="spaceValueFor(sp)" @input="setSpaceValue(sp, $event.target.value)" :placeholder="sp.text" autocomplete="off" :aria-label="sp.text"
                                            @focus="focusedSpaceText = sp.text" @blur="focusedSpaceText = ''">
                                        {{-- Pastilles du déjà-dit (bonification 2026-08-07) : jusqu'à 3
                                             dernières valeurs saisies pour cet espace (la plus récente en
                                             premier, voir spaceLastValues/_recordSpaceLastValues() dans
                                             constructeur-prompts-core.js), discret - aucune pastille si
                                             aucune valeur passée. Un clic remplit le champ. --}}
                                        <div class="d-flex flex-wrap gap-1 mt-1" x-show="spaceLastValuesFor(sp).length > 0">
                                            <template x-for="lv in spaceLastValuesFor(sp).filter(function(v){ return v !== spaceValueFor(sp); })" :key="lv">
                                                <button type="button" class="ct-btn ct-btn-outline ct-btn-xs" style="min-height:44px;" @click="setSpaceValue(sp, lv)" :aria-label="'{{ __('Reprendre :') }} ' + lv">
                                                    <span x-text="lv.length > 28 ? lv.slice(0, 28) + '…' : lv"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                                <template x-for="v in promptVariables" :key="v">
                                    <div class="mb-2">
                                        <label class="form-label fw-medium mb-1 d-block" style="font-size: 0.82rem;">
                                            <span x-text="v"></span>
                                            <input type="text" class="form-control form-control-sm mt-1" x-model="varValues[v]" autocomplete="off">
                                        </label>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Vérifications (ex-« Diagnostic rapide », renommé + sous-titre explicatif au
                             correctif #5, 2026-08-05 ; Option 3 hybride, Partie A — 2026-07-26) :
                             détection par règles simples, ZÉRO IA, ZÉRO appel réseau. Chaque manque
                             pointe vers la section « Réglages avancés » correspondante en un clic.
                             Disclosure repliée par défaut (correctif #3), rouvrable en 1 clic.
                             Correctif étape prématurée (2026-08-06) : diagnostic.issues est maintenant
                             filtré par étape (voir get diagnostic() dans le JS) - le panneau ne doit donc
                             jamais s'ouvrir vide. Le message « tout est beau » (diagnostic.ok) n'a de sens
                             qu'une fois toutes les étapes atteintes, donc réservé à l'étape 4 (dernière) ;
                             avant, un diagnostic.ok = true signifie seulement « rien à signaler POUR
                             L'INSTANT », pas « prompt complet » - le panneau reste caché dans ce cas. --}}
                        <div class="ct-disclosure" x-show="isValid && (diagnostic.issues.length > 0 || (diagnostic.ok && step === 4))" x-cloak>
                            <button type="button" class="ct-disclosure__toggle" @click="checksOpen = !checksOpen" :aria-expanded="checksOpen.toString()" aria-controls="cpChecksPanel">
                                <span class="ct-disclosure__chevron" :class="{ 'ct-disclosure__chevron--open': checksOpen }" aria-hidden="true">▸</span>
                                🔎 {{ __('Vérifications') }}
                            </button>
                            <div id="cpChecksPanel" class="ct-disclosure__panel" x-show="checksOpen" x-transition>
                                <p class="small mb-2" style="color: var(--c-text-muted);">{{ __("Ton prompt fonctionne déjà. Ces précisions le rendraient encore meilleur :") }}</p>
                                <div aria-live="polite">
                                    <template x-if="diagnostic.ok">
                                        <p class="mb-0 small" style="color: var(--c-dark);">✓ {{ __('Votre prompt contient les éléments essentiels.') }}</p>
                                    </template>
                                    <template x-if="!diagnostic.ok">
                                        <ul class="mb-0 ps-0" style="list-style:none;">
                                            <template x-for="issue in diagnostic.issues" :key="issue.key">
                                                <li class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2 small" style="color: var(--c-dark);">
                                                    <span x-text="issue.message"></span>
                                                    <button type="button" class="ct-btn ct-btn-outline ct-btn-xs" style="min-height:44px;" @click="openDiagnosticSection(issue.key)">{{ __('Ajouter cette info') }}</button>
                                                </li>
                                            </template>
                                        </ul>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Actions (correctif #3, 2026-08-05) : boutons d'action + « Ouvrir dans »
                             regroupés dans UNE section compacte (.ct-actions-panel), atténuée tant
                             qu'aucun prompt valide n'existe et mise en évidence dès que isValid. --}}
                        {{-- Round 131 (2026-07-30, passe adversariale) : cette alerte est la SEULE explication du
                             blocage des 3 boutons ci-dessous, car le panneau « Vérifications » est en
                             x-show="isValid" - donc caché précisément quand l'utilisateur aurait besoin de
                             savoir ce qui manque. Sans role/aria-live, elle n'était jamais annoncée : les 4
                             autres alertes du même fichier (lignes ~67, 71, 250, 608) en ont toutes.
                             « polite » et non « assertive » : elle apparaît et disparaît au fil de la frappe,
                             une annonce assertive interromprait le lecteur d'écran à chaque bascule. --}}
                        <div class="ct-actions-panel" :class="{ 'ct-actions-panel--active': isValid }">
                        <div x-show="!isValid" id="cpValidityHint" role="status" aria-live="polite" class="alert alert-warning small p-2 mb-2" style="font-size: 0.8rem;">
                            {{ __('Complétez le rôle de l\'IA (étape 1) et votre tâche avec un verbe d\'action (étape 2) pour générer votre prompt.') }}
                        </div>
                        {{-- Espaces à remplir (tâches 1660-1665, point 5) : mention discrète, UNIQUEMENT
                             si au moins un espace remplissable est resté vide - jamais bloquant, le
                             prompt copié garde le mot de départ. --}}
                        <p x-show="isValid && unfilledSpacesCount > 0" x-cloak role="status" aria-live="polite" class="small mb-2" style="color: var(--c-text-muted); font-size: 0.78rem;" x-text="unfilledSpacesMessage"></p>
                        {{-- C2-3 (couche 2, tâches 1660-1665, 2026-08-09) : avis d'orphelines à la copie -
                             ligne discrète NON bloquante près des boutons d'action, LECTURE SEULE (aucune
                             mutation, la copie part quand même). Réutilise spaceMissingCache/pastille
                             introuvable de la couche 1 (voir orphanSpacesCount/orphanSpacesMessage dans
                             constructeur-prompts-core.js). Même patron que unfilledSpacesMessage juste
                             au-dessus (role="status" aria-live="polite"). --}}
                        <p x-show="isValid && orphanSpacesCount > 0" x-cloak role="status" aria-live="polite" class="small mb-2" style="color: var(--c-text-muted); font-size: 0.78rem;" x-text="orphanSpacesMessage"></p>
                        <div class="d-flex gap-2 mb-3 flex-wrap">
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
                            <div id="cpMetaPromptPanel" class="mb-3 p-3 rounded" style="border: 1.5px solid var(--c-primary); border-radius: var(--r-base); background:#fff;" aria-live="polite">
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
                        {{-- #166 GEO/UX : ouvrir le prompt directement dans une IA (le prompt est aussi copié).
                             IA préférée mémorisée (bonification 2026-08-07) : tant qu'aucun choix réel n'a
                             été mémorisé (openTargetHasPref faux, voir _loadOpenTargetPref()/
                             _recordOpenTargetPref() dans constructeur-prompts-core.js), comportement
                             INCHANGÉ - 5 boutons à plat. Dès qu'un choix a été fait (cette session ou une
                             précédente, via localStorage), UN bouton principal « Ouvrir dans {IA} » prend le
                             dessus et les 4 autres destinations se replient derrière <details> « Autres
                             choix » (cible tactile 44px conservée). --}}
                        <template x-if="!openTargetHasPref">
                        <div class="d-flex gap-2 mb-0 flex-wrap align-items-center">
                            <span class="text-muted small">{{ __('Ouvrir dans') }} :</span>
                            <button class="ct-btn ct-btn-outline ct-btn-sm" :disabled="!isValid" :style="'min-height:44px;' + (!isValid ? 'opacity:0.5;cursor:not-allowed;' : '')" @click="openIn('chatgpt')">ChatGPT</button>
                            <button class="ct-btn ct-btn-outline ct-btn-sm" :disabled="!isValid" :style="'min-height:44px;' + (!isValid ? 'opacity:0.5;cursor:not-allowed;' : '')" @click="openIn('claude')">Claude</button>
                            <button class="ct-btn ct-btn-outline ct-btn-sm" :disabled="!isValid" :style="'min-height:44px;' + (!isValid ? 'opacity:0.5;cursor:not-allowed;' : '')" @click="openIn('perplexity')">Perplexity</button>
                            <button class="ct-btn ct-btn-outline ct-btn-sm" :disabled="!isValid" :style="'min-height:44px;' + (!isValid ? 'opacity:0.5;cursor:not-allowed;' : '')" @click="openIn('gemini')">Gemini</button>
                            <button class="ct-btn ct-btn-outline ct-btn-sm" :disabled="!isValid" :style="'min-height:44px;' + (!isValid ? 'opacity:0.5;cursor:not-allowed;' : '')" @click="openIn('mistral')">Mistral</button>
                        </div>
                        </template>
                        <template x-if="openTargetHasPref">
                        <div class="d-flex gap-2 mb-0 flex-wrap align-items-center">
                            <button class="ct-btn ct-btn-primary" :disabled="!isValid" :style="'min-height:44px;' + (!isValid ? 'opacity:0.5;cursor:not-allowed;' : '')" @click="openIn(openTarget)" :aria-label="'{{ __('Ouvrir dans') }} ' + openTargetLabel" x-text="'{{ __('Ouvrir dans') }} ' + openTargetLabel"></button>
                            <details>
                                <summary class="small" style="cursor:pointer; color: var(--c-text-muted); min-height:44px; display:inline-flex; align-items:center; padding: 4px 6px;">{{ __('Autres choix') }}</summary>
                                <div class="d-flex gap-2 mt-2 flex-wrap align-items-center">
                                    <template x-for="t in ['chatgpt','claude','perplexity','gemini','mistral'].filter(function(x){ return x !== openTarget; })" :key="t">
                                        <button class="ct-btn ct-btn-outline ct-btn-sm" :disabled="!isValid" :style="'min-height:44px;' + (!isValid ? 'opacity:0.5;cursor:not-allowed;' : '')" @click="openIn(t)" x-text="openTargetNames[t]"></button>
                                    </template>
                                </div>
                            </details>
                        </div>
                        </template>
                        @php
                            // Relances de secours (bonification 2026-08-07) : textes EXACTS demandés,
                            // définis UNE seule fois (DRY) - servent à la fois de libellé affiché et de
                            // contenu copié via copyText() (composant copier+toast DRY déjà existant,
                            // aucun nouvel appel réseau).
                            $followUpPrompts = [
                                __("C'est trop long : refais la même chose en moitié moins de mots."),
                                __("C'est trop vague : reprends avec des exemples concrets."),
                                __("Le ton ne convient pas : réécris le même contenu sur un ton plus simple et chaleureux."),
                            ];
                        @endphp
                        <div class="mt-3 pt-3" style="border-top: 1px dashed rgba(11,114,133,0.25);" x-show="isValid" x-cloak>
                            <p class="small mb-2" style="color: var(--c-text-muted); font-size: 0.8rem;"><strong>{{ __('Si la réponse déçoit, collez une relance :') }}</strong></p>
                            <div class="d-flex flex-column gap-2">
                                @foreach ($followUpPrompts as $followUp)
                                <button type="button" class="ct-btn ct-btn-outline ct-btn-sm" style="min-height:44px; text-align:left; white-space:normal;" @click="copyText(@js($followUp))">{{ $followUp }}</button>
                                @endforeach
                            </div>
                        </div>
                        </div>
                        <div class="mb-4"></div>

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

                        {{-- Rétention locale invités (#1580, 2026-08-07) : historique AUTOMATIQUE
                             (max 10) des derniers prompts générés, pour les visiteurs NON connectés
                             uniquement - bloc DISTINCT de « Historique » ci-dessus (lié au bouton
                             "Sauvegarder", qui exige un compte et n'enregistre donc jamais rien pour
                             un invité). Clé localStorage séparée et versionnée (cpGuestHistory_v1,
                             voir constructeur-prompts-core.js). --}}
                        <template x-if="!isAuthenticated && guestHistory.length > 0">
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h3 style="font-family: var(--f-heading); font-weight: 700; margin: 0; font-size: 1rem;">{{ __('Mes derniers prompts (sur cet appareil)') }} (<span x-text="guestHistory.length"></span>)</h3>
                                    <button class="ct-btn ct-btn-outline-danger ct-btn-xs" style="min-height:44px;" @click="clearGuestHistory()">{{ __('Tout effacer') }}</button>
                                </div>
                                <p class="small mb-2" style="color: var(--c-text-muted); font-size: 0.75rem;">🔒 {{ __('Conservés uniquement dans ton navigateur, jamais envoyés au serveur.') }}</p>
                                <template x-for="(g, gi) in guestHistory" :key="g.date + '_' + gi">
                                    <div class="d-flex justify-content-between align-items-center p-2 mb-1 rounded" style="background: #f8f9fa; font-size: 0.8rem;">
                                        <div class="flex-fill" style="cursor: pointer;" @click="loadGuestHistoryEntry(gi)">
                                            <strong x-text="g.title"></strong>
                                            <div class="text-muted" x-text="new Date(g.date).toLocaleString('fr-CA')"></div>
                                        </div>
                                        <div class="d-flex gap-1 ms-2">
                                            <button class="ct-btn ct-btn-outline ct-btn-xs" style="min-height:44px;" @click="loadGuestHistoryEntry(gi)">{{ __('Recharger') }}</button>
                                            <button class="ct-btn ct-btn-outline-danger ct-btn-xs" @click.stop="deleteGuestHistoryEntry(gi)" style="min-height:44px; min-width:44px; padding:1px 5px;">✕</button>
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
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
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
                <h4 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); border-bottom: 2px solid var(--c-primary); padding-bottom: 0.5rem;">{{ __('Comment ça marche') }}</h4>
                <ul>
                    <li><strong>{{ __('Votre objectif') }}</strong> : {{ __('choisissez un rôle pour l\'IA (étape 1), puis décrivez votre tâche avec un verbe d\'action (étape 2) - l\'aperçu se met à jour à mesure que vous complétez chaque étape') }}</li>
                    <li><strong>{{ __('Votre demande') }}</strong> : {{ __('votre prompt s\'affiche aussitôt, colorisé pour montrer ce que vous avez écrit et ce que l\'outil a ajouté ; les réglages toujours visibles permettent d\'ajuster au besoin le rôle de l\'IA, le verbe, le format, la technique et les contraintes') }}</li>
                </ul>
                <h4 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); border-bottom: 2px solid var(--c-primary); padding-bottom: 0.5rem; margin-top: 1.5rem;">{{ __('Les contraintes utiles (dans les réglages)') }}</h4>
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

{{-- Garde-fou fusion au renommage (C2-2, couche 2, tâches 1660-1665, 2026-08-09) : confirmation
     UNIQUE non punitive avant de fusionner un espace vers un texte déjà présent ailleurs - jamais
     de confirm() natif (règle 7), réutilise le mécanisme de dialogue MODAL du thème (voir
     Modules/Core/resources/views/components/confirm-modal.blade.php, déjà utilisé tel quel par
     confirmDeleteCard() plus haut dans ce fichier via open-confirm-global). Instance DÉDIÉE
     (variante "info", icône neutre) distincte de la modale "global" (variante "danger", réservée
     aux suppressions) : ce n'est pas une suppression, rien n'est perdu si on annule. Déclenchée
     par _confirmRenameMergeIfNeeded() dans constructeur-prompts-core.js. --}}
<x-core::confirm-modal name="cp-rename-merge" :confirm-label="__('Confirmer la fusion')" variant="info" icon="🔗" />

@include('fronttheme::partials.tools-newsletter-cta', ['toolSource' => 'constructeur-prompts'])
@endsection

@push('head')
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
        // Round 152 (2026-08-01) : « repliés par défaut » retiré (trouvé par la passe adversariale)
        // - les réglages avancés sont désormais des blocs TOUJOURS visibles, plus un accordéon replié.
        'description' => __('Outil gratuit et interactif pour créer des prompts optimisés en partant de votre objectif (rédiger, résumer, analyser, apprendre...), avec réglages avancés en blocs toujours visibles (rôle de l\'IA, audience, format de sortie). Compatible ChatGPT, Claude, Gemini, Mistral et tous les LLMs. Sauvegarde compte ou navigateur, partage natif, mode plein écran.'),
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
            __('Assistant guidé en 4 étapes (rôle, tâche, audience, réglages avancés) pour démarrer sans jargon'),
            // Round 152 (2026-08-01) : cette entrée décrivait encore l'ancien accordéon
            // (« repliés par défaut ») après son retrait - trouvé par la passe adversariale, pas
            // par les tests. Mise à jour pour refléter la réalité (5 blocs toujours visibles) ;
            // Round78AdversarialFixesTest.php mis à jour en conséquence pour la même raison.
            __('Réglages utiles regroupés en blocs toujours visibles (rôle de l\'IA, verbe, format, exemples, contraintes)'),
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
@php
$defaultPersonas = [['value'=>'expert_marketing','label'=>'Expert en marketing digital'],['value'=>'redacteur_web','label'=>'Rédacteur web professionnel'],['value'=>'enseignant','label'=>'Enseignant pédagogue'],['value'=>'developpeur','label'=>'Développeur senior'],['value'=>'consultant','label'=>'Consultant en stratégie'],['value'=>'graphiste','label'=>'Graphiste créatif'],['value'=>'analyste','label'=>'Analyste de données'],['value'=>'gestionnaire','label'=>'Gestionnaire de projet'],['value'=>'coach','label'=>'Coach professionnel'],['value'=>'journaliste','label'=>'Journaliste d\'investigation'],['value'=>'chercheur','label'=>'Chercheur scientifique'],['value'=>'rh','label'=>'Spécialiste en ressources humaines'],['value'=>'concepteur_pedagogique','label'=>'Concepteur pédagogique'],['value'=>'community_manager','label'=>'Gestionnaire de médias sociaux'],['value'=>'copywriter','label'=>'Rédacteur publicitaire (copywriter)'],['value'=>'formateur','label'=>'Formateur en entreprise'],['value'=>'adjoint_admin','label'=>'Adjoint administratif']];
$defaultVerbs = ['Rédige','Analyse','Crée','Génère','Explique','Compare','Résume','Traduis','Optimise','Évalue','Développe','Conçois','Planifie','Diagnostique'];
// Liste recalibrée sur l'audience réelle du site (consensus panel Codex/DeepSeek/Perplexity
// 2026-08-06, tâche 1633 : public scolaire d'abord, familles MEQ) ; les anciennes valeurs
// (pro/debutants/entrepreneurs/techniques) sont remappées côté JS à la restauration.
$defaultAudiences = [['value'=>'eleves_primaire','label'=>'Élèves du primaire'],['value'=>'eleves_secondaire','label'=>'Élèves du secondaire'],['value'=>'etudiants','label'=>'Étudiants'],['value'=>'parents','label'=>'Parents'],['value'=>'collegues','label'=>'Collègues de travail'],['value'=>'direction','label'=>'Direction ou gestionnaires'],['value'=>'clients','label'=>'Clients'],['value'=>'grand_public','label'=>'Grand public']];
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
    // Reformulé (tâche 1649, panel 5/5 unanime + EMNLP 2024/Wharton 2025) : un rôle oriente
    // ton/style/vocabulaire, il n'améliore ni l'expertise ni l'exactitude - l'ancien texte surpromettait.
    'persona' => __('Donner un rôle à l\'IA oriente le ton, le style et le vocabulaire de sa réponse - mais ne la rend ni plus experte ni plus fiable. Ex: « Tu es un expert marketing » donnera un ton plus stratégique ; pour la justesse, donnez du contexte et des consignes précises.'),
    'verb' => __('Choisir un verbe d\'action précise ce que l\'IA doit faire : rédiger, analyser, résumer, créer... Le verbe détermine le type de résultat.'),
    // Aides « espace à remplir » réécrites en geste (tâches 1660-1665, panel multi-IA 5 rounds,
    // 2026-08-07) : l'astuce {{sujet}} (accolades visibles) est remplacée par le geste de sélection,
    // conforme à la RÈGLE D'OR de cette fonctionnalité - l'utilisateur ne doit jamais voir de
    // syntaxe. Les {{...}} restent fonctionnels (mécanique #1593b intacte) mais ne sont plus mis
    // de l'avant dans cette aide.
    'taskObject' => __('Décrivez clairement et précisément ce que l\'IA doit produire. Plus vous donnez de détails, meilleur sera le résultat. Astuce : sélectionnez un mot de votre texte et cliquez sur « En faire un espace à remplir » - à chaque réutilisation, l\'outil vous demandera la nouvelle valeur (par exemple le sujet de la semaine) sans que vous ayez à réécrire le reste.'),
    // #1593a (2026-08-07) : contexte additionnel, distinct de la tâche - même emplacement/pattern
    // que les autres clés de $pbHelps ci-dessus (toutes injectées via window.promptBuilderConfig.helps).
    'contextInfo' => __('Informations de fond utiles que l\'IA doit connaître sans qu\'elles fassent partie de la demande elle-même : ce qui a déjà été essayé, des contraintes, le contexte du projet... Ici aussi, vous pouvez sélectionner un mot et cliquer sur « En faire un espace à remplir » pour pouvoir le changer facilement la prochaine fois.'),
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
// LOT 3 (2026-08-06, pédagogie Perplexity : effet concret en libellé principal, nom technique
// discret) : chaque texte d'aide commence désormais par le nom de la méthode entre parenthèses,
// affiché en petit gris SOUS le sélecteur (voir techniqueHints dans le Blade, ~471) - jamais dans
// le libellé principal de l'<option> elle-même (un <select> natif ne permet pas de styliser une
// partie du texte d'une option, d'où ce choix d'emplacement).
$pbTechniqueHints = [
    'zero-shot' => __("(Méthode : zero-shot) L'IA répond directement, sans exemple ni étape intermédiaire."),
    'zero-shot-cot' => __("(Méthode : chaîne de pensée) L'IA réfléchit en interne avant de répondre, sans montrer ce raisonnement."),
    'few-shot' => __("(Méthode : few-shot) Vous donnez 2-3 exemples du résultat attendu pour guider l'IA."),
    'few-shot-cot' => __("(Méthode : few-shot + chaîne de pensée) Exemples fournis, puis raisonnement détaillé appliqué au même modèle."),
    'iterative' => __("(Méthode : décomposition guidée) L'IA avance étape par étape et attend votre accord avant de continuer."),
    'reformulation' => __("(Méthode : reformulation) L'IA reformule d'abord ta demande dans ses mots, puis répond."),
    'auto-verification' => __("(Méthode : auto-vérification) L'IA relit sa réponse, corrige ses erreurs et ses oublis avant de te la livrer."),
    'variantes-comparees' => __("(Méthode : variantes comparées) L'IA propose 2 ou 3 versions différentes et recommande la meilleure."),
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
// Round 152 (2026-08-01, écran 3) : formats/longueurs/tons/techniques/langues/profils déplacés de
// listes <option> statiques vers des tableaux {value,label} (même contrat que $pbPersonas/
// $pbAudiences ci-dessus) pour être rendus en CARTES cliquables (x-tools::prompt-card) via x-for,
// sans dupliquer leur contenu entre ce fichier Blade et le JS (DRY). `value` reste TOUJOURS en
// français, non traduit - c'est le texte injecté tel quel dans le prompt généré (même règle que
// personas/verbes/audiences, voir le commentaire à leur définition) ; seul `label` (affiché) passe
// par __().
$defaultFormats = [
    ['value' => 'Liste à puces', 'label' => __('Liste à puces')],
    ['value' => 'Paragraphes détaillés', 'label' => __('Paragraphes détaillés')],
    ['value' => 'Tableau structuré', 'label' => __('Tableau structuré')],
    ['value' => 'Plan hiérarchisé', 'label' => __('Plan hiérarchisé')],
    ['value' => 'Étapes numérotées', 'label' => __('Étapes numérotées')],
    ['value' => 'Format JSON', 'label' => __('Format JSON')],
    ['value' => 'Diagramme Mermaid', 'label' => __('Diagramme Mermaid')],
    ['value' => 'Questionnaire / QCM avec corrigé', 'label' => __('Questionnaire / QCM avec corrigé')],
    ['value' => 'Grille d\'évaluation (rubrique)', 'label' => __('Grille d\'évaluation (rubrique)')],
    ['value' => 'Fiche pratique (1 page)', 'label' => __('Fiche pratique (1 page)')],
    ['value' => 'Modèle réutilisable (gabarit)', 'label' => __('Modèle réutilisable (gabarit)')],
    ['value' => 'FAQ structurée', 'label' => __('FAQ structurée')],
];
$defaultLengths = [
    ['value' => 'Concis (100-200 mots)', 'label' => __('Concis (100-200 mots)')],
    ['value' => 'Modéré (300-500 mots)', 'label' => __('Modéré (300-500 mots)')],
    ['value' => 'Détaillé (500-800 mots)', 'label' => __('Détaillé (500-800 mots)')],
    ['value' => 'Exhaustif (800+ mots)', 'label' => __('Exhaustif (800+ mots)')],
    ['value' => '3 à 5 points clés', 'label' => __('3 à 5 points clés')],
    ['value' => '5 à 10 points clés', 'label' => __('5 à 10 points clés')],
];
$defaultTones = [
    ['value' => 'Professionnel', 'label' => __('Professionnel')],
    ['value' => 'Accessible et pédagogique', 'label' => __('Accessible et pédagogique')],
    ['value' => 'Technique et précis', 'label' => __('Technique et précis')],
    ['value' => 'Chaleureux et engageant', 'label' => __('Chaleureux et engageant')],
    ['value' => 'Académique', 'label' => __('Académique')],
    ['value' => 'Créatif et dynamique', 'label' => __('Créatif et dynamique')],
    ['value' => 'Conversationnel', 'label' => __('Conversationnel')],
    ['value' => 'Persuasif', 'label' => __('Persuasif')],
    ['value' => 'Neutre et factuel', 'label' => __('Neutre et factuel')],
    ['value' => 'Empathique et bienveillant', 'label' => __('Empathique et bienveillant')],
    ['value' => 'Motivant et inspirant', 'label' => __('Motivant et inspirant')],
];
$defaultTechniques = [
    ['value' => 'zero-shot', 'label' => __('Réponse directe (par défaut)')],
    ['value' => 'zero-shot-cot', 'label' => __('Réponse directe + réflexion étape par étape')],
    ['value' => 'few-shot', 'label' => __('Avec des exemples')],
    ['value' => 'few-shot-cot', 'label' => __('Avec des exemples + réflexion étape par étape')],
    ['value' => 'iterative', 'label' => __('Par étapes, avec votre validation à chaque fois')],
    // LOT 3 (2026-08-06, 3 nouvelles méthodes) : instructions générées dans get prompt()
    // (constructeur-prompts-core.js, section === TECHNIQUE ===).
    ['value' => 'reformulation', 'label' => __('Reformuler la demande avant de répondre')],
    ['value' => 'auto-verification', 'label' => __('Vérifier et corriger sa réponse avant de la donner')],
    ['value' => 'variantes-comparees', 'label' => __('Proposer 2 ou 3 versions et recommander la meilleure')],
];
$defaultLanguages = [
    ['value' => 'fr', 'label' => __('Français')],
    ['value' => 'en', 'label' => 'English'],
    ['value' => 'es', 'label' => 'Español'],
];
$defaultProfiles = [
    ['value' => 'texte', 'label' => __('Texte'), 'hint' => __('Écriture humaine, typographie française, ton.')],
    ['value' => 'programmation', 'label' => __('Programmation'), 'hint' => __('Aucune règle de style français ; ajoute la mise en forme du code.')],
    ['value' => 'traduction', 'label' => __('Traduction'), 'hint' => __('Aucune règle de français du Québec appliquée au résultat.')],
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
    formats: @json($defaultFormats),
    lengths: @json($defaultLengths),
    tones: @json($defaultTones),
    techniques: @json($defaultTechniques),
    languages: @json($defaultLanguages),
    profiles: @json($defaultProfiles),
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
        diagnosticFormat: @json(__("Tu n'as pas indiqué la forme de la réponse attendue (texte court, liste, tableau...) ni sa longueur.")),
        diagnosticAudience: @json(__("Tu n'as pas indiqué à qui s'adresse la réponse (par exemple : tes élèves, des parents, des collègues). L'IA adaptera mieux son ton si elle le sait.")),
        diagnosticContraintes: @json(__("Tu n'as coché aucune règle à faire respecter (par exemple : éviter le style trop « IA », poser une question si la demande est floue).")),
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
        close: @json(__('Fermer')),
        newCardTitle: @json(__('Nouvelle carte')),
        untitledCard: @json(__('Carte sans titre')),
        deleteCardConfirm: @json(__('Supprimer cette carte ?')),
        importedPromptName: @json(__('Prompt importé')),
        summaryRole: @json(__('L\'IA va se comporter comme ')),
        summaryRoleArticle: @json(__('un(e) ')),
        summaryAction: @json(__('Tâche demandée : ')),
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
        iconLabelPrefix: @json(__('Icône : ')),
        // Round 152 (2026-08-01, écran 3) : lignes « Ajouté : ... » (voir feedbackAudience et les
        // 4 autres getters feedback* de constructeur-prompts-core.js) - même convention que
        // summary* plus haut : préfixe traduit, valeur brute (déjà en français, non traduite par
        // convention - personas/verbes/audiences/formats/longueurs/tons/techniques) concaténée telle
        // quelle.
        addedPrefix: @json(__('Sera inclus dans ton prompt : ')),
        addedAudience: @json(__('Sera inclus dans ton prompt : un niveau de langage adapté à ')),
        fragVerb: @json(__('verbe ')),
        fragFormat: @json(__('format ')),
        fragLength: @json(__('longueur ')),
        fragRole: @json(__('rôle ')),
        fragTone: @json(__('ton ')),
        fragTypo: @json(__('typographie française stricte')),
        fragAntiAI: @json(__('écriture naturelle anti-IA')),
        fragCot: @json(__('raisonnement affiché')),
        fragAsk: @json(__('questions de clarification si besoin')),
        fragCanvas: @json(__('document modifiable')),
        fragLangEn: @json(__('réponse en anglais')),
        fragLangEs: @json(__('réponse en espagnol')),
        fragCustom: @json(__('vos contraintes personnalisées')),
        fragFewShot: @json(__('des exemples')),
        fragFewShotCot: @json(__('des exemples et une réflexion visible')),
        fragZeroShotCot: @json(__('réflexion visible')),
        fragIterative: @json(__('une validation à chaque étape')),
        fragDelimiters: @json(__('délimiteurs ###')),
        // LOT 3 (2026-08-06) : fragments « Ajouté : ... » pour les 3 nouvelles méthodes (voir
        // feedbackModele, constructeur-prompts-core.js).
        fragReformulation: @json(__('une reformulation de la demande')),
        fragAutoVerification: @json(__('une vérification finale')),
        fragVariantesComparees: @json(__('plusieurs versions comparées')),
        // LOT 1 (2026-08-06) : raisons affichées sous une carte de format désactivée (garde-fous
        // multi-sélection - voir formatDisabledReason, constructeur-prompts-core.js).
        formatMaxReason: @json(__('Maximum 3 formats.')),
        formatExclusiveReason: @json(__('Ce format technique doit être utilisé seul.')),
        profileFeedbackTexte: @json(__("Vous avez choisi Texte : les règles d'écriture humaine et de typographie s'appliquent selon vos cases cochées.")),
        profileFeedbackProgrammation: @json(__("Vous avez choisi Programmation : j'ajoute les règles de mise en forme du code, je retire les règles de français du Québec.")),
        profileFeedbackTraduction: @json(__('Vous avez choisi Traduction : je retire les règles de français du Québec du résultat.')),
        // Espaces à remplir (tâches 1660-1665, panel multi-IA 5 rounds, 2026-08-07) : messages
        // JS dynamiques (toasts, mention "non rempli") - même pont i18n que le reste de ce fichier.
        spaceTooShort: @json(__('Choisis un mot plus précis pour éviter les remplacements imprévus.')),
        spaceAlreadyExists: @json(__('Cet espace existe déjà.')),
        spaceNewLabel: @json(__('information à préciser')),
        spaceUnfilledOne: @json(__('1 espace non rempli, on garde le mot de départ.')),
        spaceUnfilledMany: @json(__('{count} espaces non remplis, on garde les mots de départ.')),
        // Correctif UX « lien persistant pendant le nommage » (2026-08-09) : même patron
        // {placeholder} que spaceUnfilledMany ci-dessus, consommé par get spacePendingHintMessage()
        // dans constructeur-prompts-core.js.
        spacePendingHint: @json(__("Le texte « {text} » vient d'être ajouté dans ta demande ci-dessus - donne-lui un nom parlant.")),
        // Couche 2 (tâches 1660-1665, boucle 5 oracles 2026-08-09) : C2-2 (garde-fou de fusion au
        // renommage, voir _confirmRenameMergeIfNeeded()) et C2-3 (avis d'orphelines à la copie, voir
        // orphanSpacesMessage()) dans constructeur-prompts-core.js.
        spaceRenameMergeOne: @json(__('Ce texte apparaît déjà 1 fois dans ta demande - toutes les occurrences seront remplies ensemble.')),
        spaceRenameMergeMany: @json(__('Ce texte apparaît déjà {count} fois dans ta demande - toutes les occurrences seront remplies ensemble.')),
        spaceOrphanOne: @json(__("1 espace à remplir n'est plus dans ton texte.")),
        spaceOrphanMany: @json(__('{count} espaces à remplir ne sont plus dans ton texte.'))
    }
};
</script>
<script src="{{ asset('assets/tools/constructeur-prompts/constructeur-prompts-core.js') }}?v={{ config('version.semver') }}" defer></script>
@endpush
