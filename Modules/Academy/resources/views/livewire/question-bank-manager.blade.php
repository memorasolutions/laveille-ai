{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    Éditeur de la BANQUE DE QUESTIONS réutilisable (QB2). CRUD owner-scoped des
    catégories (arbre 1-2 niveaux) et des questions des 4 types.

    A11y : un seul h1 dans la page hôte ; ici des h2/h3 ; labels for/id ;
    aria-required/invalid/describedby ; messages role=alert/status ; cibles ≥24px
    via x-core::button. Charte (tokens var(--sys-*) / x-core::button). Confirmations
    inline à 2 temps (jamais de popup native). SÉCURITÉ : tout est gardé serveur.
--}}
<div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); line-height: 1.6;">

    {{-- Bandeaux de statut / erreur globaux --}}
    @if (session('academy_bank_status'))
        <div role="status" aria-live="polite"
             style="margin-bottom: 16px; padding: 10px 14px; border-radius: var(--sys-radius-md, 0.5rem);
                    background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0;">
            {{ session('academy_bank_status') }}
        </div>
    @endif
    @if (session('academy_bank_error'))
        <div role="alert"
             style="margin-bottom: 16px; padding: 10px 14px; border-radius: var(--sys-radius-md, 0.5rem);
                    background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA;">
            {{ session('academy_bank_error') }}
        </div>
    @endif

    <div style="display: grid; grid-template-columns: minmax(260px, 360px) 1fr; gap: 24px; align-items: start;">

        {{-- ════════════════════════ COLONNE 1 : CATÉGORIES ════════════════════════ --}}
        <section aria-labelledby="bank-cats-title"
                 style="background: #fff; border: 1px solid #E5E7EB; border-radius: var(--sys-radius-lg, 0.75rem); padding: 18px;">
            <h2 id="bank-cats-title" style="font-family: var(--f-heading); font-size: 1.05rem; margin: 0 0 12px;">Catégories</h2>

            {{-- QB3 : note d'héritage (parité Moodle). Discret, charte, a11y. --}}
            <p role="note" style="font-size: 0.74rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 12px; padding: 8px 10px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.5rem);">
                Astuce : dans un quiz, choisir une catégorie parente tire aussi les questions de ses sous-catégories (comme Moodle). Cette option reste désactivable au cas par cas dans le formulaire du quiz.
            </p>

            {{-- Liste / arbre des catégories --}}
            @if ($this->rootCategories->isEmpty())
                <x-academy::empty-state icon="🗂️" :compact="true"
                    message="Aucune catégorie pour l'instant. Créez-en une ci-dessous." />
            @else
                <ul style="list-style: none; margin: 0 0 16px; padding: 0;">
                    @foreach ($this->rootCategories as $root)
                        <li wire:key="cat-root-{{ $root->id }}" style="margin-bottom: 6px;">
                            @include('academy::livewire.partials.bank-category-row', ['category' => $root])

                            {{-- Sous-catégories (niveau 2) --}}
                            @php($children = $this->childrenByParent[$root->id] ?? collect())
                            @if ($children->isNotEmpty())
                                <ul style="list-style: none; margin: 4px 0 0; padding: 0 0 0 16px; border-left: 2px solid #F3F4F6;">
                                    @foreach ($children as $child)
                                        <li wire:key="cat-child-{{ $child->id }}" style="margin-bottom: 4px;">
                                            @include('academy::livewire.partials.bank-category-row', ['category' => $child])
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            {{-- Créer une catégorie --}}
            <form wire:submit="createCategory" style="border-top: 1px dashed #E5E7EB; padding-top: 14px; display: flex; flex-direction: column; gap: 8px;">
                <h3 style="font-size: 0.85rem; font-weight: 700; margin: 0;">Nouvelle catégorie</h3>

                <label for="newCategoryName" style="font-size: 0.8rem; font-weight: 600;">Nom <span aria-hidden="true">*</span></label>
                <input id="newCategoryName" type="text" wire:model="newCategoryName" maxlength="160"
                       aria-required="true" @error('newCategoryName') aria-invalid="true" aria-describedby="newCategoryName-err" @enderror
                       style="width: 100%; padding: 8px 12px; min-height: 38px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                @error('newCategoryName') <span id="newCategoryName-err" role="alert" style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror

                <label for="newCategoryParentId" style="font-size: 0.8rem; font-weight: 600;">Catégorie parente (facultatif)</label>
                <select id="newCategoryParentId" wire:model="newCategoryParentId"
                        style="width: 100%; padding: 8px 12px; min-height: 38px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                    <option value="">- Aucune (catégorie racine) -</option>
                    @foreach ($this->rootCategories as $root)
                        <option value="{{ $root->id }}">{{ $root->name }}</option>
                    @endforeach
                </select>

                <div><x-core::button type="submit" variant="primary" size="sm">Créer la catégorie</x-core::button></div>
            </form>
        </section>

        {{-- ═══════════════════ COLONNE 2 : QUESTIONS DE LA CATÉGORIE ═══════════════════ --}}
        <section aria-labelledby="bank-q-title"
                 style="background: #fff; border: 1px solid #E5E7EB; border-radius: var(--sys-radius-lg, 0.75rem); padding: 18px;">
            <h2 id="bank-q-title" style="font-family: var(--f-heading); font-size: 1.05rem; margin: 0 0 12px;">
                @if ($this->selectedCategory)
                    Questions - {{ $this->selectedCategory->name }}
                @else
                    Questions
                @endif
            </h2>

            @if (! $this->selectedCategory)
                <x-academy::empty-state icon="👈"
                    message="Sélectionnez une catégorie à gauche pour voir et gérer ses questions." />
            @else
                {{-- F17 (TAGS) : filtre de la liste par étiquette (owner-scopé). --}}
                @if ($this->tags->isNotEmpty())
                    <div style="display: flex; align-items: center; gap: 8px; margin: 0 0 12px; flex-wrap: wrap;">
                        <label for="filterTagId" style="font-size: 0.8rem; font-weight: 600;">Filtrer par étiquette</label>
                        <select id="filterTagId" wire:model.live="filterTagId"
                                style="padding: 6px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                            <option value="">Toutes les étiquettes</option>
                            @foreach ($this->tags as $tag)
                                <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                            @endforeach
                        </select>
                        @if ($filterTagId !== null)
                            <x-core::button type="button" wire:click="$set('filterTagId', null)" variant="ghost" size="sm">Réinitialiser</x-core::button>
                        @endif
                    </div>
                @endif

                {{-- Liste des questions existantes --}}
                @if ($this->questions->isEmpty())
                    <x-academy::empty-state icon="❓" :compact="true"
                        message="Aucune question ne correspond. Ajoutez-en une avec le formulaire ci-dessous (ou retirez le filtre)." />
                @else
                    <ul style="list-style: none; margin: 0 0 18px; padding: 0; display: flex; flex-direction: column; gap: 8px;">
                        @foreach ($this->questions as $question)
                            @php($stat = $this->questionStats[$question->id] ?? null)
                            <li wire:key="q-{{ $question->id }}"
                                style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.5rem); padding: 10px 12px;">
                                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;">
                                    <div style="min-width: 0;">
                                        <span style="display: inline-block; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
                                                     color: var(--sys-action-primary, #064E5A); background: #ECFEFF; padding: 2px 8px; border-radius: 999px;">
                                            {{ $this->typeLabel($question->type) }}
                                        </span>
                                        @unless ($question->is_active)
                                            <span style="display: inline-block; font-size: 0.7rem; font-weight: 700; color: #92400E; background: #FEF3C7; padding: 2px 8px; border-radius: 999px;">Inactive</span>
                                        @endunless

                                        {{-- F17 (STATISTIQUES) : usages + indice de facilité (lecture seule). --}}
                                        @php($statLabel = ($stat && $stat['has_data'])
                                            ? 'Utilisée '.$stat['uses'].' fois'.($stat['facility'] !== null ? ' · Facilité '.$stat['facility'].'%' : '')
                                            : 'Pas encore utilisée')
                                        @if ($stat && $stat['has_data'])
                                            {{-- aria-label remplace title (accessible clavier/mobile, WCAG 2.2). --}}
                                            <span style="display: inline-block; font-size: 0.7rem; font-weight: 600; color: #374151; background: #F3F4F6; padding: 2px 8px; border-radius: 999px;"
                                                  aria-label="Indice de facilité = pourcentage de bonnes réponses (essais exclus).">{{ $statLabel }}</span>
                                        @else
                                            {{-- Contraste : #5F6B7A > 4.5:1 sur #F9FAFB (corrige #6B7280 insuffisant). --}}
                                            <span style="display: inline-block; font-size: 0.7rem; font-weight: 600; color: #5F6B7A; background: #F9FAFB; padding: 2px 8px; border-radius: 999px;">{{ $statLabel }}</span>
                                        @endif

                                        <p style="margin: 6px 0 0; font-weight: 600;">{{ \Illuminate\Support\Str::limit($question->prompt, 120) }}</p>

                                        {{-- F17 (TAGS) : étiquettes de la question. --}}
                                        @if ($question->tags->isNotEmpty())
                                            <div style="margin-top: 6px; display: flex; flex-wrap: wrap; gap: 4px;">
                                                @foreach ($question->tags as $tag)
                                                    <span style="font-size: 0.68rem; color: #1E3A8A; background: #EFF6FF; padding: 2px 8px; border-radius: 999px;">{{ $tag->name }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    <div style="display: flex; gap: 6px; flex: 0 0 auto; flex-wrap: wrap; justify-content: flex-end;">
                                        <x-core::button type="button" wire:click="editQuestion({{ $question->id }})" variant="secondary" size="sm">Éditer</x-core::button>
                                        <x-core::button type="button" wire:click="showHistory({{ $question->id }})" variant="ghost" size="sm" aria-label="Voir l'historique des versions">Historique</x-core::button>
                                        @if ($confirmingQuestionDeletion === $question->id)
                                            <x-core::button type="button" wire:click="deleteQuestion({{ $question->id }})" variant="danger" size="sm">Confirmer</x-core::button>
                                            <x-core::button type="button" wire:click="cancelQuestionDeletion" variant="ghost" size="sm">Annuler</x-core::button>
                                        @else
                                            <x-core::button type="button" wire:click="confirmQuestionDeletion({{ $question->id }})" variant="ghost" size="sm" aria-label="Supprimer la question">Supprimer</x-core::button>
                                        @endif
                                    </div>
                                </div>

                                {{-- F17 (VERSIONS) : panneau d'historique inline (lecture seule). --}}
                                @if ($historyQuestionId === $question->id)
                                    <div style="margin-top: 10px; border-top: 1px dashed #E5E7EB; padding-top: 10px;">
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                            <strong style="font-size: 0.82rem;">Historique des versions</strong>
                                            <x-core::button type="button" wire:click="closeHistory" variant="ghost" size="sm">Fermer</x-core::button>
                                        </div>
                                        @if ($this->questionVersions->isEmpty())
                                            <p style="margin: 8px 0 0; font-size: 0.8rem; color: var(--sys-text-muted, #6B7280);">
                                                Aucune version archivée. Une version est créée à chaque modification du contenu de la question.
                                            </p>
                                        @else
                                            <ul style="list-style: none; margin: 8px 0 0; padding: 0; display: flex; flex-direction: column; gap: 6px;">
                                                @foreach ($this->questionVersions as $version)
                                                    <li wire:key="ver-{{ $version->id }}"
                                                        style="display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 0.8rem; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.5rem); padding: 6px 10px;">
                                                        <span>
                                                            Version {{ $version->version }} ·
                                                            {{ optional($version->snapshot_at)->timezone('America/Toronto')?->format('Y-m-d H:i') ?? '-' }}
                                                            <span style="color: var(--sys-text-muted, #6B7280);">({{ $this->typeLabel($version->type) }})</span>
                                                        </span>
                                                        {{-- aria-label distinct : chaque bouton est unique pour le lecteur d'écran. --}}
                                        <x-core::button type="button" wire:click="restoreVersion({{ $version->id }})" variant="secondary" size="sm" aria-label="Recharger la version {{ $version->version }}">Recharger</x-core::button>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                {{-- ───────── Formulaire de question (création / édition) ───────── --}}
                <form wire:submit="saveQuestion"
                      style="border-top: 1px dashed #E5E7EB; padding-top: 16px; display: flex; flex-direction: column; gap: 10px;">
                    <h3 style="font-size: 0.9rem; font-weight: 700; margin: 0;">
                        {{ $editingQuestionId ? 'Modifier la question' : 'Ajouter une question' }}
                    </h3>

                    <label for="qType" style="font-size: 0.8rem; font-weight: 600;">Type</label>
                    <select id="qType" wire:model.live="qType"
                            style="width: 100%; padding: 8px 12px; min-height: 38px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                        <option value="mcq">Choix multiple</option>
                        <option value="truefalse">Vrai ou faux</option>
                        <option value="short">Réponse courte</option>
                        <option value="matching">Appariement</option>
                        <option value="ordering">Ordonnancement</option>
                        <option value="cloze">Texte à trous</option>
                        <option value="numerical">Réponse numérique</option>
                        <option value="ddwtos">Glisser-déposer sur texte</option>
                        <option value="essay">Réponse rédigée (essai)</option>
                    </select>
                    @error('qType') <span role="alert" style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror

                    <label for="qPrompt" style="font-size: 0.8rem; font-weight: 600;">Énoncé <span aria-hidden="true">*</span></label>
                    <textarea id="qPrompt" wire:model="qPrompt" rows="2" maxlength="2000" aria-required="true"
                              @error('qPrompt') aria-invalid="true" aria-describedby="qPrompt-err" @enderror
                              style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;"></textarea>
                    @error('qPrompt') <span id="qPrompt-err" role="alert" style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror

                    {{-- ── Sous-formulaire par type ── --}}
                    @if ($qType === 'mcq')
                        <fieldset style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.5rem); padding: 12px; margin: 0;">
                            <legend style="font-size: 0.8rem; font-weight: 700; padding: 0 6px;">{{ $qMultiple ? 'Choix (cochez toutes les bonnes réponses)' : 'Choix (sélectionnez la bonne réponse)' }}</legend>

                            {{-- V1-e : bascule QCM simple (radio) ↔ QCM à réponses multiples (cases à cocher). --}}
                            <label style="display: inline-flex; align-items: center; gap: 8px; min-height: 36px; margin-bottom: 6px; font-size: 0.85rem; font-weight: 600;">
                                <input type="checkbox" wire:model.live="qMultiple" style="width: 20px; height: 20px;">
                                Autoriser plusieurs bonnes réponses
                            </label>
                            @if ($qMultiple)
                                <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 8px;">
                                    Cochez toutes les bonnes réponses (au moins une). Le crédit partiel est appliqué automatiquement.
                                </p>
                            @endif

                            @foreach ($qChoices as $i => $choice)
                                <div wire:key="choice-{{ $i }}" style="margin-bottom: 10px;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        @if ($qMultiple)
                                            <input type="checkbox" id="qCorrect-{{ $i }}" wire:model="qCorrectSet" value="{{ $i }}"
                                                   aria-label="Désigner le choix {{ $i + 1 }} comme bonne réponse"
                                                   style="width: 20px; height: 20px; flex: 0 0 auto;">
                                        @else
                                            <input type="radio" id="qCorrect-{{ $i }}" name="qCorrect" wire:model="qCorrect" value="{{ $i }}"
                                                   aria-label="Désigner le choix {{ $i + 1 }} comme bonne réponse"
                                                   style="width: 20px; height: 20px; flex: 0 0 auto;">
                                        @endif
                                        <label class="visually-hidden" for="qChoice-{{ $i }}">Choix {{ $i + 1 }}</label>
                                        <input type="text" id="qChoice-{{ $i }}" wire:model="qChoices.{{ $i }}" placeholder="Choix {{ $i + 1 }}"
                                               style="flex: 1; padding: 7px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                        @if (count($qChoices) > 2)
                                            <x-core::button type="button" wire:click="removeChoice({{ $i }})" variant="ghost" size="sm" aria-label="Retirer le choix {{ $i + 1 }}">✕</x-core::button>
                                        @endif
                                    </div>
                                    {{-- V1-a : rétroaction par choix (optionnelle). --}}
                                    <label class="visually-hidden" for="qChoiceFeedback-{{ $i }}">Rétroaction si le choix {{ $i + 1 }} est sélectionné</label>
                                    <input type="text" id="qChoiceFeedback-{{ $i }}" wire:model="qChoiceFeedback.{{ $i }}" maxlength="2000"
                                           placeholder="Rétroaction si ce choix est sélectionné (facultatif)"
                                           style="width: 100%; margin-top: 4px; padding: 6px 10px; min-height: 34px; border: 1px dashed #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-size: 0.82rem;">
                                </div>
                            @endforeach
                            <x-core::button type="button" wire:click="addChoice" variant="secondary" size="sm">+ Ajouter un choix</x-core::button>
                            @error('qChoices') <span role="alert" style="display: block; color: var(--sys-action-danger, #DC2626); font-size: 0.82rem; margin-top: 6px;">{{ $message }}</span> @enderror
                            @error('qCorrect') <span role="alert" style="display: block; color: var(--sys-action-danger, #DC2626); font-size: 0.82rem; margin-top: 6px;">{{ $message }}</span> @enderror
                            @error('qCorrectSet') <span role="alert" style="display: block; color: var(--sys-action-danger, #DC2626); font-size: 0.82rem; margin-top: 6px;">{{ $message }}</span> @enderror
                        </fieldset>
                    @elseif ($qType === 'truefalse')
                        <fieldset style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.5rem); padding: 12px; margin: 0;">
                            <legend style="font-size: 0.8rem; font-weight: 700; padding: 0 6px;">Réponse correcte</legend>
                            <div style="display: flex; gap: 16px;">
                                <label style="display: inline-flex; align-items: center; gap: 6px; min-height: 36px;">
                                    <input type="radio" name="qAnswerTrue" wire:model="qAnswerTrue" value="1" style="width: 20px; height: 20px;"> Vrai
                                </label>
                                <label style="display: inline-flex; align-items: center; gap: 6px; min-height: 36px;">
                                    <input type="radio" name="qAnswerTrue" wire:model="qAnswerTrue" value="0" style="width: 20px; height: 20px;"> Faux
                                </label>
                            </div>

                            {{-- V1-a : rétroaction par choix (Vrai / Faux), optionnelle. --}}
                            <label for="qTfFeedback-0" style="display: block; font-size: 0.78rem; font-weight: 600; margin-top: 10px;">Rétroaction si « Vrai » est sélectionné (facultatif)</label>
                            <input type="text" id="qTfFeedback-0" wire:model="qTfFeedback.0" maxlength="2000"
                                   style="width: 100%; margin-top: 4px; padding: 6px 10px; min-height: 34px; border: 1px dashed #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-size: 0.82rem;">
                            <label for="qTfFeedback-1" style="display: block; font-size: 0.78rem; font-weight: 600; margin-top: 8px;">Rétroaction si « Faux » est sélectionné (facultatif)</label>
                            <input type="text" id="qTfFeedback-1" wire:model="qTfFeedback.1" maxlength="2000"
                                   style="width: 100%; margin-top: 4px; padding: 6px 10px; min-height: 34px; border: 1px dashed #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-size: 0.82rem;">
                        </fieldset>
                    @elseif ($qType === 'short')
                        <fieldset style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.5rem); padding: 12px; margin: 0;">
                            <legend style="font-size: 0.8rem; font-weight: 700; padding: 0 6px;">Réponses acceptées (au moins une)</legend>
                            @foreach ($qAccepted as $i => $acc)
                                <div wire:key="acc-{{ $i }}" style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                    <label class="visually-hidden" for="qAccepted-{{ $i }}">Réponse acceptée {{ $i + 1 }}</label>
                                    <input type="text" id="qAccepted-{{ $i }}" wire:model="qAccepted.{{ $i }}" placeholder="Réponse acceptée {{ $i + 1 }}"
                                           style="flex: 1; padding: 7px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                    @if (count($qAccepted) > 1)
                                        <x-core::button type="button" wire:click="removeAccepted({{ $i }})" variant="ghost" size="sm" aria-label="Retirer la réponse {{ $i + 1 }}">✕</x-core::button>
                                    @endif
                                </div>
                            @endforeach
                            <x-core::button type="button" wire:click="addAccepted" variant="secondary" size="sm">+ Ajouter une réponse</x-core::button>
                            @error('qAccepted') <span role="alert" style="display: block; color: var(--sys-action-danger, #DC2626); font-size: 0.82rem; margin-top: 6px;">{{ $message }}</span> @enderror

                            <label for="qDisplay" style="display: block; font-size: 0.8rem; font-weight: 600; margin-top: 10px;">Indice affiché (facultatif)</label>
                            <input id="qDisplay" type="text" wire:model="qDisplay" placeholder="Texte d'aide affiché à l'apprenant"
                                   style="width: 100%; padding: 7px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                        </fieldset>
                    @elseif ($qType === 'matching')
                        <fieldset style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.5rem); padding: 12px; margin: 0;">
                            <legend style="font-size: 0.8rem; font-weight: 700; padding: 0 6px;">Paires terme / définition (au moins deux)</legend>
                            @foreach ($qPairs as $i => $pair)
                                <div wire:key="pair-{{ $i }}" style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                    <label class="visually-hidden" for="qPairTerm-{{ $i }}">Terme {{ $i + 1 }}</label>
                                    <input type="text" id="qPairTerm-{{ $i }}" wire:model="qPairs.{{ $i }}.term" placeholder="Terme {{ $i + 1 }}"
                                           style="flex: 1; padding: 7px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                    <span aria-hidden="true">→</span>
                                    <label class="visually-hidden" for="qPairDef-{{ $i }}">Définition {{ $i + 1 }}</label>
                                    <input type="text" id="qPairDef-{{ $i }}" wire:model="qPairs.{{ $i }}.def" placeholder="Définition {{ $i + 1 }}"
                                           style="flex: 1; padding: 7px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                    @if (count($qPairs) > 2)
                                        <x-core::button type="button" wire:click="removePair({{ $i }})" variant="ghost" size="sm" aria-label="Retirer la paire {{ $i + 1 }}">✕</x-core::button>
                                    @endif
                                </div>
                            @endforeach
                            <x-core::button type="button" wire:click="addPair" variant="secondary" size="sm">+ Ajouter une paire</x-core::button>
                            @error('qPairs') <span role="alert" style="display: block; color: var(--sys-action-danger, #DC2626); font-size: 0.82rem; margin-top: 6px;">{{ $message }}</span> @enderror
                        </fieldset>
                    @elseif ($qType === 'ordering')
                        <fieldset style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.5rem); padding: 12px; margin: 0;">
                            <legend style="font-size: 0.8rem; font-weight: 700; padding: 0 6px;">Éléments dans le BON ordre (au moins deux)</legend>
                            <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 8px;">
                                Saisissez les éléments dans l'ordre attendu (1 = premier). À l'examen, ils sont présentés mélangés et l'apprenant doit retrouver l'ordre.
                            </p>
                            @foreach ($qOrderingItems as $i => $element)
                                <div wire:key="order-{{ $i }}" style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                    <span aria-hidden="true" style="flex: 0 0 auto; min-width: 22px; font-weight: 700; color: var(--sys-action-primary, #064E5A);">{{ $i + 1 }}.</span>
                                    <label class="visually-hidden" for="qOrderingItems-{{ $i }}">Élément en position {{ $i + 1 }}</label>
                                    <input type="text" id="qOrderingItems-{{ $i }}" wire:model="qOrderingItems.{{ $i }}" placeholder="Élément en position {{ $i + 1 }}"
                                           style="flex: 1; padding: 7px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                    <x-core::button type="button" wire:click="moveOrderingItem({{ $i }}, 'up')" variant="ghost" size="sm" aria-label="Monter l'élément {{ $i + 1 }}" :disabled="$i === 0">↑</x-core::button>
                                    <x-core::button type="button" wire:click="moveOrderingItem({{ $i }}, 'down')" variant="ghost" size="sm" aria-label="Descendre l'élément {{ $i + 1 }}" :disabled="$i === count($qOrderingItems) - 1">↓</x-core::button>
                                    @if (count($qOrderingItems) > 2)
                                        <x-core::button type="button" wire:click="removeOrderingItem({{ $i }})" variant="ghost" size="sm" aria-label="Retirer l'élément {{ $i + 1 }}">✕</x-core::button>
                                    @endif
                                </div>
                            @endforeach
                            <x-core::button type="button" wire:click="addOrderingItem" variant="secondary" size="sm">+ Ajouter un élément</x-core::button>
                            @error('qOrderingItems') <span role="alert" style="display: block; color: var(--sys-action-danger, #DC2626); font-size: 0.82rem; margin-top: 6px;">{{ $message }}</span> @enderror
                        </fieldset>
                    @elseif ($qType === 'cloze')
                        <fieldset style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.5rem); padding: 12px; margin: 0;">
                            <legend style="font-size: 0.8rem; font-weight: 700; padding: 0 6px;">Texte à trous</legend>
                            <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 8px;">
                                Écrivez le texte et placez les trous avec des marqueurs numérotés <code>[[1]]</code>, <code>[[2]]</code>… Chaque marqueur <code>[[n]]</code> correspond au trou n° n ci-dessous.
                            </p>

                            <label for="qClozeText" style="font-size: 0.8rem; font-weight: 600;">Texte avec les trous <span aria-hidden="true">*</span></label>
                            <textarea id="qClozeText" wire:model="qClozeText" rows="3" maxlength="2000"
                                      aria-required="true" @error('qClozeText') aria-invalid="true" aria-describedby="qClozeText-err" @enderror
                                      placeholder="La capitale du Québec est [[1]] et la province compte environ [[2]] habitants."
                                      style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;"></textarea>
                            @error('qClozeText') <span id="qClozeText-err" role="alert" style="display: block; color: var(--sys-action-danger, #DC2626); font-size: 0.82rem; margin-top: 4px;">{{ $message }}</span> @enderror

                            <div style="margin-top: 12px; display: flex; flex-direction: column; gap: 10px;">
                                @foreach ($qClozeBlanks as $i => $blank)
                                    <div wire:key="cloze-blank-{{ $i }}"
                                         style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.5rem); padding: 10px;">
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px;">
                                            <span style="font-size: 0.8rem; font-weight: 700; color: var(--sys-action-primary, #064E5A);">Trou [[{{ $i + 1 }}]]</span>
                                            @if (count($qClozeBlanks) > 1)
                                                <x-core::button type="button" wire:click="removeClozeBlank({{ $i }})" variant="ghost" size="sm" aria-label="Retirer le trou {{ $i + 1 }}">✕</x-core::button>
                                            @endif
                                        </div>

                                        <label for="qClozeKind-{{ $i }}" style="font-size: 0.78rem; font-weight: 600;">Type de trou</label>
                                        <select id="qClozeKind-{{ $i }}" wire:model.live="qClozeBlanks.{{ $i }}.kind"
                                                style="width: 100%; margin: 4px 0 8px; padding: 7px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                            <option value="short">Réponse courte (texte saisi)</option>
                                            <option value="mcq">Choix (menu déroulant)</option>
                                        </select>

                                        @if (($blank['kind'] ?? 'short') === 'mcq')
                                            <label for="qClozeChoices-{{ $i }}" style="font-size: 0.78rem; font-weight: 600;">Options (une par ligne, au moins deux)</label>
                                            <textarea id="qClozeChoices-{{ $i }}" wire:model.live="qClozeBlanks.{{ $i }}.choices" rows="3"
                                                      placeholder="Une option par ligne"
                                                      style="width: 100%; margin: 4px 0 8px; padding: 7px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;"></textarea>

                                            @php($clozeOptions = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($blank['choices'] ?? '')) ?: []), fn ($o) => $o !== '')))
                                            <label for="qClozeCorrect-{{ $i }}" style="font-size: 0.78rem; font-weight: 600;">Bonne option</label>
                                            <select id="qClozeCorrect-{{ $i }}" wire:model="qClozeBlanks.{{ $i }}.correct"
                                                    style="width: 100%; margin-top: 4px; padding: 7px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                @forelse ($clozeOptions as $oi => $opt)
                                                    <option value="{{ $oi }}">{{ $oi + 1 }}. {{ \Illuminate\Support\Str::limit($opt, 60) }}</option>
                                                @empty
                                                    <option value="0">Saisissez d'abord les options</option>
                                                @endforelse
                                            </select>
                                        @else
                                            <label for="qClozeAccepted-{{ $i }}" style="font-size: 0.78rem; font-weight: 600;">Réponses acceptées (séparées par des virgules)</label>
                                            <input type="text" id="qClozeAccepted-{{ $i }}" wire:model="qClozeBlanks.{{ $i }}.accepted"
                                                   placeholder="Québec, Quebec, Ville de Québec"
                                                   style="width: 100%; margin: 4px 0 8px; padding: 7px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                            <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 0;">La casse et les espaces de début/fin sont ignorés.</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <div style="margin-top: 10px;">
                                <x-core::button type="button" wire:click="addClozeBlank" variant="secondary" size="sm">+ Ajouter un trou</x-core::button>
                            </div>
                            @error('qClozeBlanks') <span role="alert" style="display: block; color: var(--sys-action-danger, #DC2626); font-size: 0.82rem; margin-top: 6px;">{{ $message }}</span> @enderror
                        </fieldset>
                    @elseif ($qType === 'ddwtos')
                        {{-- Numéros de trous + pool non vide calculés côté composant. On
                             utilise volontairement la forme inline du directive PHP : un
                             bloc PHP multi-ligne casserait la compilation Blade en happant
                             le directive inline qui le précède dans la colonne catégories. --}}
                        @php($ddwBlankNums = $this->ddwtosBlankNumbers())
                        @php($ddwPool = $this->ddwtosPool())
                        <fieldset style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.5rem); padding: 12px; margin: 0;">
                            <legend style="font-size: 0.8rem; font-weight: 700; padding: 0 6px;">Glisser-déposer sur texte</legend>
                            <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 8px;">
                                Écrivez le texte et placez les trous avec des marqueurs numérotés <code>[[1]]</code>, <code>[[2]]</code>… Préparez un pool de mots (avec des distracteurs) ; à l'examen, chaque trou se remplit par un mot du pool (menu déroulant, le pool est présenté mélangé).
                            </p>

                            <label for="qDdwtosText" style="font-size: 0.8rem; font-weight: 600;">Texte avec les trous <span aria-hidden="true">*</span></label>
                            <textarea id="qDdwtosText" wire:model.live="qDdwtosText" rows="3" maxlength="2000"
                                      aria-required="true" @error('qDdwtosText') aria-invalid="true" aria-describedby="qDdwtosText-err" @enderror
                                      placeholder="Le [[1]] mange la [[2]] et le chat boit du lait."
                                      style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;"></textarea>
                            @error('qDdwtosText') <span id="qDdwtosText-err" role="alert" style="display: block; color: var(--sys-action-danger, #DC2626); font-size: 0.82rem; margin-top: 4px;">{{ $message }}</span> @enderror

                            {{-- Pool de mots (repeater) --}}
                            <p style="font-size: 0.8rem; font-weight: 700; margin: 12px 0 6px;">Pool de mots (avec distracteurs)</p>
                            @foreach ($qDdwtosWords as $i => $word)
                                <div wire:key="ddw-word-{{ $i }}" style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                    <span aria-hidden="true" style="flex: 0 0 auto; min-width: 22px; font-weight: 700; color: var(--sys-action-primary, #064E5A);">{{ $i + 1 }}.</span>
                                    <label class="visually-hidden" for="qDdwtosWords-{{ $i }}">Mot {{ $i + 1 }} du pool</label>
                                    <input type="text" id="qDdwtosWords-{{ $i }}" wire:model.live="qDdwtosWords.{{ $i }}" maxlength="500" placeholder="Mot {{ $i + 1 }}"
                                           style="flex: 1; padding: 7px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                    @if (count($qDdwtosWords) > 2)
                                        <x-core::button type="button" wire:click="removeDdwtosWord({{ $i }})" variant="ghost" size="sm" aria-label="Retirer le mot {{ $i + 1 }}">✕</x-core::button>
                                    @endif
                                </div>
                            @endforeach
                            <x-core::button type="button" wire:click="addDdwtosWord" variant="secondary" size="sm">+ Ajouter un mot</x-core::button>
                            @error('qDdwtosWords') <span role="alert" style="display: block; color: var(--sys-action-danger, #DC2626); font-size: 0.82rem; margin-top: 6px;">{{ $message }}</span> @enderror

                            {{-- Bon mot par trou --}}
                            <p style="font-size: 0.8rem; font-weight: 700; margin: 14px 0 6px;">Bon mot pour chaque trou</p>
                            @if (empty($ddwBlankNums))
                                <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 0;">Ajoutez d'abord un trou <code>[[1]]</code> dans le texte.</p>
                            @else
                                @foreach ($ddwBlankNums as $n)
                                    @php($blankIdx = $n - 1)
                                    <div wire:key="ddw-answer-{{ $blankIdx }}" style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                        <label for="qDdwtosAnswers-{{ $blankIdx }}" style="flex: 0 0 auto; min-width: 70px; font-size: 0.8rem; font-weight: 600;">Trou [[{{ $n }}]]</label>
                                        <select id="qDdwtosAnswers-{{ $blankIdx }}" wire:model="qDdwtosAnswers.{{ $blankIdx }}"
                                                aria-required="true"
                                                @error('qDdwtosAnswers') aria-invalid="true" aria-describedby="qDdwtosAnswers-err" @enderror
                                                style="flex: 1; max-width: 320px; padding: 7px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                            <option value="">– Choisir un mot –</option>
                                            @foreach ($ddwPool as $wi => $wv)
                                                <option value="{{ $wi }}">{{ \Illuminate\Support\Str::limit($wv, 60) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            @endif
                            @error('qDdwtosAnswers') <span id="qDdwtosAnswers-err" role="alert" style="display: block; color: var(--sys-action-danger, #DC2626); font-size: 0.82rem; margin-top: 6px;">{{ $message }}</span> @enderror
                        </fieldset>
                    @elseif ($qType === 'numerical')
                        <fieldset style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.5rem); padding: 12px; margin: 0;">
                            <legend style="font-size: 0.8rem; font-weight: 700; padding: 0 6px;">Réponse numérique</legend>
                            <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 10px;">
                                L'apprenant saisit un nombre. La réponse est correcte si elle se situe dans la tolérance (±) autour de la valeur attendue. La virgule et le point décimal sont acceptés. L'unité est indicative (non notée).
                            </p>

                            <label for="qNumericalCorrect" style="font-size: 0.8rem; font-weight: 600;">Réponse attendue <span aria-hidden="true">*</span></label>
                            <input type="text" inputmode="decimal" id="qNumericalCorrect" wire:model="qNumericalCorrect"
                                   aria-required="true" @error('qNumericalCorrect') aria-invalid="true" aria-describedby="qNumericalCorrect-err" @enderror
                                   placeholder="42"
                                   style="width: 100%; margin: 4px 0 4px; padding: 7px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                            @error('qNumericalCorrect') <span id="qNumericalCorrect-err" role="alert" style="display: block; color: var(--sys-action-danger, #DC2626); font-size: 0.82rem; margin-top: 4px;">{{ $message }}</span> @enderror

                            <label for="qNumericalTolerance" style="display: block; font-size: 0.8rem; font-weight: 600; margin-top: 10px;">Tolérance (±, facultatif)</label>
                            <input type="text" inputmode="decimal" id="qNumericalTolerance" wire:model="qNumericalTolerance"
                                   @error('qNumericalTolerance') aria-invalid="true" aria-describedby="qNumericalTolerance-err" @enderror
                                   placeholder="0"
                                   style="width: 100%; margin: 4px 0 4px; padding: 7px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                            @error('qNumericalTolerance') <span id="qNumericalTolerance-err" role="alert" style="display: block; color: var(--sys-action-danger, #DC2626); font-size: 0.82rem; margin-top: 4px;">{{ $message }}</span> @enderror

                            <label for="qNumericalUnit" style="display: block; font-size: 0.8rem; font-weight: 600; margin-top: 10px;">Unité (facultatif)</label>
                            <input type="text" id="qNumericalUnit" wire:model="qNumericalUnit" maxlength="40"
                                   placeholder="km, %, $…"
                                   style="width: 100%; margin: 4px 0 0; padding: 7px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                        </fieldset>
                    @elseif ($qType === 'essay')
                        <fieldset style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.5rem); padding: 12px; margin: 0;">
                            <legend style="font-size: 0.8rem; font-weight: 700; padding: 0 6px;">Réponse rédigée (essai)</legend>
                            <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 10px;">
                                L'apprenant rédige une réponse libre. Il n'y a pas de bonne réponse automatique : le formateur attribue les points (jusqu'au barème ci-dessous) après la soumission.
                            </p>

                            <label for="qGraderInfo" style="font-size: 0.8rem; font-weight: 600;">Consignes de correction (facultatif)</label>
                            <textarea id="qGraderInfo" wire:model="qGraderInfo" rows="3" maxlength="2000"
                                      placeholder="Critères, barème détaillé, points à vérifier…"
                                      style="width: 100%; margin: 4px 0 0; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;"></textarea>
                            <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 4px 0 0;">
                                Visibles SEULEMENT par le formateur lors de la correction (jamais par l'apprenant).
                            </p>
                            @error('qGraderInfo') <span role="alert" style="display: block; color: var(--sys-action-danger, #DC2626); font-size: 0.82rem; margin-top: 4px;">{{ $message }}</span> @enderror
                        </fieldset>
                    @endif

                    {{-- Champs communs : difficulté, explication, actif --}}
                    <label for="qDifficulty" style="font-size: 0.8rem; font-weight: 600;">Difficulté</label>
                    <select id="qDifficulty" wire:model="qDifficulty"
                            style="width: 100%; padding: 8px 12px; min-height: 38px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                        <option value="facile">Facile</option>
                        <option value="moyen">Moyen</option>
                        <option value="difficile">Difficile</option>
                    </select>

                    {{-- V1-c : pondération de la question (barème). 1 = barème neutre. --}}
                    <label for="qPoints" style="font-size: 0.8rem; font-weight: 600;">Points (barème, 1 à 100)</label>
                    {{-- aria-label retiré : le <label for="qPoints"> suffit (WCAG 2.2 - aria-label écrasait le label visible). --}}
                    <input id="qPoints" type="number" min="1" max="100" wire:model="qPoints"
                           style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                    <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                        Pondération de la question dans le score du quiz. Laissez 1 pour un barème égal entre toutes les questions.
                    </p>
                    @error('qPoints') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.8rem;">{{ $message }}</span> @enderror

                    <label for="qExplanation" style="font-size: 0.8rem; font-weight: 600;">Rétroaction générale / explication (facultatif)</label>
                    <textarea id="qExplanation" wire:model="qExplanation" rows="2" maxlength="2000"
                              style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;"></textarea>
                    <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                        Affichée à la révision quelle que soit la réponse (comme la « rétroaction générale » de Moodle).
                    </p>

                    {{-- F17 (TAGS) : étiquettes, séparées par des virgules, créées à la volée. --}}
                    <label for="qTags" style="font-size: 0.8rem; font-weight: 600;">Étiquettes (facultatif)</label>
                    <input id="qTags" type="text" wire:model="qTags" list="qTagsList" maxlength="1000"
                           placeholder="Séparez les étiquettes par des virgules (ex. : grammaire, niveau 2)"
                           aria-describedby="qTags-help"
                           style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                    <datalist id="qTagsList">
                        @foreach ($this->tags as $tag)
                            <option value="{{ $tag->name }}"></option>
                        @endforeach
                    </datalist>
                    <p id="qTags-help" style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                        Servent à regrouper et filtrer vos questions. Une étiquette inconnue est créée automatiquement.
                    </p>

                    <label style="display: inline-flex; align-items: center; gap: 8px; min-height: 36px; font-size: 0.85rem;">
                        <input type="checkbox" wire:model="qIsActive" style="width: 20px; height: 20px;"> Question active (tirable dans les quiz)
                    </label>

                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <x-core::button type="submit" variant="primary" size="sm">{{ $editingQuestionId ? 'Enregistrer les modifications' : 'Ajouter la question' }}</x-core::button>
                        @if ($editingQuestionId)
                            <x-core::button type="button" wire:click="resetQuestionForm" variant="ghost" size="sm">Annuler l'édition</x-core::button>
                        @endif
                    </div>
                </form>
            @endif
        </section>
    </div>
</div>
