@unless(request()->cookie('cookie_consent'))
<div x-data="{ showDetails: false }" class="fixed-bottom" style="background-color: #1c1c1e; color: white; z-index: 9999;" role="dialog" aria-label="{{ __('Gestion des cookies') }}" aria-modal="false">
    <div class="container-fluid py-3 px-4">
        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <p class="mb-0 cs_fs_14" style="max-width:700px">
                {{ __('Nous utilisons des cookies pour assurer le bon fonctionnement du site et améliorer votre expérience.') }}
                <a href="{{ route('privacy') }}" class="text-light text-decoration-underline">{{ __('Politique de confidentialité') }}</a>
            </p>
            <div class="d-flex gap-2 flex-shrink-0 flex-wrap">
                <form action="{{ route('cookie.decline') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-light btn-sm rounded-pill px-4 fw-semibold">{{ __('Tout refuser') }}</button>
                </form>
                <button @click="showDetails = !showDetails" class="btn btn-light btn-sm rounded-pill px-4 fw-semibold" type="button" aria-expanded="false" :aria-expanded="showDetails.toString()">
                    {{ __('Personnaliser') }}
                </button>
                <form action="{{ route('cookie.accept') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-light btn-sm rounded-pill px-4 fw-semibold">{{ __('Tout accepter') }}</button>
                </form>
            </div>
        </div>

        <div x-show="showDetails" x-cloak x-transition class="mt-3 pt-3 border-top border-secondary">
            <form action="{{ route('cookie.customize') }}" method="POST">
                @csrf
                <div class="d-flex flex-wrap gap-4 align-items-center">
                    @foreach($cookieCategories ?? [] as $category)
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox"
                                   name="{{ $category->name }}" value="1"
                                   id="cookie_{{ $category->name }}"
                                   role="switch"
                                   aria-label="{{ $category->label }}"
                                   {{ $category->isRequired() ? 'checked disabled' : '' }}>
                            <label class="form-check-label text-light" for="cookie_{{ $category->name }}">{{ $category->label }}</label>
                            @if($category->description)
                                <small class="d-block text-secondary">{{ $category->description }}</small>
                            @endif
                        </div>
                    @endforeach
                    <button type="submit" class="btn btn-light btn-sm rounded-pill px-4 fw-semibold">{{ __('Sauvegarder mes choix') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endunless
