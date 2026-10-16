@extends($faLayout)
@section('page_noindex', '1')
@section('no_ads', '1')
@section('suppress_auto_ads', '1')
@push('styles')
@include('fileattente::partials.styles')
@endpush
@section('title', __('fileattente::ui.construction.title'))
@section('breadcrumb')
@include('fileattente::partials.breadcrumb', ['crumb' => __('fileattente::ui.tool.name')])
@endsection
@section('content')
@include('fileattente::partials.page-open')
<h1 class="h2 mb-2" style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark);">{{ __('fileattente::ui.construction.heading') }}</h1>
<div class="alert alert-info" role="status">{{ __('fileattente::ui.construction.body') }}</div>
@include('fileattente::partials.page-close')
@endsection
