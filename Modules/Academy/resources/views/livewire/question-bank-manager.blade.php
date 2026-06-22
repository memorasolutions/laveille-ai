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
                {{-- Liste des questions existantes --}}
                @if ($this->questions->isEmpty())
                    <x-academy::empty-state icon="❓" :compact="true"
                        message="Cette catégorie n'a aucune question. Ajoutez-en une avec le formulaire ci-dessous." />
                @else
                    <ul style="list-style: none; margin: 0 0 18px; padding: 0; display: flex; flex-direction: column; gap: 8px;">
                        @foreach ($this->questions as $question)
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
                                        <p style="margin: 6px 0 0; font-weight: 600;">{{ \Illuminate\Support\Str::limit($question->prompt, 120) }}</p>
                                    </div>
                                    <div style="display: flex; gap: 6px; flex: 0 0 auto;">
                                        <x-core::button type="button" wire:click="editQuestion({{ $question->id }})" variant="secondary" size="sm">Éditer</x-core::button>
                                        @if ($confirmingQuestionDeletion === $question->id)
                                            <x-core::button type="button" wire:click="deleteQuestion({{ $question->id }})" variant="danger" size="sm">Confirmer</x-core::button>
                                            <x-core::button type="button" wire:click="cancelQuestionDeletion" variant="ghost" size="sm">Annuler</x-core::button>
                                        @else
                                            <x-core::button type="button" wire:click="confirmQuestionDeletion({{ $question->id }})" variant="ghost" size="sm" aria-label="Supprimer la question">Supprimer</x-core::button>
                                        @endif
                                    </div>
                                </div>
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
                            <legend style="font-size: 0.8rem; font-weight: 700; padding: 0 6px;">Choix (sélectionnez la bonne réponse)</legend>
                            @foreach ($qChoices as $i => $choice)
                                <div wire:key="choice-{{ $i }}" style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                    <input type="radio" id="qCorrect-{{ $i }}" name="qCorrect" wire:model="qCorrect" value="{{ $i }}"
                                           aria-label="Désigner le choix {{ $i + 1 }} comme bonne réponse"
                                           style="width: 20px; height: 20px; flex: 0 0 auto;">
                                    <label class="visually-hidden" for="qChoice-{{ $i }}">Choix {{ $i + 1 }}</label>
                                    <input type="text" id="qChoice-{{ $i }}" wire:model="qChoices.{{ $i }}" placeholder="Choix {{ $i + 1 }}"
                                           style="flex: 1; padding: 7px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                    @if (count($qChoices) > 2)
                                        <x-core::button type="button" wire:click="removeChoice({{ $i }})" variant="ghost" size="sm" aria-label="Retirer le choix {{ $i + 1 }}">✕</x-core::button>
                                    @endif
                                </div>
                            @endforeach
                            <x-core::button type="button" wire:click="addChoice" variant="secondary" size="sm">+ Ajouter un choix</x-core::button>
                            @error('qChoices') <span role="alert" style="display: block; color: var(--sys-action-danger, #DC2626); font-size: 0.82rem; margin-top: 6px;">{{ $message }}</span> @enderror
                            @error('qCorrect') <span role="alert" style="display: block; color: var(--sys-action-danger, #DC2626); font-size: 0.82rem; margin-top: 6px;">{{ $message }}</span> @enderror
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
                    <input id="qPoints" type="number" min="1" max="100" wire:model="qPoints"
                           aria-label="Points attribués à cette question"
                           style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                    <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                        Pondération de la question dans le score du quiz. Laissez 1 pour un barème égal entre toutes les questions.
                    </p>
                    @error('qPoints') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.8rem;">{{ $message }}</span> @enderror

                    <label for="qExplanation" style="font-size: 0.8rem; font-weight: 600;">Explication (facultatif)</label>
                    <textarea id="qExplanation" wire:model="qExplanation" rows="2" maxlength="2000"
                              style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); resize: vertical;"></textarea>

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
