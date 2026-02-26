@extends('fronttheme::themes.gosass.layouts.app')

@section('content')
<div class="container py-5">
    <h1 class="mb-3">{{ __('Préférences cookies') }}</h1>
    <p class="text-secondary-light mb-4">
        {{ __('Nous utilisons des cookies pour améliorer votre expérience de navigation, analyser le trafic et personnaliser le contenu. Vous pouvez gérer vos choix en acceptant ou en refusant les catégories non essentielles.') }}
    </p>

    <div class="card radius-12">
        <div class="card-body">
            <form action="{{ route('cookie.customize') }}" method="POST">
                @csrf
                @foreach($cookieCategories as $category)
                    <div class="d-flex align-items-start gap-3 mb-4 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox"
                                   name="{{ $category->name }}"
                                   id="cookie_{{ $category->name }}"
                                   value="1"
                                   {{ ($cookiePreferences[$category->name] ?? $category->isRequired()) ? 'checked' : '' }}
                                   {{ $category->isRequired() ? 'disabled checked' : '' }}>
                        </div>
                        <div>
                            <label class="form-check-label fw-semibold" for="cookie_{{ $category->name }}">
                                {{ $category->label }}
                                @if($category->isRequired())
                                    <span class="badge bg-primary ms-1">{{ __('Obligatoire') }}</span>
                                @endif
                            </label>
                            <p class="text-secondary-light mb-0 mt-1">{{ $category->description }}</p>
                        </div>
                    </div>
                @endforeach

                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary">{{ __('Sauvegarder mes choix') }}</button>
                    <button type="button" class="btn btn-success" onclick="document.getElementById('form-accept-all').submit()">{{ __('Tout accepter') }}</button>
                    <button type="button" class="btn btn-outline-danger" onclick="document.getElementById('form-decline-all').submit()">{{ __('Tout refuser') }}</button>
                </div>
            </form>

            <form id="form-accept-all" action="{{ route('cookie.accept') }}" method="POST" class="d-none">@csrf</form>
            <form id="form-decline-all" action="{{ route('cookie.decline') }}" method="POST" class="d-none">@csrf</form>

            <p class="mt-4 mb-0">
                <a href="{{ route('privacy') }}" class="text-decoration-underline">{{ __('Politique de confidentialité') }}</a>
            </p>
        </div>
    </div>
</div>
@endsection
