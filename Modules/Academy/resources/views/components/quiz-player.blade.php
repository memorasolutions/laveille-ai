{{-- Author: MEMORA solutions, https://memora.solutions --}}
{{--
    Composant anonyme : <x-academy::quiz-player>
    Props :
      $item        — LessonItem (type=quiz)
      $isEnrolled  — bool
      $course      — Course
      $lesson      — Lesson
      $quizResult  — array|null (from flash 'academy.quiz_result', filtered to this item)
--}}
@props([
    'item',
    'isEnrolled',
    'course',
    'lesson',
    'quizResult' => null,
])

@if(! $isEnrolled)
    {{-- Panneau gating (même style que lesson.blade pour les vidéos) --}}
    <div class="academy-gated-panel">
        <div class="gated-icon">🔐</div>
        <div class="gated-title">
            @if(! auth()->check())
                Connexion requise pour accéder à ce quiz
            @else
                Inscrivez-vous pour accéder à ce quiz
            @endif
        </div>
        <p class="gated-sub">
            @if(! auth()->check())
                Créez un compte gratuit ou connectez-vous pour participer.
            @elseif($course->access_type === 'free')
                Ce cours est gratuit — inscrivez-vous pour accéder à tous les quiz.
            @else
                Votre inscription vous donnera accès à l'ensemble du contenu.
            @endif
        </p>
        @if(! auth()->check())
            <a href="{{ Route::has('login') ? route('login') : '#' }}" class="btn ct-btn ct-btn-primary me-2">
                Se connecter
            </a>
            <a href="{{ Route::has('register') ? route('register') : '#' }}" class="btn btn-outline-secondary">
                Créer un compte
            </a>
        @elseif($course->access_type === 'free')
            <form action="{{ route('academy.courses.enroll', $course) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn ct-btn ct-btn-primary">S'inscrire gratuitement</button>
            </form>
        @endif
    </div>

@else
    {{-- ── Panneau résultat (après soumission) ── --}}
    @if($quizResult !== null)
        @php
            $passed         = (bool) ($quizResult['passed'] ?? false);
            $percent        = (int)  ($quizResult['percent'] ?? 0);
            $correct        = (int)  ($quizResult['correct'] ?? 0);
            $total          = (int)  ($quizResult['total'] ?? 0);
            // V1-c : points pondérés (défaut = nb correct/total si absent, rétrocompat).
            $pointsEarned   = (int)  ($quizResult['points_earned']   ?? $correct);
            $pointsPossible = (int)  ($quizResult['points_possible'] ?? $total);
        @endphp
        <div class="p-4 rounded mb-4" style="
            background: {{ $passed ? '#DCFCE7' : '#FEF9C3' }};
            border: 2px solid {{ $passed ? '#86EFAC' : '#FDE047' }};
        ">
            @if($passed)
                <h5 style="color: #166534; font-weight: 700;">✅ Quiz Réussi !</h5>
                <p class="mb-1">Score : <strong>{{ $percent }}%</strong></p>
            @else
                <h5 style="color: #92400E; font-weight: 700;">⚠️ Score : {{ $percent }}% — Non réussi</h5>
                <p class="mb-1">Score requis : <strong>{{ $item->payload['passing_score'] ?? 60 }}%</strong></p>
            @endif
            <p class="mb-1 text-muted" style="font-size: 0.9rem;">
                {{ $correct }} / {{ $total }} bonnes réponses
            </p>
            @if($pointsPossible > 0)
                <p class="mb-3 text-muted" style="font-size: 0.9rem;">
                    <strong>{{ $pointsEarned }}</strong> / <strong>{{ $pointsPossible }}</strong> points
                </p>
            @else
                <span class="mb-3 d-block"></span>
            @endif
            @if($passed)
                <a href="{{ route('academy.lessons.show', [$course, $lesson]) }}"
                   class="btn ct-btn ct-btn-primary">
                    Continuer →
                </a>
            @else
                <form method="POST"
                      action="{{ route('academy.quiz.start', [$course, $lesson, $item->id]) }}"
                      class="d-inline">
                    @csrf
                    <button type="submit" class="btn ct-btn ct-btn-primary">
                        Réessayer
                    </button>
                </form>
            @endif
        </div>

        {{-- ════════════════════ V1-a : RÉVISION DES RÉPONSES ════════════════════
             Bâtie depuis la DERNIÈRE QuizAttempt de l'UTILISATEUR COURANT pour CET
             item (re-résolue serveur, scope user_id + item → jamais celle d'autrui).
             Source = questions_snapshot (round joué, avec bonnes réponses + feedback)
             + answers (réponses soumises). Tous les textes passent par renderRichText
             (anti-XSS : html_input=strip). On n'affiche la révision QU'au résultat
             (après soumission) = comportement « deferred feedback » par défaut. --}}
        @php
            $review = null;
            if (auth()->check() && class_exists(\Modules\Academy\Models\QuizAttempt::class)) {
                try {
                    $review = \Modules\Academy\Models\QuizAttempt::query()
                        ->forUser((int) auth()->id())
                        ->forItem((int) $item->id)
                        ->latest('submitted_at')
                        ->latest('id')
                        ->first();
                } catch (\Throwable) {
                    $review = null;
                }
            }
        @endphp

        @if($review !== null && is_array($review->questions_snapshot) && $review->questions_snapshot !== [])
            @php
                $snapshot       = $review->questions_snapshot;
                $given          = is_array($review->answers) ? $review->answers : [];
                $overallMessage = \Modules\Academy\Services\QuizFeedbackService::messageForPercent(
                    (array) ($item->payload['overall_feedback'] ?? []),
                    (int) $review->percent
                );
            @endphp

            <section aria-label="Révision de vos réponses" class="mb-4">
                <h5 style="font-weight: 700; color: #1A1D23;">Révision de vos réponses</h5>

                {{-- Couche 3 : feedback global par tranche de score (en tête). --}}
                @if($overallMessage !== null && trim($overallMessage) !== '')
                    <div role="note" class="p-3 rounded mb-3"
                         style="background: #ECFEFF; border: 1px solid #A5F3FC; color: #155E63;">
                        {!! \Modules\Academy\Models\LessonItem::renderRichText($overallMessage) !!}
                    </div>
                @endif

                @foreach($snapshot as $i => $q)
                    @php
                        $qType    = $q['type'] ?? 'qcm';
                        $choices  = is_array($q['choices'] ?? null) ? $q['choices'] : [];
                        $userAns  = $given[(string) $i] ?? ($given[$i] ?? null);

                        // Correct ? (recalculé localement à l'affichage, défensif).
                        $isCorrect = false;
                        $expectedLabel = '';
                        $givenLabel    = '';

                        if ($qType === 'qcm' || $qType === 'vraifaux') {
                            $correctIdx = (int) ($q['correct'] ?? -1);
                            $givenIdx   = is_numeric($userAns) ? (int) $userAns : -1;
                            $isCorrect  = $givenIdx === $correctIdx;
                            $expectedLabel = $choices[$correctIdx] ?? '';
                            $givenLabel    = ($givenIdx >= 0 && isset($choices[$givenIdx])) ? $choices[$givenIdx] : 'Aucune réponse';
                        } elseif ($qType === 'court') {
                            $accepted   = array_map(fn ($s) => mb_strtolower(trim((string) $s)), (array) ($q['accepted'] ?? []));
                            $givenStr   = is_string($userAns) ? trim($userAns) : '';
                            $isCorrect  = in_array(mb_strtolower($givenStr), $accepted, true);
                            $expectedLabel = implode(', ', (array) ($q['accepted'] ?? []));
                            $givenLabel    = $givenStr !== '' ? $givenStr : 'Aucune réponse';
                        } elseif ($qType === 'appariement') {
                            $expectedArr = array_map('intval', (array) ($q['answer'] ?? []));
                            $givenArr    = is_array($userAns) ? array_map('intval', array_values($userAns)) : [];
                            $isCorrect   = $givenArr === $expectedArr;
                        }

                        // Couche 1 : feedback du CHOIX SÉLECTIONNÉ (mcq / vraifaux).
                        $choiceFb = null;
                        if (($qType === 'qcm' || $qType === 'vraifaux') && isset($q['choice_feedback']) && is_array($q['choice_feedback'])) {
                            $givenIdx2 = is_numeric($userAns) ? (int) $userAns : -1;
                            $fb        = $q['choice_feedback'][$givenIdx2] ?? ($q['choice_feedback'][(string) $givenIdx2] ?? null);
                            if (is_string($fb) && trim($fb) !== '') {
                                $choiceFb = $fb;
                            }
                        }

                        // Couche 2 : feedback GÉNÉRAL de la question (= explanation).
                        $generalFb = $q['general_feedback'] ?? ($q['explanation'] ?? null);
                        $generalFb = (is_string($generalFb) && trim($generalFb) !== '') ? $generalFb : null;
                    @endphp

                    <div wire:key="review-{{ $review->id }}-{{ $i }}" class="mb-3 p-3 rounded"
                         style="background: #fff; border: 1px solid #E2E8F0; border-left: 4px solid {{ $isCorrect ? '#16A34A' : '#DC2626' }};">
                        <p class="mb-2" style="font-weight: 600; color: #1A1D23;">
                            <span class="badge me-2" style="background: var(--c-primary, #064E5A); color: #fff;">{{ $i + 1 }}</span>
                            {{ $q['question'] ?? '' }}
                        </p>

                        <p class="mb-1" style="font-size: 0.88rem; color: {{ $isCorrect ? '#166534' : '#991B1B' }};">
                            {{ $isCorrect ? '✔ Bonne réponse' : '✗ À revoir' }}
                        </p>

                        @if($qType === 'appariement')
                            <p class="mb-1 text-muted" style="font-size: 0.85rem;">
                                {{ $isCorrect ? 'Toutes les associations sont correctes.' : 'Certaines associations sont à revoir.' }}
                            </p>
                        @else
                            <p class="mb-1 text-muted" style="font-size: 0.85rem;">
                                Votre réponse : <strong>{{ $givenLabel }}</strong>
                            </p>
                            @unless($isCorrect)
                                <p class="mb-1 text-muted" style="font-size: 0.85rem;">
                                    Bonne réponse : <strong>{{ $expectedLabel }}</strong>
                                </p>
                            @endunless
                        @endif

                        {{-- Couche 1 : rétroaction spécifique du choix sélectionné. --}}
                        @if($choiceFb !== null)
                            <div class="mt-2 p-2 rounded" style="background: #F8FAFC; border: 1px dashed #CBD5E1; font-size: 0.85rem;">
                                <span style="font-weight: 600;">À propos de votre choix :</span>
                                {!! \Modules\Academy\Models\LessonItem::renderRichText($choiceFb) !!}
                            </div>
                        @endif

                        {{-- Couche 2 : rétroaction générale de la question. --}}
                        @if($generalFb !== null)
                            <div class="mt-2 p-2 rounded" style="background: #F0F9FF; border: 1px solid #BAE6FD; font-size: 0.85rem;">
                                <span style="font-weight: 600;">Explication :</span>
                                {!! \Modules\Academy\Models\LessonItem::renderRichText($generalFb) !!}
                            </div>
                        @endif
                    </div>
                @endforeach
            </section>
        @endif
    @endif

    {{-- ── Erreur éventuelle ── --}}
    @if(session()->has('error'))
        <div class="alert alert-danger mb-3" style="font-size: 0.9rem;">
            {{ session('error') }}
        </div>
    @endif

    {{-- ── Quiz actif en session ── --}}
    @if(session()->has("academy.quiz.{$item->id}"))
        @php
            $quizData  = session("academy.quiz.{$item->id}");
            $questions = $quizData['questions'] ?? [];
        @endphp

        <form method="POST"
              action="{{ route('academy.quiz.submit', [$course, $lesson, $item->id]) }}"
              id="academy-quiz-form-{{ $item->id }}">
            @csrf

            @foreach($questions as $i => $question)
                @php
                    $type    = $question['type']     ?? 'qcm';
                    $choices = $question['choices']   ?? [];
                    $terms   = $question['terms']     ?? [];
                    $defs    = $question['defs']      ?? [];
                @endphp

                <div class="mb-4 p-3 rounded" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
                    <p class="mb-2" style="font-weight: 600; font-size: 0.95rem; color: #1A1D23;">
                        <span class="badge me-2" style="background: var(--c-primary, #064E5A); color: #fff;">
                            {{ $i + 1 }}
                        </span>
                        {{ $question['question'] ?? '' }}
                    </p>

                    {{-- QCM ou Vrai/Faux --}}
                    @if($type === 'qcm' || $type === 'vraifaux')
                        @foreach($choices as $j => $choice)
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="radio"
                                       name="answers[{{ $i }}]"
                                       id="q{{ $item->id }}_{{ $i }}_{{ $j }}"
                                       value="{{ $j }}"
                                       required>
                                <label class="form-check-label"
                                       for="q{{ $item->id }}_{{ $i }}_{{ $j }}">
                                    {{ $choice }}
                                </label>
                            </div>
                        @endforeach

                    {{-- Réponse courte --}}
                    @elseif($type === 'court')
                        <input type="text"
                               name="answers[{{ $i }}]"
                               class="form-control mt-2"
                               placeholder="Votre réponse…"
                               autocomplete="off"
                               required
                               style="max-width: 320px;">

                    {{-- Appariement --}}
                    @elseif($type === 'appariement')
                        <div class="mt-2">
                            @foreach($terms as $j => $term)
                                @php $selectId = "q{$item->id}_{$i}_match_{$j}"; @endphp
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <label for="{{ $selectId }}" style="min-width: 160px; font-size: 0.9rem; font-weight: 500; margin-bottom: 0;">
                                        {{ $term }}
                                    </label>
                                    <select id="{{ $selectId }}"
                                            name="answers[{{ $i }}][]"
                                            class="form-select form-select-sm"
                                            style="max-width: 320px;"
                                            aria-label="Définition pour : {{ $term }}"
                                            required>
                                        <option value="">— Choisir une définition —</option>
                                        @foreach($defs as $k => $def)
                                            <option value="{{ $k }}">{{ $def }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>

                    @else
                        <p class="text-muted small">Type de question non pris en charge.</p>
                    @endif

                    {{-- Lien fiche glossaire si disponible --}}
                    @if(! empty($question['fiche']))
                        <a href="{{ $question['fiche'] }}"
                           target="_blank" rel="noopener"
                           class="small text-muted d-inline-block mt-1">
                            📖 Fiche de référence →
                        </a>
                    @endif
                </div>
            @endforeach

            <button type="submit"
                    class="btn ct-btn ct-btn-primary"
                    style="margin-top: 0.5rem;">
                Soumettre le quiz
            </button>
        </form>

    @else
        {{-- ── Bouton « Commencer le quiz » ── --}}
        <div class="p-4 rounded" style="background: #F0F9FF; border: 1px solid #BAE6FD;">
            <h6 class="mb-2" style="color: #0369A1; font-weight: 700;">✏️ Quiz interactif</h6>
            @php
                $passingScore     = (int) ($item->payload['passing_score'] ?? 60);
                $attemptsAllowed  = isset($item->payload['attempts_allowed'])
                    ? (int) $item->payload['attempts_allowed']
                    : null;
            @endphp
            <p class="text-muted mb-1" style="font-size: 0.9rem;">
                Score requis pour réussir : <strong>{{ $passingScore }}%</strong>
            </p>
            @php
                // V1-b : indicateur « Tentative N / M » basé sur l'historique réel.
                // Lecture seule, défensif (si la table n'existe pas, on n'affiche rien).
                $attemptsUsed = 0;
                if (auth()->check() && class_exists(\Modules\Academy\Models\QuizAttempt::class)) {
                    try {
                        $attemptsUsed = \Modules\Academy\Models\QuizAttempt::attemptCount(
                            (int) auth()->id(),
                            (int) $item->id
                        );
                    } catch (\Throwable) {
                        $attemptsUsed = 0;
                    }
                }
                $nextAttempt = $attemptsUsed + 1;
            @endphp
            @if($attemptsAllowed !== null)
                @php $remaining = max(0, $attemptsAllowed - $attemptsUsed); @endphp
                <p class="text-muted mb-3" style="font-size: 0.9rem;">
                    Tentative <strong>{{ min($nextAttempt, $attemptsAllowed) }}</strong> / <strong>{{ $attemptsAllowed }}</strong>
                    <span aria-hidden="true">·</span>
                    {{ $remaining }} {{ $remaining > 1 ? 'restantes' : 'restante' }}
                </p>
            @else
                <p class="text-muted mb-3" style="font-size: 0.9rem;">
                    Tentative <strong>{{ $nextAttempt }}</strong>
                    <span class="visually-hidden">(tentatives illimitées)</span>
                </p>
            @endif
            <form method="POST"
                  action="{{ route('academy.quiz.start', [$course, $lesson, $item->id]) }}">
                @csrf
                <button type="submit" class="btn ct-btn ct-btn-primary">
                    Commencer le quiz
                </button>
            </form>
        </div>
    @endif
@endif
