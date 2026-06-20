<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@php
    $levelLabels = ['intro' => 'Débutant', 'intermediaire' => 'Intermédiaire', 'avance' => 'Avancé'];
    $levelLabel  = $levelLabels[$course->level] ?? ucfirst($course->level);
    $isFreeCard  = $course->access_type === 'free';
@endphp

<div class="card h-100 shadow-sm"
     style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); overflow: hidden;">

    {{-- Image de couverture (média Spatie, conversion « medium ») --}}
    @php($cardCover = $course->coverUrl('medium'))
    @if ($cardCover)
        <a href="{{ route('academy.courses.show', $course->slug) }}" tabindex="-1" aria-hidden="true">
            <img src="{{ $cardCover }}" alt="" loading="lazy"
                 style="width: 100%; aspect-ratio: 16/9; object-fit: cover; display: block;">
        </a>
    @endif

    <div class="card-body d-flex flex-column p-4">

        {{-- Badges --}}
        <div class="d-flex flex-wrap gap-1 mb-3">
            <span class="badge rounded-pill"
                  style="border: 1px solid var(--c-primary, #064E5A); color: var(--c-primary, #064E5A);
                         background: transparent; font-size: 0.75rem; padding: 4px 10px;">
                {{ $levelLabel }}
            </span>
            <span class="badge rounded-pill"
                  style="background: {{ $isFreeCard ? '#D1FAE5' : '#F3F4F6' }};
                         color: {{ $isFreeCard ? '#065F46' : '#374151' }};
                         font-size: 0.75rem; padding: 4px 10px;">
                {{ $isFreeCard ? 'Gratuit' : 'Payant' }}
            </span>
        </div>

        {{-- Titre --}}
        <h2 style="font-family: var(--f-heading); font-size: 1.1rem; font-weight: 700;
                   color: var(--sys-text-default, #1A1D23); margin-bottom: 0.5rem;">
            <a href="{{ route('academy.courses.show', $course->slug) }}"
               class="text-decoration-none" style="color: inherit;">
                {{ $course->title }}
            </a>
        </h2>

        {{-- Résumé --}}
        @if($course->summary)
            <p class="mb-3 flex-grow-1"
               style="color: var(--sys-text-muted, #6B7280); font-size: 0.9rem; overflow: hidden; display: -webkit-box;
                      -webkit-line-clamp: 3; -webkit-box-orient: vertical; line-height: 1.55;">
                {{ $course->summary }}
            </p>
        @else
            <div class="flex-grow-1"></div>
        @endif

        {{-- Footer : durée + lien --}}
        <div class="d-flex justify-content-between align-items-center mt-2 gap-2" style="min-height: 38px;">
            @if($course->duration_minutes)
                <span style="color: var(--sys-text-muted, #6B7280); font-size: 0.85rem;">{{ $course->duration_minutes }} min</span>
            @else
                <span></span>
            @endif
            <x-core::button :href="route('academy.courses.show', $course->slug)" variant="secondary" size="sm">Voir le cours</x-core::button>
        </div>

    </div>
</div>
