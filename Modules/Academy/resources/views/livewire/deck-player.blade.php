{{--
    @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
    @project memora/laravel-saas-boilerplate

    ACTION: Vue Livewire DeckPlayer - présentation de leçon en cartes plein écran
    SELF: Blade — fichier de vue, inférieur à 5 lignes de logique propre
    RAISON: Bascule activée par academy.lesson_deck_mode=true dans LessonController

    Réutilise les partials de rendu d'items de la vue classique (DRY strict) :
    le contenu de chaque carte inclut le même bloc @if($item->type === ...) que lesson.blade.php
    via le partial academy::public.partials.item-body.
--}}
<div
    class="deck-player-wrap"
    x-data="{
        init() {
            window.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') $wire.previous();
                if (e.key === 'ArrowRight') $wire.next();
            });
        }
    }"
>
    @php
        $deckItems  = $this->items;
        $deckCount  = $deckItems->count();
        $deckPct    = $deckCount > 0 ? round(($currentIndex + 1) / $deckCount * 100) : 0;
        $isFree     = $course->access_type === 'free';
        $canWatch   = $isEnrolled;
    @endphp

    {{-- Barre de progression + libellé --}}
    <div class="deck-header" role="group" aria-label="Progression de la leçon">
        <div
            class="deck-progress-bar"
            role="progressbar"
            aria-valuenow="{{ $currentIndex + 1 }}"
            aria-valuemin="1"
            aria-valuemax="{{ $deckCount }}"
            aria-label="Carte {{ $currentIndex + 1 }} sur {{ $deckCount }}"
        >
            <div class="deck-progress-fill" style="width: {{ $deckPct }}%;"></div>
        </div>
        <p class="deck-counter" aria-live="polite" aria-atomic="true">
            Carte {{ $currentIndex + 1 }} / {{ $deckCount > 0 ? $deckCount : 1 }}
        </p>
    </div>

    {{-- Zone carte principale --}}
    @if($deckCount === 0)
        <div class="deck-card deck-card--concept" role="status">
            <p class="text-muted">Cette leçon ne contient pas encore de contenu.</p>
        </div>
    @else
        @foreach($deckItems as $deckIndex => $item)
            @if($deckIndex === $currentIndex)
                @php
                    $cardClass     = $this->cardStyle($item);
                    $itemPreview   = (bool) ($item->payload['preview'] ?? false);
                    $hasAccess     = $canWatch || $itemPreview;
                    $__restrict    = (!$isPreview && $canWatch)
                        ? ($itemRestrictions[$item->id] ?? ['allowed' => true, 'hidden' => false, 'reasons' => []])
                        : ['allowed' => true, 'hidden' => false, 'reasons' => []];
                @endphp

                <article
                    class="{{ $cardClass }}"
                    id="deck-item-{{ $item->id }}"
                    aria-label="{{ $item->title ?? 'Élément ' . ($deckIndex + 1) }}"
                >
                    {{-- En-tête de carte (actions = header coloré, sinon titre simple) --}}
                    @if($item->title)
                        @if(str_contains($cardClass, 'deck-card--action'))
                            <div class="deck-card-header">
                                <h2 class="h5 mb-0" style="font-family: var(--f-heading); color: #fff;">
                                    {{ $item->title }}
                                </h2>
                            </div>
                        @else
                            <h2 class="h5 mb-3" style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23);">
                                {{ $item->title }}
                            </h2>
                        @endif
                    @endif

                    {{-- Item grisé (restriction non remplie mais pas masqué) --}}
                    @if(!$__restrict['allowed'])
                        <div class="academy-restricted-panel" role="region" aria-label="Contenu verrouillé : {{ $item->title }}">
                            <div style="display: flex; align-items: flex-start; gap: 10px;">
                                <span aria-hidden="true" style="font-size: 1.5rem; flex-shrink: 0;">🔒</span>
                                <div>
                                    <ul style="margin: 0; padding-left: 1.1rem; font-size: 0.9rem; color: #6B7280;">
                                        @foreach($__restrict['reasons'] as $__reason)
                                            <li>{{ $__reason }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>

                    {{-- Contenu de l'item - réutilise le partial DRY partagé avec lesson.blade.php --}}
                    @else
                        @include('academy::public.partials.item-body', [
                            'item'              => $item,
                            'hasAccess'         => $hasAccess,
                            'isEnrolled'        => $isEnrolled,
                            'isPreview'         => $isPreview,
                            'isFree'            => $isFree,
                            'course'            => $course,
                            'lesson'            => $lesson,
                            'choiceVotes'       => $choiceVotes,
                            'feedbackResponses' => $feedbackResponses,
                            'videoRedirectUrls' => $videoRedirectUrls,
                        ])
                    @endif

                    {{-- Complétion par item (même logique que la vue classique) --}}
                    @if($isEnrolled && !$isPreview)
                        @php
                            $isItemCompleted = false;
                            try {
                                $isItemCompleted = \Modules\Academy\Models\Completion::where('user_id', auth()->id())
                                    ->where('lesson_item_id', $item->id)
                                    ->where('status', 'completed')
                                    ->exists();
                            } catch (\Throwable) {}
                            $itemCriterion = class_exists(\Modules\Academy\Services\ActivityCompletionService::class)
                                ? \Modules\Academy\Services\ActivityCompletionService::criterionFor($item)
                                : 'manual';
                            $itemModeLabel = class_exists(\Modules\Academy\Services\ActivityCompletionService::class)
                                ? \Modules\Academy\Services\ActivityCompletionService::modeLabel($itemCriterion)
                                : 'à marquer comme terminé';
                        @endphp
                        @if($isItemCompleted)
                            <p class="mt-3" style="font-size: 0.9rem; color: #166534;" role="status">
                                ✅ Terminé
                            </p>
                        @elseif($itemCriterion === 'manual' && in_array($item->type, ['video', 'doc', 'document', 'choice', 'forum', 'h5p', 'wiki', 'database', 'workshop']))
                            <form method="POST"
                                  action="{{ route('academy.lessons.complete', [$course, $lesson, $item->id]) }}"
                                  class="mt-3">
                                @csrf
                                <x-core::button type="submit" variant="secondary" size="sm" icon="✓">
                                    Marquer comme terminé
                                </x-core::button>
                            </form>
                        @else
                            <p class="mt-3" style="font-size: 0.9rem; color: var(--sys-text-muted, #6B7280);" role="status">
                                ◯ À faire <span style="font-size: 0.82rem;">({{ $itemModeLabel }})</span>
                            </p>
                        @endif
                    @endif

                </article>
            @endif
        @endforeach
    @endif

    {{-- Contrôles de navigation --}}
    <nav class="deck-nav" aria-label="Navigation entre les cartes">
        <button
            type="button"
            wire:click="previous"
            {{ $currentIndex <= 0 ? 'disabled' : '' }}
            aria-disabled="{{ $currentIndex <= 0 ? 'true' : 'false' }}"
            class="btn btn-outline-secondary deck-btn-prev {{ $currentIndex <= 0 ? 'opacity-50' : '' }}"
        >
            <span aria-hidden="true">←</span>
            <span>Précédent</span>
        </button>

        {{-- Points de navigation --}}
        @if($deckCount > 1)
            <div class="deck-dots" role="list" aria-label="Indicateurs de cartes">
                @foreach($deckItems as $deckIndex => $item)
                    @php
                        $isDone = false;
                        try {
                            $isDone = \Modules\Academy\Models\Completion::where('user_id', auth()->id())
                                ->where('lesson_item_id', $item->id)
                                ->where('status', 'completed')
                                ->exists();
                        } catch (\Throwable) {}
                    @endphp
                    <button
                        type="button"
                        role="listitem"
                        wire:click="goTo({{ $deckIndex }})"
                        class="deck-dot {{ $deckIndex === $currentIndex ? 'is-current' : ($isDone ? 'is-done' : '') }}"
                        aria-label="Aller à la carte {{ $deckIndex + 1 }}{{ $isDone ? ' (terminée)' : '' }}"
                        aria-current="{{ $deckIndex === $currentIndex ? 'true' : 'false' }}"
                    ></button>
                @endforeach
            </div>
        @else
            <span></span>
        @endif

        <button
            type="button"
            wire:click="next"
            {{ $currentIndex >= $deckCount - 1 ? 'disabled' : '' }}
            aria-disabled="{{ $currentIndex >= $deckCount - 1 ? 'true' : 'false' }}"
            class="btn btn-outline-secondary deck-btn-next {{ $currentIndex >= $deckCount - 1 ? 'opacity-50' : '' }}"
        >
            <span>Suivant</span>
            <span aria-hidden="true">→</span>
        </button>
    </nav>

    {{-- Certificat de fin de cours (même logique vue classique) --}}
    @if(auth()->check() && $isEnrolled && !$isPreview && $courseCompleted)
        @php
            $__certificate = null;
            try {
                if (class_exists(\Modules\Academy\Services\CertificateService::class)) {
                    $__certSvc     = new \Modules\Academy\Services\CertificateService();
                    $__certificate = $__certSvc->issueFor(auth()->user(), $course);
                }
            } catch (\Throwable) {}
        @endphp
        @if($__certificate)
            <div class="mt-4 mb-2 p-4 text-center"
                 style="background: #ECFDF5; border: 1px solid #6EE7B7; border-radius: 12px;">
                <div style="font-size: 1.5rem; margin-bottom: 0.4rem;">🎓</div>
                <p class="mb-3 fw-bold" style="color: #065F46;">
                    Félicitations ! Tu as complété ce cours.
                </p>
                <x-core::button :href="route('academy.certificates.show', $__certificate->public_url_slug)" variant="primary">
                    Obtenir mon certificat
                </x-core::button>
            </div>
        @endif
    @endif

</div>

@push('styles')
<style>
    /* ── DeckPlayer ── */
    .deck-player-wrap {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        max-width: 860px;
        margin: 0 auto;
    }
    .deck-header { display: flex; flex-direction: column; gap: 0.25rem; }
    .deck-progress-bar {
        height: 6px;
        background: #E5E7EB;
        border-radius: 999px;
        overflow: hidden;
    }
    .deck-progress-fill {
        height: 100%;
        background: var(--c-primary, #064E5A);
        border-radius: 999px;
        transition: width 0.35s ease;
    }
    .deck-counter {
        font-size: 0.82rem;
        color: var(--sys-text-muted, #6B7280);
        text-align: right;
        margin: 0;
    }
    /* Carte de base */
    .deck-card {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 14px;
        padding: 2rem;
        min-height: 400px;
        max-height: calc(100vh - 240px);
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    }
    /* Style Concept (neutre) - déjà les defaults de .deck-card */
    /* Style Action (teal header) */
    .deck-card--action { border-color: var(--c-primary, #064E5A); }
    .deck-card--action .deck-card-header {
        background: var(--c-primary, #064E5A);
        color: #fff;
        margin: -2rem -2rem 0;
        padding: 1rem 2rem;
        border-radius: 14px 14px 0 0;
    }
    /* Style Vérification (quiz/choice/feedback) */
    .deck-card--verification { border-color: #6EE7B7; border-width: 2px; }
    /* Navigation */
    .deck-nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }
    .deck-btn-prev, .deck-btn-next {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        min-width: 120px;
        min-height: 44px;
        font-size: 0.9rem;
    }
    .deck-btn-prev { justify-content: flex-start; }
    .deck-btn-next { justify-content: flex-end; }
    /* Points indicateurs */
    .deck-dots {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: center;
    }
    .deck-dot {
        width: 12px;
        height: 12px;
        min-width: 12px;
        min-height: 12px;
        border-radius: 50%;
        background: #D1D5DB;
        border: 2px solid transparent;
        cursor: pointer;
        transition: background 0.2s, transform 0.15s;
        padding: 0;
    }
    .deck-dot:hover { transform: scale(1.25); }
    .deck-dot:focus-visible {
        outline: 2px solid var(--c-primary, #064E5A);
        outline-offset: 3px;
    }
    .deck-dot.is-current {
        background: var(--c-primary, #064E5A);
        border-color: var(--c-primary, #064E5A);
        transform: scale(1.2);
    }
    .deck-dot.is-done { background: #34D399; }
    /* Responsive */
    @media (max-width: 640px) {
        .deck-card { padding: 1.25rem; min-height: 300px; max-height: calc(100vh - 200px); }
        .deck-btn-prev, .deck-btn-next { min-width: 80px; }
    }
</style>
@endpush
