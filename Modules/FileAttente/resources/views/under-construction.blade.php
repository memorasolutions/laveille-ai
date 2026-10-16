@extends($faLayout)
@section('page_noindex', '1')
@section('no_ads', '1')
@section('suppress_auto_ads', '1')
@push('styles')
@include('fileattente::partials.styles')
@endpush
@section('title', __('fileattente::ui.construction.title'))
@section('content')
<div class="fa fa-wrap">
<h1>{{ __('fileattente::ui.construction.heading') }}</h1>
<div class="card" role="status">
  <p style="margin:0">{{ __('fileattente::ui.construction.body') }}</p>
</div>
</div>
@endsection
