{{-- Author: MEMORA solutions, https://memora.solutions --}}
{{--
    Partiel : barre de progression du cours.
    Variables :
      $progress     — Progress|null (modèle Progress de l'utilisateur courant)
      $course       — Course
      $resumeLesson — Lesson|null (1re leçon avec un item requis non complété, null si terminé)
      $firstLesson  — Lesson|null (1re leçon du cours, pour CTA "Commencer")
--}}
@if($progress !== null)
    <div class="mb-4 p-3 rounded" style="background: #F0FDF4; border: 1px solid #BBF7D0;">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span style="font-size: 0.85rem; font-weight: 600; color: var(--c-primary, #064E5A);">
                Votre progression
            </span>
            <span style="font-size: 0.85rem; color: #374151;">
                {{ $progress->required_completed }} / {{ $progress->required_total }} leçon{{ $progress->required_total > 1 ? 's' : '' }} requise{{ $progress->required_total > 1 ? 's' : '' }}
            </span>
        </div>

        {{-- Barre Bootstrap 5 --}}
        <div class="progress mb-2" style="height: 10px; border-radius: 6px; background: #D1FAE5;">
            <div class="progress-bar"
                 role="progressbar"
                 style="width: {{ $progress->percent }}%; background: var(--c-primary, #064E5A); border-radius: 6px;"
                 aria-valuenow="{{ $progress->percent }}"
                 aria-valuemin="0"
                 aria-valuemax="100">
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <span style="font-size: 0.8rem; color: #6B7280;">
                {{ $progress->percent }}% complété
            </span>

            @if($resumeLesson !== null)
                <a href="{{ route('academy.lessons.show', [$course, $resumeLesson]) }}"
                   class="btn btn-sm ct-btn ct-btn-primary"
                   style="font-size: 0.8rem; padding: 0.3rem 0.8rem;">
                    Reprendre →
                </a>
            @elseif($progress->percent >= 100)
                <span class="badge"
                      style="background: var(--c-primary, #064E5A); font-size: 0.78rem; padding: 0.35rem 0.65rem;">
                    ✓ Cours terminé !
                </span>
            @endif
        </div>
    </div>

@elseif(isset($firstLesson) && $firstLesson !== null)
    {{-- Pas encore de progression — CTA Commencer --}}
    <div class="mb-4">
        <a href="{{ route('academy.lessons.show', [$course, $firstLesson]) }}"
           class="btn ct-btn ct-btn-primary">
            Commencer le cours →
        </a>
    </div>
@endif
