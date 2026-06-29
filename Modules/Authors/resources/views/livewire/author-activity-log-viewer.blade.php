<div class="bg-gray-50 rounded-lg p-6">
    <h2 class="text-xl font-bold mb-4 text-[#064E5A]">📜 Historique des modifications (Loi 25 art. 17)</h2>

    <div class="flex flex-wrap gap-3 mb-4" role="toolbar" aria-label="Filtres historique">
        <div class="flex gap-2" role="group" aria-label="Période">
            @foreach(['today' => "Aujourd'hui", '7d' => '7 jours', '30d' => '30 jours', 'all' => 'Tout'] as $key => $label)
                <button type="button" wire:click="setPeriod('{{ $key }}')"
                    class="px-3 py-2 rounded text-sm font-medium min-h-[44px] {{ $period === $key ? 'bg-[#064E5A] text-white' : 'bg-white text-[#064E5A] border border-[#064E5A]' }}"
                    aria-pressed="{{ $period === $key ? 'true' : 'false' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <div class="flex gap-2" role="group" aria-label="Type">
            @foreach(['all' => 'Tout', 'author_post' => 'Articles', 'author_profile' => 'Profil'] as $key => $label)
                <button type="button" wire:click="setLogName('{{ $key }}')"
                    class="px-3 py-2 rounded text-sm font-medium min-h-[44px] {{ $logName === $key ? 'bg-[#9A2A06] text-white' : 'bg-white text-[#9A2A06] border border-[#9A2A06]' }}"
                    aria-pressed="{{ $logName === $key ? 'true' : 'false' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    @if($activities->isEmpty())
        <p class="text-center py-12 text-gray-600 bg-white rounded-lg">
            Aucune activité enregistrée pour cette période.
        </p>
    @else
        <div class="overflow-x-auto bg-white rounded-lg">
            <table class="min-w-full text-sm" role="table" aria-label="Liste des modifications">
                <thead class="bg-[#F8FAFB]">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-[#064E5A]">Date</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-[#064E5A]">Action</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-[#064E5A]">Sujet</th>
                        <th scope="col" class="px-4 py-3 text-left font-semibold text-[#064E5A]">Détails</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activities as $activity)
                        <tr class="border-t border-gray-200" wire:key="activity-{{ $activity->id }}">
                            <td class="px-4 py-3 text-gray-700">
                                <time datetime="{{ $activity->created_at->toIso8601String() }}">
                                    {{ $activity->created_at->diffForHumans() }}
                                </time>
                            </td>
                            <td class="px-4 py-3 text-gray-800">{{ ucfirst($activity->description ?? $activity->event ?? 'modifié') }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ class_basename($activity->subject_type ?? '') }}
                                @if($activity->subject_id)
                                    #{{ $activity->subject_id }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">
                                @if($activity->properties && $activity->properties->isNotEmpty())
                                    <details>
                                        <summary class="cursor-pointer text-[#064E5A]">Voir changements</summary>
                                        <pre class="mt-2 bg-gray-50 p-2 rounded overflow-x-auto">{{ $activity->properties->toJson(JSON_PRETTY_PRINT) }}</pre>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('backoffice::partials.infinite-scroll', ['paginator' => \$activities])
    @endif

    <p class="mt-6 text-xs text-gray-500">
        Conforme Loi 25 (QC) art. 17 — droit à la rectification et traçabilité ·
        RGPD Art. 30 — registre des activités de traitement
    </p>
</div>
