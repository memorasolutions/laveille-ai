{{-- Vote button style Facebook — usage: @include('voting::components.vote-button', ['item' => $tool, 'type' => 'tool']) --}}
@php
    $voteCount = method_exists($item, 'communityVoteCount') ? $item->communityVoteCount() : 0;
    $hasVoted = method_exists($item, 'hasVoted') ? $item->hasVoted(auth()->user()) : false;
    $tier = method_exists($item, 'getBadgeTier') ? $item->getBadgeTier() : 'none';
@endphp
<div x-data="{ count: {{ $voteCount }}, voted: {{ $hasVoted ? 'true' : 'false' }}, tier: '{{ $tier }}', pulse: false }" style="display:inline-flex;align-items:center;gap:4px;">
    <button
        @click="
            @auth
                pulse = true; setTimeout(() => pulse = false, 300);
                fetch('{{ route('community.vote', ['type' => $type, 'id' => $item->id]) }}', {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}
                }).then(r => r.json()).then(d => { voted = d.voted; count = d.count; tier = d.tier; })
            @else
                $dispatch('open-auth-modal', { message: '{{ __('Connectez-vous pour voter.') }}' })
            @endauth
        "
        style="background:none;border:none;cursor:pointer;padding:4px;display:inline-flex;align-items:center;gap:5px;font-size:14px;transition:transform .2s;"
        :style="pulse && 'transform:scale(1.3)'"
        :aria-label="voted ? '{{ __('Retirer le vote') }}' : '{{ __('Soutenir') }}'"
    >
        {{-- Pouce outline (pas voté) ou plein (voté) --}}
        <svg x-show="!voted" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#65676B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 22V11l5-9a2 2 0 0 1 2 2v4h5.5a2 2 0 0 1 2 2.1l-1.5 9A2 2 0 0 1 18 21H7z"/><path d="M2 13v8a1 1 0 0 0 1 1h3V12H3a1 1 0 0 0-1 1z"/></svg>
        <svg x-show="voted" x-cloak width="20" height="20" viewBox="0 0 24 24" fill="#1877F2" stroke="#1877F2" stroke-width="1"><path d="M7 22V11l5-9a2 2 0 0 1 2 2v4h5.5a2 2 0 0 1 2 2.1l-1.5 9A2 2 0 0 1 18 21H7z"/><path d="M2 13v8a1 1 0 0 0 1 1h3V12H3a1 1 0 0 0-1 1z"/></svg>
        <span :style="voted ? 'color:#1877F2;font-weight:600' : 'color:#65676B'" x-text="count > 0 ? count : ''"></span>
    </button>
    @include('voting::components.content-badge', ['tier' => $tier, 'isAdmin' => $item->is_approved ?? false])
</div>
