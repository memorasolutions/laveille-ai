<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@php($course = $this->course)
<div style="display: flex; flex-direction: column; gap: 28px;">

    {{-- ───────────────────────── Message de succès ───────────────────────── --}}
    @if (session('academy_editor_status'))
        <div role="status" aria-live="polite"
             style="border: 1px solid #BBF7D0; background: #F0FDF4; color: #166534; border-radius: var(--sys-radius-md, 0.75rem); padding: 12px 16px; font-weight: 600;">
            {{ session('academy_editor_status') }}
        </div>
    @endif

    {{-- ───────────────────────── Métadonnées + publication ───────────────────────── --}}
    <section aria-labelledby="editor-meta"
             style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 22px 24px;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2" style="margin-bottom: 18px;">
            <h2 id="editor-meta" style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0; font-size: 1.25rem;">
                Métadonnées du cours
            </h2>
            <span style="font-size: 0.72rem; font-weight: 600; padding: 3px 12px; border-radius: 999px;
                         background: {{ $course->status === 'published' ? '#DCFCE7' : '#F1F5F9' }};
                         color: {{ $course->status === 'published' ? '#166534' : '#475569' }};">
                {{ $course->status === 'published' ? 'Publié' : 'Brouillon' }}
            </span>
        </div>

        <form wire:submit="save" style="display: flex; flex-direction: column; gap: 16px;">
            <div>
                <label for="meta-title" style="display: block; font-weight: 600; margin-bottom: 6px;">Titre</label>
                <input id="meta-title" type="text" wire:model="title"
                       style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                @error('title') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="meta-subtitle" style="display: block; font-weight: 600; margin-bottom: 6px;">Sous-titre</label>
                <input id="meta-subtitle" type="text" wire:model="subtitle"
                       style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                @error('subtitle') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="meta-summary" style="display: block; font-weight: 600; margin-bottom: 6px;">Résumé</label>
                <textarea id="meta-summary" wire:model="summary" rows="3"
                          style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);"></textarea>
                @error('summary') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
            </div>

            <div class="d-flex flex-wrap gap-3">
                <div style="flex: 1 1 200px;">
                    <label for="meta-level" style="display: block; font-weight: 600; margin-bottom: 6px;">Niveau</label>
                    <select id="meta-level" wire:model="level"
                            style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                        <option value="intro">Débutant</option>
                        <option value="inter">Intermédiaire</option>
                        <option value="avance">Avancé</option>
                    </select>
                    @error('level') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>

                <div style="flex: 1 1 200px;">
                    <label for="meta-language" style="display: block; font-weight: 600; margin-bottom: 6px;">Langue</label>
                    <input id="meta-language" type="text" wire:model="language" maxlength="10"
                           style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                    @error('language') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="d-flex flex-wrap gap-3">
                <div style="flex: 1 1 200px;">
                    <label for="meta-visibility" style="display: block; font-weight: 600; margin-bottom: 6px;">Visibilité</label>
                    <select id="meta-visibility" wire:model="visibility"
                            style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                        <option value="public">Publique</option>
                        <option value="unlisted">Non répertoriée</option>
                        <option value="private">Privée</option>
                    </select>
                    @error('visibility') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>

                <div style="flex: 1 1 200px;">
                    <label for="meta-access" style="display: block; font-weight: 600; margin-bottom: 6px;">Accès</label>
                    <select id="meta-access" wire:model.live="access_type"
                            style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                        <option value="free">Gratuit</option>
                        <option value="paid_one_time">Payant (achat unique)</option>
                        <option value="paid_subscription">Payant (abonnement)</option>
                    </select>
                    @error('access_type') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
            </div>

            @if (in_array($access_type, ['paid_one_time', 'paid_subscription'], true))
                <div style="flex: 1 1 200px;">
                    <label for="meta-price" style="display: block; font-weight: 600; margin-bottom: 6px;">Prix (en cents)</label>
                    <input id="meta-price" type="number" min="0" wire:model="price_cents"
                           style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                    @error('price_cents') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
            @endif

            <div class="d-flex flex-wrap align-items-center gap-2" style="margin-top: 6px;">
                @can('update', $course)
                    <x-core::button type="submit" variant="primary" size="sm">Enregistrer</x-core::button>
                @endcan

                @can('publish', $course)
                    <x-core::button type="button" wire:click="togglePublish" variant="secondary" size="sm">
                        {{ $course->status === 'published' ? 'Dépublier' : 'Publier' }}
                    </x-core::button>
                @endcan
            </div>
        </form>
    </section>

    {{-- ───────────────────────── Chapitres + leçons ───────────────────────── --}}
    <section aria-labelledby="editor-structure"
             style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 22px 24px;">
        <h2 id="editor-structure" style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0 0 18px; font-size: 1.25rem;">
            Contenu de la formation
        </h2>

        {{-- Ajouter un chapitre --}}
        @can('manageStructure', $course)
            <form wire:submit="addChapter"
                  style="border: 1px dashed #CBD5E1; border-radius: var(--sys-radius-md, 0.75rem); padding: 14px 16px; margin-bottom: 22px; display: flex; flex-direction: column; gap: 10px;">
                <label for="new-chapter-title" style="font-weight: 600;">Nouveau chapitre</label>
                <input id="new-chapter-title" type="text" wire:model="newChapterTitle" placeholder="Titre du chapitre"
                       style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                @error('newChapterTitle') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                <input type="text" wire:model="newChapterSummary" placeholder="Résumé (facultatif)" aria-label="Résumé du chapitre"
                       style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                <div>
                    <x-core::button type="submit" variant="primary" size="sm">Ajouter le chapitre</x-core::button>
                </div>
            </form>
        @endcan

        @forelse ($course->chapters as $chapter)
            <article style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 16px 18px; margin-bottom: 16px;">
                {{-- En-tête de chapitre --}}
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2" style="margin-bottom: 12px;">
                    <div style="flex: 1 1 240px;">
                        <h3 style="font-family: var(--f-heading); font-size: 1.05rem; color: var(--sys-text-default, #1A1D23); margin: 0 0 4px;">
                            {{ $chapter->title }}
                        </h3>
                        @if ($chapter->summary)
                            <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0;">{{ $chapter->summary }}</p>
                        @endif
                    </div>

                    @can('manageStructure', $course)
                        <div class="d-flex flex-wrap align-items-center gap-1">
                            <x-core::button type="button" wire:click="moveChapterUp({{ $chapter->id }})" variant="ghost" size="sm" title="Monter le chapitre" aria-label="Monter le chapitre">↑</x-core::button>
                            <x-core::button type="button" wire:click="moveChapterDown({{ $chapter->id }})" variant="ghost" size="sm" title="Descendre le chapitre" aria-label="Descendre le chapitre">↓</x-core::button>
                            @if ($confirmingChapterDeletion === $chapter->id)
                                <span style="font-size: 0.82rem; color: var(--sys-text-muted, #6B7280);">Supprimer ?</span>
                                <x-core::button type="button" wire:click="deleteChapter({{ $chapter->id }})" variant="danger" size="sm">Confirmer</x-core::button>
                                <x-core::button type="button" wire:click="cancelChapterDeletion" variant="ghost" size="sm">Annuler</x-core::button>
                            @else
                                <x-core::button type="button" wire:click="confirmChapterDeletion({{ $chapter->id }})" variant="ghost" size="sm" title="Supprimer le chapitre">Supprimer</x-core::button>
                            @endif
                        </div>
                    @endcan
                </div>

                {{-- Édition inline du titre/résumé du chapitre --}}
                @can('manageStructure', $course)
                    <details style="margin-bottom: 12px;">
                        <summary style="cursor: pointer; font-size: 0.85rem; color: var(--sys-action-primary, #064E5A); font-weight: 600;">Renommer ce chapitre</summary>
                        <form wire:submit="updateChapter({{ $chapter->id }}, $event.target.title.value, $event.target.summary.value)"
                              style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px;">
                            <input type="text" name="title" value="{{ $chapter->title }}" aria-label="Titre du chapitre"
                                   style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                            <input type="text" name="summary" value="{{ $chapter->summary }}" aria-label="Résumé du chapitre"
                                   style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                            <div><x-core::button type="submit" variant="secondary" size="sm">Mettre à jour</x-core::button></div>
                        </form>
                    </details>
                @endcan

                {{-- Leçons du chapitre --}}
                @if ($chapter->lessons->isNotEmpty())
                    <ul class="list-unstyled" style="margin: 0 0 12px; display: flex; flex-direction: column; gap: 8px;">
                        @foreach ($chapter->lessons as $lesson)
                            <li style="border: 1px solid #F1F5F9; border-radius: var(--sys-radius-md, 0.5rem); padding: 12px 14px;">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                    <div style="flex: 1 1 220px;">
                                        <strong style="color: var(--sys-text-default, #1A1D23);">{{ $lesson->title }}</strong>
                                        <p style="font-size: 0.8rem; color: var(--sys-text-muted, #6B7280); margin: 4px 0 0;">
                                            {{ $lesson->lesson_items_count }} {{ $lesson->lesson_items_count > 1 ? 'éléments' : 'élément' }}
                                            @if ($lesson->estimated_minutes) · {{ $lesson->estimated_minutes }} min @endif
                                        </p>
                                    </div>

                                    @can('manageStructure', $course)
                                        <div class="d-flex flex-wrap align-items-center gap-1">
                                            <x-core::button type="button" wire:click="moveLessonUp({{ $lesson->id }})" variant="ghost" size="sm" title="Monter la leçon" aria-label="Monter la leçon">↑</x-core::button>
                                            <x-core::button type="button" wire:click="moveLessonDown({{ $lesson->id }})" variant="ghost" size="sm" title="Descendre la leçon" aria-label="Descendre la leçon">↓</x-core::button>
                                            @if ($confirmingLessonDeletion === $lesson->id)
                                                <span style="font-size: 0.8rem; color: var(--sys-text-muted, #6B7280);">Supprimer ?</span>
                                                <x-core::button type="button" wire:click="deleteLesson({{ $lesson->id }})" variant="danger" size="sm">Confirmer</x-core::button>
                                                <x-core::button type="button" wire:click="cancelLessonDeletion" variant="ghost" size="sm">Annuler</x-core::button>
                                            @else
                                                <x-core::button type="button" wire:click="confirmLessonDeletion({{ $lesson->id }})" variant="ghost" size="sm" title="Supprimer la leçon">Supprimer</x-core::button>
                                            @endif
                                        </div>
                                    @endcan
                                </div>

                                @can('manageStructure', $course)
                                    <details style="margin-top: 8px;">
                                        <summary style="cursor: pointer; font-size: 0.8rem; color: var(--sys-action-primary, #064E5A); font-weight: 600;">Modifier cette leçon</summary>
                                        <form wire:submit="updateLesson({{ $lesson->id }}, $event.target.title.value, $event.target.summary.value, $event.target.estimated_minutes.value)"
                                              style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px;">
                                            <input type="text" name="title" value="{{ $lesson->title }}" aria-label="Titre de la leçon"
                                                   style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                            <input type="text" name="summary" value="{{ $lesson->summary }}" aria-label="Résumé de la leçon"
                                                   style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                            <input type="number" min="1" name="estimated_minutes" value="{{ $lesson->estimated_minutes }}" aria-label="Durée estimée en minutes"
                                                   style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                            <div><x-core::button type="submit" variant="secondary" size="sm">Enregistrer la leçon</x-core::button></div>
                                        </form>
                                    </details>
                                @endcan

                                {{-- ───────── Contenu de la leçon : éléments (vidéo / document / quiz) ───────── --}}
                                @can('manageStructure', $course)
                                    <div style="margin-top: 12px; border-top: 1px solid #F1F5F9; padding-top: 12px;">
                                        <p style="font-weight: 600; font-size: 0.82rem; color: var(--sys-text-default, #1A1D23); margin: 0 0 8px;">
                                            Contenu de la leçon
                                        </p>

                                        @if ($lesson->lessonItems->isNotEmpty())
                                            <ul class="list-unstyled" style="margin: 0 0 10px; display: flex; flex-direction: column; gap: 8px;">
                                                @foreach ($lesson->lessonItems as $item)
                                                    @php($typeLabel = ['video' => 'Vidéo', 'document' => 'Document', 'quiz' => 'Quiz'][$item->type] ?? $item->type)
                                                    <li style="border: 1px solid #F1F5F9; border-radius: var(--sys-radius-md, 0.5rem); padding: 10px 12px;">
                                                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                                            <div style="flex: 1 1 200px;">
                                                                <span style="display: inline-block; font-size: 0.68rem; font-weight: 600; padding: 2px 8px; border-radius: 999px; background: #E0F2F1; color: #064E5A; margin-bottom: 4px;">
                                                                    {{ $typeLabel }}
                                                                </span>
                                                                @if ($item->is_required)
                                                                    <span style="display: inline-block; font-size: 0.68rem; font-weight: 600; padding: 2px 8px; border-radius: 999px; background: #FEF3C7; color: #92400E; margin-bottom: 4px;">Obligatoire</span>
                                                                @endif
                                                                <strong style="display: block; color: var(--sys-text-default, #1A1D23); font-size: 0.9rem;">{{ $item->title }}</strong>
                                                                @if ($item->estimated_minutes)
                                                                    <span style="font-size: 0.75rem; color: var(--sys-text-muted, #6B7280);">{{ $item->estimated_minutes }} min</span>
                                                                @endif
                                                            </div>

                                                            <div class="d-flex flex-wrap align-items-center gap-1">
                                                                <x-core::button type="button" wire:click="moveItemUp({{ $item->id }})" variant="ghost" size="sm" title="Monter l'élément" aria-label="Monter l'élément">↑</x-core::button>
                                                                <x-core::button type="button" wire:click="moveItemDown({{ $item->id }})" variant="ghost" size="sm" title="Descendre l'élément" aria-label="Descendre l'élément">↓</x-core::button>
                                                                <x-core::button type="button" wire:click="toggleRequired({{ $item->id }})" variant="ghost" size="sm" title="Basculer « obligatoire »">
                                                                    {{ $item->is_required ? 'Rendre facultatif' : 'Rendre obligatoire' }}
                                                                </x-core::button>
                                                                @if ($confirmingItemDeletion === $item->id)
                                                                    <span style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280);">Supprimer ?</span>
                                                                    <x-core::button type="button" wire:click="deleteItem({{ $item->id }})" variant="danger" size="sm">Confirmer</x-core::button>
                                                                    <x-core::button type="button" wire:click="cancelItemDeletion" variant="ghost" size="sm">Annuler</x-core::button>
                                                                @else
                                                                    <x-core::button type="button" wire:click="confirmItemDeletion({{ $item->id }})" variant="ghost" size="sm" title="Supprimer l'élément">Supprimer</x-core::button>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        {{-- Modifier cet élément (formulaire par type) --}}
                                                        <details style="margin-top: 8px;">
                                                            <summary style="cursor: pointer; font-size: 0.78rem; color: var(--sys-action-primary, #064E5A); font-weight: 600;">Modifier cet élément</summary>
                                                            <form wire:submit="updateItem(
                                                                    {{ $item->id }},
                                                                    '{{ $item->type }}',
                                                                    $event.target.title.value,
                                                                    $event.target.estimated_minutes.value,
                                                                    $event.target.external_ref ? $event.target.external_ref.value : null,
                                                                    $event.target.rich_text ? $event.target.rich_text.value : null,
                                                                    $event.target.qt_bank_key ? $event.target.qt_bank_key.value : null
                                                                  )"
                                                                  style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px;">
                                                                <label style="font-size: 0.78rem; font-weight: 600;" for="item-title-{{ $item->id }}">Titre</label>
                                                                <input id="item-title-{{ $item->id }}" type="text" name="title" value="{{ $item->title }}" aria-label="Titre de l'élément"
                                                                       style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                                @if ($item->type === 'video')
                                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="item-ref-{{ $item->id }}">Référence d'intégration ScreenPal</label>
                                                                    <input id="item-ref-{{ $item->id }}" type="text" name="external_ref" value="{{ $item->external_ref ?? ($item->payload['embed'] ?? '') }}" placeholder="Identifiant ou URL d'intégration ScreenPal" aria-label="Référence ScreenPal"
                                                                           style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                    <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                                                                        La vidéo doit être non répertoriée, avec verrou de domaine activé sur ScreenPal.
                                                                    </p>
                                                                @elseif ($item->type === 'document')
                                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="item-doc-{{ $item->id }}">Contenu (texte riche / markdown simple)</label>
                                                                    <textarea id="item-doc-{{ $item->id }}" name="rich_text" rows="4" aria-label="Contenu du document"
                                                                              style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">{{ $item->payload['rich_text'] ?? '' }}</textarea>
                                                                @elseif ($item->type === 'quiz')
                                                                    <label style="font-size: 0.78rem; font-weight: 600;" for="item-qt-{{ $item->id }}">Clé de banque QT</label>
                                                                    <input id="item-qt-{{ $item->id }}" type="text" name="qt_bank_key" value="{{ $item->payload['qt_bank_key'] ?? '' }}" placeholder="Clé de banque QT" aria-label="Clé de banque QT"
                                                                           style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                    <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                                                                        Le quiz réutilise QtService à partir de cette clé.
                                                                    </p>
                                                                @endif

                                                                <label style="font-size: 0.78rem; font-weight: 600;" for="item-min-{{ $item->id }}">Durée estimée (min, facultatif)</label>
                                                                <input id="item-min-{{ $item->id }}" type="number" min="1" name="estimated_minutes" value="{{ $item->estimated_minutes }}" aria-label="Durée estimée en minutes"
                                                                       style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                                <div><x-core::button type="submit" variant="secondary" size="sm">Enregistrer l'élément</x-core::button></div>
                                                            </form>
                                                        </details>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p style="font-size: 0.8rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 10px;">Aucun élément dans cette leçon.</p>
                                        @endif

                                        {{-- Ajouter un élément (formulaire par type) --}}
                                        <details style="border-top: 1px dashed #E5E7EB; padding-top: 10px;">
                                            <summary style="cursor: pointer; font-size: 0.8rem; color: var(--sys-action-primary, #064E5A); font-weight: 600;">Ajouter un élément</summary>

                                            <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px;">
                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-title-{{ $lesson->id }}">Titre de l'élément</label>
                                                <input id="newitem-title-{{ $lesson->id }}" type="text" wire:model="newItem.{{ $lesson->id }}.title" placeholder="Titre de l'élément"
                                                       style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                @error('title') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror

                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-ref-{{ $lesson->id }}">Référence ScreenPal (pour une vidéo)</label>
                                                <input id="newitem-ref-{{ $lesson->id }}" type="text" wire:model="newItem.{{ $lesson->id }}.external_ref" placeholder="Identifiant ou URL d'intégration ScreenPal"
                                                       style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                <p style="font-size: 0.72rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                                                    Vidéo : utilisez une vidéo non répertoriée avec verrou de domaine sur ScreenPal.
                                                </p>

                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-doc-{{ $lesson->id }}">Contenu (pour un document)</label>
                                                <textarea id="newitem-doc-{{ $lesson->id }}" wire:model="newItem.{{ $lesson->id }}.rich_text" rows="3" placeholder="Texte riche / markdown simple"
                                                          style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);"></textarea>

                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-qt-{{ $lesson->id }}">Clé de banque QT (pour un quiz)</label>
                                                <input id="newitem-qt-{{ $lesson->id }}" type="text" wire:model="newItem.{{ $lesson->id }}.qt_bank_key" placeholder="Clé de banque QT (réutilise QtService)"
                                                       style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                <label style="font-size: 0.78rem; font-weight: 600;" for="newitem-min-{{ $lesson->id }}">Durée estimée (min, facultatif)</label>
                                                <input id="newitem-min-{{ $lesson->id }}" type="number" min="1" wire:model="newItem.{{ $lesson->id }}.estimated_minutes" placeholder="Durée estimée"
                                                       style="width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">

                                                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 600;">
                                                    <input type="checkbox" wire:model="newItem.{{ $lesson->id }}.is_required" style="width: 18px; height: 18px;">
                                                    Élément obligatoire
                                                </label>

                                                <div class="d-flex flex-wrap gap-2" style="margin-top: 4px;">
                                                    <x-core::button type="button" wire:click="addItem({{ $lesson->id }}, 'video')" variant="primary" size="sm">Ajouter une vidéo</x-core::button>
                                                    <x-core::button type="button" wire:click="addItem({{ $lesson->id }}, 'document')" variant="primary" size="sm">Ajouter un document</x-core::button>
                                                    <x-core::button type="button" wire:click="addItem({{ $lesson->id }}, 'quiz')" variant="primary" size="sm">Ajouter un quiz</x-core::button>
                                                </div>
                                            </div>
                                        </details>
                                    </div>
                                @endcan
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 12px;">Aucune leçon dans ce chapitre.</p>
                @endif

                {{-- Ajouter une leçon à CE chapitre --}}
                @can('manageStructure', $course)
                    <form wire:submit="addLesson({{ $chapter->id }})"
                          style="border-top: 1px dashed #E5E7EB; padding-top: 12px; display: flex; flex-direction: column; gap: 8px;">
                        <label for="new-lesson-{{ $chapter->id }}" style="font-weight: 600; font-size: 0.85rem;">Nouvelle leçon</label>
                        <input id="new-lesson-{{ $chapter->id }}" type="text" wire:model="newLesson.{{ $chapter->id }}.title" placeholder="Titre de la leçon"
                               style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                        @error('newLesson.' . $chapter->id . '.title') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                        <input type="number" min="1" wire:model="newLesson.{{ $chapter->id }}.estimated_minutes" placeholder="Durée estimée (min, facultatif)" aria-label="Durée estimée de la nouvelle leçon"
                               style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                        <div><x-core::button type="submit" variant="primary" size="sm">Ajouter la leçon</x-core::button></div>
                    </form>
                @endcan
            </article>
        @empty
            <p style="color: var(--sys-text-muted, #6B7280);">Aucun chapitre pour l'instant. Commencez par en ajouter un.</p>
        @endforelse
    </section>

    {{-- ───────────────────────── Zone sensible : suppression du cours ───────────────────────── --}}
    @can('delete', $course)
        <section aria-labelledby="editor-danger"
                 style="border: 1px solid #FECACA; background: #FEF2F2; border-radius: var(--sys-radius-md, 0.75rem); padding: 20px 24px;">
            <h2 id="editor-danger" style="font-family: var(--f-heading); color: #991B1B; margin: 0 0 8px; font-size: 1.05rem;">
                Supprimer la formation
            </h2>
            <p style="font-size: 0.85rem; color: #7F1D1D; margin: 0 0 14px;">
                Cette action supprime le cours et tout son contenu. Elle est réservée au propriétaire et à l'administration.
            </p>
            @if ($confirmingCourseDeletion)
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span style="font-size: 0.85rem; color: #7F1D1D; font-weight: 600;">Confirmer la suppression définitive ?</span>
                    <x-core::button type="button" wire:click="deleteCourse" variant="danger" size="sm">Oui, supprimer</x-core::button>
                    <x-core::button type="button" wire:click="cancelCourseDeletion" variant="ghost" size="sm">Annuler</x-core::button>
                </div>
            @else
                <x-core::button type="button" wire:click="confirmCourseDeletion" variant="danger" size="sm">Supprimer ce cours</x-core::button>
            @endif
        </section>
    @endcan

</div>
