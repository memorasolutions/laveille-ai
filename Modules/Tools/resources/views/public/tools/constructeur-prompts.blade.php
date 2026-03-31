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
            <div class="col-lg-8 col-12">
                <div class="card shadow-sm tool-fullscreen-target" style="border-radius: var(--r-base);">
                    <div class="card-body p-4 p-md-5" x-data="promptBuilder()" x-init="init()">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h1 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); margin: 0;">{{ $tool->name }}</h1>
                                <p class="text-muted mb-0">{{ __('Créez des prompts optimisés pour ChatGPT, Claude, Gemini et autres IA.') }}</p>
                            </div>
                            <div class="d-flex gap-1">
                                @include('tools::partials.fullscreen-btn')
                                <button class="btn btn-sm" @click="jQuery('#promptHelpModal').modal('show')" style="background: var(--c-primary); color: #fff; border-radius: 50%; width: 32px; height: 32px; font-weight: 700; font-size: 1rem; padding: 0; line-height: 32px; flex-shrink: 0;" title="{{ __('Aide') }}">?</button>
                            </div>
                        </div>
                        {{-- Barre sauvegarde (visible avant les étapes) --}}
                        <div class="mt-3 mb-3 p-3 rounded" x-show="isAuthenticated" x-cloak style="background: rgba(11,114,133,0.04); border: 1px solid rgba(11,114,133,0.12); border-radius: 10px;">
                            <div class="d-flex gap-2 align-items-center">
                                <input type="text" class="form-control form-control-sm flex-fill" x-model="saveName" placeholder="{{ __('Nommer ce prompt pour le retrouver...') }}" aria-label="{{ __('Titre du prompt') }}" style="border-radius: 8px;">
                                <button class="btn btn-sm" @click="addToHistory()" :disabled="!isValid || saving" style="background: var(--c-primary); color: #fff; border-radius: 8px; font-weight: 600; white-space: nowrap; padding: 6px 16px;"
                                        x-text="saving ? '{{ __('Sauvegarde...') }}' : (_editingId ? '{{ __('Mettre a jour') }}' : '{{ __('Sauvegarder') }}')"></button>
                            </div>
                            <div class="small mt-2 mb-0" style="font-size: 0.8rem; color: var(--c-text-muted);">
                                {{ __('Retrouvez vos prompts sauvegardes dans') }} <a href="{{ route('user.contributions') }}?tab=prompts" style="color: var(--c-primary); text-decoration: underline;">{{ __('votre espace personnel') }}</a>.
                            </div>
                            <template x-if="saveError">
                                <div class="alert alert-danger small p-1 mt-2 mb-0" style="font-size: 0.8rem; border-radius: 6px;" x-text="saveError"></div>
                            </template>
                            <template x-if="hasLocalData">
                                <div class="small mt-2 mb-0" style="font-size: 0.8rem; color: var(--c-text-muted);">
                                    {{ __('Des prompts de votre navigateur ont ete trouves.') }}
                                    <button class="btn btn-sm btn-outline-primary ms-1" @click="importLocalStorage()" style="font-size: 0.7rem; padding: 1px 8px; border-radius: 6px;">{{ __('Importer') }}</button>
                                </div>
                            </template>
                        </div>
                        <template x-if="!isAuthenticated">
                            <div class="mt-3 mb-3 p-2 rounded" style="background: rgba(11,114,133,0.06); border: 1px solid rgba(11,114,133,0.15); border-radius: 10px; font-size: 0.85rem;">
                                <strong style="color: var(--c-primary);">{{ __('Connectez-vous') }}</strong> {{ __('pour sauvegarder vos prompts et les retrouver sur tous vos appareils.') }}
                                <button class="btn btn-sm ms-1" @click="$dispatch('open-auth-modal')" style="background: var(--c-primary); color: #fff; border-radius: 6px; font-size: 0.75rem; padding: 2px 10px;">{{ __('Se connecter') }}</button>
                            </div>
                        </template>

                        {{-- Indicateur d'étapes --}}
                        <div class="d-flex justify-content-between mb-4" style="position: relative;">
                            <template x-for="s in [1,2,3,4]" :key="s">
                                <div class="text-center" style="flex: 1; position: relative; z-index: 1;">
                                    <div @click="goToStep(s)" style="cursor: pointer; margin: 0 auto; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; transition: all 0.2s;" :style="step >= s ? 'background: var(--c-primary); color: #fff;' : 'background: #e9ecef; color: #6c757d;'" x-text="s"></div>
                                    <small class="d-block mt-1" style="font-size: 0.7rem;" :style="step >= s ? 'color: var(--c-primary); font-weight: 600;' : 'color: #adb5bd;'" x-text="s === 1 ? '{{ __('Persona') }}' : s === 2 ? '{{ __('Tâche') }}' : s === 3 ? '{{ __('Audience') }}' : '{{ __('Options') }}'"></small>
                                </div>
                            </template>
                        </div>

                        {{-- Étape 1 : Persona --}}
                        <div x-show="step === 1" x-transition>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h3 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); font-size: 1.1rem; margin: 0;">{{ __('1. Définir la persona') }} <span style="color: #DC2626;">*</span></h3>
                                <button class="btn btn-sm" @click="showHelp.persona = !showHelp.persona" style="background: #e9ecef; color: var(--c-dark); border-radius: 50%; width: 22px; height: 22px; font-size: 0.7rem; padding: 0; line-height: 22px; font-weight: 700; margin-left: 4px; flex-shrink: 0;">?</button>
                            </div>
                            <p class="text-muted small mb-1">{{ __('Quel rôle l\'IA doit-elle jouer ?') }}</p>
                            <div x-show="showHelp.persona" x-transition class="alert alert-info small mb-3 p-2" style="font-size: 0.8rem;" x-text="helps.persona"></div>
                            <div class="d-flex gap-3 mb-3">
                                <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem;">
                                    <input type="radio" name="personaType" value="preset" x-model="personaType"> {{ __('Prédéfinie') }}
                                </label>
                                <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem;">
                                    <input type="radio" name="personaType" value="custom" x-model="personaType"> {{ __('Personnalisée') }}
                                </label>
                            </div>
                            <div x-show="personaType === 'preset'" class="form-group mb-3">
                                <select class="form-control" x-model="personaPreset" aria-label="{{ __('Choisir une persona') }}">
                                    <option value="">{{ __('-- Sélectionnez une persona --') }}</option>
                                    <template x-for="p in personas" :key="p.value">
                                        <option :value="p.value" x-text="p.label"></option>
                                    </template>
                                </select>
                            </div>
                            <div x-show="personaType === 'custom'" class="form-group mb-3">
                                <input type="text" class="form-control" x-model="personaCustom" placeholder="{{ __('Ex: un expert en cybersécurité spécialisé en PME québécoises') }}" aria-label="{{ __('Persona personnalisée') }}">
                            </div>
                        </div>

                        {{-- Étape 2 : Tâche --}}
                        <div x-show="step === 2" x-transition>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h3 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); font-size: 1.1rem; margin: 0;">{{ __('2. Définir la tâche') }} <span style="color: #DC2626;">*</span></h3>
                                <button class="btn btn-sm" @click="showHelp.task = !showHelp.task" style="background: #e9ecef; color: var(--c-dark); border-radius: 50%; width: 22px; height: 22px; font-size: 0.7rem; padding: 0; line-height: 22px; font-weight: 700; margin-left: 4px; flex-shrink: 0;">?</button>
                            </div>
                            <p class="text-muted small mb-1">{{ __('Que doit faire l\'IA ?') }}</p>
                            <div x-show="showHelp.task" x-transition class="alert alert-info small mb-3 p-2" style="font-size: 0.8rem;">
                                <strong>{{ __('Verbe d\'action') }}</strong> : <span x-text="helps.verb"></span><br>
                                <strong>{{ __('Description') }}</strong> : <span x-text="helps.taskObject"></span>
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label fw-medium">{{ __('Action principale') }} <span style="color: #DC2626;">*</span></label>
                                <div class="d-flex gap-3 mb-2">
                                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem;">
                                        <input type="radio" name="verbType" value="preset" x-model="verbType"> {{ __('Prédéfinie') }}
                                    </label>
                                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem;">
                                        <input type="radio" name="verbType" value="custom" x-model="verbType"> {{ __('Personnalisée') }}
                                    </label>
                                </div>
                                <select class="form-control" x-show="verbType === 'preset'" x-model="verb" aria-label="{{ __('Verbe d\'action') }}">
                                    <option value="">{{ __('-- Sélectionnez un verbe --') }}</option>
                                    <template x-for="v in verbs" :key="v">
                                        <option :value="v" x-text="v"></option>
                                    </template>
                                </select>
                                <input type="text" class="form-control" x-show="verbType === 'custom'" x-model="verbCustom" placeholder="{{ __('Ex: Reformule, Synthétise, Décortique...') }}" aria-label="{{ __('Verbe personnalisé') }}">
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label fw-medium">{{ __('Objet de la tâche') }} <span style="color: #DC2626;">*</span></label>
                                <textarea class="form-control" rows="3" x-model="taskObject" placeholder="{{ __('Ex: un plan marketing pour le lancement d\'une application mobile au Québec') }}" aria-label="{{ __('Description de la tâche') }}"></textarea>
                                <small class="text-muted">{{ __('Décrivez précisément ce que l\'IA doit produire.') }}</small>
                            </div>
                        </div>

                        {{-- Étape 3 : Audience --}}
                        <div x-show="step === 3" x-transition>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h3 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); font-size: 1.1rem; margin: 0;">{{ __('3. Définir l\'audience') }}</h3>
                                <button class="btn btn-sm" @click="showHelp.audience = !showHelp.audience" style="background: #e9ecef; color: var(--c-dark); border-radius: 50%; width: 22px; height: 22px; font-size: 0.7rem; padding: 0; line-height: 22px; font-weight: 700; margin-left: 4px; flex-shrink: 0;">?</button>
                            </div>
                            <p class="text-muted small mb-1">{{ __('À qui s\'adresse le contenu ?') }}</p>
                            <div x-show="showHelp.audience" x-transition class="alert alert-info small mb-3 p-2" style="font-size: 0.8rem;" x-text="helps.audience"></div>
                            <div class="d-flex gap-3 mb-3">
                                <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem;">
                                    <input type="radio" name="audienceType" value="preset" x-model="audienceType"> {{ __('Prédéfinie') }}
                                </label>
                                <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem;">
                                    <input type="radio" name="audienceType" value="custom" x-model="audienceType"> {{ __('Personnalisée') }}
                                </label>
                            </div>
                            <div x-show="audienceType === 'preset'" class="form-group mb-3">
                                <select class="form-control" x-model="audiencePreset" aria-label="{{ __('Choisir une audience') }}">
                                    <option value="">{{ __('-- Sélectionnez une audience --') }}</option>
                                    <template x-for="a in audiences" :key="a.value">
                                        <option :value="a.value" x-text="a.label"></option>
                                    </template>
                                </select>
                            </div>
                            <div x-show="audienceType === 'custom'" class="form-group mb-3">
                                <input type="text" class="form-control" x-model="audienceCustom" placeholder="{{ __('Ex: enseignants du secondaire au Québec') }}" aria-label="{{ __('Audience personnalisée') }}">
                            </div>
                        </div>

                        {{-- Étape 4 : Options --}}
                        <div x-show="step === 4" x-transition>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <h3 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); font-size: 1.1rem; margin: 0;">{{ __('4. Options avancées') }}</h3>
                                <button class="btn btn-sm" @click="showHelp.options = !showHelp.options" style="background: #e9ecef; color: var(--c-dark); border-radius: 50%; width: 22px; height: 22px; font-size: 0.7rem; padding: 0; line-height: 22px; font-weight: 700; margin-left: 4px; flex-shrink: 0;">?</button>
                            </div>
                            <div x-show="showHelp.options" x-transition class="alert alert-info small mb-3 p-2" style="font-size: 0.8rem;">
                                <strong>{{ __('Format') }}</strong> : <span x-text="helps.format"></span><br>
                                <strong>{{ __('Longueur') }}</strong> : <span x-text="helps.length"></span><br>
                                <strong>{{ __('Ton') }}</strong> : <span x-text="helps.tone"></span>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label fw-medium" style="font-size: 0.85rem;">{{ __('Format') }}</label>
                                    <select class="form-control form-control-sm" x-model="format" aria-label="{{ __('Format de sortie') }}">
                                        <option value="">{{ __('-- Aucun --') }}</option>
                                        <option value="Liste à puces">{{ __('Liste à puces') }}</option>
                                        <option value="Paragraphes détaillés">{{ __('Paragraphes détaillés') }}</option>
                                        <option value="Tableau structuré">{{ __('Tableau structuré') }}</option>
                                        <option value="Plan hiérarchisé">{{ __('Plan hiérarchisé') }}</option>
                                        <option value="Étapes numérotées">{{ __('Étapes numérotées') }}</option>
                                        <option value="Format JSON">{{ __('Format JSON') }}</option>
                                        <option value="Diagramme Mermaid">{{ __('Diagramme Mermaid') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label fw-medium" style="font-size: 0.85rem;">{{ __('Longueur') }}</label>
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
                                    <label class="form-label fw-medium" style="font-size: 0.85rem;">{{ __('Ton') }}</label>
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
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-medium" style="font-size: 0.85rem;">{{ __('Langue de réponse') }}</label>
                                    <select class="form-control form-control-sm" x-model="language" aria-label="{{ __('Langue') }}">
                                        <option value="fr">{{ __('Français') }}</option>
                                        <option value="en">English</option>
                                        <option value="es">Español</option>
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2" style="margin-top: 1.5rem; margin-bottom: 0.5rem;">
                                <h3 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); font-size: 1rem; margin: 0;">{{ __('Technique de prompting') }}</h3>
                                <button class="btn btn-sm" @click="showHelp.technique = !showHelp.technique" style="background: #e9ecef; color: var(--c-dark); border-radius: 50%; width: 22px; height: 22px; font-size: 0.7rem; padding: 0; line-height: 22px; font-weight: 700; margin-left: 4px; flex-shrink: 0;">?</button>
                            </div>
                            <div x-show="showHelp.technique" x-transition class="alert alert-info small mb-2 p-2" style="font-size: 0.8rem;" x-text="helps.technique"></div>
                            <div class="row mb-3">
                                <div class="col-md-6 mb-2">
                                    <select class="form-control form-control-sm" x-model="technique" aria-label="{{ __('Technique de prompting') }}">
                                        <option value="zero-shot">{{ __('Zero-shot (par défaut)') }}</option>
                                        <option value="zero-shot-cot">{{ __('Zero-shot + chaîne de pensée') }}</option>
                                        <option value="few-shot">{{ __('Few-shot (avec exemples)') }}</option>
                                        <option value="few-shot-cot">{{ __('Few-shot + chaîne de pensée') }}</option>
                                        <option value="iterative">{{ __('Prompt itératif (étape par étape)') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem;">
                                        <input type="checkbox" x-model="useDelimiters" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                        <span>{{ __('Délimiteurs de données (###)') }}</span>
                                    </label>
                                </div>
                            </div>
                            <div x-show="technique === 'few-shot' || technique === 'few-shot-cot'" class="form-group mb-3">
                                <label class="form-label fw-medium" style="font-size: 0.85rem;">{{ __('Exemples (2-3 recommandés)') }}</label>
                                <textarea class="form-control form-control-sm" rows="4" x-model="examples" placeholder="{{ __('Exemple 1 :\nEntrée : ...\nSortie : ...\n\nExemple 2 :\nEntrée : ...\nSortie : ...') }}" aria-label="{{ __('Exemples pour few-shot') }}"></textarea>
                                <small class="text-muted">{{ __('Donnez 2-3 exemples du résultat attendu pour guider l\'IA.') }}</small>
                            </div>

                            <div class="d-flex align-items-center gap-2" style="margin-top: 1.5rem; margin-bottom: 0.5rem;">
                                <h3 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); font-size: 1rem; margin: 0;">{{ __('Contraintes') }}</h3>
                                <button class="btn btn-sm" @click="showHelp.constraints = !showHelp.constraints" style="background: #e9ecef; color: var(--c-dark); border-radius: 50%; width: 22px; height: 22px; font-size: 0.7rem; padding: 0; line-height: 22px; font-weight: 700; margin-left: 4px; flex-shrink: 0;">?</button>
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
                                    <span><strong>{{ __('Canvas / artefact') }}</strong> — {{ __('force un espace de travail dédié') }}</span>
                                </label>
                                <div x-show="constraintCanvas" class="ms-4 mt-1 mb-1 p-2 rounded" style="background: #f0f9ff; border-left: 3px solid var(--c-primary);">
                                    <div class="d-flex flex-wrap gap-2 align-items-end">
                                        <div>
                                            <label class="form-label" style="font-size: 0.75rem;">{{ __('IA cible') }}</label>
                                            <select class="form-control form-control-sm" x-model="canvasAI" style="width: auto;" aria-label="{{ __('IA cible') }}">
                                                <option value="chatgpt">ChatGPT</option>
                                                <option value="claude">Claude</option>
                                                <option value="gemini">Gemini</option>
                                                <option value="mistral">Mistral</option>
                                                <option value="custom">{{ __('Personnalisé') }}</option>
                                            </select>
                                        </div>
                                        <div x-show="canvasAI !== 'custom'">
                                            <label class="form-label" style="font-size: 0.75rem;">{{ __('Format de sortie') }}</label>
                                            <select class="form-control form-control-sm" x-model="canvasFormat" style="width: auto;" aria-label="{{ __('Format Canvas') }}">
                                                <option value="">{{ __('-- Aucun --') }}</option>
                                                <template x-for="f in canvasFormats" :key="f">
                                                    <option :value="f" x-text="f"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div x-show="canvasAI === 'custom'">
                                            <label class="form-label" style="font-size: 0.75rem;">{{ __('Format personnalisé') }}</label>
                                            <input type="text" class="form-control form-control-sm" x-model="canvasCustomFormat" placeholder="{{ __('Ex: LaTeX, YAML, CSV...') }}" style="width: 150px;" aria-label="{{ __('Format personnalisé') }}">
                                        </div>
                                    </div>
                                </div>
                                <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem;">
                                    <input type="checkbox" x-model="constraintChainOfThought" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                    <span><strong>{{ __('Raisonnement étape par étape') }}</strong> — {{ __('chain of thought') }}</span>
                                </label>
                                <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.85rem;">
                                    <input type="checkbox" x-model="constraintAskIfUnclear" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                    <span><strong>{{ __('Poser des questions') }}</strong> — {{ __('demander des précisions si nécessaire') }}</span>
                                </label>
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label fw-medium" style="font-size: 0.85rem;">{{ __('Contraintes spécifiques') }}</label>
                                <textarea class="form-control form-control-sm" rows="2" x-model="constraintCustom" placeholder="{{ __('Ex: éviter le jargon technique, inclure des exemples concrets') }}" aria-label="{{ __('Contraintes personnalisées') }}"></textarea>
                            </div>
                        </div>

                        {{-- Navigation --}}
                        <div x-show="showValidation && ((step === 1 && !personaText) || (step === 2 && (!taskObject || (verbType === 'custom' ? !verbCustom : !verb))))" x-transition class="alert alert-danger small p-2 mb-2" style="font-size: 0.85rem;">
                            <span x-show="step === 1 && !personaText">{{ __('Veuillez choisir ou saisir une persona avant de continuer.') }}</span>
                            <span x-show="step === 2 && verbType === 'preset' && !verb">{{ __('Veuillez choisir un verbe d\'action.') }}</span>
                            <span x-show="step === 2 && verbType === 'custom' && !verbCustom">{{ __('Veuillez écrire votre verbe d\'action dans le champ personnalisé.') }}</span>
                            <span x-show="step === 2 && (verbType === 'custom' ? !!verbCustom : !!verb) && !taskObject">{{ __('Veuillez décrire la tâche.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <button class="btn btn-outline-secondary" @click="prevStep()" x-show="step > 1" style="border-radius: var(--r-btn);">{{ __('Précédent') }}</button>
                            <div x-show="step === 1"></div>
                            <button class="btn" @click="nextStep()" x-show="step < 4" style="background: var(--c-primary); color: #fff; border-radius: var(--r-btn); font-family: var(--f-heading); font-weight: 700;">
{{ __('Suivant') }}</button>
                        </div>

                        {{-- Prévisualisation du prompt --}}
                        <div class="p-3 rounded mb-1" style="background: var(--c-primary-light); white-space: pre-wrap; font-family: monospace; font-size: 0.9rem; min-height: 60px; line-height: 1.6;" x-text="prompt || '{{ __('Remplissez les étapes ci-dessus...') }}'"></div>
                        <div class="d-flex justify-content-end gap-3 mb-3" style="font-size: 0.8rem;">
                            <span class="text-muted" x-text="prompt.length + ' {{ __('caractères') }}'"></span>
                            <span class="text-muted" x-text="'~' + Math.ceil(prompt.length / 4) + ' tokens'"></span>
                            <span class="text-muted" x-text="prompt.split(/\s+/).filter(function(w){ return w; }).length + ' {{ __('mots') }}'"></span>
                        </div>

                        {{-- Actions --}}
                        <div x-show="!isValid" class="alert alert-warning small p-2 mb-2" style="font-size: 0.8rem;">
                            {{ __('Remplissez la persona (étape 1) et la tâche (étape 2) pour générer votre prompt.') }}
                        </div>
                        <div class="d-flex gap-2 mb-4 flex-wrap">
                            <button class="btn flex-fill" @click="copy()" :disabled="!isValid" :style="isValid ? 'background: var(--c-accent); color: #fff;' : 'background: #e9ecef; color: #adb5bd; cursor: not-allowed;'" style="border-radius: var(--r-btn); font-family: var(--f-heading); font-weight: 700;"
                                    x-text="copied ? '{{ __('Copié !') }}' : '{{ __('Copier le prompt') }}'"></button>
                            <button class="btn btn-outline-secondary" @click="exportPrompt()" :disabled="!isValid" style="border-radius: var(--r-btn);">{{ __('Exporter .txt') }}</button>
                        </div>

                        {{-- Historique --}}
                        <template x-if="history.length > 0">
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h3 style="font-family: var(--f-heading); font-weight: 700; margin: 0; font-size: 1rem;">{{ __('Historique') }} (<span x-text="history.length"></span>)</h3>
                                    <button class="btn btn-sm btn-outline-danger" @click="clearHistory()" style="font-size: 0.7rem;">{{ __('Effacer') }}</button>
                                </div>
                                <template x-for="(h, i) in history" :key="i">
                                    <div class="d-flex justify-content-between align-items-center p-2 mb-1 rounded" style="background: #f8f9fa; font-size: 0.8rem;">
                                        <div class="flex-fill" style="cursor: pointer;" @click="copyText(h.prompt)">
                                            <strong x-text="h.name"></strong>
                                            <div class="text-muted" x-text="h.prompt.substring(0, 80) + '...'"></div>
                                        </div>
                                        <div class="d-flex gap-1 ms-2">
                                            <button class="btn btn-sm btn-outline-secondary" @click="copyText(h.prompt)" style="font-size: 0.7rem;">{{ __('Copier') }}</button>
                                            <button class="btn btn-sm btn-outline-danger" @click.stop="deletePrompt(h.id, i)" style="font-size: 0.6rem; padding: 1px 5px;">✕</button>
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
                <h4 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); border-bottom: 2px solid var(--c-primary); padding-bottom: 0.5rem;">{{ __('La méthode en 4 étapes') }}</h4>
                <ul>
                    <li><strong>{{ __('Persona') }}</strong> — {{ __('quel rôle l\'IA joue (ex: expert marketing, enseignant)') }}</li>
                    <li><strong>{{ __('Tâche') }}</strong> — {{ __('verbe d\'action + ce que l\'IA doit produire') }}</li>
                    <li><strong>{{ __('Audience') }}</strong> — {{ __('à qui s\'adresse le résultat (optionnel mais recommandé)') }}</li>
                    <li><strong>{{ __('Options') }}</strong> — {{ __('format, longueur, ton, contraintes') }}</li>
                </ul>
                <h4 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); border-bottom: 2px solid var(--c-primary); padding-bottom: 0.5rem; margin-top: 1.5rem;">{{ __('Les contraintes utiles') }}</h4>
                <ul>
                    <li><strong>{{ __('Écriture naturelle') }}</strong> — {{ __('évite le style « robotique » typique de l\'IA') }}</li>
                    <li><strong>{{ __('Canvas / artefact') }}</strong> — {{ __('ouvre un espace de travail dédié dans ChatGPT ou Claude') }}</li>
                    <li><strong>{{ __('Raisonnement étape par étape') }}</strong> — {{ __('meilleur pour les problèmes complexes') }}</li>
                    <li><strong>{{ __('Poser des questions') }}</strong> — {{ __('l\'IA clarifie avant de répondre = meilleur résultat') }}</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="jQuery('#promptHelpModal').modal('hide')" style="background: var(--c-primary); color: #fff; border-radius: var(--r-btn);">{{ __('Compris !') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@php
$defaultPersonas = [['value'=>'expert_marketing','label'=>'Expert en marketing digital'],['value'=>'redacteur_web','label'=>'Redacteur web professionnel'],['value'=>'enseignant','label'=>'Enseignant pedagogue'],['value'=>'developpeur','label'=>'Developpeur senior'],['value'=>'consultant','label'=>'Consultant en strategie'],['value'=>'graphiste','label'=>'Graphiste creatif'],['value'=>'analyste','label'=>'Analyste de donnees'],['value'=>'gestionnaire','label'=>'Gestionnaire de projet'],['value'=>'coach','label'=>'Coach professionnel'],['value'=>'journaliste','label'=>'Journaliste d\'investigation'],['value'=>'chercheur','label'=>'Chercheur scientifique'],['value'=>'rh','label'=>'Specialiste en ressources humaines']];
$defaultVerbs = ['Redige','Analyse','Cree','Genere','Explique','Compare','Resume','Traduis','Optimise','Evalue','Developpe','Concois','Planifie','Diagnostique'];
$defaultAudiences = [['value'=>'pro','label'=>'Professionnels du secteur'],['value'=>'debutants','label'=>'Debutants'],['value'=>'entrepreneurs','label'=>'Entrepreneurs et dirigeants'],['value'=>'etudiants','label'=>'Etudiants universitaires'],['value'=>'grand_public','label'=>'Grand public'],['value'=>'techniques','label'=>'Collegues techniques'],['value'=>'direction','label'=>'Direction generale']];
$pbPersonas = class_exists(\Modules\Settings\Facades\Settings::class) ? \Modules\Settings\Facades\Settings::get('tools.prompt_builder.personas', $defaultPersonas) : $defaultPersonas;
$pbVerbs = class_exists(\Modules\Settings\Facades\Settings::class) ? \Modules\Settings\Facades\Settings::get('tools.prompt_builder.verbs', $defaultVerbs) : $defaultVerbs;
$pbAudiences = class_exists(\Modules\Settings\Facades\Settings::class) ? \Modules\Settings\Facades\Settings::get('tools.prompt_builder.audiences', $defaultAudiences) : $defaultAudiences;
@endphp
<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('promptBuilder', function() {
        return {
            step: 1,
            personaType: 'preset',
            personaPreset: '',
            personaCustom: '',
            personas: @json($pbPersonas),
            verbType: 'preset',
            verb: '',
            verbs: @json($pbVerbs),
            verbCustom: '',
            taskObject: '',
            audienceType: 'preset',
            audiencePreset: '',
            audienceCustom: '',
            audiences: @json($pbAudiences),
            format: '',
            length: '',
            tone: '',
            language: 'fr',
            constraintAntiAI: true,
            constraintTypo: false,
            constraintCanvas: false,
            canvasAI: 'chatgpt',
            canvasFormat: '',
            canvasCustomFormat: '',
            canvasFormatMap: {
                chatgpt: ['Markdown', 'DOCX', 'HTML', 'Code', 'Tableau'],
                claude: ['Markdown', 'HTML', 'SVG', 'Code', 'Mermaid', 'React'],
                gemini: ['Markdown', 'HTML', 'Code', 'JSON'],
                mistral: ['Markdown', 'HTML', 'Code'],
                custom: ['Markdown', 'HTML', 'DOCX', 'Code', 'JSON', 'LaTeX', 'CSV']
            },
            get canvasFormats() { return this.canvasFormatMap[this.canvasAI] || []; },
            constraintChainOfThought: false,
            constraintAskIfUnclear: false,
            constraintCustom: '',
            technique: 'zero-shot',
            examples: '',
            useDelimiters: false,
            showHelp: {},
            helps: {
                persona: 'Donner un rôle à l\'IA aide à orienter ses réponses selon une expertise ou un style spécifique. Ex: « Tu es un expert marketing » donnera des réponses plus stratégiques.',
                verb: 'Choisir un verbe d\'action précise ce que l\'IA doit faire : rédiger, analyser, résumer, créer... Le verbe détermine le type de résultat.',
                taskObject: 'Décrivez clairement et précisément ce que l\'IA doit produire. Plus vous donnez de contexte et de détails, meilleur sera le résultat.',
                audience: 'Spécifier le public aide l\'IA à adapter son langage. Un texte pour des débutants sera différent d\'un texte pour des experts.',
                format: 'Le format guide la structure de la réponse. Une liste à puces est facile à lire, un tableau est bon pour comparer, un plan est idéal pour organiser.',
                length: 'Indiquer une longueur permet de contrôler si la réponse est concise (pour un résumé) ou détaillée (pour un article complet).',
                tone: 'Le ton change le style : professionnel pour un rapport, chaleureux pour un courriel client, académique pour un mémoire.',
                technique: 'Zero-shot : l\'IA répond directement sans exemple. Few-shot : vous donnez 2-3 exemples pour guider l\'IA. Chain of thought : l\'IA raisonne étape par étape (meilleur pour la logique). Itératif : l\'IA valide chaque étape avec vous.',
                delimiters: 'Les délimiteurs (###) séparent vos instructions de vos données. Utile quand vous analysez un texte spécifique — l\'IA sait où commence le texte à analyser.',
                constraintAntiAI: 'L\'IA a tendance à produire des textes génériques reconnaissables. Cette option force un style plus naturel, varié et authentiquement humain.',
                constraintCanvas: 'Canvas (ChatGPT) et artefact (Claude) sont des espaces de travail dédiés où l\'IA crée du contenu que vous pouvez modifier directement.',
                constraintChainOfThought: 'La chaîne de pensée force l\'IA à montrer son raisonnement, pas juste le résultat. Très utile pour les problèmes complexes, les mathématiques ou la logique.',
                constraintAskIfUnclear: 'Au lieu de deviner, l\'IA vous posera des questions de clarification. Résultat : des réponses beaucoup plus pertinentes dès le premier essai.'
            },
            copied: false,
            showValidation: false,
            saveName: '',
            saving: false,
            saveError: '',
            isAuthenticated: {{ auth()->check() ? 'true' : 'false' }},
            hasLocalData: false,
            history: [],

            get isValid() {
                var hasVerb = this.verbType === 'custom' ? !!this.verbCustom : !!this.verb;
                return this.personaText.length > 0 && this.taskObject.length > 0 && hasVerb;
            },

            get personaText() {
                if (this.personaType === 'custom' && this.personaCustom) return this.personaCustom;
                if (this.personaType === 'preset' && this.personaPreset) {
                    for (var i = 0; i < this.personas.length; i++) {
                        if (this.personas[i].value === this.personaPreset) return this.personas[i].label;
                    }
                }
                return '';
            },

            get audienceText() {
                if (this.audienceType === 'none') return '';
                if (this.audienceType === 'custom' && this.audienceCustom) return this.audienceCustom;
                if (this.audienceType === 'preset' && this.audiencePreset) {
                    for (var i = 0; i < this.audiences.length; i++) {
                        if (this.audiences[i].value === this.audiencePreset) return this.audiences[i].label;
                    }
                }
                return '';
            },

            get prompt() {
                var sections = [];
                var actionVerb = this.verbType === 'custom' ? this.verbCustom : this.verb;

                // === RÔLE (enrichi) ===
                if (this.personaText) {
                    sections.push('Tu es un(e) ' + this.personaText + ' avec une expertise approfondie dans ton domaine. Tu communiques de manière claire et efficace, en adaptant ton niveau de langage à ton audience.');
                }

                // === TÂCHE ===
                if (actionVerb && this.taskObject) {
                    sections.push('Ta tâche : ' + actionVerb + ' ' + this.taskObject + '.');
                } else if (this.taskObject) {
                    sections.push('Ta tâche : ' + this.taskObject + '.');
                }

                // === AUDIENCE ===
                if (this.audienceText) {
                    sections.push('Audience cible : ' + this.audienceText + '. Adapte ton vocabulaire, tes exemples et ton niveau de détail en conséquence. Assure-toi que le contenu soit pertinent et accessible pour ce public.');
                }

                // === FORMAT DE SORTIE ===
                var outputRules = [];
                if (this.format) outputRules.push('Structure : ' + this.format);
                if (this.length) outputRules.push('Longueur visée : ' + this.length);
                if (this.tone) outputRules.push('Ton et style : ' + this.tone);
                if (this.language === 'en') outputRules.push('Langue de rédaction : anglais');
                if (this.language === 'es') outputRules.push('Langue de rédaction : espagnol');
                if (outputRules.length > 0) {
                    sections.push('Format de la réponse :\n- ' + outputRules.join('\n- '));
                }

                // === CONTRAINTES ===
                var constraints = [];
                if (this.constraintAntiAI) constraints.push('Écriture naturelle et humaine : varie la longueur des phrases, utilise des expressions authentiques et des transitions fluides. Évite les formulations génériques (« dans un monde en constante évolution »), les listes à puces systématiques et les répétitions de structure.');
                if (this.constraintTypo) constraints.push('Typographie française stricte : majuscules en début de phrase et noms propres uniquement, pas de tiret cadratin (utilise le tiret court), ponctuation correcte, accents toujours présents.');
                if (this.constraintCanvas) {
                    var canvasNames = { chatgpt: 'Canvas', claude: 'artefact', gemini: 'espace de travail', mistral: 'espace de travail', custom: 'espace de travail dédié' };
                    var canvasName = canvasNames[this.canvasAI] || 'espace de travail';
                    var canvasLine = 'Crée un nouveau ' + canvasName + ' pour ta réponse.';
                    var fmt = this.canvasAI === 'custom' ? this.canvasCustomFormat : this.canvasFormat;
                    if (fmt) canvasLine += ' Format de sortie : ' + fmt + '.';
                    constraints.push(canvasLine);
                }
                if (this.constraintChainOfThought) constraints.push('Montre ton raisonnement complet étape par étape avant de formuler ta réponse finale.');
                if (this.constraintAskIfUnclear) constraints.push('Si un élément de ma demande est ambigu ou manque de contexte, pose-moi des questions de clarification avant de commencer. Ne devine pas — demande.');
                if (this.constraintCustom) constraints.push(this.constraintCustom);
                if (constraints.length > 0) {
                    sections.push('Contraintes à respecter :\n- ' + constraints.join('\n- '));
                }

                // === CRITÈRES DE QUALITÉ ===
                var quality = [];
                if (this.tone) quality.push('le ton demandé est respecté du début à la fin');
                if (this.audienceText) quality.push('le contenu est adapté à l\'audience cible');
                if (this.length) quality.push('la longueur correspond à ce qui est demandé');
                if (this.constraintAntiAI) quality.push('le texte ne ressemble pas à du contenu généré par IA');
                if (quality.length > 0) {
                    sections.push('Avant de finaliser, vérifie que :\n- ' + quality.join('\n- '));
                }

                // === DÉLIMITEURS ===
                if (this.useDelimiters) {
                    sections.push('Utilise des délimiteurs ### pour séparer clairement chaque section de ta réponse.');
                }

                // === TECHNIQUE ===
                if (this.technique === 'zero-shot-cot') {
                    sections.push('Avant de répondre, réfléchis étape par étape à ta stratégie (ne montre pas ce raisonnement dans ta réponse finale).');
                }
                if ((this.technique === 'few-shot' || this.technique === 'few-shot-cot') && this.examples) {
                    sections.push('Voici des exemples pour guider ta réponse :\n\n' + this.examples);
                    if (this.technique === 'few-shot-cot') {
                        sections.push('Applique le même type de raisonnement détaillé que dans les exemples ci-dessus.');
                    }
                }
                if (this.technique === 'iterative') {
                    sections.push('Procède étape par étape. Après chaque étape majeure, présente ton travail et demande ma validation avant de continuer.');
                }

                return sections.join('\n\n');
            },

            get wizardParams() {
                return { personaType: this.personaType, personaPreset: this.personaPreset, personaCustom: this.personaCustom, verbType: this.verbType, verb: this.verb, verbCustom: this.verbCustom, taskObject: this.taskObject, audienceType: this.audienceType, audiencePreset: this.audiencePreset, audienceCustom: this.audienceCustom, format: this.format, length: this.length, tone: this.tone, language: this.language, technique: this.technique, constraintAntiAI: this.constraintAntiAI, constraintCanvas: this.constraintCanvas, canvasAI: this.canvasAI, canvasFormat: this.canvasFormat };
            },
            _headers: function() {
                return { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json', 'Content-Type': 'application/json' };
            },
            init: function() {
                var self = this;
                if (this.isAuthenticated) {
                    fetch('/api/prompts', { headers: this._headers() })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            self.history = (data.data || []).map(function(item) {
                                return { id: item.id, prompt: item.prompt_text, name: item.name, date: new Date(item.created_at).toLocaleString('fr-CA'), params: item.params };
                            });
                            if (localStorage.getItem('pb_history')) self.hasLocalData = true;
                        })
                        .catch(function() {
                            try { self.history = JSON.parse(localStorage.getItem('pb_history') || '[]'); } catch(e) { self.history = []; }
                        });
                    // Charger un prompt existant pour edition (?edit=ID)
                    var editId = new URLSearchParams(window.location.search).get('edit');
                    if (editId) {
                        fetch('/api/prompts', { headers: self._headers() })
                            .then(function(r) { return r.json(); })
                            .then(function(data) {
                                var found = (data.data || []).find(function(p) { return p.id == editId; });
                                if (found && found.params) {
                                    var p = found.params;
                                    if (p.personaType) self.personaType = p.personaType;
                                    if (p.personaPreset) self.personaPreset = p.personaPreset;
                                    if (p.personaCustom) { self.personaCustom = p.personaCustom; self.personaType = 'custom'; }
                                    if (p.verbType) self.verbType = p.verbType;
                                    if (p.verb) self.verb = p.verb;
                                    if (p.verbCustom) { self.verbCustom = p.verbCustom; self.verbType = 'custom'; }
                                    if (p.taskObject) self.taskObject = p.taskObject;
                                    if (p.audienceType) self.audienceType = p.audienceType;
                                    if (p.audiencePreset) self.audiencePreset = p.audiencePreset;
                                    if (p.audienceCustom) { self.audienceCustom = p.audienceCustom; self.audienceType = 'custom'; }
                                    if (p.format) self.format = p.format;
                                    if (p.length) self.length = p.length;
                                    if (p.tone) self.tone = p.tone;
                                    if (p.language) self.language = p.language;
                                    if (p.technique) self.technique = p.technique;
                                    if (p.constraintAntiAI !== undefined) self.constraintAntiAI = p.constraintAntiAI;
                                    if (p.constraintCanvas) self.constraintCanvas = p.constraintCanvas;
                                    if (p.canvasAI) self.canvasAI = p.canvasAI;
                                    if (p.canvasFormat) self.canvasFormat = p.canvasFormat;
                                    self.saveName = found.name;
                                    self.step = 4;
                                    self._editingId = found.id;
                                }
                            });
                    }
                } else {
                    try { this.history = JSON.parse(localStorage.getItem('pb_history') || '[]'); } catch(e) { this.history = []; }
                }
            },

            nextStep: function() {
                if (this.step === 1 && !this.personaText) { this.showValidation = true; return; }
                var hasVerb2 = this.verbType === 'custom' ? !!this.verbCustom : !!this.verb;
                if (this.step === 2 && (!this.taskObject || !hasVerb2)) { this.showValidation = true; return; }
                this.showValidation = false;
                if (this.step < 4) this.step++;
            },
            canGoToStep: function(s) {
                if (s <= 1) return true;
                if (s >= 2 && !this.personaText) return false;
                var stepHasVerb = this.verbType === 'custom' ? !!this.verbCustom : !!this.verb;
                if (s >= 3 && (!this.taskObject || !stepHasVerb)) return false;
                return true;
            },
            goToStep: function(s) {
                if (this.canGoToStep(s)) { this.showValidation = false; this.step = s; }
                else { this.showValidation = true; }
            },
            prevStep: function() { if (this.step > 1) this.step--; },

            copy: function() {
                var self = this;
                navigator.clipboard.writeText(this.prompt);
                this.copied = true;
                setTimeout(function() { self.copied = false; }, 2000);
            },

            copyText: function(text) { navigator.clipboard.writeText(text); },

            addToHistory: function() {
                if (this.saving) return;
                var self = this;
                var title = this.saveName.trim() || this.personaText || 'Prompt';
                if (this.isAuthenticated) {
                    this.saving = true;
                    var isEdit = !!this._editingId;
                    var url = isEdit ? '/api/prompts/' + this._editingId : '/api/prompts';
                    var method = isEdit ? 'PUT' : 'POST';
                    fetch(url, {
                        method: method, headers: this._headers(),
                        body: JSON.stringify({ name: title, prompt_text: this.prompt, params: this.wizardParams })
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (isEdit) {
                            var idx = self.history.findIndex(function(h) { return h.id == data.id; });
                            if (idx >= 0) self.history[idx] = { id: data.id, prompt: data.prompt_text, name: data.name, date: new Date(data.updated_at).toLocaleString('fr-CA'), params: data.params };
                            self._editingId = null;
                        } else {
                            self.history.unshift({ id: data.id, prompt: data.prompt_text, name: data.name, date: new Date(data.created_at).toLocaleString('fr-CA'), params: data.params });
                        }
                        self.saveName = '';
                        self.saving = false;
                    })
                    .catch(function() { self.saving = false; self.saveError = '{{ __("Erreur de sauvegarde. Reessayez.") }}'; setTimeout(function() { self.saveError = ''; }, 4000); });
                } else {
                    this.$dispatch('open-auth-modal');
                }
            },
            deletePrompt: function(id, index) {
                var self = this;
                if (this.isAuthenticated && id) {
                    fetch('/api/prompts/' + id, { method: 'DELETE', headers: this._headers() })
                        .then(function() { self.history.splice(index, 1); })
                        .catch(console.error);
                } else {
                    this.history.splice(index, 1);
                    localStorage.setItem('pb_history', JSON.stringify(this.history));
                }
            },
            importLocalStorage: function() {
                var self = this;
                var local = [];
                try { local = JSON.parse(localStorage.getItem('pb_history') || '[]'); } catch(e) { return; }
                var promises = local.map(function(item) {
                    return fetch('/api/prompts', {
                        method: 'POST', headers: self._headers(),
                        body: JSON.stringify({ name: item.name || 'Prompt importé', prompt_text: item.prompt, params: {} })
                    }).then(function(r) { return r.json(); });
                });
                Promise.all(promises).then(function(results) {
                    results.forEach(function(data) {
                        self.history.push({ id: data.id, prompt: data.prompt_text, name: data.name, date: new Date(data.created_at).toLocaleString('fr-CA'), params: data.params });
                    });
                    localStorage.removeItem('pb_history');
                    self.hasLocalData = false;
                });
            },
            clearHistory: function() { this.history = []; if (!this.isAuthenticated) localStorage.removeItem('pb_history'); },

            exportPrompt: function() {
                var blob = new Blob([this.prompt], { type: 'text/plain' });
                var a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'prompt.txt';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }
        };
    });
});
</script>
@endpush
