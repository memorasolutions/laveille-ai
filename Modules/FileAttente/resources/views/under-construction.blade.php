@extends('fileattente::layout')
@section('title', __('fileattente::ui.construction.title'))
@section('content')
<h1>{{ __('fileattente::ui.construction.heading') }}</h1>
<div class="card" role="status">
  <p style="margin:0">{{ __('fileattente::ui.construction.body') }}</p>
</div>
@endsection
