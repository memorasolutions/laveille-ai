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
                    <div class="card-body p-4 p-md-5" x-data="promptBuilder()" x-init="init()">
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
                                <button class="ct-btn ct-btn-primary ct-btn-sm" @click="addToHistory()" :disabled="!isValid || saving" style="white-space:nowrap;"
                                        x-text="saving ? '{{ __('Sauvegarde...') }}' : (_editingId ? '{{ __('Mettre à jour') }}' : '{{ __('Sauvegarder') }}')"></button>
                            </div>
                            <div class="small mt-2 mb-0" style="font-size: 0.8rem; color: var(--c-text-muted);">
                                {{ __('Retrouvez vos prompts dans') }} <a href="{{ route('user.saved') }}?tab=prompts" style="color: #0A3A42; font-weight: 600; text-decoration: underline;">{{ __('vos sauvegardes') }}</a>.
                            </div>
                            <template x-if="saveError">
                                <div class="alert alert-danger small p-1 mt-2 mb-0" style="font-size: 0.8rem; border-radius: 6px;" x-text="saveError"></div>
                            </template>
                            <template x-if="hasLocalData">
                                <div class="small mt-2 mb-0" style="font-size: 0.8rem; color: var(--c-text-muted);">
                                    {{ __('Des prompts de votre navigateur ont été trouvés.') }}
                                    <button class="ct-btn ct-btn-outline ct-btn-xs ms-1" @click="importLocalStorage()">{{ __('Importer') }}</button>
                                </div>
                            </template>
                        </div>
                        <template x-if="!isAuthenticated">
                            <div class="mt-3 mb-3 p-2 rounded" style="background: rgba(11,114,133,0.06); border: 1px solid rgba(11,114,133,0.15); border-radius: 10px; font-size: 0.85rem;">
                                <strong style="color: var(--c-primary);">{{ __('Connectez-vous') }}</strong> {{ __('pour sauvegarder vos prompts et les retrouver sur tous vos appareils.') }}
                                <button class="ct-btn ct-btn-primary ct-btn-xs ms-1" @click="$dispatch('open-auth-modal')">{{ __('Se connecter') }}</button>
                            </div>
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
                        </style>

                        {{-- Indicateur d'étapes (2 étapes : objectif d'abord, puis la demande) --}}
                        <div class="d-flex justify-content-between mb-4" style="position: relative;">
                            <template x-for="s in [1,2]" :key="s">
                                <div class="text-center" style="flex: 1; position: relative; z-index: 1;">
                                    <div @click="goToStep(s)" style="cursor: pointer; margin: 0 auto; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; transition: all 0.2s;" :style="step >= s ? 'background: var(--c-primary); color: #fff;' : 'background: #e9ecef; color: #6c757d;'" x-text="s"></div>
                                    <small class="d-block mt-1" style="font-size: 0.7rem;" :style="step >= s ? 'color: var(--c-primary); font-weight: 600;' : 'color: #adb5bd;'" x-text="s === 1 ? '{{ __('Votre objectif') }}' : '{{ __('Votre demande') }}'"></small>
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
                            <div class="row g-2 mb-3" role="group" aria-label="{{ __('Choisir un objectif') }}">
                                <template x-for="c in taskCards" :key="c.id">
                                    <div class="col-6 col-md-4">
                                        <button type="button" class="ct-task-card" :class="{ 'ct-task-card--on': selectedTask === c.id }" @click="selectTask(c)" :aria-pressed="selectedTask === c.id">
                                            <span aria-hidden="true" style="font-size:1.3rem;" x-text="c.icon"></span>
                                            <span class="ct-task-card__title" x-text="c.label"></span>
                                            <span class="ct-task-card__desc" x-text="c.description"></span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Étape 2 : Votre demande (essentiel visible, réglages avancés repliés) --}}
                        <div x-show="step === 2" x-transition>
                            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap" x-show="selectedTask">
                                <span class="small p-2 rounded" style="background: var(--c-primary-light); border-left: 3px solid var(--c-primary); color: var(--c-dark);">
                                    {{ __('Objectif choisi :') }} <strong x-text="selectedTaskLabel"></strong>
                                </span>
                                <button type="button" class="ct-btn ct-btn-ghost ct-btn-xs" @click="goToStep(1)">{{ __('Changer d\'objectif') }}</button>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label fw-medium">{{ __('Sur quoi porte votre demande ?') }} <span style="color: #DC2626;">*</span></label>
                                <p class="small mb-2 p-2 rounded" style="font-size: 0.82rem; color: var(--c-dark); background: var(--c-primary-light); border-left: 3px solid var(--c-primary); border-radius: 8px;">🔒 {{ __('Ton texte contient un vrai nom, courriel, numéro ou adresse ? Ne mets jamais tes vraies infos dans une IA. Masque-les d\'abord avec le bouton ci-dessous.') }}</p>
                                <textarea id="cpTaskObject" class="form-control" rows="3" x-model="taskObject" aria-required="true" placeholder="{{ __('Ex: un plan marketing pour le lancement d\'une application mobile au Québec') }}" aria-label="{{ __('Description de la demande') }}"></textarea>
                                <small class="text-muted">{{ __('Décrivez précisément ce que vous voulez obtenir.') }}</small>
                            </div>

                            {{-- Liaison anonymiseur (pattern « module partagé in-page », 100% local) --}}
                            <div class="form-group mb-3">
                                <button id="cpAnonToggle" type="button" class="ct-btn ct-btn-outline ct-btn-sm" aria-expanded="false" aria-controls="cpAnonPanel">🛡️ {{ __('Masquer mes infos personnelles d\'abord') }}</button>
                                <a href="/outils/anonymiseur" class="ct-btn ct-btn-ghost ct-btn-sm ms-1" title="{{ __('Ouvrir l\'anonymiseur complet (restauration des réponses IA)') }}">↗ {{ __('Anonymiseur complet') }}</a>
                                <div id="cpAnonPanel" class="anon-wrap" style="display:none; border:1px solid var(--anon-line,#e2e6ea); border-radius:12px; padding:1rem; margin-top:.75rem; background:#f8fafb;" aria-hidden="true">
                                    <p style="font-size:.85rem; color:#52586a; margin:0 0 .5rem;">🔒 {{ __('100 % local — aucune donnée ne quitte votre navigateur. Sélectionnez un passage, surlignez, anonymisez, puis insérez le texte masqué dans votre tâche.') }}</p>
                                    {{-- Éditeur d'anonymisation RÉUTILISABLE (même UX que /outils/anonymiseur) --}}
                                    <x-tools::anonymizer-editor>
                                        <x-slot:previewActions>
                                            <button type="button" id="btnCopyAnon" class="anon-btn secondary">📋 {{ __('Copier') }}</button>
                                            <button type="button" id="cpAnonInsert" class="anon-btn"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true" focusable="false" style="vertical-align:-2px;margin-right:5px;flex-shrink:0;"><path d="M12 5v14M5 12h14"/></svg>{{ __('Insérer dans la tâche') }}</button>
                                        </x-slot:previewActions>
                                    </x-tools::anonymizer-editor>
                                </div>
                            </div>

                            <div class="row mb-3">
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
                                    <input type="text" class="form-control" x-model="audienceCustom" placeholder="{{ __('Ex: enseignants du secondaire au Québec') }}" aria-label="{{ __('Audience personnalisée') }}">
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

                            {{-- Divulgation progressive (Niveau 2) : un seul panneau, rien n'est supprimé --}}
                            <button type="button" class="ct-advanced-toggle mb-3" @click="showAdvanced = !showAdvanced" :aria-expanded="showAdvanced.toString()" aria-controls="cpAdvancedPanel">
                                <span x-text="showAdvanced ? '{{ __('Masquer tous les réglages') }}' : '{{ __('Afficher tous les réglages') }}'"></span>
                                <span class="ct-chevron" aria-hidden="true">▾</span>
                            </button>

                            <div id="cpAdvancedPanel" x-show="showAdvanced" x-transition x-cloak>
                                <div class="p-3 mb-3 rounded" style="border: 1px solid #e5e7eb; border-radius: 10px;">
                                    <h3 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); font-size: 1rem; margin: 0 0 0.5rem;">{{ __('Rôle que l\'IA doit jouer') }} <span style="color: #DC2626;">*</span></h3>
                                    <div class="d-flex gap-3 mb-2">
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; min-height: 24px; padding: 4px 6px;">
                                            <input type="radio" name="personaType" value="preset" x-model="personaType" style="width:24px;height:24px;accent-color:var(--c-primary);margin:0;flex-shrink:0;cursor:pointer;"> {{ __('Prédéfini') }}
                                        </label>
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; min-height: 24px; padding: 4px 6px;">
                                            <input type="radio" name="personaType" value="custom" x-model="personaType" style="width:24px;height:24px;accent-color:var(--c-primary);margin:0;flex-shrink:0;cursor:pointer;"> {{ __('Personnalisé') }}
                                        </label>
                                    </div>
                                    <div x-show="personaType === 'preset'" class="form-group mb-0">
                                        <select class="form-control" x-model="personaPreset" aria-label="{{ __('Choisir un rôle') }}">
                                            <option value="">{{ __('-- Sélectionnez un rôle --') }}</option>
                                            <template x-for="p in personas" :key="p.value">
                                                <option :value="p.value" x-text="p.label"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div x-show="personaType === 'custom'" class="form-group mb-0">
                                        <input type="text" class="form-control" x-model="personaCustom" placeholder="{{ __('Ex: un expert en cybersécurité spécialisé en PME québécoises') }}" aria-label="{{ __('Rôle personnalisé') }}">
                                    </div>
                                </div>

                                <div class="p-3 mb-3 rounded" style="border: 1px solid #e5e7eb; border-radius: 10px;">
                                    <h3 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); font-size: 1rem; margin: 0 0 0.5rem;">{{ __('Verbe d\'action') }} <span style="color: #DC2626;">*</span></h3>
                                    <div class="d-flex gap-3 mb-2">
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem;">
                                            <input type="radio" name="verbType" value="preset" x-model="verbType" style="width:24px;height:24px;accent-color:var(--c-primary);margin:0;flex-shrink:0;cursor:pointer;"> {{ __('Prédéfini') }}
                                        </label>
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem;">
                                            <input type="radio" name="verbType" value="custom" x-model="verbType" style="width:24px;height:24px;accent-color:var(--c-primary);margin:0;flex-shrink:0;cursor:pointer;"> {{ __('Personnalisé') }}
                                        </label>
                                    </div>
                                    <select class="form-control" x-show="verbType === 'preset'" x-model="verb" :aria-required="verbType === 'preset'" aria-label="{{ __('Verbe d\'action') }}">
                                        <option value="">{{ __('-- Sélectionnez un verbe --') }}</option>
                                        <template x-for="v in verbs" :key="v">
                                            <option :value="v" x-text="v"></option>
                                        </template>
                                    </select>
                                    <input type="text" class="form-control" x-show="verbType === 'custom'" x-model="verbCustom" :aria-required="verbType === 'custom'" placeholder="{{ __('Ex: Reformule, Synthétise, Décortique...') }}" aria-label="{{ __('Verbe personnalisé') }}">
                                </div>

                                <div class="p-3 mb-3 rounded" style="border: 1px solid #e5e7eb; border-radius: 10px;">
                                    <h3 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); font-size: 1rem; margin: 0 0 0.5rem;">{{ __('Format, longueur et langue') }}</h3>
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

                                <div class="p-3 mb-3 rounded" style="border: 1px solid #e5e7eb; border-radius: 10px;">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <h3 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); font-size: 1rem; margin: 0;">{{ __('Comment l\'IA doit réfléchir') }}</h3>
                                        <button class="ct-btn ct-btn-ghost ct-btn-xs" @click="showHelp.technique = !showHelp.technique" style="border-radius:50%;width:22px;height:22px;padding:0;line-height:22px;margin-left:4px;flex-shrink:0;">?</button>
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
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem;">
                                                <input type="checkbox" x-model="useDelimiters" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                                <span>{{ __('Séparer clairement les données du reste (délimiteurs ###)') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div x-show="technique === 'few-shot' || technique === 'few-shot-cot'" class="form-group mb-0">
                                        <label class="form-label fw-medium" style="font-size: 0.85rem;">{{ __('Exemples (2-3 recommandés)') }}</label>
                                        <textarea class="form-control form-control-sm" rows="4" x-model="examples" placeholder="{{ __('Exemple 1 :\nEntrée : ...\nSortie : ...\n\nExemple 2 :\nEntrée : ...\nSortie : ...') }}" aria-label="{{ __('Exemples à donner à l\'IA') }}"></textarea>
                                        <small class="text-muted">{{ __('Donnez 2-3 exemples du résultat attendu pour guider l\'IA.') }}</small>
                                    </div>
                                </div>

                                <div class="p-3 mb-1 rounded" style="border: 1px solid #e5e7eb; border-radius: 10px;">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <h3 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); font-size: 1rem; margin: 0;">{{ __('Contraintes') }}</h3>
                                        <button class="ct-btn ct-btn-ghost ct-btn-xs" @click="showHelp.constraints = !showHelp.constraints" style="border-radius:50%;width:22px;height:22px;padding:0;line-height:22px;margin-left:4px;flex-shrink:0;">?</button>
                                    </div>
                                    <div x-show="showHelp.constraints" x-transition class="alert alert-info small mb-2 p-2" style="font-size: 0.8rem;">
                                        {{ __('Les contraintes limitent ou orientent le comportement de l\'IA. Cochez celles qui correspondent à votre besoin. Elles seront ajoutées automatiquement au prompt.') }}
                                    </div>
                                    <div class="d-flex flex-column gap-2 mb-3">
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem;">
                                            <input type="checkbox" x-model="constraintAntiAI" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                            <span><strong>{{ __('Écriture naturelle (anti-IA)') }}</strong> — {{ __('style humain, phrases variées, pas de formulations génériques') }}</span>
                                        </label>
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem;">
                                            <input type="checkbox" x-model="constraintTypo" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                            <span><strong>{{ __('Règles typographiques') }}</strong> — {{ __('majuscules en début de phrase, pas de tiret cadratin') }}</span>
                                        </label>
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem;">
                                            <input type="checkbox" x-model="constraintCanvas" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                            <span><strong>{{ __('Espace de travail dédié') }}</strong> — {{ __('Canvas (ChatGPT) ou artefact (Claude, Gemini, Mistral)') }}</span>
                                        </label>
                                        <div x-show="constraintCanvas" class="ms-4 mt-1 mb-1 p-2 rounded" style="background: #f0f9ff; border-left: 3px solid var(--c-primary);">
                                            <div class="d-flex flex-wrap gap-2 align-items-end mb-2">
                                                <div>
                                                    <label class="form-label" style="font-size: 0.75rem;">{{ __('IA cible') }}</label>
                                                    <select class="form-control form-control-sm" x-model="canvasAI" style="width: auto;" aria-label="{{ __('IA cible') }}">
                                                        <option value="chatgpt">ChatGPT</option>
                                                        <option value="claude">Claude</option>
                                                        <option value="gemini">Gemini</option>
                                                        <option value="mistral">Mistral</option>
                                                    </select>
                                                </div>
                                            </div>
                                            {{-- 2026-05-05 #104 : toggle universel preset/custom pour les 4 IA --}}
                                            <div class="d-flex gap-3 mb-2" role="radiogroup" aria-label="{{ __('Mode de sélection du format') }}">
                                                <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.8rem;">
                                                    <input type="radio" name="formatMode" value="preset" x-model="formatMode" style="width:24px;height:24px;accent-color:var(--c-primary);margin:0;flex-shrink:0;cursor:pointer;">
                                                    <span>{{ __('Format prédéfini') }}</span>
                                                </label>
                                                <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.8rem;">
                                                    <input type="radio" name="formatMode" value="custom" x-model="formatMode" style="width:24px;height:24px;accent-color:var(--c-primary);margin:0;flex-shrink:0;cursor:pointer;">
                                                    <span>{{ __('Format personnalisé') }}</span>
                                                </label>
                                            </div>
                                            <div x-show="formatMode === 'preset'">
                                                <label class="form-label" style="font-size: 0.75rem;">{{ __('Format de sortie') }}</label>
                                                <select class="form-control form-control-sm" x-model="canvasFormat" style="max-width: 280px;" aria-label="{{ __('Format Canvas prédéfini') }}">
                                                    <option value="">{{ __('-- Aucun --') }}</option>
                                                    <template x-for="f in canvasFormats" :key="f">
                                                        <option :value="f" x-text="f"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div x-show="formatMode === 'custom'">
                                                <label class="form-label" style="font-size: 0.75rem;">{{ __('Format personnalisé') }}</label>
                                                <input type="text" class="form-control form-control-sm" x-model="canvasCustomFormat" placeholder="{{ __('Ex: LaTeX, YAML, BibTeX, AsciiDoc, Apache config, MJML email...') }}" style="max-width: 380px;" aria-label="{{ __('Format personnalisé libre') }}">
                                                <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">{{ __('Décrivez le format souhaité dans vos propres mots — disponible pour les 4 IA.') }}</small>
                                            </div>
                                        </div>
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem;">
                                            <input type="checkbox" x-model="constraintChainOfThought" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                            <span><strong>{{ __('Réflexion étape par étape') }}</strong> — {{ __('utile pour les calculs et les problèmes complexes') }}</span>
                                        </label>
                                        <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem;">
                                            <input type="checkbox" x-model="constraintAskIfUnclear" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                            <span><strong>{{ __('Poser des questions') }}</strong> — {{ __('demander des précisions si nécessaire') }}</span>
                                        </label>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="form-label fw-medium" style="font-size: 0.85rem;">{{ __('Contraintes spécifiques') }}</label>
                                        <textarea class="form-control form-control-sm" rows="2" x-model="constraintCustom" placeholder="{{ __('Ex: éviter le jargon technique, inclure des exemples concrets') }}" aria-label="{{ __('Contraintes personnalisées') }}"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Navigation --}}
                        <div x-show="showValidation && ((step === 1 && !selectedTask) || (step === 2 && (!taskObject || (verbType === 'custom' ? !verbCustom : !verb))))" x-transition class="alert alert-danger small p-2 mb-2" style="font-size: 0.85rem;">
                            <span x-show="step === 1 && !selectedTask">{{ __('Veuillez choisir une carte avant de continuer.') }}</span>
                            <span x-show="step === 2 && verbType === 'preset' && !verb">{{ __('Ouvrez « Afficher tous les réglages » pour choisir un verbe d\'action.') }}</span>
                            <span x-show="step === 2 && verbType === 'custom' && !verbCustom">{{ __('Écrivez votre verbe d\'action dans le champ personnalisé (réglages avancés).') }}</span>
                            <span x-show="step === 2 && (verbType === 'custom' ? !!verbCustom : !!verb) && !taskObject">{{ __('Veuillez décrire votre demande.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <button class="ct-btn ct-btn-outline" @click="prevStep()" x-show="step > 1">{{ __('Précédent') }}</button>
                            <div x-show="step === 1"></div>
                            <button class="ct-btn ct-btn-primary" @click="nextStep()" x-show="step < 2">
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

                        {{-- Actions --}}
                        <div x-show="!isValid" class="alert alert-warning small p-2 mb-2" style="font-size: 0.8rem;">
                            {{ __('Choisissez un objectif (étape 1) et décrivez votre demande (étape 2) pour générer votre prompt.') }}
                        </div>
                        <div class="d-flex gap-2 mb-4 flex-wrap">
                            <button class="ct-btn ct-btn-accent flex-fill" @click="copy()" :disabled="!isValid" :style="!isValid && 'opacity:0.5;cursor:not-allowed;'"
                                    x-text="copied ? '{{ __('Copié !') }}' : '{{ __('Copier le prompt') }}'"></button>
                            <button class="ct-btn ct-btn-outline" @click="exportPrompt()" :disabled="!isValid">{{ __('Exporter .txt') }}</button>
                            <button class="ct-btn ct-btn-outline-danger" @click="armReset()" x-text="resetArmed ? '{{ __('⚠️ Confirmer la réinitialisation') }}' : '{{ __('🔄 Recommencer') }}'"></button>
                        </div>
                        {{-- #166 GEO/UX : ouvrir le prompt directement dans une IA (le prompt est aussi copié) --}}
                        <div class="d-flex gap-2 mb-4 flex-wrap align-items-center">
                            <span class="text-muted small">{{ __('Ouvrir dans') }} :</span>
                            <button class="ct-btn ct-btn-outline ct-btn-sm" :disabled="!isValid" :style="!isValid && 'opacity:0.5;cursor:not-allowed;'" @click="openIn('chatgpt')">ChatGPT</button>
                            <button class="ct-btn ct-btn-outline ct-btn-sm" :disabled="!isValid" :style="!isValid && 'opacity:0.5;cursor:not-allowed;'" @click="openIn('claude')">Claude</button>
                            <button class="ct-btn ct-btn-outline ct-btn-sm" :disabled="!isValid" :style="!isValid && 'opacity:0.5;cursor:not-allowed;'" @click="openIn('perplexity')">Perplexity</button>
                            <button class="ct-btn ct-btn-outline ct-btn-sm" :disabled="!isValid" :style="!isValid && 'opacity:0.5;cursor:not-allowed;'" @click="openIn('gemini')">Gemini</button>
                        </div>

                        {{-- Historique (visible seulement pour les non-connectes, les connectes ont "Mes prompts") --}}
                        <template x-if="!isAuthenticated && history.length > 0">
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h3 style="font-family: var(--f-heading); font-weight: 700; margin: 0; font-size: 1rem;">{{ __('Historique') }} (<span x-text="history.length"></span>)</h3>
                                    <button class="ct-btn ct-btn-outline-danger ct-btn-xs" @click="clearHistory()">{{ __('Effacer') }}</button>
                                </div>
                                <template x-for="(h, i) in history" :key="i">
                                    <div class="d-flex justify-content-between align-items-center p-2 mb-1 rounded" style="background: #f8f9fa; font-size: 0.8rem;">
                                        <div class="flex-fill" style="cursor: pointer;" @click="copyText(h.prompt)">
                                            <strong x-text="h.name"></strong>
                                            <div class="text-muted" x-text="h.prompt.substring(0, 80) + '...'"></div>
                                        </div>
                                        <div class="d-flex gap-1 ms-2">
                                            <button class="ct-btn ct-btn-outline ct-btn-xs" @click="copyText(h.prompt)">{{ __('Copier') }}</button>
                                            <button class="ct-btn ct-btn-outline-danger ct-btn-xs" @click.stop="deletePrompt(h.id, i)" style="padding:1px 5px;">✕</button>
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
<div class="modal fade" id="promptHelpModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: var(--r-base);">
            <div class="modal-header" style="background: var(--c-primary); border-radius: var(--r-base) var(--r-base) 0 0;">
                <h4 class="modal-title" style="color: #fff; font-family: var(--f-heading); font-weight: 700;">{{ __('Comment créer un bon prompt') }}</h4>
                <button type="button" onclick="jQuery('#promptHelpModal').modal('hide')" style="background: none; border: none; color: #fff !important; opacity: 1; font-size: 1.5rem; font-weight: 700; cursor: pointer; float: right;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <h4 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); border-bottom: 2px solid var(--c-primary); padding-bottom: 0.5rem;">{{ __('La méthode en 2 étapes') }}</h4>
                <ul>
                    <li><strong>{{ __('Votre objectif') }}</strong> — {{ __('choisissez la carte qui correspond à ce que vous voulez faire (rédiger, résumer, apprendre...)') }}</li>
                    <li><strong>{{ __('Votre demande') }}</strong> — {{ __('précisez le sujet, à qui ça s\'adresse et le ton ; les réglages avancés (rôle de l\'IA, format, exemples, contraintes) restent à un clic dans « Afficher tous les réglages »') }}</li>
                </ul>
                <h4 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); border-bottom: 2px solid var(--c-primary); padding-bottom: 0.5rem; margin-top: 1.5rem;">{{ __('Les contraintes utiles (réglages avancés)') }}</h4>
                <ul>
                    <li><strong>{{ __('Écriture naturelle') }}</strong> — {{ __('évite le style « robotique » typique de l\'IA') }}</li>
                    <li><strong>{{ __('Espace de travail dédié') }}</strong> — {{ __('ouvre un espace de travail (Canvas ou artefact) dans ChatGPT ou Claude') }}</li>
                    <li><strong>{{ __('Réflexion étape par étape') }}</strong> — {{ __('meilleur pour les problèmes complexes') }}</li>
                    <li><strong>{{ __('Poser des questions') }}</strong> — {{ __('l\'IA clarifie avant de répondre = meilleur résultat') }}</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="ct-btn ct-btn-primary" onclick="jQuery('#promptHelpModal').modal('hide')">{{ __('Compris !') }}</button>
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
        'name' => 'Constructeur de prompts IA',
        'alternateName' => ['Prompt Builder', 'Générateur de prompts ChatGPT', 'Constructeur de prompts'],
        'description' => 'Outil gratuit et interactif pour créer des prompts optimisés en partant de votre objectif (rédiger, résumer, analyser, apprendre...), avec réglages avancés repliés par défaut (rôle de l\'IA, audience, format de sortie). Compatible ChatGPT, Claude, Gemini, Mistral et tous les LLMs. Sauvegarde compte ou navigateur, partage natif, mode plein écran.',
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
            'Cartes d\'objectifs cliquables (rédiger, résumer, analyser, apprendre...) pour démarrer sans jargon',
            'Réglages avancés repliés par défaut (rôle de l\'IA, verbe, format, exemples, contraintes)',
            'Sauvegarde locale (navigateur) ou compte utilisateur',
            'Partage natif (Web Share API) et copier-coller',
            'Mode plein écran sans distraction',
            'Compatible ChatGPT, Claude, Gemini, Mistral, DeepSeek, Qwen, Llama',
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
// Phase 1 (audit 2026-07-26) : taxonomie de tâches concrètes pour l'étape 1 « objectif d'abord ».
// Mapping simple (pas d'IA) vers les personas/verbes existants ci-dessus — dérivé des mêmes valeurs,
// jamais une taxonomie parallèle. « autre » = échappatoire vers la sélection manuelle (réglages avancés).
$defaultTaskCards = [
    ['id' => 'redaction', 'icon' => '✍️', 'label' => 'Rédiger un texte', 'description' => 'Un article, un courriel, une publication...', 'personaValue' => 'redacteur_web', 'verb' => 'Rédige'],
    ['id' => 'resume', 'icon' => '📝', 'label' => 'Résumer un contenu', 'description' => 'Condenser un texte, un rapport, une réunion...', 'personaValue' => 'analyste', 'verb' => 'Résume'],
    ['id' => 'idees', 'icon' => '💡', 'label' => 'Trouver des idées', 'description' => 'Brainstormer des angles, des options, des titres...', 'personaValue' => 'consultant', 'verb' => 'Génère'],
    ['id' => 'analyse', 'icon' => '🔍', 'label' => 'Analyser ou comparer', 'description' => 'Étudier des données, comparer des options...', 'personaValue' => 'analyste', 'verb' => 'Analyse'],
    ['id' => 'apprendre', 'icon' => '🎓', 'label' => 'Apprendre ou comprendre', 'description' => 'Faire expliquer un sujet clairement, étape par étape...', 'personaValue' => 'enseignant', 'verb' => 'Explique'],
    ['id' => 'traduire', 'icon' => '🌐', 'label' => 'Traduire un texte', 'description' => 'Passer d\'une langue à une autre...', 'personaValue' => 'redacteur_web', 'verb' => 'Traduis'],
    ['id' => 'planifier', 'icon' => '🗂️', 'label' => 'Planifier ou organiser', 'description' => 'Un projet, une stratégie, un horaire...', 'personaValue' => 'gestionnaire', 'verb' => 'Planifie'],
    ['id' => 'coder', 'icon' => '💻', 'label' => 'Écrire ou déboguer du code', 'description' => 'Créer, corriger ou expliquer du code...', 'personaValue' => 'developpeur', 'verb' => 'Développe'],
    ['id' => 'autre', 'icon' => '✨', 'label' => 'Autre chose', 'description' => 'Je préfère tout choisir moi-même', 'personaValue' => '', 'verb' => ''],
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
    i18n: {
        promptCopied: @json(__('Prompt copié')),
        promptSaved: @json(__('Prompt sauvegardé')),
        saveError: @json(__('Erreur de sauvegarde. Réessayez.'))
    }
};
</script>
<script src="{{ asset('assets/tools/constructeur-prompts/constructeur-prompts-core.js') }}?v={{ config('version.semver') }}" defer></script>
@endpush
