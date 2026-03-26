{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Section Highlights : Ajoutes recemment + Les plus populaires --}}

@if(isset($recentTools) && $recentTools->count() > 0)
<div class="rt-highlights">
    {{-- Ajoutes recemment --}}
    <div class="rt-hl-section">
        <h3 class="rt-hl-title"><i class="fa fa-clock-o"></i> {{ __('Ajoutes recemment') }}</h3>
        <div class="rt-hl-scroll">
            @foreach($recentTools as $tool)
                @include('directory::public.partials._highlight_card', ['tool' => $tool])
            @endforeach
        </div>
    </div>

    {{-- Les plus populaires --}}
    @if(isset($popularTools) && $popularTools->count() > 0)
    <div class="rt-hl-section" style="margin-top: 24px;">
        <h3 class="rt-hl-title"><i class="fa fa-fire"></i> {{ __('Les plus populaires') }}</h3>
        <div class="rt-hl-scroll">
            @foreach($popularTools as $tool)
                @include('directory::public.partials._highlight_card', ['tool' => $tool])
            @endforeach
        </div>
    </div>
    @endif
</div>
@endif
