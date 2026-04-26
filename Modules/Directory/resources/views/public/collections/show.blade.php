<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('fronttheme::layouts.master')

@section('title', $collection->name)
@section('meta_description', $collection->description ?: 'Collection d\'outils ' . $collection->name . ' — ' . $collection->tools->count() . ' outils sélectionnés.')

@section('content')
<section class="wpo-blog-pg-section section-padding">
    <div class="container">

        {{-- Header collection --}}
        <div style="background: #fff; border: 1px solid #e8e8e8; border-radius: 8px; padding: 30px; margin-bottom: 40px;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                <div style="flex: 1; min-width: 260px;">
                    <h2 style="color: var(--c-primary, #0B7285); margin: 0 0 10px 0; font-size: 28px;">{{ $collection->name }}</h2>
                    @if($collection->description)
                        <p style="color: #666; font-size: 15px; line-height: 1.7; margin-bottom: 12px;">{{ $collection->description }}</p>
                    @endif
                    <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                        <span class="badge" style="background-color: var(--c-primary, #0B7285); color: #fff; font-size: 13px; padding: 5px 14px; border-radius: 12px;">
                            {{ $collection->tools->count() }} {{ __('outils') }}
                        </span>
                        <span style="font-size: 14px; color: #595959;">
                            <i class="fa fa-user" style="margin-right: 4px;"></i>
                            {{ __('Par') }} <strong style="color: #555;">{{ $collection->user->name }}</strong>
                        </span>
                    </div>
                </div>
                <a href="{{ route('collections.index') }}" class="btn btn-sm" style="background-color: #f5f5f5; color: #555; border: 1px solid #ddd; border-radius: 4px; padding: 8px 18px; font-size: 13px;">
                    <i class="fa fa-arrow-left" style="margin-right: 4px;"></i> {{ __('Retour') }}
                </a>
            </div>
        </div>

        {{-- Contenu --}}
        @if($collection->tools->count())
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 24px;">
                @foreach($collection->tools as $tool)
                    @php
                        $screenshotUrl = null;
                        if ($tool->screenshot) {
                            $screenshotUrl = str_starts_with($tool->screenshot, 'http')
                                ? $tool->screenshot
                                : asset($tool->screenshot) . '?v=' . ($tool->updated_at?->timestamp ?? '0');
                        }
                        $host = $tool->url ? parse_url($tool->url, PHP_URL_HOST) : null;
                        $pricingLabels = \Modules\Directory\Support\PricingCategories::labels();
                        $pricingColors = \Modules\Directory\Support\PricingCategories::colors();
                    @endphp
                    <article style="background:#fff; border:1px solid #E5E7EB; border-radius:12px; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 1px 3px rgba(0,0,0,0.04); transition:transform .25s, box-shadow .25s;"
                             onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 25px -5px rgba(0,0,0,0.1)';"
                             onmouseout="this.style.transform='';this.style.boxShadow='0 1px 3px rgba(0,0,0,0.04)';">
                        <a href="{{ route('directory.show', $tool->slug) }}" style="display:block; text-decoration:none; color:inherit;">
                            @if($screenshotUrl)
                                <div style="position:relative; height:140px; overflow:hidden;">
                                    <img src="{{ $screenshotUrl }}" alt="{{ $tool->name }}" loading="lazy" style="width:100%; height:140px; object-fit:cover; display:block;">
                                    <div style="position:absolute; inset:0; background:linear-gradient(to bottom, rgba(0,0,0,0.15) 0%, rgba(0,0,0,0.55) 100%);"></div>
                                </div>
                            @else
                                @php
                                    $gradients = [['#0B7285','#1a365d'],['#8E44AD','#2C3E50'],['#E67E22','#C0392B'],['#2ECC71','#16A085'],['#3498DB','#2980B9']];
                                    $idx = abs(crc32($tool->name)) % count($gradients);
                                    [$c1, $c2] = $gradients[$idx];
                                @endphp
                                <div style="height:140px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg, {{ $c1 }} 0%, {{ $c2 }} 100%);">
                                    <span style="color:rgba(255,255,255,0.9); font-weight:700; font-size:1.1rem; text-shadow:0 1px 3px rgba(0,0,0,0.3); padding:0 12px; text-align:center;">{{ $tool->name }}</span>
                                </div>
                            @endif
                        </a>
                        <div style="padding:18px; display:flex; flex-direction:column; flex-grow:1;">
                            <div style="display:flex; align-items:flex-start; gap:10px; margin-bottom:10px;">
                                @if($host)
                                    <x-core::smart-favicon :domain="$host" :size="40" class="" />
                                @endif
                                <div style="flex:1; min-width:0;">
                                    <h5 style="margin:0 0 6px 0; font-size:16px; font-weight:700; line-height:1.3;">
                                        <a href="{{ route('directory.show', $tool->slug) }}" style="color:#1a1a1a; text-decoration:none;">{{ $tool->name }}</a>
                                    </h5>
                                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                        @if($tool->pricing)
                                            @php
                                                $pl = $pricingLabels[$tool->pricing] ?? ucfirst($tool->pricing);
                                                [$bg, $fg] = $pricingColors[$tool->pricing] ?? ['#F3F4F6', '#374151'];
                                            @endphp
                                            <span style="background:{{ $bg }}; color:{{ $fg }}; font-size:10px; font-weight:700; text-transform:uppercase; padding:3px 8px; border-radius:4px; letter-spacing:0.5px;">{{ $pl }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <p style="color:#4B5563; font-size:13px; line-height:1.55; margin:0 0 12px 0; flex-grow:1;">{{ Str::limit($tool->short_description, 110) }}</p>
                            <div style="display:flex; justify-content:space-between; align-items:center; padding-top:12px; border-top:1px solid #F3F4F6; font-size:12px; color:#6B7280;">
                                @if($tool->clicks_count)
                                    <span><i class="fa fa-mouse-pointer" style="margin-right:4px;"></i>{{ $tool->clicks_count }} {{ __('clics') }}</span>
                                @else
                                    <span>&nbsp;</span>
                                @endif
                                <a href="{{ route('directory.show', $tool->slug) }}" style="color:var(--c-primary, #0B7285); text-decoration:none; font-weight:600;">{{ __('Voir') }} →</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 60px 20px; background: #f9f9f9; border-radius: 8px;">
                <i class="fa fa-folder-open-o" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 15px;"></i>
                <p style="color: #595959; font-size: 16px; margin: 0;">{{ __('Cette collection est vide.') }}</p>
            </div>
        @endif

    </div>
</section>
@endsection
