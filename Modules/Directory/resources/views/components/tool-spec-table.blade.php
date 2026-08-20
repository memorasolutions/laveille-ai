{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    Fiche technique DRY : table HTML sémantique des champs propriétaires réels d'un outil,
    jamais rendus ailleurs sur la fiche (underlying_model, is_multimodal, output_types,
    unique_value, opt_out_training, tutoriels approuvés). Omission silencieuse LIGNE PAR LIGNE -
    une ligne n'apparaît que si la donnée existe et est signifiante. Si aucune ligne n'a de
    donnée, le composant ne rend RIEN (pas de table vide, pas de titre orphelin).

    Usage : <x-directory::tool-spec-table :tool="$tool" />

    ZÉRO fabrication : n'affiche QUE des champs réels. privacy_compliance/learning_curve/
    has_api_access sont exclus (0% remplis / défauts non fiables, spec 2026-08-20).
--}}
@props(['tool'])

@php
    $rows = [];

    if (! empty($tool->underlying_model)) {
        $rows[] = ['label' => __('Modèle sous-jacent'), 'value' => $tool->underlying_model];
    }

    // is_multimodal est un booléen NULLABLE sans défaut en base (migration
    // 2026_05_08_120000) : le cast Eloquent préserve null tant que le champ n'a jamais été
    // renseigné. is_null() distingue donc un « non » réel d'un champ jamais rempli - ne
    // JAMAIS afficher un faux « Non » par défaut.
    if (! is_null($tool->is_multimodal)) {
        $rows[] = ['label' => __('Multimodal'), 'value' => $tool->is_multimodal ? __('Oui') : __('Non')];
    }

    if (! empty($tool->output_types) && is_array($tool->output_types)) {
        $rows[] = ['label' => __('Types de sortie'), 'value' => implode(', ', $tool->output_types)];
    }

    if (! empty($tool->unique_value)) {
        $rows[] = ['label' => __('Ce qui le distingue'), 'value' => $tool->unique_value];
    }

    // opt_out_training : enum yes/no/unknown, défaut 'unknown' (500/507 outils = bruit, spec
    // 2026-08-20) - seules les valeurs explicitement renseignées 'yes'/'no' sont affichées.
    if ($tool->opt_out_training === 'yes' || $tool->opt_out_training === 'no') {
        $rows[] = [
            'label' => __("Exclusion des données d'entraînement"),
            'value' => $tool->opt_out_training === 'yes' ? __('Oui') : __('Non'),
        ];
    }

    // Signal social vérifiable, même compteur que Tool::generateSocialPosts() (~L443).
    $tutoCount = (int) $tool->resources()->where('is_approved', true)->count();
    if ($tutoCount > 0) {
        $rows[] = ['label' => __('Tutoriels disponibles'), 'value' => (string) $tutoCount];
    }
@endphp

@if(! empty($rows))
    @once
        @push('styles')
        <style>
            .lv-spec-table-wrap {
                overflow-x: auto;
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: var(--r-base, 12px);
                margin-bottom: 24px;
            }
            .lv-spec-table {
                width: 100%;
                border-collapse: collapse;
                font-family: var(--f-body, inherit);
            }
            .lv-spec-table caption {
                text-align: left;
                font-weight: 700;
                font-size: 20px;
                color: #1e293b;
                padding: 20px 24px 12px;
                caption-side: top;
            }
            .lv-spec-table tbody tr {
                border-top: 1px solid #e2e8f0;
            }
            .lv-spec-table th,
            .lv-spec-table td {
                padding: 12px 24px;
                text-align: left;
                vertical-align: top;
                font-size: 14px;
                line-height: 1.5;
            }
            .lv-spec-table th[scope="row"] {
                color: var(--c-text-secondary, #6B7280);
                font-weight: 600;
                white-space: nowrap;
                width: 1%;
            }
            .lv-spec-table td {
                color: #1e293b;
                font-weight: 600;
            }
        </style>
        @endpush
    @endonce
    <div class="lv-spec-table-wrap">
        <table class="lv-spec-table">
            <caption>🔧 {{ __('Fiche technique') }}</caption>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <th scope="row">{{ $row['label'] }}</th>
                    <td>{{ $row['value'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
