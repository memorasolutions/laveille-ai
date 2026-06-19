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
            $passed  = (bool) ($quizResult['passed'] ?? false);
            $percent = (int)  ($quizResult['percent'] ?? 0);
            $correct = (int)  ($quizResult['correct'] ?? 0);
            $total   = (int)  ($quizResult['total'] ?? 0);
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
            <p class="mb-3 text-muted" style="font-size: 0.9rem;">
                {{ $correct }} / {{ $total }} bonnes réponses
            </p>
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
            @if($attemptsAllowed !== null)
                <p class="text-muted mb-3" style="font-size: 0.9rem;">
                    Tentatives autorisées : <strong>{{ $attemptsAllowed }}</strong>
                </p>
            @else
                <p class="mb-3"></p>
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
