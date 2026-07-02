{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    F22-b - GRAPHE DE COMPÉTENCES (vue minimale, texte structuré prioritaire sur
    le rendu graphique). Liste les compétences pertinentes d'un cours avec leur
    statut pour l'utilisateur courant (verrouillée / déverrouillée / maîtrisée)
    et leurs prérequis. AUCUNE dépendance JS : CSS/HTML pur.

    Fondation de données correcte > sophistication visuelle (cf. tâche F22-b).
    NO-OP silencieux si le drapeau academy.competency_graph_enabled est désactivé
    (rien n'est affiché — rétrocompat stricte, comportement actuel inchangé).

    Props :
      $graph  array ['nodes' => [...], 'edges' => [...]] — voir CompetencyGraphService::graphFor()
      $user   \App\Models\User courant (pour le statut de maîtrise par nœud)
--}}
@props([
    'graph' => ['nodes' => [], 'edges' => []],
    'user' => null,
])
@php
    $graphService = app(\Modules\Academy\Services\CompetencyGraphService::class);
    $enabled = $graphService->isEnabled();
    $nodes = $graph['nodes'] ?? [];
    $edges = $graph['edges'] ?? [];

    // Prérequis par compétence (to => [ {from, mastery_threshold, weight}, ... ]).
    $prereqsByNode = [];
    foreach ($edges as $edge) {
        $prereqsByNode[$edge['to']][] = $edge;
    }
    $nodesById = collect($nodes)->keyBy('id');
@endphp
@if ($enabled && $nodes !== [] && $user)
    <div class="academy-competency-graph" role="region" aria-label="{{ __('Graphe de compétences du cours') }}"
         style="display: flex; flex-direction: column; gap: 10px;">
        @foreach ($nodes as $node)
            @php
                $competencyModel = \Modules\Academy\Models\Competency::find($node['id']);
                $mastery = $competencyModel ? $graphService->masteryFor($user, $competencyModel) : 0.0;
                $unlocked = $competencyModel ? $graphService->isUnlocked($user, $competencyModel) : true;
                $prereqs = $prereqsByNode[$node['id']] ?? [];

                if (! $unlocked) {
                    $statusLabel = __('Verrouillée');
                    $statusColor = '#94A3B8';
                    $statusIcon = '🔒';
                } elseif ($mastery >= 1.0) {
                    $statusLabel = __('Maîtrisée');
                    $statusColor = '#16A34A';
                    $statusIcon = '✅';
                } else {
                    $statusLabel = __('Déverrouillée');
                    $statusColor = '#0EA5E9';
                    $statusIcon = '🔓';
                }
            @endphp
            <div style="border: 1px solid #E2E8F0; border-radius: var(--sys-radius-md, 0.5rem);
                        padding: 12px 14px; background: #FFFFFF;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                    <strong style="font-size: 0.95rem;">{{ $node['name'] }}</strong>
                    <span style="display: inline-flex; align-items: center; gap: 4px;
                                 color: {{ $statusColor }}; font-size: 0.82rem; font-weight: 600;">
                        <span aria-hidden="true">{{ $statusIcon }}</span>
                        {{ $statusLabel }}
                        <span style="color: var(--sys-text-muted, #475569); font-weight: 400;">
                            ({{ (int) round($mastery * 100) }}%)
                        </span>
                    </span>
                </div>
                @if ($prereqs !== [])
                    <ul style="margin: 8px 0 0; padding-left: 18px; font-size: 0.82rem;
                               color: var(--sys-text-muted, #475569);">
                        @foreach ($prereqs as $edge)
                            <li>
                                {{ __('Nécessite') }}
                                <strong>{{ $nodesById[$edge['from']]['name'] ?? '?' }}</strong>
                                {{ __('à') }} {{ (int) round($edge['mastery_threshold'] * 100) }}%
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
@endif
