{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
     F22 - Association compétences <-> cours/items + rapport d'acquisition par étudiant.
     Charte (tokens var(--sys-*) / x-core::button). --}}
<div>
    @php
        $stateLabels = [
            'acquired'    => ['Acquise', '#065F46', '#D1FAE5'],
            'in_progress' => ['En cours', '#92400E', '#FEF3C7'],
            'not_started' => ['Non commencée', '#6B7280', '#F3F4F6'],
        ];
    @endphp

    {{-- Associer une compétence au cours entier --}}
    <div style="border:1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding:16px; margin-bottom:16px;">
        <h3 style="font-family: var(--f-heading); font-size:1rem; margin:0 0 10px;">Associer une compétence</h3>

        @if ($this->availableCompetencies->isEmpty())
            <p style="color: var(--sys-text-muted, #6B7280); margin:0;">
                Aucune compétence disponible. <a href="{{ route('academy.competencies') }}" style="color: var(--sys-accent, #0d9488); text-decoration: underline;">Créez vos compétences</a> d'abord.
            </p>
        @else
            <div class="d-flex flex-wrap align-items-end gap-2">
                <div>
                    <label for="cc-select" style="display:block; font-weight:600; font-size:0.85rem; margin-bottom:4px;">Compétence</label>
                    <select id="cc-select" wire:model="selectedCompetencyId" class="form-select" style="min-width:240px;">
                        <option value="">Choisir…</option>
                        @foreach ($this->availableCompetencies as $competency)
                            <option value="{{ $competency->id }}">{{ $competency->name }}@unless($competency->is_active) (inactive)@endunless</option>
                        @endforeach
                    </select>
                </div>
                <x-core::button type="button" variant="primary" size="sm"
                    :disabled="! $selectedCompetencyId"
                    wire:click="attachToCourse({{ (int) ($selectedCompetencyId ?? 0) }})">Associer au cours entier</x-core::button>
            </div>

            @if ($selectedCompetencyId && $this->courseItems->isNotEmpty())
                <div style="margin-top:14px;">
                    <div style="font-weight:600; font-size:0.85rem; margin-bottom:6px;">…ou à un item précis :</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($this->courseItems as $item)
                            <x-core::button type="button" variant="ghost" size="sm"
                                wire:click="attachToItem({{ (int) $selectedCompetencyId }}, {{ $item->id }})">
                                + {{ $item->lesson?->title ? $item->lesson->title.' · ' : '' }}{{ $item->title }}
                            </x-core::button>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>

    {{-- Liens existants --}}
    <div style="margin-bottom:16px;">
        <h3 style="font-family: var(--f-heading); font-size:1rem; margin:0 0 10px;">Compétences associées à ce cours</h3>
        @if ($this->courseLinks->isEmpty())
            <p style="color: var(--sys-text-muted, #6B7280); margin:0;">Aucune association pour l'instant.</p>
        @else
            <ul class="list-unstyled d-flex flex-column gap-2" style="margin:0;">
                @foreach ($this->courseLinks as $link)
                    <li wire:key="link-{{ $link->id }}"
                        class="d-flex flex-wrap justify-content-between align-items-center gap-2"
                        style="border:1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding:10px 14px;">
                        <span>
                            <strong>{{ $link->competency?->name ?? '(supprimée)' }}</strong>
                            <span style="font-size:0.8rem; color: var(--sys-text-muted, #6B7280);">
                                @if ($link->course_id)
                                    · cours entier
                                @else
                                    · item #{{ $link->lesson_item_id }}
                                @endif
                            </span>
                        </span>
                        <x-core::button type="button" variant="ghost" size="sm" wire:click="detach({{ $link->id }})" aria-label="Retirer l'association de {{ $link->competency?->name ?? 'cette compétence' }}">Retirer</x-core::button>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Rapport d'acquisition par étudiant (gâté manageEnrollments) --}}
    @if ($this->canViewReport)
        @php $matrix = $this->matrix; @endphp
        <div style="border-top:1px solid #E5E7EB; padding-top:16px;">
            <h3 style="font-family: var(--f-heading); font-size:1rem; margin:0 0 10px;">
                <span aria-hidden="true">📋</span> Suivi d'acquisition par étudiant
            </h3>

            @if ($matrix['competencies']->isEmpty() || $matrix['students']->isEmpty())
                <p style="color: var(--sys-text-muted, #6B7280); margin:0;">
                    {{ $matrix['students']->isEmpty() ? 'Aucun étudiant inscrit actif.' : 'Aucune compétence associée à suivre.' }}
                </p>
            @else
                <div style="overflow-x:auto;">
                    <table class="table" style="min-width:520px; font-size:0.85rem;">
                        <thead>
                            <tr>
                                <th scope="col" style="text-align:left;">Étudiant</th>
                                @foreach ($matrix['competencies'] as $competency)
                                    <th scope="col" style="text-align:center;">{{ $competency->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($matrix['students'] as $student)
                                <tr wire:key="row-{{ $student->id }}">
                                    <th scope="row" style="text-align:left; font-weight:600;">{{ $student->name }}</th>
                                    @foreach ($matrix['competencies'] as $competency)
                                        @php
                                            $st = $matrix['states'][$competency->id][$student->id] ?? ['state' => 'not_started', 'level' => 'Non atteint', 'achieved' => 0, 'total' => 0];
                                            [$label, $fg, $bg] = $stateLabels[$st['state']] ?? $stateLabels['not_started'];
                                        @endphp
                                        <td style="text-align:center;">
                                            <span style="display:inline-block; padding:2px 8px; border-radius:999px; background:{{ $bg }}; color:{{ $fg }}; font-weight:600; font-size:0.78rem;"
                                                  title="{{ $st['level'] }} ({{ $st['achieved'] }}/{{ $st['total'] }})">
                                                {{ $label }}
                                            </span>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>
