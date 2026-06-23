{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@php
    // Styles de pastille par statut de participation (contraste AA sur fond clair).
    $statusStyles = [
        'completed'     => 'background: #DCFCE7; color: #166534; border: 1px solid #86EFAC;',
        'in_progress'   => 'background: #DBEAFE; color: #1E40AF; border: 1px solid #93C5FD;',
        'never_started' => 'background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5;',
    ];
    // Étiquettes de type d'évènement pour le filtre du journal.
    $eventTypes = [
        'item_viewed'    => 'Consultation',
        'item_completed' => 'Complétion',
        'quiz_attempt'   => 'Tentative de quiz',
        'submission'     => 'Remise de devoir',
    ];
@endphp
<div style="display: flex; flex-direction: column; gap: 24px; font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23);">

    {{-- ───────────────────────── Onglets ───────────────────────── --}}
    <div role="tablist" aria-label="Type de rapport" class="d-flex flex-wrap" style="gap: 8px; border-bottom: 1px solid #E5E7EB; padding-bottom: 4px;">
        <button type="button" role="tab" wire:click="setTab('participation')"
                aria-selected="{{ $tab === 'participation' ? 'true' : 'false' }}"
                style="border: 0; background: transparent; cursor: pointer; padding: 10px 14px; min-height: 44px; font-weight: 600; border-bottom: 3px solid {{ $tab === 'participation' ? 'var(--sys-action-primary, #064E5A)' : 'transparent' }}; color: {{ $tab === 'participation' ? 'var(--sys-action-primary, #064E5A)' : 'var(--sys-text-muted, #6B7280)' }};">
            Participation
        </button>
        <button type="button" role="tab" wire:click="setTab('journal')"
                aria-selected="{{ $tab === 'journal' ? 'true' : 'false' }}"
                style="border: 0; background: transparent; cursor: pointer; padding: 10px 14px; min-height: 44px; font-weight: 600; border-bottom: 3px solid {{ $tab === 'journal' ? 'var(--sys-action-primary, #064E5A)' : 'transparent' }}; color: {{ $tab === 'journal' ? 'var(--sys-action-primary, #064E5A)' : 'var(--sys-text-muted, #6B7280)' }};">
            Journal d'activité
        </button>
    </div>

    {{-- ════════════════════ RAPPORT DE PARTICIPATION ════════════════════ --}}
    @if ($tab === 'participation')
        @php $rows = $this->participation; @endphp
        <section aria-labelledby="reports-participation">
            <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap: 12px; margin-bottom: 14px;">
                <h2 id="reports-participation" style="font-family: var(--f-heading); margin: 0; font-size: 1.2rem;">
                    Participation par étudiant
                </h2>
                @if ($rows->isNotEmpty())
                    <x-core::button
                        :href="route('academy.courses.reports.participation.csv', $this->course->slug)"
                        variant="secondary" size="sm">
                        <span aria-hidden="true">⬇</span> Exporter en CSV
                    </x-core::button>
                @endif
            </div>

            @if ($rows->isEmpty())
                <p style="color: var(--sys-text-muted, #6B7280);">Aucun étudiant inscrit pour le moment.</p>
            @else
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <caption class="visually-hidden">Tableau de participation des étudiants inscrits</caption>
                        <thead>
                            <tr style="text-align: left; border-bottom: 2px solid #E5E7EB;">
                                <th scope="col" style="padding: 10px 8px;">Étudiant</th>
                                <th scope="col" style="padding: 10px 8px;">Statut</th>
                                <th scope="col" style="padding: 10px 8px;">Progression</th>
                                <th scope="col" style="padding: 10px 8px;">Items</th>
                                <th scope="col" style="padding: 10px 8px;">Note finale</th>
                                <th scope="col" style="padding: 10px 8px;">Dernière activité</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr style="border-bottom: 1px solid #F3F4F6;">
                                    <td style="padding: 10px 8px;">
                                        <span style="font-weight: 600;">{{ $row['name'] }}</span><br>
                                        <span style="font-size: 0.8rem; color: var(--sys-text-muted, #6B7280);">{{ $row['email'] }}</span>
                                    </td>
                                    <td style="padding: 10px 8px;">
                                        <span style="display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 600; {{ $statusStyles[$row['status_key']] ?? '' }}">
                                            {{ $row['status'] }}
                                        </span>
                                    </td>
                                    <td style="padding: 10px 8px;">{{ $row['percent'] }} %</td>
                                    <td style="padding: 10px 8px;">{{ $row['items_completed'] }} / {{ $row['items_total'] }}</td>
                                    <td style="padding: 10px 8px;">
                                        @if ($row['grade']['hasWeighting'])
                                            {{ number_format($row['grade']['final'], 1, ',', ' ') }} %
                                            @if ($row['grade']['letter'] !== '')
                                                <span style="color: var(--sys-text-muted, #6B7280);">({{ $row['grade']['letter'] }})</span>
                                            @endif
                                        @else
                                            <span style="color: var(--sys-text-muted, #6B7280);">n/d</span>
                                        @endif
                                    </td>
                                    <td style="padding: 10px 8px;">
                                        @if ($row['last_activity'] !== null)
                                            {{ $row['last_activity']->copy()->timezone('America/Toronto')->format('Y-m-d H:i') }}
                                        @else
                                            <span style="color: var(--sys-text-muted, #6B7280);">Aucune</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif

    {{-- ════════════════════ JOURNAL D'ACTIVITÉ ════════════════════ --}}
    @if ($tab === 'journal')
        @php $log = $this->activityLog; @endphp
        <section aria-labelledby="reports-journal">
            <h2 id="reports-journal" style="font-family: var(--f-heading); margin: 0 0 14px; font-size: 1.2rem;">
                Journal d'activité
            </h2>

            {{-- Filtres --}}
            <div class="d-flex flex-wrap" style="gap: 14px; margin-bottom: 16px;">
                <div style="flex: 1 1 220px; min-width: 200px;">
                    <label for="filter-user" style="display: block; font-size: 0.8rem; color: var(--sys-text-muted, #6B7280); margin-bottom: 4px;">Filtrer par étudiant</label>
                    <select id="filter-user" wire:model.live="filterUser"
                            style="width: 100%; min-height: 44px; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                        <option value="0">Tous les étudiants</option>
                        @foreach ($this->enrolledUsers as $uid => $uname)
                            <option value="{{ $uid }}">{{ $uname }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="flex: 1 1 220px; min-width: 200px;">
                    <label for="filter-type" style="display: block; font-size: 0.8rem; color: var(--sys-text-muted, #6B7280); margin-bottom: 4px;">Filtrer par type</label>
                    <select id="filter-type" wire:model.live="filterType"
                            style="width: 100%; min-height: 44px; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                        <option value="">Tous les types</option>
                        @foreach ($eventTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if ($log->total() === 0)
                <p style="color: var(--sys-text-muted, #6B7280);">Aucun évènement enregistré pour ce filtre.</p>
            @else
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <caption class="visually-hidden">Journal chronologique des évènements du cours</caption>
                        <thead>
                            <tr style="text-align: left; border-bottom: 2px solid #E5E7EB;">
                                <th scope="col" style="padding: 10px 8px;">Date (Québec)</th>
                                <th scope="col" style="padding: 10px 8px;">Étudiant</th>
                                <th scope="col" style="padding: 10px 8px;">Évènement</th>
                                <th scope="col" style="padding: 10px 8px;">Item concerné</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($log as $event)
                                <tr style="border-bottom: 1px solid #F3F4F6;">
                                    <td style="padding: 10px 8px; white-space: nowrap;">{{ $event['at']->copy()->timezone('America/Toronto')->format('Y-m-d H:i') }}</td>
                                    <td style="padding: 10px 8px;">{{ $event['user_name'] }}</td>
                                    <td style="padding: 10px 8px;">{{ $event['type_label'] }}</td>
                                    <td style="padding: 10px 8px;">{{ $event['item'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination manuelle (collection dérivée). --}}
                @if ($log->lastPage() > 1)
                    <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap: 12px; margin-top: 16px;">
                        <span style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280);">
                            Page {{ $log->currentPage() }} sur {{ $log->lastPage() }} ({{ $log->total() }} évènements)
                        </span>
                        <div class="d-flex" style="gap: 8px;">
                            <x-core::button type="button" wire:click="gotoLogPage({{ $log->currentPage() - 1 }})"
                                            variant="ghost" size="sm" :disabled="$log->onFirstPage()">
                                Précédent
                            </x-core::button>
                            <x-core::button type="button" wire:click="gotoLogPage({{ $log->currentPage() + 1 }})"
                                            variant="ghost" size="sm" :disabled="! $log->hasMorePages()">
                                Suivant
                            </x-core::button>
                        </div>
                    </div>
                @endif
            @endif
        </section>
    @endif
</div>
