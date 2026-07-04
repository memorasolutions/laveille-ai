{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    Calendrier global - Vague 4 (GlobalCalendar Livewire). Vue mensuelle en
    lecture seule, agrégeant les échéances de TOUS les cours pertinents de
    l'utilisateur (inscriptions + cours gérés). Grille de boutons clavier-
    accessible (pas de bibliothèque JS externe) + panneau de détail du jour.
--}}
<div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23);">

    <style>
        .academy-cal-day {
            min-height: 64px;
            border: 1px solid #E5E7EB;
            border-radius: var(--sys-radius-sm, 0.5rem);
            background: #FFFFFF;
            width: 100%;
            padding: 6px 8px;
            text-align: left;
            cursor: pointer;
            font: inherit;
        }
        .academy-cal-day:hover {
            background: #F0FDF4;
        }
        .academy-cal-day:focus-visible {
            outline: 3px solid var(--sys-action-primary, #064E5A);
            outline-offset: 2px;
        }
        .academy-cal-day[aria-current="date"] {
            border-color: var(--sys-action-primary, #064E5A);
            border-width: 2px;
        }
        .academy-cal-day[aria-pressed="true"] {
            background: #D1FAE5;
        }
        .academy-cal-day .cal-day-count {
            display: inline-block;
            margin-top: 4px;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--sys-action-primary, #064E5A);
        }
    </style>

    @php
        $typeLabels = [
            'due'    => 'Échéance',
            'exam'   => 'Examen',
            'live'   => 'En direct',
            'manual' => 'Événement',
        ];
        $typeColors = [
            'due'    => 'background: #FEE2E2; color: #991B1B;',
            'exam'   => 'background: #FFEDD5; color: #9A3412;',
            'live'   => 'background: #D1FAE5; color: #065F46;',
            'manual' => 'background: #E0F2FE; color: #075985;',
        ];
    @endphp

    {{-- ── En-tête : navigation mensuelle ── --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3" style="margin-bottom: 20px;">
        <h2 style="font-family: var(--f-heading); font-size: 1.35rem;
                   color: var(--sys-text-default, #1A1D23); margin: 0;">
            Mon calendrier
        </h2>

        <nav class="d-flex align-items-center gap-2" aria-label="Navigation entre les mois">
            <x-core::button type="button" variant="ghost" size="sm" wire:click="previousMonth" aria-label="Mois précédent">
                ← Précédent
            </x-core::button>
            <span aria-live="polite" style="font-weight: 700; min-width: 160px; text-align: center;">
                {{ $this->monthLabel }}
            </span>
            <x-core::button type="button" variant="ghost" size="sm" wire:click="nextMonth" aria-label="Mois suivant">
                Suivant →
            </x-core::button>
            <x-core::button type="button" variant="secondary" size="sm" wire:click="goToToday">
                Aujourd'hui
            </x-core::button>
        </nav>
    </div>

    {{-- ── Grille mensuelle (clavier-accessible : boutons natifs) ── --}}
    <div role="grid" aria-label="Grille du mois de {{ $this->monthLabel }}" style="margin-bottom: 24px;">
        <div role="row" class="d-flex" style="gap: 6px; margin-bottom: 6px;">
            @foreach(['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $dayName)
                <div role="columnheader" style="flex: 1 1 0; text-align: center; font-size: 0.78rem;
                                                 font-weight: 700; color: var(--sys-text-muted, #6B7280);">
                    {{ $dayName }}
                </div>
            @endforeach
        </div>

        @foreach($this->weeks as $week)
            <div role="row" class="d-flex" style="gap: 6px; margin-bottom: 6px;">
                @foreach($week as $cell)
                    <div role="gridcell" style="flex: 1 1 0;">
                        @if($cell === null)
                            <div aria-hidden="true" style="min-height: 64px;"></div>
                        @else
                            @php $dayEvents = $this->eventsByDay->get($cell['date'], collect()); @endphp
                            <button type="button" class="academy-cal-day"
                                    wire:click="selectDay('{{ $cell['date'] }}')"
                                    @if($cell['is_today']) aria-current="date" @endif
                                    aria-pressed="{{ $selectedDate === $cell['date'] ? 'true' : 'false' }}"
                                    aria-label="{{ $cell['day'] }} {{ $this->monthLabel }}{{ $dayEvents->isNotEmpty() ? ', ' . $dayEvents->count() . ' événement(s)' : '' }}">
                                <span>{{ $cell['day'] }}</span>
                                @if($dayEvents->isNotEmpty())
                                    <span class="cal-day-count">{{ $dayEvents->count() }} évén.</span>
                                @endif
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    {{-- ── Panneau de détail du jour sélectionné ── --}}
    <div role="region" aria-live="polite" aria-label="Détail du jour sélectionné">
        @if($selectedDate)
            <h3 style="font-family: var(--f-heading); font-size: 1.05rem; margin-bottom: 12px;">
                {{ \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $selectedDate)->translatedFormat('l j F Y') }}
            </h3>

            @if($this->selectedDayEvents->isNotEmpty())
                <ul class="list-unstyled d-flex flex-column gap-2" role="list" style="margin: 0;">
                    @foreach($this->selectedDayEvents as $ev)
                        @php
                            $label      = $typeLabels[$ev['type']] ?? 'Événement';
                            $badgeStyle = $typeColors[$ev['type']] ?? $typeColors['manual'];
                        @endphp
                        <li wire:key="gcal-{{ $ev['id'] }}" role="listitem"
                            style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem);
                                   padding: 12px 16px; background: #FFFFFF;">
                            <div class="d-flex flex-wrap align-items-center gap-2" style="margin-bottom: 4px;">
                                <span style="font-size: 0.72rem; font-weight: 700; padding: 2px 10px;
                                             border-radius: 999px; {{ $badgeStyle }}">
                                    {{ $label }}
                                </span>
                                <span style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280);">
                                    {{ $ev['course_title'] }}
                                </span>
                            </div>
                            <p style="margin: 0 0 2px; font-weight: 600;">{{ $ev['title'] }}</p>
                            <p style="margin: 0; font-size: 0.82rem; color: var(--sys-text-muted, #6B7280);">
                                <time datetime="{{ $ev['starts_at']->timezone('America/Toronto')->toIso8601String() }}">
                                    {{ $ev['starts_at']->timezone('America/Toronto')->translatedFormat('H\hi') }}
                                </time>
                                @if($ev['course_slug'])
                                    · <a href="{{ route('academy.courses.show', $ev['course_slug']) }}" style="color: var(--sys-action-primary, #064E5A);">
                                        Voir le cours
                                    </a>
                                @endif
                            </p>
                        </li>
                    @endforeach
                </ul>
            @else
                <p style="color: var(--sys-text-muted, #6B7280);">Aucun événement ce jour-là.</p>
            @endif
        @else
            <p style="color: var(--sys-text-muted, #6B7280);">
                Sélectionnez un jour dans la grille pour voir le détail des événements.
            </p>
        @endif
    </div>

    {{-- ── Liste complète du mois (repli lecture, utile hors interaction JS) ── --}}
    @if($this->events->isNotEmpty())
        <details style="margin-top: 28px;">
            <summary style="cursor: pointer; font-weight: 700; margin-bottom: 12px;">
                Liste complète du mois ({{ $this->events->count() }})
            </summary>
            <ul class="list-unstyled d-flex flex-column gap-2" role="list" style="margin: 0;">
                @foreach($this->events as $ev)
                    @php
                        $label      = $typeLabels[$ev['type']] ?? 'Événement';
                        $badgeStyle = $typeColors[$ev['type']] ?? $typeColors['manual'];
                    @endphp
                    <li wire:key="gcal-list-{{ $ev['id'] }}" role="listitem"
                        style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem);
                               padding: 10px 14px; background: #FAFAFA; font-size: 0.9rem;">
                        <span style="font-size: 0.7rem; font-weight: 700; padding: 2px 8px;
                                     border-radius: 999px; {{ $badgeStyle }}">{{ $label }}</span>
                        <strong>{{ $ev['title'] }}</strong>
                        - {{ $ev['course_title'] }}
                        - <time datetime="{{ $ev['starts_at']->timezone('America/Toronto')->toIso8601String() }}">
                            {{ $ev['starts_at']->timezone('America/Toronto')->translatedFormat('D j M \à H\hi') }}
                        </time>
                    </li>
                @endforeach
            </ul>
        </details>
    @else
        <p style="color: var(--sys-text-muted, #6B7280); margin-top: 20px;">
            Aucun événement ce mois-ci.
        </p>
    @endif
</div>
