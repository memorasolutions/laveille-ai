{{-- Partial leaderboard : podium + table. Variables : $users (collection), $pointsField (string) --}}
@php
    $getLevel = function($lvl) {
        if (class_exists(\Modules\Directory\Services\ReputationService::class)) {
            return \Modules\Directory\Services\ReputationService::getLevelInfo($lvl);
        }
        return ['emoji' => '👤', 'name' => 'Membre'];
    };
    $maxPts = $users->first()?->{$pointsField} ?: 1;
@endphp

@if($users->isEmpty())
    <div style="text-align:center;padding:48px 24px;background:linear-gradient(180deg,#f9fafb 0%,#fff 100%);border-radius:20px;border:2px dashed #d1d5db;">
        {{-- Podium illustratif avec places vides --}}
        <div style="display:flex!important;justify-content:center!important;align-items:flex-end!important;gap:12px;margin-bottom:24px;">
            <div style="width:70px;text-align:center;">
                <div style="width:52px;height:52px;margin:0 auto 6px;border-radius:50%;border:2px dashed #d1d5db;display:flex!important;align-items:center!important;justify-content:center!important;color:#d1d5db;font-size:18px;">?</div>
                <div style="background:#e5e7eb;border-radius:8px 8px 0 0;height:50px;display:flex!important;align-items:center!important;justify-content:center!important;font-size:18px;">🥈</div>
            </div>
            <div style="width:80px;text-align:center;">
                <div style="width:60px;height:60px;margin:0 auto 6px;border-radius:50%;border:2px dashed #fbbf24;display:flex!important;align-items:center!important;justify-content:center!important;color:#fbbf24;font-size:22px;">?</div>
                <div style="background:#fef3c7;border-radius:8px 8px 0 0;height:70px;display:flex!important;align-items:center!important;justify-content:center!important;font-size:22px;">🥇</div>
            </div>
            <div style="width:70px;text-align:center;">
                <div style="width:52px;height:52px;margin:0 auto 6px;border-radius:50%;border:2px dashed #d1d5db;display:flex!important;align-items:center!important;justify-content:center!important;color:#d1d5db;font-size:18px;">?</div>
                <div style="background:#e5e7eb;border-radius:8px 8px 0 0;height:40px;display:flex!important;align-items:center!important;justify-content:center!important;font-size:18px;">🥉</div>
            </div>
        </div>
        <h3 style="font-family:var(--f-heading, 'Plus Jakarta Sans', sans-serif);font-weight:800;color:var(--c-dark, #1A1D23);margin-bottom:8px;font-size:1.3rem;">{{ __('Le podium vous attend !') }}</h3>
        <p style="color:#6b7280;max-width:380px;margin:0 auto 20px;line-height:1.6;">{{ __('Soyez parmi les premiers à contribuer et décrochez la première place du classement.') }}</p>
        <a href="{{ route('directory.index') }}" style="display:inline-block;background:var(--c-primary, #064E5A);color:#fff;padding:12px 28px;border-radius:0.5rem;font-weight:700;text-decoration:none;font-size:15px;transition:transform .2s,background .2s;" onmouseover="this.style.transform='translateY(-2px)';this.style.background='var(--c-primary-hover, #064E5C)'" onmouseout="this.style.transform='none';this.style.background='var(--c-primary, #064E5A)'">
            🚀 {{ __('Commencez à contribuer') }}
        </a>
    </div>
@else
    {{-- Podium top 3 --}}
    @if($users->count() >= 3)
    <div class="row" style="display:flex!important;align-items:flex-end!important;justify-content:center!important;margin-bottom:40px;flex-wrap:wrap!important;">
        @foreach([1, 0, 2] as $podiumIndex)
            @if(isset($users[$podiumIndex]))
            @php
                $u = $users[$podiumIndex];
                $lvl = $getLevel($u->trust_level);
                $isFirst = $podiumIndex === 0;
                $colors = ['#FFD700', '#C0C0C0', '#CD7F32'];
                $medals = ['🥇', '🥈', '🥉'];
                $pts = $u->{$pointsField};
            @endphp
            <div class="col-sm-4 col-xs-12" style="margin-bottom:16px;">
                <div style="background:#fff;border-radius:16px;padding:24px;text-align:center;box-shadow:0 6px 20px rgba(0,0,0,0.06);border-bottom:4px solid {{ $colors[$podiumIndex] }};{{ $isFirst ? 'transform:scale(1.05);' : '' }}">
                    <div style="font-size:36px;margin-bottom:8px;">{{ $medals[$podiumIndex] }}</div>
                    <h3 style="font-family:var(--f-heading);margin:8px 0;font-weight:700;font-size:17px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        @if(Route::has('directory.profile'))<a href="{{ route('directory.profile', $u->id) }}" style="color:inherit;text-decoration:none;">{{ $u->name }}</a>@else{{ $u->name }}@endif
                    </h3>
                    <div style="display:inline-flex!important;align-items:center!important;gap:6px;background:#f3f4f6;padding:4px 12px;border-radius:20px;font-size:12px;margin-bottom:12px;">
                        <span>{{ $lvl['emoji'] }} {{ $lvl['name'] }}</span>
                        @if(($u->streak_days ?? 0) >= 3)
                            <span style="color:#ef4444;font-weight:700;">🔥{{ $u->streak_days }}j</span>
                        @endif
                    </div>
                    <div style="font-weight:800;font-size:22px;color:{{ $colors[$podiumIndex] }};">{{ $pts }} <span style="font-size:11px;color:#6b7280;">pts</span></div>
                </div>
            </div>
            @endif
        @endforeach
    </div>
    @endif

    {{-- Table 4-10 --}}
    @if($users->count() > 3)
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.03);margin-bottom:30px;">
        <table class="table table-hover" style="margin-bottom:0;">
            <tbody>
                @foreach($users->slice(3) as $key => $u)
                @php $lvl = $getLevel($u->trust_level); $pts = $u->{$pointsField}; @endphp
                <tr>
                    <td style="vertical-align:middle;width:50px;font-weight:700;color:#6b7280;text-align:center;">{{ $key + 1 }}</td>
                    <td style="vertical-align:middle;">
                        <div style="display:flex!important;align-items:center!important;gap:10px;">
                            <div style="width:34px;height:34px;background:#e0e7ff;color:#4f46e5;border-radius:50%;display:flex!important;align-items:center!important;justify-content:center!important;font-weight:700;font-size:14px;">{{ substr($u->name, 0, 1) }}</div>
                            @if(Route::has('directory.profile'))<a href="{{ route('directory.profile', $u->id) }}" style="font-weight:600;color:inherit;text-decoration:none;">{{ $u->name }}</a>@else<span style="font-weight:600;">{{ $u->name }}</span>@endif
                            <span style="font-size:11px;background:#f3f4f6;padding:2px 6px;border-radius:4px;">{{ $lvl['emoji'] }}</span>
                            @if(($u->streak_days ?? 0) >= 3)
                                <span style="font-size:11px;color:#ef4444;font-weight:600;">🔥{{ $u->streak_days }}j</span>
                            @endif
                        </div>
                    </td>
                    <td style="vertical-align:middle;width:35%;">
                        <div style="display:flex!important;align-items:center!important;gap:8px;">
                            <div style="flex:1;height:5px;background:#f3f4f6;border-radius:3px;overflow:hidden;">
                                <div style="height:100%;width:{{ ($pts / $maxPts) * 100 }}%;background:var(--c-primary);border-radius:3px;"></div>
                            </div>
                            <span style="font-weight:700;color:#4b5563;min-width:45px;text-align:right;font-size:14px;">{{ $pts }}</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
@endif
