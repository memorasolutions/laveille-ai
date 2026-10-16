@extends('fileattente::layout')
@section('title', __('fileattente::ui.create.title'))
@section('content')
<h1>{{ __('fileattente::ui.create.heading') }}</h1>
<p>{{ __('fileattente::ui.create.intro') }}</p>

@if (session('fa_notice'))<div class="ok" role="status">{{ session('fa_notice') }}</div>@endif
@if ($errors->any())<div class="err" role="alert">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

<form method="POST" action="{{ route('fileattente.store') }}">
@csrf
<label for="title">{{ __('fileattente::ui.create.title_label') }}</label>
<input type="text" id="title" name="title" maxlength="120" value="{{ old('title') }}">

<label for="names">{{ __('fileattente::ui.create.names_label', ['max' => config('file-attente.max_participants'), 'len' => config('file-attente.max_name_len')]) }}</label>
<textarea id="names" name="names" required>{{ old('names') }}</textarea>

<label for="mask_mode">{{ __('fileattente::ui.create.mask_label') }}</label>
<select id="mask_mode" name="mask_mode">
  <option value="initial" @selected(old('mask_mode', 'initial') === 'initial')>{{ __('fileattente::ui.create.mask_initial') }}</option>
  <option value="pseudo" @selected(old('mask_mode') === 'pseudo')>{{ __('fileattente::ui.create.mask_pseudo') }}</option>
  <option value="numero" @selected(old('mask_mode') === 'numero')>{{ __('fileattente::ui.create.mask_numero') }}</option>
</select>
<p class="muted">{{ __('fileattente::ui.create.mask_help') }}</p>

<label for="scan_mode">{{ __('fileattente::ui.create.scan_label') }}</label>
<select id="scan_mode" name="scan_mode">
  <option value="borne" @selected(old('scan_mode', 'borne') === 'borne')>{{ __('fileattente::ui.create.scan_borne') }}</option>
  <option value="telephone" @selected(old('scan_mode') === 'telephone')>{{ __('fileattente::ui.create.scan_telephone') }}</option>
</select>

<label><input type="checkbox" name="allow_self_leave" value="1" @checked(old('allow_self_leave', '1')) > {{ __('fileattente::ui.create.self_leave') }}</label>

<p class="muted">{{ __('fileattente::ui.create.responsibility', ['hours' => config('file-attente.purge_hours')]) }}</p>
<p><button class="btn" type="submit">{{ __('fileattente::ui.create.submit') }}</button></p>
</form>
@endsection
