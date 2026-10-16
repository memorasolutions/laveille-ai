@extends($faLayout)
@section('page_noindex', '1')
@section('no_ads', '1')
@section('suppress_auto_ads', '1')
@push('styles')
@include('fileattente::partials.styles')
@endpush
@section('title', __('fileattente::ui.create.title'))
@section('breadcrumb')
@include('fileattente::partials.breadcrumb', ['crumb' => __('fileattente::ui.tool.name')])
@endsection
@section('content')
@include('fileattente::partials.page-open')
<h1 class="h2 mb-2" style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark);">{{ __('fileattente::ui.create.heading') }}</h1>
<p class="text-muted mb-4">{{ __('fileattente::ui.create.intro') }}</p>

@if (session('fa_notice'))<div class="alert alert-success" role="status">{{ session('fa_notice') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger" role="alert">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

<form method="POST" action="{{ route('fileattente.store') }}">
@csrf
<div class="mb-3">
  <label for="title" class="form-label fw-medium">{{ __('fileattente::ui.create.title_label') }}</label>
  <input type="text" id="title" name="title" class="form-control" maxlength="120" value="{{ old('title') }}">
</div>
<div class="mb-3">
  <label for="names" class="form-label fw-medium">{{ __('fileattente::ui.create.names_label', ['max' => config('file-attente.max_participants'), 'len' => config('file-attente.max_name_len')]) }}</label>
  <textarea id="names" name="names" class="form-control" rows="8" required>{{ old('names') }}</textarea>
</div>
<div class="mb-3">
  <label for="mask_mode" class="form-label fw-medium">{{ __('fileattente::ui.create.mask_label') }}</label>
  <select id="mask_mode" name="mask_mode" class="form-select">
    <option value="initial" @selected(old('mask_mode', 'initial') === 'initial')>{{ __('fileattente::ui.create.mask_initial') }}</option>
    <option value="pseudo" @selected(old('mask_mode') === 'pseudo')>{{ __('fileattente::ui.create.mask_pseudo') }}</option>
    <option value="numero" @selected(old('mask_mode') === 'numero')>{{ __('fileattente::ui.create.mask_numero') }}</option>
  </select>
  <div class="form-text">{{ __('fileattente::ui.create.mask_help') }}</div>
</div>
<div class="mb-3">
  <label for="scan_mode" class="form-label fw-medium">{{ __('fileattente::ui.create.scan_label') }}</label>
  <select id="scan_mode" name="scan_mode" class="form-select">
    <option value="borne" @selected(old('scan_mode', 'borne') === 'borne')>{{ __('fileattente::ui.create.scan_borne') }}</option>
    <option value="telephone" @selected(old('scan_mode') === 'telephone')>{{ __('fileattente::ui.create.scan_telephone') }}</option>
  </select>
</div>
<div class="form-check mb-3">
  <input class="form-check-input" type="checkbox" id="allow_self_leave" name="allow_self_leave" value="1" @checked(old('allow_self_leave', '1'))>
  <label class="form-check-label" for="allow_self_leave">{{ __('fileattente::ui.create.self_leave') }}</label>
</div>
<p class="text-muted small">{{ __('fileattente::ui.create.responsibility', ['hours' => config('file-attente.purge_hours')]) }}</p>
<button class="ct-btn ct-btn-primary" type="submit">{{ __('fileattente::ui.create.submit') }}</button>
@include('fileattente::partials.page-close')
@endsection
