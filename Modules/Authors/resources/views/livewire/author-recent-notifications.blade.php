<div aria-live="polite" role="feed" aria-label="Notifications récentes" class="bg-gray-50 rounded-lg p-4" style="max-height: 400px; overflow-y: auto;">
    <h3 class="text-sm font-bold text-[#064E5A] mb-3">🔔 Activité récente</h3>

    @if($events->isEmpty())
        <p class="text-center py-6 text-gray-500 text-sm">
            Pas encore d'activité. Publie ton premier article !
        </p>
    @else
        <ul role="list" class="space-y-2">
            @foreach($events as $event)
                <li class="flex items-start gap-3 p-3 bg-white rounded-lg border border-gray-200" wire:key="event-{{ $event['type'] }}-{{ $event['created_at']?->timestamp }}-{{ $loop->index }}">
                    <span class="text-xl flex-shrink-0" aria-hidden="true">{{ $event['icon'] }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-[#064E5A] text-sm truncate">{{ $event['label'] }}</p>
                        <p class="text-xs text-gray-600 mt-1">{{ $event['detail'] }}</p>
                        @if($event['created_at'])
                            <time class="text-xs text-gray-500 mt-1 block" datetime="{{ $event['created_at']->toIso8601String() }}">
                                {{ $event['created_at']->diffForHumans() }}
                            </time>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
        <div class="mt-3 text-center text-xs text-gray-600">
            Voir tout l'historique dans l'onglet <strong>📜 Historique</strong>
        </div>
    @endif
</div>
