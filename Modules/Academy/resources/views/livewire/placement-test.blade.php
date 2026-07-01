{{--
    @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
    @project memora/laravel-saas-boilerplate

    Vue Livewire PlacementTest - test de positionnement adaptatif (CAT), une
    question à la fois (esprit DeckPlayer/SrsReviewer). Charte : teal #064E5A.
    Contrastes WCAG AAA (paires solid-fill + texte blanc reprises telles
    quelles des boutons d'auto-évaluation SrsReviewer, déjà validées AAA).
    Aucune popup native ; tout est piloté par wire:click serveur.
--}}
<div style="max-width:720px;margin:0 auto;padding:24px 16px;">

    {{-- État A : banque de questions insuffisante pour ce cours --}}
    @if($unavailable)
        <div style="text-align:center;padding:32px 16px;background:#ffffff;border:1px solid #cbd5e1;border-radius:16px;box-shadow:0 4px 16px rgba(6,78,90,.10);">
            <p style="color:#334155;margin:0 0 16px;">Ce test n'est pas encore disponible pour ce cours (banque de questions insuffisante). Vous pouvez commencer le cours directement.</p>
            <a href="{{ route('academy.courses.show', $course->slug) }}"
               style="display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:10px 20px;background:#064E5A;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:600;">
                Accéder au cours
            </a>
        </div>

    {{-- État B : pas encore commencé --}}
    @elseif(!$started)
        <div style="background:#ffffff;border:2px solid #064E5A;border-radius:16px;box-shadow:0 4px 16px rgba(6,78,90,.10);padding:32px 28px;text-align:center;">
            <h2 style="font-size:22px;font-weight:700;color:#0f172a;margin:0 0 16px;">🎯 Test de positionnement (2 minutes)</h2>
            <p style="color:#334155;line-height:1.6;margin:0 0 24px;">
                Ce test s'adapte à vos réponses : moins de questions qu'un quiz classique, mais une évaluation précise.
                À la fin, nous vous indiquerons exactement par quelle leçon commencer.
            </p>
            <button type="button"
                    wire:click="startTest"
                    style="width:100%;background:#064E5A;color:#ffffff;border:none;padding:14px 24px;border-radius:12px;font-size:16px;font-weight:600;cursor:pointer;min-height:48px;">
                Commencer le test
            </button>
        </div>

    {{-- État C : en cours --}}
    @elseif($started && !$finished)
        @php
            $difficulty = $this->currentQuestionView['difficulty'] ?? 'moyen';
            // Paires solid-fill + texte blanc reprises des boutons SrsReviewer (AAA validées).
            $badgeStyles = [
                'facile'    => 'background:#065f46;color:#ffffff;',
                'moyen'     => 'background:#064E5A;color:#ffffff;',
                'difficile' => 'background:#92400e;color:#ffffff;',
            ];
        @endphp
        <div style="margin-bottom:24px;">
            <p aria-live="polite" style="font-size:14px;color:#334155;margin:0 0 12px;display:flex;align-items:center;gap:8px;font-weight:600;">
                Question {{ $questionIndex }} / {{ $this->maxQuestions() }}
                <span style="font-size:12px;font-weight:700;padding:4px 10px;border-radius:999px;{{ $badgeStyles[$difficulty] ?? $badgeStyles['moyen'] }}">
                    {{ ucfirst($difficulty) }}
                </span>
            </p>

            <div style="border:2px solid #064E5A;border-radius:16px;background:#ffffff;box-shadow:0 4px 16px rgba(6,78,90,.10);overflow:hidden;">
                <div style="padding:32px 28px;min-height:140px;display:flex;flex-direction:column;justify-content:center;">
                    <div style="font-size:20px;line-height:1.5;color:#0f172a;font-weight:600;">
                        {{ $this->currentQuestionView['question'] ?? '' }}
                    </div>
                </div>
            </div>
        </div>

        @if($lastAnswerCorrect === null)
            {{-- Choix de réponse (soumission immédiate au clic) --}}
            <div role="group" aria-label="Choix de réponse" style="display:grid;gap:10px;">
                @foreach(($this->currentQuestionView['choices'] ?? []) as $i => $choice)
                    <button type="button"
                            wire:click="submitAnswer({{ $i }})"
                            style="background:#ffffff;border:2px solid #064E5A;color:#0f172a;padding:12px 16px;text-align:left;border-radius:12px;font-size:15px;font-weight:500;min-height:48px;cursor:pointer;">
                        {{ $choice }}
                    </button>
                @endforeach
            </div>
        @else
            {{-- Feedback après réponse --}}
            <div aria-live="polite" style="margin-top:8px;padding:16px;border-radius:12px;font-weight:600;{{ $lastAnswerCorrect ? 'background:#D1FAE5;color:#065F46;' : 'background:#FEE2E2;color:#7f1d1d;' }}">
                {{ $lastAnswerCorrect ? '✓ Bonne réponse !' : '✗ Pas tout à fait.' }}
            </div>

            @if($lastExplanation)
                <div style="margin-top:12px;padding:12px 16px;background:#f8fafc;border-left:4px solid #cbd5e1;color:#334155;font-size:15px;line-height:1.5;border-radius:0 8px 8px 0;">
                    {{ $lastExplanation }}
                </div>
            @endif

            <button type="button"
                    wire:click="advance"
                    style="width:100%;background:#064E5A;color:#ffffff;border:none;padding:14px 24px;border-radius:12px;font-size:16px;font-weight:600;cursor:pointer;min-height:48px;margin-top:20px;">
                Question suivante ▸
            </button>
        @endif

    {{-- État D : terminé --}}
    @elseif($finished)
        <div style="background:#ffffff;border:2px solid #064E5A;border-radius:16px;box-shadow:0 4px 16px rgba(6,78,90,.10);padding:32px 28px;text-align:center;">
            <p style="font-size:48px;margin:0 0 12px;" aria-hidden="true">🎉</p>
            <h2 style="font-size:22px;font-weight:700;color:#0f172a;margin:0 0 16px;">Test terminé !</h2>
            <p style="font-size:16px;color:#334155;margin:0 0 24px;">
                Niveau estimé : <strong>{{ $estimatedLevel }}</strong>
            </p>

            @if($recommendedLessonTitle)
                <p style="color:#334155;margin:0 0 24px;">Nous vous recommandons de commencer à : <strong>{{ $recommendedLessonTitle }}</strong></p>

                <div style="display:grid;grid-template-columns:{{ $firstLessonId ? '1fr 1fr' : '1fr' }};gap:10px;">
                    <a href="{{ route('academy.lessons.show', ['course' => $course->slug, 'lesson' => $recommendedLessonId]) }}"
                       style="min-height:48px;display:flex;align-items:center;justify-content:center;text-decoration:none;background:#064E5A;color:#ffffff;border-radius:12px;font-weight:600;">
                        Aller à cette leçon
                    </a>

                    @if($firstLessonId)
                        <a href="{{ route('academy.lessons.show', ['course' => $course->slug, 'lesson' => $firstLessonId]) }}"
                           style="min-height:48px;display:flex;align-items:center;justify-content:center;text-decoration:none;background:#ffffff;color:#064E5A;border:2px solid #064E5A;border-radius:12px;font-weight:600;">
                            Commencer au début
                        </a>
                    @endif
                </div>
            @else
                <a href="{{ route('academy.courses.show', $course->slug) }}"
                   style="display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:10px 20px;background:#064E5A;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:600;">
                    Retour au cours
                </a>
            @endif
        </div>
    @endif

</div>
