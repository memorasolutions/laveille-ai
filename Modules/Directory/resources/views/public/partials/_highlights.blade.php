{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Section Highlights : Ajoutés récemment + Les plus populaires --}}

@if(isset($recentTools) && $recentTools->count() > 0)
<div class="rt-highlights">
    {{-- Ajoutés récemment --}}
    <div class="rt-hl-section">
        <h3 class="rt-hl-title">🕐 {{ __('Ajoutés récemment') }}</h3>
        <div class="rt-hl-slider" x-data="{ sl: 0 }">
            <button type="button" class="rt-hl-arrow left" aria-label="{{ __('Défiler vers la gauche') }}" x-show="sl > 0" x-cloak @click="$refs.recentTrack.scrollBy({ left: -400, behavior: 'smooth' })"><i class="ti-angle-left" aria-hidden="true"></i></button>
            <div class="rt-hl-track" x-ref="recentTrack" @scroll="sl = $refs.recentTrack.scrollLeft">
                @foreach($recentTools as $tool)
                    @include('directory::public.partials._highlight_card', ['tool' => $tool])
                @endforeach
            </div>
            <button type="button" class="rt-hl-arrow right" aria-label="{{ __('Défiler vers la droite') }}" @click="$refs.recentTrack.scrollBy({ left: 400, behavior: 'smooth' })"><i class="ti-angle-right" aria-hidden="true"></i></button>
        </div>
    </div>

    {{-- Les plus populaires --}}
    @if(isset($popularTools) && $popularTools->count() > 0)
    <div class="rt-hl-section" style="margin-top: 24px;">
        <h3 class="rt-hl-title">🔥 {{ __('Les plus populaires') }}</h3>
        <div class="rt-hl-slider" x-data="{ sl: 0 }">
            <button type="button" class="rt-hl-arrow left" aria-label="{{ __('Défiler vers la gauche') }}" x-show="sl > 0" x-cloak @click="$refs.popTrack.scrollBy({ left: -400, behavior: 'smooth' })"><i class="ti-angle-left" aria-hidden="true"></i></button>
            <div class="rt-hl-track" x-ref="popTrack" @scroll="sl = $refs.popTrack.scrollLeft">
                @foreach($popularTools as $tool)
                    @include('directory::public.partials._highlight_card', ['tool' => $tool])
                @endforeach
            </div>
            <button type="button" class="rt-hl-arrow right" aria-label="{{ __('Défiler vers la droite') }}" @click="$refs.popTrack.scrollBy({ left: 400, behavior: 'smooth' })"><i class="ti-angle-right" aria-hidden="true"></i></button>
        </div>
    </div>
    @endif
</div>
@endif
