{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
     F22 - « Mes compétences » (étudiant, lecture seule). Rendu si au moins une
     compétence est rattachée à un cours suivi (sinon section masquée = rétrocompat). --}}
<div>
    @php
        $stateLabels = [
            'acquired'    => ['Acquise', '#065F46', '#D1FAE5'],
            'in_progress' => ['En cours', '#92400E', '#FEF3C7'],
            'not_started' => ['Non commencée', '#6B7280', '#F3F4F6'],
        ];
    @endphp

    @if ($this->competencies->isNotEmpty())
        <section aria-labelledby="student-competencies" style="margin-top: 24px;">
            <h2 id="student-competencies" style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0 0 12px; font-size: 1.25rem;">
                <span aria-hidden="true">🎯</span> Mes compétences
            </h2>

            <ul class="list-unstyled d-flex flex-column gap-2" style="margin: 0;">
                @foreach ($this->competencies as $row)
                    @php
                        $st = $row['state'];
                        [$label, $fg, $bg] = $stateLabels[$st['state']] ?? $stateLabels['not_started'];
                    @endphp
                    <li wire:key="my-comp-{{ $row['competency']->id }}"
                        class="d-flex flex-wrap justify-content-between align-items-center gap-2"
                        style="border:1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding:14px 16px;">
                        <div style="min-width:0;">
                            <div style="font-weight:700; color: var(--sys-text-default, #1A1D23);">{{ $row['competency']->name }}</div>
                            @if ($row['competency']->description)
                                <div style="font-size:0.85rem; color: var(--sys-text-muted, #6B7280); margin-top:2px;">{{ $row['competency']->description }}</div>
                            @endif
                            @if ($st['total'] > 0)
                                <div style="font-size:0.8rem; color: var(--sys-text-muted, #6B7280); margin-top:6px;">
                                    Niveau : {{ $st['level'] }} · {{ $st['achieved'] }}/{{ $st['total'] }} acquis ({{ $st['percent'] }} %)
                                </div>
                            @endif
                        </div>
                        <span style="display:inline-block; padding:4px 12px; border-radius:999px; background:{{ $bg }}; color:{{ $fg }}; font-weight:600; font-size:0.82rem; flex-shrink:0;">
                            {{ $label }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
