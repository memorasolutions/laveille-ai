<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Classement') . ' - ' . config('app.name'))
@section('meta_description', __('Les membres les plus actifs de la communauté La veille. Contribuez et montez dans le classement !'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Classement')])
@endsection

@section('content')
@php
    $getLevel = function($lvl) {
        if (class_exists(\Modules\Directory\Services\ReputationService::class)) {
            return \Modules\Directory\Services\ReputationService::getLevelInfo($lvl);
        }
        return ['emoji' => '👤', 'name' => 'Membre', 'next_threshold' => null];
    };
    $maxPoints = $topAllTime->first()?->reputation_points ?: 1;
@endphp

<section class="section-padding" style="padding-top: 20px;">
<div class="container">

    {{-- Hero --}}
    <div class="text-center" style="margin-bottom: 36px;">
        <h1 style="font-family: var(--f-heading); font-weight: 800; font-size: 2rem; color: var(--c-dark); margin-bottom: 8px;">
            🏆 {{ __('Classement de la communauté') }}
        </h1>
        <p style="font-size: 1.1rem; color: #6B7280;">{{ __('Les membres les plus actifs de La veille') }}</p>
    </div>

    {{-- Ma position --}}
    @auth
    @php
        $myRank = $topAllTime->search(fn($u) => $u->id === auth()->id());
        $rankDisplay = $myRank !== false ? '#' . ($myRank + 1) : '-';
        $myLvl = $getLevel(auth()->user()->trust_level ?? 0);
    @endphp
    <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.04); margin-bottom: 32px; overflow: hidden;">
        <div style="padding: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 50px; height: 50px; background: #E0E7FF; color: #4F46E5; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700;">{{ substr(auth()->user()->name, 0, 1) }}</div>
                <div>
                    <div style="font-weight: 700; font-size: 16px; color: var(--c-dark);">{{ __('Votre position') }}</div>
                    <span style="font-size: 13px; color: #9CA3AF;">{{ $myLvl['emoji'] }} {{ $myLvl['name'] }}</span>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 24px; font-weight: 800; color: var(--c-dark);">{{ $rankDisplay }}</div>
                <div style="font-size: 14px; color: var(--c-primary); font-weight: 600;">{{ auth()->user()->reputation_points ?? 0 }} {{ __('pts') }}</div>
            </div>
        </div>
        <div style="height: 5px; background: #F3F4F6;">
            <div style="height: 100%; background: linear-gradient(90deg, var(--c-primary), var(--c-accent)); width: {{ min(100, max(1, ((auth()->user()->reputation_points ?? 0) / max(1, $maxPoints)) * 100)) }}%; border-radius: 0 3px 3px 0;"></div>
        </div>
    </div>
    @endauth

    @if($topAllTime->isEmpty())
        {{-- Empty state --}}
        <div style="text-align: center; padding: 60px 20px; background: #F9FAFB; border-radius: 16px; border: 1px dashed #D1D5DB;">
            <div style="font-size: 48px; margin-bottom: 12px;">🌱</div>
            <h3 style="font-family: var(--f-heading); color: var(--c-dark); margin-bottom: 8px;">{{ __('La communauté vient de démarrer !') }}</h3>
            <p style="color: #6B7280;">{{ __('Soyez le premier à contribuer et à gagner des points.') }}</p>
        </div>
    @else
        {{-- Podium Top 3 --}}
        @if($topAllTime->count() >= 3)
        <div class="row" style="display: flex; align-items: flex-end; justify-content: center; margin-bottom: 40px; flex-wrap: wrap;">
            @foreach([1, 0, 2] as $podiumIndex)
                @if(isset($topAllTime[$podiumIndex]))
                @php
                    $u = $topAllTime[$podiumIndex];
                    $lvl = $getLevel($u->trust_level);
                    $isFirst = $podiumIndex === 0;
                    $colors = ['#FFD700', '#C0C0C0', '#CD7F32'];
                    $medals = ['🥇', '🥈', '🥉'];
                @endphp
                <div class="col-sm-4 col-xs-12" style="margin-bottom: 16px;">
                    <div style="background: #fff; border-radius: 16px; padding: 24px; text-align: center; box-shadow: 0 6px 20px rgba(0,0,0,0.06); border-bottom: 4px solid {{ $colors[$podiumIndex] }}; {{ $isFirst ? 'transform: scale(1.05);' : '' }}">
                        <div style="font-size: 36px; margin-bottom: 8px;">{{ $medals[$podiumIndex] }}</div>
                        <h3 style="font-family: var(--f-heading); margin: 8px 0; font-weight: 700; font-size: 17px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><a href="{{ route('directory.profile', $u->id) }}" style="color: inherit; text-decoration: none;">{{ $u->name }}</a></h3>
                        <div style="background: #F3F4F6; display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; margin-bottom: 12px;">{{ $lvl['emoji'] }} {{ $lvl['name'] }}</div>
                        <div style="font-weight: 800; font-size: 22px; color: {{ $colors[$podiumIndex] }};">{{ $u->reputation_points }} <span style="font-size: 11px; color: #9CA3AF;">pts</span></div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
        @endif

        {{-- Table 4-10 --}}
        @if($topAllTime->count() > 3)
        <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.03); margin-bottom: 30px;">
            <table class="table table-hover" style="margin-bottom: 0;">
                <tbody>
                    @foreach($topAllTime->slice(3) as $key => $u)
                    @php $lvl = $getLevel($u->trust_level); @endphp
                    <tr>
                        <td style="vertical-align: middle; width: 50px; font-weight: 700; color: #9CA3AF; text-align: center;">{{ $key + 1 }}</td>
                        <td style="vertical-align: middle;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 34px; height: 34px; background: #E0E7FF; color: #4F46E5; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">{{ substr($u->name, 0, 1) }}</div>
                                <a href="{{ route('directory.profile', $u->id) }}" style="font-weight: 600; color: inherit; text-decoration: none;">{{ $u->name }}</a>
                                <span style="font-size: 11px; background: #F3F4F6; padding: 2px 6px; border-radius: 4px;">{{ $lvl['emoji'] }}</span>
                            </div>
                        </td>
                        <td style="vertical-align: middle; width: 35%;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="flex: 1; height: 5px; background: #F3F4F6; border-radius: 3px; overflow: hidden;">
                                    <div style="height: 100%; width: {{ ($u->reputation_points / $maxPoints) * 100 }}%; background: var(--c-primary); border-radius: 3px;"></div>
                                </div>
                                <span style="font-weight: 700; color: #4B5563; min-width: 45px; text-align: right; font-size: 14px;">{{ $u->reputation_points }}</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    @endif

    {{-- CTA --}}
    <div class="text-center" style="margin: 40px 0;">
        <a href="{{ route('directory.index') }}" style="display: inline-block; background: linear-gradient(135deg, var(--c-primary), var(--c-accent)); color: #fff; padding: 14px 36px; border-radius: 50px; font-weight: 700; text-decoration: none; font-size: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            🚀 {{ __('Commencez à contribuer') }}
        </a>
    </div>
</div>
</section>
@endsection
