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
    <div class="text-center" style="margin-bottom: 24px;">
        <h1 style="font-family: var(--f-heading); font-weight: 800; font-size: 2rem; color: var(--c-dark); margin-bottom: 8px;">
            🏆 {{ __('Classement de la communauté') }}
        </h1>
        <p style="font-size: 1.1rem; color: #6B7280;">{{ __('Les membres les plus actifs de La veille') }}</p>
    </div>

    {{-- Onglets mois/tout temps --}}
    <div x-data="{ tab: 'alltime' }" style="margin-bottom: 32px;">
        <div style="display:flex!important;justify-content:center!important;gap:8px;margin-bottom:24px;">
            <button @click="tab='alltime'"
                :style="tab==='alltime'
                    ? 'background:#fff;color:var(--c-dark);border:2px solid var(--c-primary);border-radius:12px;padding:10px 24px;font-family:var(--f-heading);font-weight:600;font-size:14px;cursor:pointer;box-shadow:0 4px 12px rgba(11,114,133,0.2);transition:all .2s;'
                    : 'background:rgba(255,255,255,0.7);color:var(--c-text-muted);border:2px solid transparent;border-radius:12px;padding:10px 24px;font-family:var(--f-heading);font-weight:600;font-size:14px;cursor:pointer;transition:all .2s;'">
                <i class="fa fa-trophy"></i> {{ __('Tout temps') }}
            </button>
            <button @click="tab='monthly'"
                :style="tab==='monthly'
                    ? 'background:#fff;color:var(--c-dark);border:2px solid var(--c-primary);border-radius:12px;padding:10px 24px;font-family:var(--f-heading);font-weight:600;font-size:14px;cursor:pointer;box-shadow:0 4px 12px rgba(11,114,133,0.2);transition:all .2s;'
                    : 'background:rgba(255,255,255,0.7);color:var(--c-text-muted);border:2px solid transparent;border-radius:12px;padding:10px 24px;font-family:var(--f-heading);font-weight:600;font-size:14px;cursor:pointer;transition:all .2s;'">
                <i class="fa fa-calendar"></i> {{ __('Ce mois') }}
            </button>
        </div>

        {{-- Contenu tout temps --}}
        <div x-show="tab==='alltime'" x-transition>
            @include('directory::public.partials.leaderboard-list', ['users' => $topAllTime, 'pointsField' => 'reputation_points'])
        </div>

        {{-- Contenu mensuel --}}
        <div x-show="tab==='monthly'" x-cloak x-transition>
            @if($topMonthly->isEmpty())
                <div style="text-align:center;padding:40px 20px;background:#f9fafb;border-radius:16px;border:1px dashed #d1d5db;">
                    <div style="font-size:36px;margin-bottom:8px;">📅</div>
                    <h3 style="font-family:var(--f-heading);color:var(--c-dark);margin-bottom:8px;">{{ __('Pas encore de points ce mois-ci') }}</h3>
                    <p style="color:#6b7280;">{{ __('Contribuez pour apparaitre dans le classement mensuel !') }}</p>
                </div>
            @else
                @include('directory::public.partials.leaderboard-list', ['users' => $topMonthly, 'pointsField' => 'monthly_points'])
            @endif
        </div>
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

    {{-- CTA --}}
    <div class="text-center" style="margin: 40px 0;">
        <a href="{{ route('directory.index') }}" style="display: inline-block; background: linear-gradient(135deg, var(--c-primary), var(--c-accent)); color: #fff; padding: 14px 36px; border-radius: 50px; font-weight: 700; text-decoration: none; font-size: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            🚀 {{ __('Commencez à contribuer') }}
        </a>
    </div>
</div>
</section>
@endsection
