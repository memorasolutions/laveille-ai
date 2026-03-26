{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Section Highlights : Ajoutés récemment + Les plus populaires (slider Alpine.js) --}}

@if(isset($recentTools) && $recentTools->count() > 0)
<div class="rt-highlights">
    {{-- Ajoutés récemment --}}
    <div class="rt-hl-section">
        <h3 class="rt-hl-title"><i class="fa fa-clock-o"></i> {{ __('Ajoutés récemment') }}</h3>
        <div x-data="{ offset: 0, step: 212 }" x-init="$data.total = $el.querySelectorAll('.rt-hl-card').length" class="rt-hl-slider">
            <div class="rt-hl-arrow left" @click="offset = Math.max(0, offset - step)" x-show="offset > 0" x-cloak>
                <i class="fa fa-chevron-left"></i>
            </div>
            <div class="rt-hl-track" :style="`transform: translateX(-${offset}px)`">
                @foreach($recentTools as $tool)
                    @include('directory::public.partials._highlight_card', ['tool' => $tool])
                @endforeach
            </div>
            <div class="rt-hl-arrow right" @click="offset = Math.min(total * step - $el.offsetWidth, offset + step)" x-show="offset < (total * step - $el.closest('.rt-hl-slider').offsetWidth)" x-cloak>
                <i class="fa fa-chevron-right"></i>
            </div>
        </div>
    </div>

    {{-- Les plus populaires --}}
    @if(isset($popularTools) && $popularTools->count() > 0)
    <div class="rt-hl-section" style="margin-top: 24px;">
        <h3 class="rt-hl-title"><i class="fa fa-fire"></i> {{ __('Les plus populaires') }}</h3>
        <div x-data="{ offset: 0, step: 212 }" x-init="$data.total = $el.querySelectorAll('.rt-hl-card').length" class="rt-hl-slider">
            <div class="rt-hl-arrow left" @click="offset = Math.max(0, offset - step)" x-show="offset > 0" x-cloak>
                <i class="fa fa-chevron-left"></i>
            </div>
            <div class="rt-hl-track" :style="`transform: translateX(-${offset}px)`">
                @foreach($popularTools as $tool)
                    @include('directory::public.partials._highlight_card', ['tool' => $tool])
                @endforeach
            </div>
            <div class="rt-hl-arrow right" @click="offset = Math.min(total * step - $el.offsetWidth, offset + step)" x-show="offset < (total * step - $el.closest('.rt-hl-slider').offsetWidth)" x-cloak>
                <i class="fa fa-chevron-right"></i>
            </div>
        </div>
    </div>
    @endif
</div>
@endif
