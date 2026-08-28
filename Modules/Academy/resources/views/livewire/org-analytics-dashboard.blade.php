{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@php
    $s = $this->summary;

    // Couleur du badge score moyen (+ libellé texte — WCAG AAA, pas couleur seule).
    $riskBadge = static function (int $avg): array {
        if ($avg >= 67) {
            return ['bg' => 'var(--sys-status-danger-bg, #FEE2E2)', 'color' => 'var(--sys-status-danger-text, #7F1414)', 'label' => 'Élevé'];
        }
        if ($avg >= 34) {
            return ['bg' => 'var(--sys-status-warning-bg, #FFEDD5)', 'color' => 'var(--sys-status-warning-text, #7C2D12)', 'label' => 'Modéré'];
        }
        return ['bg' => 'var(--sys-status-success-bg, #D1FAE5)', 'color' => 'var(--sys-status-success-text, #054F3A)', 'label' => 'Faible'];
    };
@endphp

<div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23); display: flex; flex-direction: column; gap: 28px;">

    <h1 style="font-family: var(--f-heading); font-size: 1.35rem; margin: 0; color: var(--sys-text-default, #1A1D23);">
        Tableau de bord organisationnel&nbsp;: Analytiques prédictifs
    </h1>

    {{-- ── KPIs ── --}}
    <section aria-labelledby="org-kpis-heading">
        <h2 id="org-kpis-heading" style="font-family: var(--f-heading); font-size: 1.1rem; margin: 0 0 14px; color: var(--sys-text-default, #1A1D23);">
            Indicateurs clés
        </h2>

        <div class="d-flex flex-wrap" style="gap: 14px;">
            <div role="group" aria-label="Total inscrits actifs"
                 style="flex: 1 1 150px; min-width: 140px; border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 16px 18px;">
                <p style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--sys-text-muted, #6B7280); margin: 0 0 6px;">Inscrits actifs</p>
                <p style="font-size: 1.9rem; font-weight: 700; color: var(--sys-action-primary, #064E5A); margin: 0; line-height: 1;">{{ $s['total_enrolled'] }}</p>
            </div>

            <div role="group" aria-label="Apprenants à risque"
                 style="flex: 1 1 150px; min-width: 140px; border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 16px 18px;">
                <p style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--sys-text-muted, #6B7280); margin: 0 0 6px;">À risque (modéré ou élevé)</p>
                <p style="font-size: 1.9rem; font-weight: 700; color: #9A3412; margin: 0; line-height: 1;">{{ $s['at_risk_count'] }}</p>
                @if ($s['total_enrolled'] > 0)
                    <p style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280); margin: 6px 0 0;">
                        {{ (int) round(($s['at_risk_count'] / $s['total_enrolled']) * 100) }}&nbsp;% des inscrits
                    </p>
                @endif
            </div>

            <div role="group" aria-label="Apprenants à risque élevé"
                 style="flex: 1 1 150px; min-width: 140px; border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 16px 18px;">
                <p style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--sys-text-muted, #6B7280); margin: 0 0 6px;">Risque élevé</p>
                <p style="font-size: 1.9rem; font-weight: 700; color: #991B1B; margin: 0; line-height: 1;">{{ $s['high_risk_count'] }}</p>
            </div>

            <div role="group" aria-label="Taux de complétion moyen"
                 style="flex: 1 1 150px; min-width: 140px; border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 16px 18px;">
                <p style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--sys-text-muted, #6B7280); margin: 0 0 6px;">Progression moyenne</p>
                <p style="font-size: 1.9rem; font-weight: 700; color: var(--sys-action-primary, #064E5A); margin: 0; line-height: 1;">{{ $s['avg_completion_rate'] }}<span style="font-size: 1rem;">&nbsp;%</span></p>
            </div>
        </div>
    </section>

    {{-- ── Tableau par formation ── --}}
    <section aria-labelledby="org-courses-heading"
             style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 22px 24px;">
        <h2 id="org-courses-heading" style="font-family: var(--f-heading); font-size: 1.1rem; margin: 0 0 6px; color: var(--sys-text-default, #1A1D23);">
            Détail par formation
        </h2>
        <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 18px;">
            Formations triées par nombre d'apprenants à risque décroissant.
        </p>

        @if (empty($s['courses_summary']))
            <div style="border: 1px dashed #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 24px; text-align: center;">
                <p style="color: var(--sys-text-muted, #6B7280); margin: 0;">Aucune formation avec des inscrits actifs pour l'instant.</p>
            </div>
        @else
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem;">
                    <caption class="visually-hidden" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0);">
                        Répartition des apprenants par formation, avec le nombre d'inscrits, à risque et le score moyen de risque.
                    </caption>
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid #E5E7EB;">
                            <th scope="col" style="padding: 8px 10px; font-weight: 600;">Formation</th>
                            <th scope="col" style="padding: 8px 10px; font-weight: 600; text-align: right;">Inscrits</th>
                            <th scope="col" style="padding: 8px 10px; font-weight: 600; text-align: right;">À risque</th>
                            <th scope="col" style="padding: 8px 10px; font-weight: 600; text-align: right;">Score moyen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($s['courses_summary'] as $cs)
                            @php $badge = $riskBadge($cs['avg_score']); @endphp
                            <tr style="border-bottom: 1px solid #F3F4F6;">
                                <td style="padding: 10px; font-weight: 500;">{{ $cs['course_title'] }}</td>
                                <td style="padding: 10px; text-align: right; color: var(--sys-text-muted, #6B7280);">{{ $cs['enrolled'] }}</td>
                                <td style="padding: 10px; text-align: right; color: var(--sys-text-muted, #6B7280);">{{ $cs['at_risk'] }}</td>
                                <td style="padding: 10px; text-align: right;">
                                    <span style="display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 600; background: {{ $badge['bg'] }}; color: {{ $badge['color'] }};">
                                        {{ $cs['avg_score'] }}&nbsp;– {{ $badge['label'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
