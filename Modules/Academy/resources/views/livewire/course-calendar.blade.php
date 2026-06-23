{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    Calendrier d'echeances - V5-b (CourseCalendar Livewire).
    - Etudiant : liste en lecture seule.
    - Gerant   : liste + CRUD d'evenements manuels, confirmation inline a 2 temps.
    Aucune popup native (confirm/alert/prompt).
--}}
<div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23);">

    {{-- Flash statut --}}
    @if(session('calendar_status'))
        <div role="status" aria-live="polite"
             style="border: 1px solid #BBF7D0; background: #F0FDF4; color: #166534;
                    border-radius: var(--sys-radius-md, 0.75rem); padding: 12px 16px;
                    margin-bottom: 18px; font-size: 0.9rem;">
            {{ session('calendar_status') }}
        </div>
    @endif

    {{-- ── En-tete : titre + bouton Ajouter (gerant uniquement) ── --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3" style="margin-bottom: 20px;">
        <h2 style="font-family: var(--f-heading); font-size: 1.35rem;
                   color: var(--sys-text-default, #1A1D23); margin: 0;">
            Calendrier des echeances
        </h2>

        <div class="d-flex flex-wrap gap-2 align-items-center">
            {{-- Export iCal (etudiant inscrit OU gerant) --}}
            <a href="{{ route('academy.courses.calendar.ical', $this->course->slug) }}"
               style="font-size: 0.82rem; color: var(--sys-action-primary, #064E5A);
                      text-decoration: underline; white-space: nowrap;"
               aria-label="Telecharger le calendrier au format iCal">
                Telecharger .ics
            </a>

            @if($this->canManage && !$showForm)
                <x-core::button type="button" variant="primary" size="sm"
                                wire:click="openCreate">
                    Ajouter un evenement
                </x-core::button>
            @endif
        </div>
    </div>

    {{-- ── Formulaire de creation/edition (gerant uniquement) ── --}}
    @if($showForm && $this->canManage)
        <div role="region"
             aria-labelledby="cal-form-title"
             style="border: 1px solid #D1FAE5; border-radius: var(--sys-radius-md, 0.75rem);
                    padding: 20px 22px; margin-bottom: 24px; background: #F0FDF4;">

            <h3 id="cal-form-title"
                style="font-family: var(--f-heading); font-size: 1rem;
                       color: var(--sys-text-default, #1A1D23); margin: 0 0 16px;">
                {{ $editingEvent ? 'Modifier l\'evenement' : 'Nouvel evenement' }}
            </h3>

            {{-- Titre --}}
            <div style="margin-bottom: 14px;">
                <label for="cal-title"
                       style="display: block; font-size: 0.88rem; font-weight: 600;
                              color: var(--sys-text-default, #374151); margin-bottom: 4px;">
                    Titre <span aria-hidden="true" style="color:#B91C1C;">*</span>
                </label>
                <input id="cal-title" type="text" wire:model.blur="evTitle"
                       maxlength="200" required
                       style="width: 100%; border: 1px solid #D1D5DB;
                              border-radius: var(--sys-radius-sm, 0.5rem);
                              padding: 8px 12px; font-size: 0.92rem; color: #1A1D23;"
                       placeholder="Ex. : Remise du devoir 1">
                @error('evTitle')
                    <p role="alert" style="font-size: 0.8rem; color: #B91C1C; margin: 4px 0 0;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div style="margin-bottom: 14px;">
                <label for="cal-desc"
                       style="display: block; font-size: 0.88rem; font-weight: 600;
                              color: var(--sys-text-default, #374151); margin-bottom: 4px;">
                    Description (optionnel)
                </label>
                <textarea id="cal-desc" wire:model.blur="evDescription"
                          maxlength="1000" rows="2"
                          style="width: 100%; border: 1px solid #D1D5DB;
                                 border-radius: var(--sys-radius-sm, 0.5rem);
                                 padding: 8px 12px; font-size: 0.92rem;
                                 color: #1A1D23; resize: vertical;"></textarea>
                @error('evDescription')
                    <p role="alert" style="font-size: 0.8rem; color: #B91C1C; margin: 4px 0 0;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Type --}}
            <div style="margin-bottom: 14px;">
                <label for="cal-type"
                       style="display: block; font-size: 0.88rem; font-weight: 600;
                              color: var(--sys-text-default, #374151); margin-bottom: 4px;">
                    Type <span aria-hidden="true" style="color:#B91C1C;">*</span>
                </label>
                <select id="cal-type" wire:model.blur="evType"
                        style="width: 100%; max-width: 260px; border: 1px solid #D1D5DB;
                               border-radius: var(--sys-radius-sm, 0.5rem);
                               padding: 8px 12px; font-size: 0.92rem; color: #1A1D23;
                               background: #fff;">
                    <option value="due">Echeance de devoir</option>
                    <option value="exam">Examen</option>
                    <option value="live">Session en direct</option>
                    <option value="manual">Autre evenement</option>
                </select>
                @error('evType')
                    <p role="alert" style="font-size: 0.8rem; color: #B91C1C; margin: 4px 0 0;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Dates --}}
            <div class="d-flex flex-wrap gap-4" style="margin-bottom: 20px;">
                <div style="flex: 1 1 220px;">
                    <label for="cal-starts"
                           style="display: block; font-size: 0.88rem; font-weight: 600;
                                  color: var(--sys-text-default, #374151); margin-bottom: 4px;">
                        Date de debut <span aria-hidden="true" style="color:#B91C1C;">*</span>
                    </label>
                    <input id="cal-starts" type="datetime-local" wire:model.blur="evStartsAt"
                           style="width: 100%; border: 1px solid #D1D5DB;
                                  border-radius: var(--sys-radius-sm, 0.5rem);
                                  padding: 8px 12px; font-size: 0.92rem; color: #1A1D23;">
                    @error('evStartsAt')
                        <p role="alert" style="font-size: 0.8rem; color: #B91C1C; margin: 4px 0 0;">{{ $message }}</p>
                    @enderror
                </div>

                <div style="flex: 1 1 220px;">
                    <label for="cal-ends"
                           style="display: block; font-size: 0.88rem; font-weight: 600;
                                  color: var(--sys-text-default, #374151); margin-bottom: 4px;">
                        Date de fin (optionnel)
                    </label>
                    <input id="cal-ends" type="datetime-local" wire:model.blur="evEndsAt"
                           style="width: 100%; border: 1px solid #D1D5DB;
                                  border-radius: var(--sys-radius-sm, 0.5rem);
                                  padding: 8px 12px; font-size: 0.92rem; color: #1A1D23;">
                    @error('evEndsAt')
                        <p role="alert" style="font-size: 0.8rem; color: #B91C1C; margin: 4px 0 0;">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Boutons --}}
            <div class="d-flex flex-wrap gap-2">
                <x-core::button type="button" variant="primary" size="sm"
                                wire:click="save"
                                wire:loading.attr="disabled" wire:target="save">
                    Enregistrer
                </x-core::button>
                <x-core::button type="button" variant="ghost" size="sm"
                                wire:click="cancelForm">
                    Annuler
                </x-core::button>
            </div>
        </div>
    @endif

    {{-- ── Liste chronologique des evenements ── --}}
    @php
        $typeLabels = [
            'due'    => 'Echeance',
            'exam'   => 'Examen',
            'live'   => 'En direct',
            'manual' => 'Evenement',
        ];
        $typeColors = [
            'due'    => 'background: #FEE2E2; color: #991B1B;',
            'exam'   => 'background: #FFEDD5; color: #9A3412;',
            'live'   => 'background: #D1FAE5; color: #065F46;',
            'manual' => 'background: #E0F2FE; color: #075985;',
        ];
    @endphp

    @if($this->events->isNotEmpty())
        <ul class="list-unstyled d-flex flex-column gap-3" role="list" style="margin: 0;">
            @foreach($this->events as $ev)
                @php
                    $isPast   = $ev['is_past'];
                    $isManual = $ev['source'] === 'manual';
                    $label    = $typeLabels[$ev['type']] ?? 'Evenement';
                    $badgeStyle = $typeColors[$ev['type']] ?? $typeColors['manual'];
                @endphp
                <li wire:key="cal-ev-{{ $ev['id'] }}" role="listitem"
                    style="border: 1px solid {{ $isPast ? '#E5E7EB' : '#D1FAE5' }};
                           border-radius: var(--sys-radius-md, 0.75rem);
                           padding: 16px 18px;
                           background: {{ $isPast ? '#FAFAFA' : '#FFFFFF' }};
                           opacity: {{ $isPast ? '0.72' : '1' }};">

                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        {{-- Info principale --}}
                        <div style="flex: 1 1 260px; min-width: 200px;">
                            <div class="d-flex flex-wrap align-items-center gap-2" style="margin-bottom: 6px;">
                                {{-- Badge type --}}
                                <span style="font-size: 0.72rem; font-weight: 700;
                                             padding: 2px 10px; border-radius: 999px;
                                             {{ $badgeStyle }}">
                                    {{ $label }}
                                </span>

                                {{-- Badge 'derive' (devoir) --}}
                                @if($ev['source'] === 'derived')
                                    <span style="font-size: 0.7rem; color: var(--sys-text-muted, #6B7280);
                                                 background: #F3F4F6; border-radius: 999px;
                                                 padding: 2px 8px;">
                                        depuis un devoir
                                    </span>
                                @endif

                                @if($isPast)
                                    <span aria-label="Echeance passee"
                                          style="font-size: 0.7rem; font-weight: 600;
                                                 color: #9A3412; background: #FFEDD5;
                                                 border-radius: 999px; padding: 2px 8px;">
                                        Passe
                                    </span>
                                @endif
                            </div>

                            <h3 style="font-family: var(--f-heading); font-size: 1.05rem;
                                       color: var(--sys-text-default, #1A1D23); margin: 0 0 4px;">
                                {{ $ev['title'] }}
                            </h3>

                            @if($ev['description'])
                                <p style="font-size: 0.88rem; color: var(--sys-text-default, #374151);
                                          margin: 0 0 6px; line-height: 1.5;">
                                    {{ $ev['description'] }}
                                </p>
                            @endif

                            <p style="font-size: 0.82rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
                                <time datetime="{{ $ev['starts_at']->timezone('America/Toronto')->toIso8601String() }}">
                                    {{ $ev['starts_at']->timezone('America/Toronto')->translatedFormat('D j M Y \a\t H\hi') }}
                                </time>
                                @if($ev['ends_at'])
                                    - <time datetime="{{ $ev['ends_at']->timezone('America/Toronto')->toIso8601String() }}">
                                        {{ $ev['ends_at']->timezone('America/Toronto')->translatedFormat('H\hi') }}
                                    </time>
                                @endif
                            </p>
                        </div>

                        {{-- Actions gerant : uniquement evenements manuels --}}
                        @if($this->canManage && $isManual)
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                @if($confirmingRemoval === $ev['event_id'])
                                    <span style="font-size: 0.82rem; color: var(--sys-text-muted, #6B7280);">
                                        Supprimer ?
                                    </span>
                                    <x-core::button type="button" variant="primary" size="sm"
                                                    wire:click="remove({{ $ev['event_id'] }})"
                                                    wire:loading.attr="disabled" wire:target="remove">
                                        Oui, supprimer
                                    </x-core::button>
                                    <x-core::button type="button" variant="ghost" size="sm"
                                                    wire:click="cancelRemove">
                                        Annuler
                                    </x-core::button>
                                @else
                                    <x-core::button type="button" variant="secondary" size="sm"
                                                    wire:click="openEdit({{ $ev['event_id'] }})">
                                        Modifier
                                    </x-core::button>
                                    <x-core::button type="button" variant="ghost" size="sm"
                                                    wire:click="confirmRemove({{ $ev['event_id'] }})">
                                        Supprimer
                                    </x-core::button>
                                @endif
                            </div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <div style="border: 1px dashed #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem);
                    padding: 28px; text-align: center;">
            <p style="color: var(--sys-text-muted, #6B7280); margin: 0;">
                Aucune echeance pour ce cours.
                @if($this->canManage)
                    Utilisez le bouton « Ajouter un evenement » pour en creer une.
                @endif
            </p>
        </div>
    @endif
</div>
