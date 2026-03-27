{{-- Vote button — usage: @include('voting::components.vote-button', ['item' => $tool, 'type' => 'tool']) --}}
@php
    $voteCount = method_exists($item, 'communityVoteCount') ? $item->communityVoteCount() : 0;
    $hasVoted = method_exists($item, 'hasVoted') ? $item->hasVoted(auth()->user()) : false;
    $tier = method_exists($item, 'getBadgeTier') ? $item->getBadgeTier() : 'none';
@endphp
<div x-data="{ count: {{ $voteCount }}, voted: {{ $hasVoted ? 'true' : 'false' }}, tier: '{{ $tier }}' }" style="display:inline-flex;align-items:center;gap:8px;">
    <button
        @click="
            @auth
                fetch('{{ route('community.vote', ['type' => $type, 'id' => $item->id]) }}', {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}
                }).then(r => r.json()).then(d => { voted = d.voted; count = d.count; tier = d.tier; })
            @else
                $dispatch('open-auth-modal', { message: '{{ __('Connectez-vous pour voter.') }}' })
            @endauth
        "
        :style="voted
            ? 'background:var(--c-primary);color:#fff;border:1px solid var(--c-primary)'
            : 'background:#fff;color:var(--c-dark);border:1px solid #e2e8f0'"
        style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:20px;cursor:pointer;font-weight:600;font-size:14px;transition:all .2s;line-height:1;"
        :aria-label="voted ? '{{ __('Retirer le vote') }}' : '{{ __('Soutenir') }}'"
    >
        <span style="font-size:16px;">👍</span>
        <span x-text="count"></span>
    </button>
    @include('voting::components.content-badge', ['tier' => $tier, 'isAdmin' => $item->is_approved ?? false])
</div>
