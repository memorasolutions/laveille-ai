<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@php
    $levelLabels = ['intro' => 'Débutant', 'intermediaire' => 'Intermédiaire', 'avance' => 'Avancé'];
    $levelLabel  = $levelLabels[$course->level] ?? ucfirst($course->level);
    $isFreeCard  = $course->access_type === 'free';
@endphp

<div class="card h-100 border-0 shadow-sm"
     style="border-radius: var(--r-base, 12px); transition: transform 0.25s, box-shadow 0.25s;"
     onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 25px -5px rgba(0,0,0,0.1)'"
     onmouseout="this.style.transform='';this.style.boxShadow=''">
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
                   color: var(--c-dark, #1A1D23); margin-bottom: 0.5rem;">
            <a href="{{ route('academy.courses.show', $course->slug) }}"
               class="text-decoration-none" style="color: inherit;">
                {{ $course->title }}
            </a>
        </h2>

        {{-- Résumé --}}
        @if($course->summary)
            <p class="text-muted mb-3 flex-grow-1"
               style="font-size: 0.9rem; overflow: hidden; display: -webkit-box;
                      -webkit-line-clamp: 3; -webkit-box-orient: vertical; line-height: 1.55;">
                {{ $course->summary }}
            </p>
        @else
            <div class="flex-grow-1"></div>
        @endif

        {{-- Footer : durée + lien --}}
        <div class="d-flex justify-content-between align-items-center mt-2" style="min-height: 30px;">
            @if($course->duration_minutes)
                <span class="text-muted" style="font-size: 0.85rem;">⏱ {{ $course->duration_minutes }} min</span>
            @else
                <span></span>
            @endif
            <a href="{{ route('academy.courses.show', $course->slug) }}"
               style="color: var(--c-primary, #064E5A); font-weight: 600; font-size: 0.9rem;
                      text-decoration: none;">
                Voir le cours →
            </a>
        </div>

    </div>
</div>
