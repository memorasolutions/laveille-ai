<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Statistiques') . ' - ' . $shortUrl->slug . ' - ' . config('app.name'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Statistiques du lien')])
@endsection

@section('content')
<section class="section-padding" style="padding-top: 20px;">
<div class="container" style="max-width: 800px; margin: 0 auto;">

    {{-- Header --}}
    <div class="text-center" style="margin-bottom: 32px;">
        <a href="{{ $shortUrl->getShortUrl() }}" target="_blank"
            style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-size: 1.8rem; font-weight: 800; color: var(--c-primary, #0B7285); text-decoration: none; display: block; margin-bottom: 8px; word-break: break-all;">
            {{ $shortUrl->getShortUrl() }}
        </a>
        <div style="font-size: 14px; color: var(--c-text-muted, #6E7687); word-break: break-all; margin-bottom: 12px;">
            ↗️ {{ Str::limit($shortUrl->original_url, 80) }}
        </div>
        @if($shortUrl->expires_at)
            <span style="background: #FFFBEB; color: #92400E; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; border: 1px solid #FDE68A;">
                ⏰ {{ __('Expire le') }} {{ $shortUrl->expires_at->format('d/m/Y') }}
            </span>
        @endif
    </div>

    {{-- Résumé --}}
    @php
        $totalClicks = $analytics['total_clicks'] ?? 0;
        $clicksByDay = $analytics['clicks_by_day'] ?? [];
        $todayClicks = collect($clicksByDay)->where('date', now()->toDateString())->first()['count'] ?? 0;
        $maxDaily = collect($clicksByDay)->max('count') ?: 1;
    @endphp
    <div style="display: flex !important; flex-wrap: wrap !important; gap: 12px; margin-bottom: 32px;">
        <div style="flex: 1 !important; min-width: 120px; background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 20px; text-align: center;">
            <div style="font-size: 2rem; font-weight: 800; color: var(--c-primary, #0B7285);">{{ number_format($totalClicks) }}</div>
            <div style="font-size: 13px; color: var(--c-text-muted, #6E7687);">{{ __('Total clics') }}</div>
        </div>
        <div style="flex: 1 !important; min-width: 120px; background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 20px; text-align: center;">
            <div style="font-size: 2rem; font-weight: 800; color: var(--c-dark, #1A1D23);">{{ $todayClicks }}</div>
            <div style="font-size: 13px; color: var(--c-text-muted, #6E7687);">{{ __("Aujourd'hui") }}</div>
        </div>
        <div style="flex: 1 !important; min-width: 120px; background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 20px; text-align: center;">
            <div style="font-size: 2rem; font-weight: 800; color: var(--c-dark, #1A1D23);">{{ $shortUrl->created_at->diffForHumans(null, true, 1) }}</div>
            <div style="font-size: 13px; color: var(--c-text-muted, #6E7687);">{{ __('Actif depuis') }}</div>
        </div>
    </div>

    {{-- Graphique clics par jour --}}
    @if(count($clicksByDay) > 0)
    <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
        <h3 style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; font-size: 1rem; color: var(--c-dark, #1A1D23); margin-bottom: 16px;">
            📊 {{ __('Clics par jour') }}
        </h3>
        <div style="display: flex !important; align-items: flex-end !important; justify-content: center !important; height: 160px; gap: 3px; overflow-x: auto; padding-bottom: 24px;">
            @foreach(array_slice($clicksByDay, -30) as $day)
                <div style="flex: 1 !important; min-width: 16px; max-width: 40px; display: flex !important; flex-direction: column !important; align-items: center !important; justify-content: flex-end !important; height: 100%;" title="{{ $day['date'] }} — {{ $day['count'] }} {{ __('clics') }}">
                    <div style="width: 100%; background: var(--c-primary, #0B7285); border-radius: 4px 4px 0 0; min-height: 2px; height: {{ ($day['count'] / $maxDaily) * 100 }}%; transition: height .3s;"></div>
                    <span style="font-size: 9px; color: var(--c-text-muted, #6E7687); margin-top: 4px; white-space: nowrap;">{{ \Carbon\Carbon::parse($day['date'])->format('d') }}</span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="row" style="display: flex !important; flex-wrap: wrap !important; gap: 16px; margin-bottom: 24px;">
        {{-- Referrers --}}
        <div class="col-md-6 col-sm-12" style="flex: 1 !important; min-width: 280px;">
            <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 20px; height: 100%;">
                <h3 style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; font-size: 1rem; color: var(--c-dark, #1A1D23); margin-bottom: 16px;">
                    🌐 {{ __('Sources') }}
                </h3>
                @php $maxRef = collect($analytics['top_referrers'] ?? [])->max('count') ?: 1; @endphp
                @forelse($analytics['top_referrers'] ?? [] as $ref)
                    <div style="margin-bottom: 10px;">
                        <div style="display: flex !important; justify-content: space-between !important; font-size: 13px; margin-bottom: 4px;">
                            <span style="color: var(--c-dark, #1A1D23); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;">{{ parse_url($ref['referrer'], PHP_URL_HOST) ?: __('Direct') }}</span>
                            <span style="color: var(--c-text-muted, #6E7687); font-weight: 600;">{{ $ref['count'] }}</span>
                        </div>
                        <div style="height: 6px; background: #F3F4F6; border-radius: 3px; overflow: hidden;">
                            <div style="height: 100%; width: {{ ($ref['count'] / $maxRef) * 100 }}%; background: var(--c-primary, #0B7285); border-radius: 3px;"></div>
                        </div>
                    </div>
                @empty
                    <p style="color: var(--c-text-muted, #6E7687); font-size: 13px;">{{ __('Pas encore de données.') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Appareils --}}
        <div class="col-md-6 col-sm-12" style="flex: 1 !important; min-width: 280px;">
            <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 20px; height: 100%;">
                <h3 style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; font-size: 1rem; color: var(--c-dark, #1A1D23); margin-bottom: 16px;">
                    💻 {{ __('Appareils') }}
                </h3>
                @php
                    $devices = collect($analytics['devices'] ?? []);
                    $totalDev = $devices->sum('count') ?: 1;
                    $deviceIcons = ['desktop' => 'fa-desktop', 'mobile' => 'fa-mobile', 'tablet' => 'fa-tablet'];
                    $deviceNames = ['desktop' => __('Ordinateur'), 'mobile' => __('Mobile'), 'tablet' => __('Tablette')];
                @endphp
                <div style="display: flex !important; justify-content: space-around !important; text-align: center;">
                    @foreach(['desktop', 'mobile', 'tablet'] as $dtype)
                        @php $dcount = $devices->firstWhere('device_type', $dtype)['count'] ?? 0; @endphp
                        <div>
                            <i class="fa {{ $deviceIcons[$dtype] }}" style="font-size: 28px; color: var(--c-primary, #0B7285); margin-bottom: 8px; display: block;"></i>
                            <div style="font-size: 1.3rem; font-weight: 800; color: var(--c-dark, #1A1D23);">{{ round(($dcount / $totalDev) * 100) }}%</div>
                            <div style="font-size: 12px; color: var(--c-text-muted, #6E7687);">{{ $deviceNames[$dtype] }}</div>
                        </div>
                    @endforeach
                </div>

                {{-- Navigateurs --}}
                <h4 style="font-weight: 700; font-size: 0.9rem; color: var(--c-dark, #1A1D23); margin: 20px 0 12px; border-top: 1px solid #E5E7EB; padding-top: 16px;">
                    🌐 {{ __('Navigateurs') }}
                </h4>
                @forelse(array_slice($analytics['browsers'] ?? [], 0, 5) as $browser)
                    <div style="display: flex !important; justify-content: space-between !important; padding: 4px 0; font-size: 13px;">
                        <span style="color: var(--c-dark, #1A1D23);">{{ $browser['browser'] }}</span>
                        <span style="color: var(--c-text-muted, #6E7687);">{{ $browser['count'] }}</span>
                    </div>
                @empty
                    <p style="color: var(--c-text-muted, #6E7687); font-size: 13px;">{{ __('Pas encore de données.') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- CTA --}}
    <div class="text-center" style="margin-top: 32px;">
        <a href="{{ route('shorturl.create') }}"
            style="display: inline-block; background: var(--c-primary, #0B7285); color: #fff; padding: 12px 28px; border-radius: 10px; font-weight: 700; font-size: 15px; text-decoration: none; transition: background .2s;"
            onmouseover="this.style.background='#096474'" onmouseout="this.style.background='var(--c-primary, #0B7285)'">
            🔗 {{ __('Raccourcir un autre lien') }}
        </a>
    </div>

</div>
</section>
@endsection
