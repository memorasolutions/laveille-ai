@extends($faLayout)
@section('page_noindex', '1')
@section('no_ads', '1')
@section('suppress_auto_ads', '1')
@push('styles')
@include('fileattente::partials.styles')
@endpush
@section('title', __('fileattente::ui.scan.title'))
@section('content')
<div class="fa fa-wrap">
@php $st = $state['status']; @endphp
<h1>{{ $board->title ?: __('fileattente::ui.app_title') }}</h1>
<p class="muted">{{ __('fileattente::ui.scan.card_of') }} <strong>{{ $state['label'] }}</strong></p>

<div id="fa-state" class="card" role="status" aria-live="polite">
@if ($st === 'called')
  <h2 style="font-size:2rem;margin:0">{{ __('fileattente::ui.scan.your_turn') }}</h2>
@elseif ($st === 'in_list')
  <h2 style="margin:0">{{ __('fileattente::ui.scan.in_list') }}</h2>
  <p style="margin:.4rem 0 0">{{ __('fileattente::ui.scan.place', ['position' => $state['position']]) }} ({{ $state['ahead'] > 0 ? __($state['ahead'] > 1 ? 'fileattente::ui.scan.ahead_many' : 'fileattente::ui.scan.ahead_one', ['count' => $state['ahead']]) : __('fileattente::ui.scan.next') }}).</p>
@elseif ($st === 'removed')
  <h2 style="margin:0">{{ __('fileattente::ui.scan.removed') }}</h2>
@else
  <h2 style="margin:0">{{ __('fileattente::ui.scan.not_yet') }}</h2>
@endif
</div>

@if (in_array($st, ['pending', 'removed'], true))
<form method="POST" action="{{ route('fileattente.join', $token) }}">@csrf
  <button class="btn" type="submit">{{ __($st === 'removed' ? 'fileattente::ui.scan.rejoin' : 'fileattente::ui.scan.join') }}</button>
</form>
@elseif ($state['allow_self_leave'])
<form method="POST" action="{{ route('fileattente.leave', $token) }}">@csrf
  <button class="btn alt" type="submit">{{ __('fileattente::ui.scan.leave') }}</button>
</form>
@endif
@if (in_array($st, ['pending', 'removed'], true))
<p class="muted">{{ __('fileattente::ui.scan.no_change') }}</p>
@endif
</div>
@endsection
@push('scripts')
@include('fileattente::partials.poll')
<script>
faPoll({url:@json(route('fileattente.scan.etat', $token)),version:@json($state['version']),interval:@json($cadence),
  onData:function(){location.reload();},
  onGone:function(){document.getElementById('fa-state').textContent=@json(__('fileattente::ui.scan.over'));}});
</script>
@endpush
