{{-- Badge compteur réutilisable — @include('fronttheme::partials.badge-count', ['count' => X, 'color' => '#ef4444']) --}}
@if(($count ?? 0) > 0)
<span style="position:absolute; top:-4px; right:-6px; background:{{ $color ?? '#ef4444' }}; color:#fff; font-size:10px; font-weight:700; min-width:18px; height:18px; border-radius:50%; display:flex!important; align-items:center!important; justify-content:center!important; border:2px solid #fff; line-height:1; padding:0 3px;">{{ min($count, 9) }}{{ ($count ?? 0) > 9 ? '+' : '' }}</span>
@endif
