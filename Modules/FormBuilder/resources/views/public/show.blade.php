@extends('fronttheme::themes.gosass.layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="mb-3">{{ $form->title }}</h1>

            @if($form->description)
                <p class="text-muted mb-4">{{ $form->description }}</p>
            @endif

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('formbuilder.submit', $form) }}" enctype="multipart/form-data">
                @csrf
                <input type="text" name="_honeypot" value="" style="display:none !important" tabindex="-1" autocomplete="off">

                @foreach($form->fields as $field)
                    @if($field->type === 'hidden')
                        <input type="hidden" name="fields[{{ $field->name }}]" value="">
                        @continue
                    @endif

                    <div class="mb-3">
                        @if($field->type !== 'checkbox')
                            <label for="field_{{ $field->name }}" class="form-label">
                                {{ $field->label }}
                                @if($field->is_required) <span class="text-danger">*</span> @endif
                            </label>
                        @endif

                        @switch($field->type)
                            @case('text')
                            @case('email')
                            @case('number')
                            @case('date')
                                <input type="{{ $field->type }}"
                                       name="fields[{{ $field->name }}]"
                                       id="field_{{ $field->name }}"
                                       class="form-control @error('fields.' . $field->name) is-invalid @enderror"
                                       placeholder="{{ $field->placeholder }}"
                                       value="{{ old('fields.' . $field->name) }}"
                                       @if($field->is_required) required @endif>
                                @break

                            @case('textarea')
                                <textarea name="fields[{{ $field->name }}]"
                                          id="field_{{ $field->name }}"
                                          class="form-control @error('fields.' . $field->name) is-invalid @enderror"
                                          rows="4"
                                          placeholder="{{ $field->placeholder }}"
                                          @if($field->is_required) required @endif>{{ old('fields.' . $field->name) }}</textarea>
                                @break

                            @case('select')
                                <select name="fields[{{ $field->name }}]"
                                        id="field_{{ $field->name }}"
                                        class="form-select @error('fields.' . $field->name) is-invalid @enderror"
                                        @if($field->is_required) required @endif>
                                    <option value="">-- Sélectionner --</option>
                                    @foreach($field->options ?? [] as $opt)
                                        <option value="{{ $opt }}" {{ old('fields.' . $field->name) === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                                @break

                            @case('checkbox')
                                <div class="form-check">
                                    <input type="checkbox"
                                           name="fields[{{ $field->name }}]"
                                           id="field_{{ $field->name }}"
                                           class="form-check-input @error('fields.' . $field->name) is-invalid @enderror"
                                           value="1"
                                           {{ old('fields.' . $field->name) ? 'checked' : '' }}
                                           @if($field->is_required) required @endif>
                                    <label class="form-check-label" for="field_{{ $field->name }}">
                                        {{ $field->label }}
                                        @if($field->is_required) <span class="text-danger">*</span> @endif
                                    </label>
                                </div>
                                @break

                            @case('radio')
                                @foreach($field->options ?? [] as $i => $opt)
                                    <div class="form-check">
                                        <input type="radio"
                                               name="fields[{{ $field->name }}]"
                                               id="field_{{ $field->name }}_{{ $i }}"
                                               class="form-check-input @error('fields.' . $field->name) is-invalid @enderror"
                                               value="{{ $opt }}"
                                               {{ old('fields.' . $field->name) === $opt ? 'checked' : '' }}
                                               @if($field->is_required) required @endif>
                                        <label class="form-check-label" for="field_{{ $field->name }}_{{ $i }}">{{ $opt }}</label>
                                    </div>
                                @endforeach
                                @break

                            @case('file')
                                <input type="file"
                                       name="fields[{{ $field->name }}]"
                                       id="field_{{ $field->name }}"
                                       class="form-control @error('fields.' . $field->name) is-invalid @enderror"
                                       @if($field->is_required) required @endif>
                                @break
                        @endswitch

                        @error('fields.' . $field->name)
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Envoyer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
