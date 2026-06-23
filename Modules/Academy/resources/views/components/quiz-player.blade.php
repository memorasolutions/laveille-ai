{{-- Author: MEMORA solutions, https://memora.solutions --}}
{{--
    Composant anonyme : <x-academy::quiz-player>
    Props :
      $item        – LessonItem (type=quiz)
      $isEnrolled  – bool
      $course      – Course
      $lesson      – Lesson
      $quizResult  – array|null (from flash 'academy.quiz_result', filtered to this item)
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
                Ce cours est gratuit : inscrivez-vous pour accéder à tous les quiz.
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
    @php
        // C1 - Un quiz est-il ACTIF en session (mid-quiz) ? Si oui, on n'affiche pas de
        // panneau résultat persistant (l'étudiant est en train de répondre).
        $hasActiveSession = session()->has("academy.quiz.{$item->id}");

        // C1 - DERNIÈRE tentative PERSISTANTE de l'UTILISATEUR COURANT pour CET item,
        // scopée user_id + lesson_item_id (anti-IDOR : jamais celle d'autrui). Source
        // unique du panneau résultat persistant ET de la section de révision. Défensif.
        $latestAttempt = null;
        if (auth()->check() && class_exists(\Modules\Academy\Models\QuizAttempt::class)) {
            try {
                $latestAttempt = \Modules\Academy\Models\QuizAttempt::query()
                    ->forUser((int) auth()->id())
                    ->forItem((int) $item->id)
                    ->latest('submitted_at')
                    ->latest('id')
                    ->first();
            } catch (\Throwable) {
                $latestAttempt = null;
            }
        }

        // C1 - Source du panneau résultat :
        //   • flash 'academy.quiz_result' = affichage IMMÉDIAT juste après soumission ;
        //   • sinon, si une tentative existe et qu'aucun quiz n'est en cours, on rend le
        //     DERNIER résultat de façon PERSISTANTE (note + révision + feedback formateur).
        $showPersistent = ($quizResult === null && $latestAttempt !== null && ! $hasActiveSession);
        $showResult     = ($quizResult !== null) || $showPersistent;

        // Révision (immédiat ET persistant) : même tentative déjà résolue (anti-IDOR).
        $review = $latestAttempt;
    @endphp

    {{-- ── Panneau résultat (immédiat = flash, ou persistant = dernière tentative) ── --}}
    @if($showResult)
        @php
            // Normalisation commune du panneau (flash OU persistant). Mêmes noms de
            // variables que l'affichage flash historique (rendu inchangé en aval).
            if ($quizResult !== null) {
                // ESSAI : tentative en attente de correction → on n'affiche PAS un faux
                // score/percent final ni « réussi », mais un message « en attente ».
                $needsGradingResult = (bool) ($quizResult['needs_grading'] ?? false);
                $passed         = (bool) ($quizResult['passed'] ?? false);
                $percent        = (int)  ($quizResult['percent'] ?? 0);
                $correct        = (int)  ($quizResult['correct'] ?? 0);
                $total          = (int)  ($quizResult['total'] ?? 0);
                // V1-c : points pondérés (défaut = nb correct/total si absent, rétrocompat).
                // C1 : points_earned peut être FRACTIONNAIRE (crédit partiel, ex. 0,5) →
                // garder le float et l'afficher en décimal localisé (virgule FR).
                $pointsEarned   = (float) ($quizResult['points_earned']   ?? $correct);
                $pointsPossible = (int)   ($quizResult['points_possible'] ?? $total);
                // V1-d : soumission hors-temps (garde serveur).
                $timedOut       = (bool) ($quizResult['timed_out'] ?? false);
            } else {
                // C1 - PERSISTANT : on relit la tentative. Tant qu'un essai n'est pas
                // corrigé (needs_grading), on n'affiche jamais un faux score. Sinon, on
                // RECALCULE les points pondérés depuis le round snapshoté (auto + manuel
                // d'essai) via EssayGradingService::recompute - DRY : pour un quiz sans
                // essai, le recalcul redonne exactement le score auto.
                $needsGradingResult = (bool) $latestAttempt->needs_grading;
                $passed         = (bool) $latestAttempt->passed;
                $percent        = (int)  $latestAttempt->percent;
                $total          = (int)  $latestAttempt->max_score;
                $timedOut       = (bool) $latestAttempt->timed_out;
                $correct        = (int)  $latestAttempt->score;
                $pointsEarned   = (float) $correct;
                $pointsPossible = $total;
                if (! $needsGradingResult) {
                    try {
                        $rc = \Modules\Academy\Services\EssayGradingService::recompute($latestAttempt);
                        $correct        = (int)   $rc['correct'];
                        $pointsEarned   = (float) $rc['points_earned'];
                        $pointsPossible = (int)   $rc['points_possible'];
                    } catch (\Throwable) {
                        // Repli : on garde les colonnes stockées (déjà posées ci-dessus).
                    }
                }
            }
            $pointsEarnedFr = str_replace('.', ',', \Modules\Academy\Services\QuizService::formatNumber((float) $pointsEarned));
        @endphp
        @if($timedOut)
            <div role="alert" class="p-3 rounded mb-3"
                 style="background: #FEF3C7; border: 1px solid #FCD34D; color: #92400E; font-size: 0.9rem;">
                ⏱️ Temps écoulé : votre quiz a été soumis automatiquement à l'expiration de la limite.
            </div>
        @endif

        @if($needsGradingResult)
            {{-- ESSAI : tentative en attente de correction manuelle. On n'affiche PAS de
                 score/percent final ni « réussi » : la note définitive viendra après la
                 correction du formateur. --}}
            <div role="status" class="p-4 rounded mb-4"
                 style="background: #EFF6FF; border: 2px solid #BFDBFE; color: #1E3A8A;">
                <h5 style="font-weight: 700; color: #1E40AF;">📝 Réponse enregistrée - en attente de correction</h5>
                <p class="mb-1" style="font-size: 0.92rem;">
                    Votre quiz contient une réponse rédigée (essai) corrigée manuellement par le formateur.
                    Votre note finale s'affichera une fois la correction effectuée.
                </p>
                <a href="{{ route('academy.lessons.show', [$course, $lesson]) }}"
                   class="btn ct-btn ct-btn-primary mt-2">
                    Continuer →
                </a>
            </div>
        @else
        <div class="p-4 rounded mb-4" style="
            background: {{ $passed ? '#DCFCE7' : '#FEF9C3' }};
            border: 2px solid {{ $passed ? '#86EFAC' : '#FDE047' }};
        ">
            @if($passed)
                <h5 style="color: #166534; font-weight: 700;">✅ Quiz Réussi !</h5>
                <p class="mb-1">Score : <strong>{{ $percent }}%</strong></p>
            @else
                <h5 style="color: #92400E; font-weight: 700;">⚠️ Score : {{ $percent }}% – Non réussi</h5>
                <p class="mb-1">Score requis : <strong>{{ $item->payload['passing_score'] ?? 60 }}%</strong></p>
            @endif
            <p class="mb-1 text-muted" style="font-size: 0.9rem;">
                {{ $correct }} / {{ $total }} bonnes réponses
            </p>
            @if($pointsPossible > 0)
                <p class="mb-3 text-muted" style="font-size: 0.9rem;">
                    <strong>{{ $pointsEarnedFr }}</strong> / <strong>{{ $pointsPossible }}</strong> {{ $pointsPossible >= 2 ? 'points' : 'point' }}
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
        @if($review !== null && is_array($review->questions_snapshot) && $review->questions_snapshot !== [])
            @php
                $snapshot       = $review->questions_snapshot;
                $given          = is_array($review->answers) ? $review->answers : [];
                $overallMessage = \Modules\Academy\Services\QuizFeedbackService::messageForPercent(
                    (array) ($item->payload['overall_feedback'] ?? []),
                    (int) $review->percent
                );
                // V1-d : options de révision (défaut « tout vrai » = V1-a inchangé).
                $reviewOpts = \Modules\Academy\Services\QuizReviewOptions::normalize(
                    $item->payload['review_options'] ?? null
                );
            @endphp

            <section aria-label="Révision de vos réponses" class="mb-4">
                <h5 style="font-weight: 700; color: #1A1D23;">Révision de vos réponses</h5>

                {{-- Couche 3 : feedback global par tranche de score (en tête). --}}
                @if($reviewOpts['show_overall_feedback'] && $overallMessage !== null && trim($overallMessage) !== '')
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

                        // V1-e : QCM à réponses multiples → `correct` est un TABLEAU.
                        $isMultiReview = ($qType === 'qcm') && is_array($q['correct'] ?? null);

                        if ($isMultiReview) {
                            $correctSet = array_values(array_unique(array_map('intval', (array) $q['correct'])));
                            sort($correctSet);
                            $givenSet = is_array($userAns)
                                ? array_values(array_unique(array_map('intval', $userAns)))
                                : (is_numeric($userAns) ? [(int) $userAns] : []);
                            sort($givenSet);
                            // Correct (badge) seulement si fraction == 1 (exactement les bonnes).
                            $isCorrect     = $givenSet === $correctSet;
                            $expectedLabel = implode(', ', array_map(fn ($k) => $choices[$k] ?? '', $correctSet)) ?: '–';
                            $givenLabels   = array_filter(array_map(fn ($k) => $choices[$k] ?? null, $givenSet), fn ($v) => $v !== null);
                            $givenLabel    = $givenLabels !== [] ? implode(', ', $givenLabels) : 'Aucune réponse';
                        } elseif ($qType === 'qcm' || $qType === 'vraifaux') {
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
                        } elseif ($qType === 'numerique') {
                            // Correctness recalculée via le parseur partagé (tolérance + unité indicative).
                            $expectedNum = isset($q['correct']) && is_numeric($q['correct']) ? (float) $q['correct'] : null;
                            $tolNum      = isset($q['tolerance']) && is_numeric($q['tolerance']) ? abs((float) $q['tolerance']) : 0.0;
                            $unitNum     = isset($q['unit']) && is_string($q['unit']) ? trim($q['unit']) : '';
                            $givenNum    = \Modules\Academy\Services\QuizService::parseNumber($userAns);
                            $isCorrect   = $expectedNum !== null && $givenNum !== null && abs($givenNum - $expectedNum) <= $tolNum;
                            // C3 : formatage centralisé (DRY) au lieu d'une closure dupliquée.
                            $fmtNum      = fn (float $v): string => \Modules\Academy\Services\QuizService::formatNumber($v);
                            $expectedLabel = $expectedNum !== null
                                ? $fmtNum($expectedNum).($tolNum > 0 ? ' (± '.$fmtNum($tolNum).')' : '').($unitNum !== '' ? ' '.$unitNum : '')
                                : '';
                            $givenLabel = (is_string($userAns) && trim($userAns) !== '')
                                ? trim($userAns)
                                : (is_numeric($userAns) ? (string) $userAns : 'Aucune réponse');
                        } elseif ($qType === 'appariement') {
                            $expectedArr = array_map('intval', (array) ($q['answer'] ?? []));
                            $givenArr    = is_array($userAns) ? array_map('intval', array_values($userAns)) : [];
                            $isCorrect   = $givenArr === $expectedArr;
                        } elseif ($qType === 'ordonnancement') {
                            // `answer` = position absolue correcte (0-based) de chaque élément affiché.
                            $orderElements = is_array($q['elements'] ?? null) ? $q['elements'] : [];
                            $expectedArr   = array_map('intval', (array) ($q['answer'] ?? []));
                            $givenArr      = is_array($userAns) ? array_map('intval', array_values($userAns)) : [];
                            $isCorrect     = $givenArr === $expectedArr;

                            // Ordre correct reconstruit (élément à sa position absolue).
                            $correctSequence = [];
                            foreach ($expectedArr as $j => $pos) {
                                if (isset($orderElements[$j])) {
                                    $correctSequence[$pos] = $orderElements[$j];
                                }
                            }
                            ksort($correctSequence);
                            $correctSequence = array_values($correctSequence);
                        } elseif ($qType === 'cloze') {
                            // Crédit par trou : correct (badge) seulement si TOUS les trous justes.
                            // Le détail par trou (réponse/bonne réponse) est rendu par le partial
                            // cloze-review (C3) ; ici on ne calcule QUE la synthèse « tout juste ».
                            $blanks   = is_array($q['blanks'] ?? null) ? $q['blanks'] : [];
                            $givenMap = is_array($userAns) ? $userAns : [];
                            $allBlanksOk = count($blanks) > 0;
                            foreach ($blanks as $bk => $blank) {
                                $kind = ($blank['kind'] ?? 'short') === 'mcq' ? 'mcq' : 'short';
                                $ans  = $givenMap[$bk] ?? ($givenMap[(string) $bk] ?? null);
                                if ($kind === 'mcq') {
                                    $bOk = (is_numeric($ans) ? (int) $ans : -1) === (int) ($blank['correct'] ?? -1);
                                } else {
                                    $bAccepted = array_map(fn ($s) => mb_strtolower(trim((string) $s)), (array) ($blank['accepted'] ?? []));
                                    $bGivenStr = is_string($ans) ? trim($ans) : '';
                                    $bOk       = $bGivenStr !== '' && in_array(mb_strtolower($bGivenStr), $bAccepted, true);
                                }
                                if (! $bOk) {
                                    $allBlanksOk = false;
                                }
                            }
                            $isCorrect = $allBlanksOk;
                        } elseif ($qType === 'glisser-texte') {
                            // Correct (badge) seulement si TOUS les trous portent le bon mot.
                            // Le détail par trou est rendu par le partial ddwtos-review.
                            $glAnswers = is_array($q['answers'] ?? null) ? $q['answers'] : [];
                            $glGiven   = is_array($userAns) ? $userAns : [];
                            $allGlOk   = count($glAnswers) > 0;
                            foreach ($glAnswers as $gk => $gCorrect) {
                                $gAns = $glGiven[$gk] ?? ($glGiven[(string) $gk] ?? null);
                                if ((is_numeric($gAns) ? (int) $gAns : -1) !== (int) $gCorrect) {
                                    $allGlOk = false;
                                }
                            }
                            $isCorrect = $allGlOk;
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
                         style="background: #fff; border: 1px solid #E2E8F0; border-left: 4px solid {{ $qType === 'essai' ? '#94A3B8' : ($reviewOpts['show_correctness'] ? ($isCorrect ? '#16A34A' : '#DC2626') : '#94A3B8') }};">
                        <p class="mb-2" style="font-weight: 600; color: #1A1D23;">
                            <span class="badge me-2" style="background: var(--c-primary, #064E5A); color: #fff;">{{ $i + 1 }}</span>
                            {{ $q['question'] ?? '' }}
                        </p>

                        @if($reviewOpts['show_correctness'] && $qType !== 'essai')
                            <p class="mb-1" style="font-size: 0.88rem; color: {{ $isCorrect ? '#166534' : '#991B1B' }};">
                                {{ $isCorrect ? '✔ Bonne réponse' : '✗ À revoir' }}
                            </p>
                        @endif

                        @if($qType === 'essai')
                            {{-- ESSAI : pas d'auto-correction. On rappelle la réponse soumise
                                 (rendue anti-XSS) ; la note vient de la correction manuelle. --}}
                            <p class="mb-1 text-muted" style="font-size: 0.85rem;">Réponse rédigée (corrigée par le formateur) :</p>
                            <div class="mb-1 p-2 rounded" style="background: #F8FAFC; border: 1px solid #E2E8F0; font-size: 0.85rem;">
                                {!! \Modules\Academy\Models\LessonItem::renderRichText(is_string($userAns) ? $userAns : '') !!}
                            </div>
                            @php
                                // C1 - Note + commentaire du formateur (correction manuelle). Affichés
                                // seulement si la tentative est CORRIGÉE (needs_grading=false). Ce sont
                                // les données PERSONNELLES de CET étudiant (re-résolues anti-IDOR), donc
                                // toujours visibles pour lui, indépendamment de review_options (qui ne
                                // gouverne que l'exposition des bonnes réponses, sans objet pour un essai).
                                $essayManualScore = null;
                                $essayManualMax   = null;
                                $essayManualFb    = null;
                                if ($review !== null && ! $review->needs_grading) {
                                    $ms = is_array($review->manual_scores) ? $review->manual_scores : [];
                                    $mf = is_array($review->manual_feedback) ? $review->manual_feedback : [];
                                    $sc = $ms[$i] ?? ($ms[(string) $i] ?? null);
                                    if ($sc !== null && is_numeric($sc)) {
                                        $essayManualScore = (int) $sc;
                                        $essayManualMax   = \Modules\Academy\Services\EssayGradingService::essayMaxPoints($review, (int) $i);
                                    }
                                    $fbVal = $mf[$i] ?? ($mf[(string) $i] ?? null);
                                    if (is_string($fbVal) && trim($fbVal) !== '') {
                                        $essayManualFb = $fbVal;
                                    }
                                }
                            @endphp
                            @if($essayManualScore !== null)
                                <p class="mb-1" style="font-size: 0.85rem; color: #166534; font-weight: 600;">
                                    Points obtenus : {{ $essayManualScore }} / {{ $essayManualMax }} {{ $essayManualMax >= 2 ? 'points' : 'point' }}
                                </p>
                            @endif
                            @if($essayManualFb !== null)
                                <div class="mt-2 p-2 rounded" style="background: #F0F9FF; border: 1px solid #BAE6FD; font-size: 0.85rem;">
                                    <span style="font-weight: 600;">Commentaire du formateur :</span>
                                    {!! \Modules\Academy\Models\LessonItem::renderRichText($essayManualFb) !!}
                                </div>
                            @endif
                        @elseif($qType === 'appariement')
                            @if($reviewOpts['show_correctness'])
                                <p class="mb-1 text-muted" style="font-size: 0.85rem;">
                                    {{ $isCorrect ? 'Toutes les associations sont correctes.' : 'Certaines associations sont à revoir.' }}
                                </p>
                            @endif
                        @elseif($qType === 'ordonnancement')
                            @if($reviewOpts['show_correctness'])
                                <p class="mb-1 text-muted" style="font-size: 0.85rem;">
                                    {{ $isCorrect ? 'L\'ordre est exact.' : 'L\'ordre est à revoir.' }}
                                </p>
                            @endif
                            {{-- Votre ordre : chaque élément → la position que vous lui avez donnée. --}}
                            <p class="mb-1 text-muted" style="font-size: 0.85rem;">Votre ordre :</p>
                            <ul class="mb-1 text-muted" style="font-size: 0.85rem; padding-left: 1.1rem;">
                                @foreach(($orderElements ?? []) as $j => $el)
                                    @php $chosen = $givenArr[$j] ?? null; @endphp
                                    <li>{{ $el }} <span aria-hidden="true">→</span> position {{ is_int($chosen) && $chosen >= 0 ? $chosen + 1 : '–' }}</li>
                                @endforeach
                            </ul>
                            @if($reviewOpts['show_right_answer'])
                                @unless($isCorrect)
                                    <p class="mb-1 text-muted" style="font-size: 0.85rem;">Ordre correct :</p>
                                    <ol class="mb-1 text-muted" style="font-size: 0.85rem; padding-left: 1.3rem;">
                                        @foreach(($correctSequence ?? []) as $el)
                                            <li>{{ $el }}</li>
                                        @endforeach
                                    </ol>
                                @endunless
                            @endif
                        @elseif($qType === 'cloze')
                            {{-- C3 : révision par trou DRY (différé respecte review_options). --}}
                            @include('academy::livewire.partials.cloze-review', [
                                'blanks'      => is_array($q['blanks'] ?? null) ? $q['blanks'] : [],
                                'userAns'     => $userAns,
                                'showRight'   => $reviewOpts['show_right_answer'],
                                'showSummary' => $reviewOpts['show_correctness'],
                                'isCorrect'   => $isCorrect,
                            ])
                        @elseif($qType === 'glisser-texte')
                            {{-- Révision par trou DRY (différé respecte review_options). --}}
                            @include('academy::livewire.partials.ddwtos-review', [
                                'answers'     => is_array($q['answers'] ?? null) ? $q['answers'] : [],
                                'options'     => is_array($q['options'] ?? null) ? $q['options'] : [],
                                'userAns'     => $userAns,
                                'showRight'   => $reviewOpts['show_right_answer'],
                                'showSummary' => $reviewOpts['show_correctness'],
                                'isCorrect'   => $isCorrect,
                            ])
                        @else
                            <p class="mb-1 text-muted" style="font-size: 0.85rem;">
                                Votre réponse : <strong>{{ $givenLabel }}</strong>
                            </p>
                            @if($reviewOpts['show_right_answer'])
                                @unless($isCorrect)
                                    <p class="mb-1 text-muted" style="font-size: 0.85rem;">
                                        Bonne réponse : <strong>{{ $expectedLabel }}</strong>
                                    </p>
                                @endunless
                            @endif
                        @endif

                        {{-- Couche 1 : rétroaction spécifique du choix sélectionné. --}}
                        @if($reviewOpts['show_specific_feedback'] && $choiceFb !== null)
                            <div class="mt-2 p-2 rounded" style="background: #F8FAFC; border: 1px dashed #CBD5E1; font-size: 0.85rem;">
                                <span style="font-weight: 600;">À propos de votre choix :</span>
                                {!! \Modules\Academy\Models\LessonItem::renderRichText($choiceFb) !!}
                            </div>
                        @endif

                        {{-- Couche 2 : rétroaction générale de la question. --}}
                        @if($reviewOpts['show_general_feedback'] && $generalFb !== null)
                            <div class="mt-2 p-2 rounded" style="background: #F0F9FF; border: 1px solid #BAE6FD; font-size: 0.85rem;">
                                <span style="font-weight: 600;">Explication :</span>
                                {!! \Modules\Academy\Models\LessonItem::renderRichText($generalFb) !!}
                            </div>
                        @endif
                    </div>
                @endforeach
            </section>
        @endif
        @endif {{-- /needsGradingResult --}}
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
            // V1-f / ADAPTATIF : comportement de rétroaction. Différé = défaut (1 soumission).
            // Immédiat ET adaptatif = rétroaction PAR QUESTION (même rendu « Vérifier ») ;
            // l'adaptatif ajoute le RÉESSAI PÉNALISÉ (la question n'est pas verrouillée
            // tant qu'elle n'est pas correcte ou que le max d'essais n'est pas atteint).
            $behaviour     = \Modules\Academy\Services\QuizBehaviour::for($item->payload);
            $isImmediate   = ($behaviour === \Modules\Academy\Services\QuizBehaviour::IMMEDIATE);
            $isAdaptive    = ($behaviour === \Modules\Academy\Services\QuizBehaviour::ADAPTIVE);
            $isPerQuestion = $isImmediate || $isAdaptive;
            $validatedAll  = ($isPerQuestion && is_array($quizData['validated'] ?? null)) ? $quizData['validated'] : [];
            // Réglages adaptatifs pour l'affichage (% pénalité + nb d'essais).
            $adaptivePenaltyPct = $isAdaptive
                ? (int) round(\Modules\Academy\Services\QuizBehaviour::penaltyFor($item->payload) * 100)
                : 0;
            $adaptiveMaxTries = $isAdaptive
                ? \Modules\Academy\Services\QuizBehaviour::maxTriesFor($item->payload)
                : 0;
            // V1-d : limite de temps (minutes) + horodatage de début posé SERVEUR.
            // Le compte à rebours est dérivé d'un instant de fin calculé serveur
            // (started_at + limite) ; le client n'a qu'à afficher/auto-soumettre.
            // La garde réelle (anti-triche) est appliquée serveur dans submitQuiz.
            $timeLimitMinutes = isset($item->payload['time_limit_minutes'])
                ? (int) $item->payload['time_limit_minutes']
                : null;
            $deadlineTs = null;
            if ($timeLimitMinutes !== null && $timeLimitMinutes > 0 && ! empty($quizData['started_at'])) {
                try {
                    $deadlineTs = \Illuminate\Support\Carbon::parse($quizData['started_at'])
                        ->addMinutes($timeLimitMinutes)
                        ->timestamp;
                } catch (\Throwable) {
                    $deadlineTs = null;
                }
            }
        @endphp

        {{-- ════════════ V1-f : MODE DIFFÉRÉ (défaut) – un seul formulaire ════════════
             Comportement HISTORIQUE strictement inchangé : toutes les questions, une
             seule soumission, révision affichée à la fin. Le mode immédiat (par
             question) est rendu dans la branche @else ci-dessous. --}}
        @if(! $isPerQuestion)
        <form method="POST"
              action="{{ route('academy.quiz.submit', [$course, $lesson, $item->id]) }}"
              id="academy-quiz-form-{{ $item->id }}">
            @csrf

            {{-- V1-d : compte à rebours (limite de temps). Échéance serveur ;
                 à 0 → soumission automatique du formulaire. role=timer + texte
                 (pas seulement la couleur) pour l'accessibilité. --}}
            @if($deadlineTs !== null)
                <div x-data="{
                        deadline: {{ $deadlineTs }} * 1000,
                        remaining: 0,
                        submitted: false,
                        tick() {
                            this.remaining = Math.max(0, Math.round((this.deadline - Date.now()) / 1000));
                            if (this.remaining <= 0 && ! this.submitted) {
                                this.submitted = true;
                                document.getElementById('academy-quiz-form-{{ $item->id }}').requestSubmit();
                            }
                        },
                        get label() {
                            const m = Math.floor(this.remaining / 60);
                            const s = this.remaining % 60;
                            return m + ' min ' + (s < 10 ? '0' : '') + s + ' s';
                        }
                     }"
                     x-init="tick(); setInterval(() => tick(), 1000)"
                     role="timer"
                     aria-live="polite"
                     class="mb-3 p-2 rounded d-inline-block"
                     :style="remaining <= 30
                        ? 'background:#FEE2E2;border:1px solid #FCA5A5;color:#991B1B;font-weight:600;'
                        : 'background:#ECFEFF;border:1px solid #A5F3FC;color:#155E63;font-weight:600;'"
                     style="font-size:0.9rem;">
                    Temps restant : <span x-text="label">{{ $timeLimitMinutes }} min 00 s</span>
                </div>
            @endif

            @foreach($questions as $i => $question)
                @php
                    $type     = $question['type']     ?? 'qcm';
                    $choices  = $question['choices']   ?? [];
                    $terms    = $question['terms']     ?? [];
                    $defs     = $question['defs']      ?? [];
                    $elements = $question['elements']  ?? [];
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
                        @php
                            // V1-e : QCM à réponses multiples → cases à cocher (le scoring
                            // relit answers[i][] en TABLEAU). QCM simple / vraifaux = radio.
                            $isMulti = ($type === 'qcm') && ! empty($question['multiple']);
                        @endphp
                        {{-- M4 (WCAG 1.3.1) : groupe radio/checkbox enrobé dans un
                             <fieldset> (sans bordure) + <legend> reprenant l'intitulé
                             (visually-hidden : l'énoncé visible reste le <p> ci-dessus,
                             la mise en page est inchangée). --}}
                        <fieldset style="border:0;padding:0;margin:0;">
                            <legend class="visually-hidden">{{ $question['question'] ?? 'Question' }}</legend>
                        @if($isMulti)
                            <p class="text-muted mb-2" style="font-size: 0.85rem;">Plusieurs réponses possibles.</p>
                        @endif
                        @foreach($choices as $j => $choice)
                            <div class="form-check">
                                @if($isMulti)
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="answers[{{ $i }}][]"
                                           id="q{{ $item->id }}_{{ $i }}_{{ $j }}"
                                           value="{{ $j }}">
                                @else
                                    <input class="form-check-input"
                                           type="radio"
                                           name="answers[{{ $i }}]"
                                           id="q{{ $item->id }}_{{ $i }}_{{ $j }}"
                                           value="{{ $j }}"
                                           required>
                                @endif
                                <label class="form-check-label"
                                       for="q{{ $item->id }}_{{ $i }}_{{ $j }}">
                                    {{ $choice }}
                                </label>
                            </div>
                        @endforeach
                        </fieldset>

                    {{-- Réponse courte --}}
                    @elseif($type === 'court')
                        <input type="text"
                               name="answers[{{ $i }}]"
                               class="form-control mt-2"
                               placeholder="Votre réponse…"
                               autocomplete="off"
                               required
                               style="max-width: 320px;">

                    {{-- Réponse numérique (valeur + unité indicative ; bonnes réponses serveur) --}}
                    @elseif($type === 'numerique')
                        {{-- C3 : rendu DRY (partial) – name = answers[i], id dédié. --}}
                        @include('academy::livewire.partials.numerical-input', [
                            'nameAttr' => 'answers['.$i.']',
                            'inputId'  => 'q'.$item->id.'_'.$i.'_num',
                            'unit'     => $question['unit'] ?? '',
                        ])

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
                                        <option value="">Choisir une définition…</option>
                                        @foreach($defs as $k => $def)
                                            <option value="{{ $k }}">{{ $def }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>

                    {{-- Ordonnancement (alternative clavier WCAG : un select de position
                         par élément, comme l'appariement). « Choisissez la position de
                         chaque élément ». Les bonnes réponses restent serveur. --}}
                    @elseif($type === 'ordonnancement')
                        <p class="text-muted mb-2" style="font-size: 0.85rem;">Choisissez la position de chaque élément (1 = premier).</p>
                        <div class="mt-2">
                            @foreach($elements as $j => $element)
                                @php $selectId = "q{$item->id}_{$i}_order_{$j}"; @endphp
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <label for="{{ $selectId }}" style="min-width: 220px; font-size: 0.9rem; font-weight: 500; margin-bottom: 0;">
                                        {{ $element }}
                                    </label>
                                    <select id="{{ $selectId }}"
                                            name="answers[{{ $i }}][]"
                                            class="form-select form-select-sm"
                                            style="max-width: 160px;"
                                            aria-label="Position de : {{ $element }}"
                                            required>
                                        <option value="">Position…</option>
                                        @foreach($elements as $p => $ignored)
                                            <option value="{{ $p }}">{{ $p + 1 }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>

                    {{-- Cloze / texte à trous : chaque trou est rendu INLINE dans le texte
                         (input pour short, select pour mcq), relié à answers[i][indexTrou].
                         Les bonnes réponses ne sont jamais exposées (segments seuls). --}}
                    @elseif($type === 'cloze')
                        {{-- C3 : rendu DRY (fieldset/legend intégré) ; name = answers[i][k]. --}}
                        @include('academy::livewire.partials.cloze-inputs', [
                            'segments'   => $question['segments'] ?? [],
                            'namePrefix' => 'answers['.$i.']',
                            'legend'     => $question['question'] ?? 'Texte à trous',
                        ])

                    {{-- Glisser-déposer sur texte : chaque trou est un <select> du pool
                         de mots mélangé (a11y-first) ; les bonnes réponses restent serveur. --}}
                    @elseif($type === 'glisser-texte')
                        @include('academy::livewire.partials.ddwtos-inputs', [
                            'segments'   => $question['segments'] ?? [],
                            'options'    => $question['options'] ?? [],
                            'namePrefix' => 'answers['.$i.']',
                            'legend'     => $question['question'] ?? 'Glisser-déposer sur texte',
                        ])

                    {{-- ESSAI : réponse rédigée libre, corrigée manuellement (pas de
                         « bonne réponse », pas de validation auto). --}}
                    @elseif($type === 'essai')
                        <p class="text-muted mb-2" style="font-size: 0.85rem;">
                            Réponse rédigée : elle sera corrigée par le formateur.
                        </p>
                        <textarea name="answers[{{ $i }}]"
                                  class="form-control mt-1"
                                  rows="6"
                                  maxlength="50000"
                                  aria-label="{{ $question['question'] ?? 'Réponse rédigée' }}"
                                  placeholder="Rédigez votre réponse…"></textarea>

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
        {{-- ════════════ V1-f : MODE IMMÉDIAT – validation par question ════════════
             Chaque question non encore validée porte SON PROPRE formulaire « Vérifier »
             (POST quiz.verify) : le scoring se fait SERVEUR puis la question est
             verrouillée (relue depuis la session). Les questions déjà validées sont
             affichées en lecture seule avec leur rétroaction. Aucune bonne réponse
             n'est exposée avant validation (le round + les corrigés restent serveur). --}}

            {{-- C1 (audit F6) : options de révision du formateur, normalisées (défaut tout
                 vrai = rétrocompat). En mode VERROUILLÉ (immédiat/adaptatif), l'exposition
                 des bonnes réponses et du ✔/✗ suit EXACTEMENT les mêmes toggles qu'en
                 différé (parité Moodle ; pas de fuite si « afficher la bonne réponse » est
                 désactivé). Le feedback spécifique/général garde ses propres règles. --}}
            @php
                $reviewOpts = \Modules\Academy\Services\QuizReviewOptions::normalize(
                    $item->payload['review_options'] ?? null
                );
            @endphp

            {{-- C2 (WCAG 2.4.7) : focus visible pour l'alerte de réessai (focus
                 programmatique). On garde un outline net plutôt qu'outline:none. --}}
            <style>
                .academy-quiz-retry-alert:focus,
                .academy-quiz-retry-alert:focus-visible {
                    outline: 2px solid #991B1B;
                    outline-offset: 2px;
                }
            </style>

            {{-- Compte à rebours informatif (la garde réelle reste serveur au « Terminer »). --}}
            @if($deadlineTs !== null)
                <div x-data="{
                        deadline: {{ $deadlineTs }} * 1000,
                        remaining: 0,
                        tick() { this.remaining = Math.max(0, Math.round((this.deadline - Date.now()) / 1000)); },
                        get label() {
                            const m = Math.floor(this.remaining / 60);
                            const s = this.remaining % 60;
                            return m + ' min ' + (s < 10 ? '0' : '') + s + ' s';
                        }
                     }"
                     x-init="tick(); setInterval(() => tick(), 1000)"
                     role="timer"
                     aria-live="polite"
                     class="mb-3 p-2 rounded d-inline-block"
                     :style="remaining <= 30
                        ? 'background:#FEE2E2;border:1px solid #FCA5A5;color:#991B1B;font-weight:600;'
                        : 'background:#ECFEFF;border:1px solid #A5F3FC;color:#155E63;font-weight:600;'"
                     style="font-size:0.9rem;">
                    Temps restant : <span x-text="label">{{ $timeLimitMinutes }} min 00 s</span>
                </div>
            @endif

            @foreach($questions as $i => $question)
                @php
                    $type     = $question['type']     ?? 'qcm';
                    $choices  = $question['choices']   ?? [];
                    $terms    = $question['terms']     ?? [];
                    $defs     = $question['defs']      ?? [];
                    $elements = $question['elements']  ?? [];

                    $v         = $validatedAll[$i] ?? ($validatedAll[(string) $i] ?? null);
                    $hasEntry  = is_array($v);
                    // VERROUILLAGE : en immédiat, toute entrée est verrouillée. En adaptatif,
                    // seules les entrées marquées `locked` le sont (correcte OU max d'essais).
                    $locked    = $hasEntry && ($isAdaptive ? (bool) ($v['locked'] ?? false) : true);
                    // RÉESSAI adaptatif : une entrée existe (échec) mais n'est pas verrouillée.
                    $showRetry = $isAdaptive && $hasEntry && ! $locked;
                    $isCorrect = $locked ? (bool) ($v['correct'] ?? false) : false;
                    $triesUsed = $hasEntry ? (int) ($v['tries'] ?? 0) : 0;
                    $triesLeft = $isAdaptive ? max(0, $adaptiveMaxTries - $triesUsed) : 0;
                    $isMulti   = ($type === 'qcm') && ! empty($question['multiple']);
                @endphp

                <div class="mb-4 p-3 rounded" style="background: #F8FAFC; border: 1px solid #E2E8F0;
                     border-left: 4px solid {{ $locked ? ($reviewOpts['show_correctness'] ? ($isCorrect ? '#16A34A' : '#DC2626') : '#94A3B8') : '#CBD5E1' }};">
                    <p class="mb-2" style="font-weight: 600; font-size: 0.95rem; color: #1A1D23;">
                        <span class="badge me-2" style="background: var(--c-primary, #064E5A); color: #fff;">{{ $i + 1 }}</span>
                        {{ $question['question'] ?? '' }}
                    </p>

                    @if($type === 'essai')
                        {{-- ESSAI : aucune validation par question (« Vérifier » n'a pas de
                             sens). La réponse est saisie ici et rattachée au formulaire
                             « Terminer » via l'attribut form= (HTML5) ; elle est soumise
                             au « Terminer le quiz ». À rédiger en dernier (les autres
                             validations rechargent la page). --}}
                        <p class="text-muted mb-2" style="font-size: 0.85rem;">
                            Réponse rédigée : corrigée par le formateur. Rédigez-la avant de terminer le quiz.
                        </p>
                        <textarea name="answers[{{ $i }}]"
                                  form="academy-quiz-finish-{{ $item->id }}"
                                  class="form-control mt-1"
                                  rows="6"
                                  maxlength="50000"
                                  aria-label="{{ $question['question'] ?? 'Réponse rédigée' }}"
                                  placeholder="Rédigez votre réponse…"></textarea>

                    @elseif(! $locked)
                        {{-- ADAPTATIF, RÉESSAI : la question a déjà reçu un essai RATÉ mais
                             n'est pas verrouillée. On annonce l'échec (sans révéler la bonne
                             réponse, pour permettre un vrai réessai) + les essais restants +
                             la pénalité. aria-live=assertive + focus (a11y) pour annoncer le
                             changement après le rechargement de page. --}}
                        @if($showRetry)
                            {{-- C2 (WCAG 2.4.7) : l'alerte reçoit un focus programmatique ; le
                                 focus DOIT rester visible (classe academy-quiz-retry-alert
                                 fournit un outline visible, voir le bloc de styles plus haut). --}}
                            <div role="alert" aria-live="assertive" tabindex="-1"
                                 x-data x-init="$el.focus()"
                                 class="mb-2 p-2 rounded academy-quiz-retry-alert"
                                 style="background: #FEF2F2; border: 1px solid #FCA5A5;">
                                <p class="mb-1" style="font-size: 0.88rem; font-weight: 600; color: #991B1B;">
                                    ✗ Réponse incorrecte. Réessayez.
                                </p>
                                <p class="mb-0 text-muted" style="font-size: 0.82rem;">
                                    @if($triesLeft > 0)
                                        Il vous reste {{ $triesLeft }} essai{{ $triesLeft > 1 ? 's' : '' }}.
                                    @endif
                                    Chaque essai raté retranche {{ $adaptivePenaltyPct }} % des points de cette question.
                                </p>
                            </div>
                        @endif
                        {{-- Formulaire de validation de CETTE question (scoring serveur). --}}
                        <form method="POST" action="{{ route('academy.quiz.verify', [$course, $lesson, $item->id]) }}">
                            @csrf
                            <input type="hidden" name="index" value="{{ $i }}">

                            @if($type === 'qcm' || $type === 'vraifaux')
                                {{-- M4 (WCAG 1.3.1) : groupe radio/checkbox enrobé dans un
                                     <fieldset> (sans bordure) + <legend> (visually-hidden,
                                     mise en page inchangée). --}}
                                <fieldset style="border:0;padding:0;margin:0;">
                                    <legend class="visually-hidden">{{ $question['question'] ?? 'Question' }}</legend>
                                @if($isMulti)
                                    <p class="text-muted mb-2" style="font-size: 0.85rem;">Plusieurs réponses possibles.</p>
                                @endif
                                @foreach($choices as $j => $choice)
                                    <div class="form-check">
                                        <input class="form-check-input"
                                               type="{{ $isMulti ? 'checkbox' : 'radio' }}"
                                               name="answer{{ $isMulti ? '[]' : '' }}"
                                               id="iq{{ $item->id }}_{{ $i }}_{{ $j }}"
                                               value="{{ $j }}"
                                               @if(! $isMulti) required @endif>
                                        <label class="form-check-label" for="iq{{ $item->id }}_{{ $i }}_{{ $j }}">
                                            {{ $choice }}
                                        </label>
                                    </div>
                                @endforeach
                                </fieldset>

                            @elseif($type === 'court')
                                <input type="text" name="answer" class="form-control mt-2"
                                       placeholder="Votre réponse…" autocomplete="off" required
                                       style="max-width: 320px;">

                            @elseif($type === 'numerique')
                                {{-- C3 : MÊME partial qu'en différé – name = answer, id ajouté (cohérence). --}}
                                @include('academy::livewire.partials.numerical-input', [
                                    'nameAttr' => 'answer',
                                    'inputId'  => 'iq'.$item->id.'_'.$i.'_num',
                                    'unit'     => $question['unit'] ?? '',
                                ])

                            @elseif($type === 'appariement')
                                <div class="mt-2">
                                    @foreach($terms as $j => $term)
                                        @php $selectId = "iq{$item->id}_{$i}_match_{$j}"; @endphp
                                        <div class="d-flex align-items-center gap-3 mb-2">
                                            <label for="{{ $selectId }}" style="min-width: 160px; font-size: 0.9rem; font-weight: 500; margin-bottom: 0;">
                                                {{ $term }}
                                            </label>
                                            <select id="{{ $selectId }}" name="answer[]"
                                                    class="form-select form-select-sm" style="max-width: 320px;"
                                                    aria-label="Définition pour : {{ $term }}" required>
                                                <option value="">Choisir une définition…</option>
                                                @foreach($defs as $k => $def)
                                                    <option value="{{ $k }}">{{ $def }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif($type === 'ordonnancement')
                                <p class="text-muted mb-2" style="font-size: 0.85rem;">Choisissez la position de chaque élément (1 = premier).</p>
                                <div class="mt-2">
                                    @foreach($elements as $j => $element)
                                        @php $selectId = "iq{$item->id}_{$i}_order_{$j}"; @endphp
                                        <div class="d-flex align-items-center gap-3 mb-2">
                                            <label for="{{ $selectId }}" style="min-width: 220px; font-size: 0.9rem; font-weight: 500; margin-bottom: 0;">
                                                {{ $element }}
                                            </label>
                                            <select id="{{ $selectId }}" name="answer[]"
                                                    class="form-select form-select-sm" style="max-width: 160px;"
                                                    aria-label="Position de : {{ $element }}" required>
                                                <option value="">Position…</option>
                                                @foreach($elements as $p => $ignored)
                                                    <option value="{{ $p }}">{{ $p + 1 }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif($type === 'cloze')
                                {{-- C2 + C3 : même rendu DRY, fieldset/legend AUSSI en immédiat ; name = answer[k]. --}}
                                @include('academy::livewire.partials.cloze-inputs', [
                                    'segments'   => $question['segments'] ?? [],
                                    'namePrefix' => 'answer',
                                    'legend'     => $question['question'] ?? 'Texte à trous',
                                ])
                            @elseif($type === 'glisser-texte')
                                @include('academy::livewire.partials.ddwtos-inputs', [
                                    'segments'   => $question['segments'] ?? [],
                                    'options'    => $question['options'] ?? [],
                                    'namePrefix' => 'answer',
                                    'legend'     => $question['question'] ?? 'Glisser-déposer sur texte',
                                ])
                            @else
                                <p class="text-muted small">Type de question non pris en charge.</p>
                            @endif

                            @if(! empty($question['fiche']))
                                <a href="{{ $question['fiche'] }}" target="_blank" rel="noopener"
                                   class="small text-muted d-inline-block mt-1">📖 Fiche de référence →</a>
                            @endif

                            <div class="mt-2">
                                <button type="submit" class="btn ct-btn ct-btn-primary btn-sm">{{ $showRetry ? 'Réessayer (pénalité appliquée)' : 'Vérifier' }}</button>
                            </div>
                        </form>
                    @else
                        {{-- ── Question VERROUILLÉE : rétroaction immédiate (relue serveur) ── --}}
                        @php
                            $userAns = $v['answer'] ?? null;
                            $givenLabel = 'Aucune réponse';
                            $expectedLabel = '';
                            $choiceFb = null;
                            $generalFb = $question['general_feedback'] ?? ($question['explanation'] ?? null);
                            $generalFb = (is_string($generalFb) && trim($generalFb) !== '') ? $generalFb : null;

                            if ($isMulti) {
                                $correctSet = array_values(array_unique(array_map('intval', (array) ($question['correct'] ?? []))));
                                sort($correctSet);
                                $givenSet = is_array($userAns)
                                    ? array_values(array_unique(array_map('intval', $userAns)))
                                    : (is_numeric($userAns) ? [(int) $userAns] : []);
                                $expectedLabel = implode(', ', array_map(fn ($k) => $choices[$k] ?? '', $correctSet)) ?: '–';
                                $givenLabels = array_filter(array_map(fn ($k) => $choices[$k] ?? null, $givenSet), fn ($x) => $x !== null);
                                $givenLabel = $givenLabels !== [] ? implode(', ', $givenLabels) : 'Aucune réponse';
                            } elseif ($type === 'qcm' || $type === 'vraifaux') {
                                $correctIdx = (int) ($question['correct'] ?? -1);
                                $givenIdx   = is_numeric($userAns) ? (int) $userAns : -1;
                                $expectedLabel = $choices[$correctIdx] ?? '';
                                $givenLabel = ($givenIdx >= 0 && isset($choices[$givenIdx])) ? $choices[$givenIdx] : 'Aucune réponse';
                                if (isset($question['choice_feedback']) && is_array($question['choice_feedback'])) {
                                    $fb = $question['choice_feedback'][$givenIdx] ?? ($question['choice_feedback'][(string) $givenIdx] ?? null);
                                    if (is_string($fb) && trim($fb) !== '') {
                                        $choiceFb = $fb;
                                    }
                                }
                            } elseif ($type === 'court') {
                                $expectedLabel = implode(', ', (array) ($question['accepted'] ?? []));
                                $givenLabel = (is_string($userAns) && trim($userAns) !== '') ? trim($userAns) : 'Aucune réponse';
                            } elseif ($type === 'numerique') {
                                $expectedNum = isset($question['correct']) && is_numeric($question['correct']) ? (float) $question['correct'] : null;
                                $tolNum      = isset($question['tolerance']) && is_numeric($question['tolerance']) ? abs((float) $question['tolerance']) : 0.0;
                                $unitNum     = isset($question['unit']) && is_string($question['unit']) ? trim($question['unit']) : '';
                                // C3 : formatage centralisé (DRY) au lieu d'une closure dupliquée.
                                $fmtNum      = fn (float $v): string => \Modules\Academy\Services\QuizService::formatNumber($v);
                                $expectedLabel = $expectedNum !== null
                                    ? $fmtNum($expectedNum).($tolNum > 0 ? ' (± '.$fmtNum($tolNum).')' : '').($unitNum !== '' ? ' '.$unitNum : '')
                                    : '';
                                $givenLabel = (is_string($userAns) && trim($userAns) !== '')
                                    ? trim($userAns)
                                    : (is_numeric($userAns) ? (string) $userAns : 'Aucune réponse');
                            }
                        @endphp

                        @if($reviewOpts['show_correctness'])
                            <p class="mb-1" style="font-size: 0.88rem; font-weight: 600; color: {{ $isCorrect ? '#166534' : '#991B1B' }};">
                                {{ $isCorrect ? '✔ Bonne réponse' : '✗ À revoir' }}
                            </p>
                        @endif

                        {{-- ADAPTATIF : rappel du nombre d'essais utilisés + pénalité appliquée
                             (la question est désormais verrouillée : réussie ou max d'essais). --}}
                        @if($isAdaptive && $triesUsed > 0)
                            <p class="mb-1 text-muted" style="font-size: 0.8rem;">
                                {{ $triesUsed }} essai{{ $triesUsed > 1 ? 's' : '' }} raté{{ $triesUsed > 1 ? 's' : '' }} : pénalité de {{ $adaptivePenaltyPct }} % par essai appliquée.
                            </p>
                        @endif

                        @if($type === 'appariement')
                            {{-- C1 : le verdict d'association suit show_correctness (comme en différé). --}}
                            @if($reviewOpts['show_correctness'])
                                <p class="mb-1 text-muted" style="font-size: 0.85rem;">
                                    {{ $isCorrect ? 'Toutes les associations sont correctes.' : 'Certaines associations sont à revoir.' }}
                                </p>
                            @endif
                        @elseif($type === 'ordonnancement')
                            {{-- C1 : le verdict d'ordre suit show_correctness (comme en différé). --}}
                            @if($reviewOpts['show_correctness'])
                                <p class="mb-1 text-muted" style="font-size: 0.85rem;">
                                    {{ $isCorrect ? 'L\'ordre est exact.' : 'L\'ordre est à revoir.' }}
                                </p>
                            @endif
                        @elseif($type === 'cloze')
                            {{-- C1/C3 : révision par trou DRY ; respecte review_options (parité différé). --}}
                            @include('academy::livewire.partials.cloze-review', [
                                'blanks'      => is_array($question['blanks'] ?? null) ? $question['blanks'] : [],
                                'userAns'     => $userAns,
                                'showRight'   => $reviewOpts['show_right_answer'],
                                'showSummary' => $reviewOpts['show_correctness'],
                                'isCorrect'   => $isCorrect,
                            ])
                        @elseif($type === 'glisser-texte')
                            {{-- C1 : révision par trou DRY ; respecte review_options (parité différé). --}}
                            @include('academy::livewire.partials.ddwtos-review', [
                                'answers'     => is_array($question['answers'] ?? null) ? $question['answers'] : [],
                                'options'     => is_array($question['options'] ?? null) ? $question['options'] : [],
                                'userAns'     => $userAns,
                                'showRight'   => $reviewOpts['show_right_answer'],
                                'showSummary' => $reviewOpts['show_correctness'],
                                'isCorrect'   => $isCorrect,
                            ])
                        @else
                            <p class="mb-1 text-muted" style="font-size: 0.85rem;">
                                Votre réponse : <strong>{{ $givenLabel }}</strong>
                            </p>
                            {{-- C1 : la bonne réponse n'est exposée que si le formateur l'autorise. --}}
                            @if($reviewOpts['show_right_answer'])
                                @unless($isCorrect)
                                    <p class="mb-1 text-muted" style="font-size: 0.85rem;">
                                        Bonne réponse : <strong>{{ $expectedLabel }}</strong>
                                    </p>
                                @endunless
                            @endif
                        @endif

                        @if($choiceFb !== null)
                            <div class="mt-2 p-2 rounded" style="background: #F8FAFC; border: 1px dashed #CBD5E1; font-size: 0.85rem;">
                                <span style="font-weight: 600;">À propos de votre choix :</span>
                                {!! \Modules\Academy\Models\LessonItem::renderRichText($choiceFb) !!}
                            </div>
                        @endif

                        @if($generalFb !== null)
                            <div class="mt-2 p-2 rounded" style="background: #F0F9FF; border: 1px solid #BAE6FD; font-size: 0.85rem;">
                                <span style="font-weight: 600;">Explication :</span>
                                {!! \Modules\Academy\Models\LessonItem::renderRichText($generalFb) !!}
                            </div>
                        @endif
                    @endif
                </div>
            @endforeach

            {{-- Terminer : disponible quand toutes les questions AUTO-NOTÉES sont validées
                 (les essais n'ont pas de validation par question). Le « Terminer » porte
                 les réponses d'ESSAI (rattachées via form=) ; les autres réponses restent
                 verrouillées serveur. --}}
            @php
                $essayCount = 0;
                foreach ($questions as $qq) {
                    if (($qq['type'] ?? null) === 'essai') { $essayCount++; }
                }
                $requiredValidations = count($questions) - $essayCount;
                // ADAPTATIF : ne comptent que les questions VERROUILLÉES (en immédiat, toute
                // entrée l'est ; en adaptatif, une question « en réessai » ne compte pas).
                $lockedCount = 0;
                foreach ($validatedAll as $entry) {
                    if (! is_array($entry)) { continue; }
                    if ($isAdaptive ? ($entry['locked'] ?? false) : true) { $lockedCount++; }
                }
                $allValidated = count($questions) > 0 && $lockedCount >= $requiredValidations;
            @endphp
            @if($allValidated)
                <form method="POST" action="{{ route('academy.quiz.submit', [$course, $lesson, $item->id]) }}"
                      id="academy-quiz-finish-{{ $item->id }}">
                    @csrf
                    <button type="submit" class="btn ct-btn ct-btn-primary" style="margin-top: 0.5rem;">
                        Terminer le quiz
                    </button>
                </form>
            @else
                <p class="text-muted" style="font-size: 0.9rem;">
                    Validez chaque question pour terminer le quiz.
                </p>
            @endif
        @endif

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
