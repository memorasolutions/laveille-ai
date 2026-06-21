{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@php
    $m = $this->metrics;
    $enrollment = $m['enrollment'];
    $completion = $m['completion'];
    $dropoff = $m['dropoff'];
    $dropoffPoint = $m['dropoffPoint'];
    $activity = $m['activity'];
    $certificates = $m['certificates'];
@endphp
<div style="display: flex; flex-direction: column; gap: 28px; font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23);">

    {{-- ───────────────────────── Cartes de KPIs ───────────────────────── --}}
    <section aria-labelledby="analytics-kpis">
        <h2 id="analytics-kpis" style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0 0 14px; font-size: 1.25rem;">
            Vue d'ensemble
        </h2>

        <div class="d-flex flex-wrap" style="gap: 14px;">
            {{-- Inscrits actifs --}}
            <div role="group" aria-label="Inscrits actifs"
                 style="flex: 1 1 160px; min-width: 150px; border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 16px 18px;">
                <p style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--sys-text-muted, #6B7280); margin: 0 0 6px;">Inscrits actifs</p>
                <p style="font-size: 1.9rem; font-weight: 700; color: var(--sys-action-primary, #064E5A); margin: 0; line-height: 1;">{{ $enrollment['total'] }}</p>
                <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 8px 0 0;">
                    +{{ $enrollment['last7'] }} sur 7 j · +{{ $enrollment['last30'] }} sur 30 j
                </p>
            </div>

            {{-- Taux de complétion --}}
            <div role="group" aria-label="Taux de complétion"
                 style="flex: 1 1 160px; min-width: 150px; border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 16px 18px;">
                <p style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--sys-text-muted, #6B7280); margin: 0 0 6px;">Complétion</p>
                <p style="font-size: 1.9rem; font-weight: 700; color: var(--sys-action-primary, #064E5A); margin: 0; line-height: 1;">{{ $completion['rate'] }}<span style="font-size: 1rem;">&nbsp;%</span></p>
                <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 8px 0 0;">
                    {{ $completion['completed'] }} sur {{ $completion['enrolled'] }} {{ $completion['enrolled'] > 1 ? 'inscrits' : 'inscrit' }}
                </p>
            </div>

            {{-- Progression moyenne --}}
            <div role="group" aria-label="Progression moyenne"
                 style="flex: 1 1 160px; min-width: 150px; border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 16px 18px;">
                <p style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--sys-text-muted, #6B7280); margin: 0 0 6px;">Progression moyenne</p>
                <p style="font-size: 1.9rem; font-weight: 700; color: var(--sys-action-primary, #064E5A); margin: 0; line-height: 1;">{{ $completion['avgPercent'] }}<span style="font-size: 1rem;">&nbsp;%</span></p>
                <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 8px 0 0;">moyenne des inscrits</p>
            </div>

            {{-- Certificats émis --}}
            <div role="group" aria-label="Certificats émis"
                 style="flex: 1 1 160px; min-width: 150px; border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 16px 18px;">
                <p style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--sys-text-muted, #6B7280); margin: 0 0 6px;">Certificats émis</p>
                <p style="font-size: 1.9rem; font-weight: 700; color: var(--sys-action-primary, #064E5A); margin: 0; line-height: 1;">{{ $certificates }}</p>
                <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 8px 0 0;">parcours terminés certifiés</p>
            </div>
        </div>
    </section>

    {{-- ───────────────────────── Décrochage par leçon ───────────────────────── --}}
    <section aria-labelledby="analytics-dropoff"
             style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 22px 24px;">
        <h2 id="analytics-dropoff" style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0 0 6px; font-size: 1.25rem;">
            Complétion par leçon
        </h2>
        <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 18px;">
            Part des inscrits actifs ayant terminé chaque leçon. La leçon où le plus d'apprenants s'arrêtent est mise en évidence.
        </p>

        @if (! empty($dropoffPoint) && $dropoffPoint['total'] > 0)
            <div role="note"
                 style="border: 1px solid #FED7AA; background: #FFF7ED; color: #9A3412; border-radius: var(--sys-radius-md, 0.5rem); padding: 12px 16px; margin-bottom: 18px; font-size: 0.88rem;">
                <strong>Point de décrochage :</strong>
                « {{ $dropoffPoint['title'] }} » — seulement {{ $dropoffPoint['rate'] }}&nbsp;% des inscrits l'ont terminée
                ({{ $dropoffPoint['completed'] }} sur {{ $dropoffPoint['total'] }}).
            </div>
        @endif

        @if (! empty($dropoff))
            <ul class="list-unstyled" style="margin: 0; display: flex; flex-direction: column; gap: 14px;">
                @foreach ($dropoff as $row)
                    @php($isWorst = $dropoffPoint && $row['lesson_id'] === $dropoffPoint['lesson_id'])
                    <li>
                        <div class="d-flex flex-wrap justify-content-between align-items-baseline" style="gap: 8px; margin-bottom: 4px;">
                            <span style="font-weight: 600; font-size: 0.9rem;">
                                {{ $row['title'] }}
                                <span style="font-weight: 400; color: var(--sys-text-muted, #6B7280); font-size: 0.8rem;">· {{ $row['chapter_title'] }}</span>
                            </span>
                            <span style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280);">
                                {{ $row['completed'] }} / {{ $row['total'] }} ({{ $row['rate'] }}&nbsp;%)
                            </span>
                        </div>
                        <div role="progressbar"
                             aria-valuenow="{{ $row['rate'] }}" aria-valuemin="0" aria-valuemax="100"
                             aria-label="Complétion de la leçon {{ $row['title'] }}"
                             style="width: 100%; height: 12px; background: #EEF2F1; border-radius: 999px; overflow: hidden;">
                            <div style="width: {{ $row['rate'] }}%; height: 100%; background: {{ $isWorst ? '#C2410C' : 'var(--sys-action-primary, #064E5A)' }}; border-radius: 999px;"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div style="border: 1px dashed #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 24px; text-align: center;">
                <p style="color: var(--sys-text-muted, #6B7280); margin: 0;">Aucune donnée pour l'instant (aucune leçon requise ou aucun inscrit).</p>
            </div>
        @endif
    </section>

    {{-- ───────────────────────── Activité récente ───────────────────────── --}}
    <section aria-labelledby="analytics-activity"
             style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 22px 24px;">
        <h2 id="analytics-activity" style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0 0 18px; font-size: 1.25rem;">
            Activité récente
        </h2>

        <div class="d-flex flex-wrap" style="gap: 28px;">
            {{-- Dernières inscriptions --}}
            <div style="flex: 1 1 280px; min-width: 240px;">
                <h3 style="font-size: 0.95rem; font-family: var(--f-heading); margin: 0 0 10px;">Dernières inscriptions</h3>
                @if ($activity['enrollments']->isNotEmpty())
                    <ul class="list-unstyled" style="margin: 0; display: flex; flex-direction: column; gap: 8px;">
                        @foreach ($activity['enrollments'] as $e)
                            <li style="font-size: 0.88rem; display: flex; justify-content: space-between; gap: 10px;">
                                <span>{{ $e->user?->name ?? 'Apprenant' }}</span>
                                <span style="color: var(--sys-text-muted, #6B7280); white-space: nowrap;">
                                    {{ $e->enrolled_at?->timezone('America/Toronto')->diffForHumans() }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p style="color: var(--sys-text-muted, #6B7280); font-size: 0.88rem; margin: 0;">Aucune inscription pour l'instant.</p>
                @endif
            </div>

            {{-- Dernières complétions --}}
            <div style="flex: 1 1 280px; min-width: 240px;">
                <h3 style="font-size: 0.95rem; font-family: var(--f-heading); margin: 0 0 10px;">Dernières complétions</h3>
                @if ($activity['completions']->isNotEmpty())
                    <ul class="list-unstyled" style="margin: 0; display: flex; flex-direction: column; gap: 8px;">
                        @foreach ($activity['completions'] as $c)
                            <li style="font-size: 0.88rem; display: flex; justify-content: space-between; gap: 10px;">
                                <span>
                                    {{ $c->user?->name ?? 'Apprenant' }}
                                    <span style="color: var(--sys-text-muted, #6B7280);">· {{ $c->lessonItem?->title ?? 'élément' }}</span>
                                </span>
                                <span style="color: var(--sys-text-muted, #6B7280); white-space: nowrap;">
                                    {{ $c->completed_at?->timezone('America/Toronto')->diffForHumans() }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p style="color: var(--sys-text-muted, #6B7280); font-size: 0.88rem; margin: 0;">Aucune complétion pour l'instant.</p>
                @endif
            </div>
        </div>
    </section>

</div>
