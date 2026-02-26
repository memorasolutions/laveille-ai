<div>
    <h1 class="mb-12 fw-bold" style="font-size: 1.5rem;">{{ __('Connexion') }}</h1>
    <p class="mb-32 text-secondary-light text-lg">{{ __('Bienvenue ! Entrez vos identifiants') }}</p>

    @if(session('status'))
        <div class="alert alert-success radius-8 mb-20">{{ session('status') }}</div>
    @endif

    <form wire:submit="authenticate">
        <div class="icon-field mb-16">
            <label for="login-email" class="visually-hidden">{{ __('Courriel') }}</label>
            <span class="icon top-50 translate-middle-y" aria-hidden="true">
                <iconify-icon icon="mage:email"></iconify-icon>
            </span>
            <input wire:model="email" type="email" id="login-email" autocomplete="email" class="form-control h-56-px bg-neutral-50 radius-12 @error('email') is-invalid @enderror" placeholder="{{ __('Courriel') }}" required autofocus>
        </div>
        @error('email')<div class="text-danger-main text-sm mb-16">{{ $message }}</div>@enderror

        <div class="position-relative mb-20">
            <div class="icon-field">
                <label for="your-password" class="visually-hidden">{{ __('Mot de passe') }}</label>
                <span class="icon top-50 translate-middle-y" aria-hidden="true">
                    <iconify-icon icon="solar:lock-password-outline"></iconify-icon>
                </span>
                <input wire:model="password" type="password" class="form-control h-56-px bg-neutral-50 radius-12 @error('password') is-invalid @enderror" id="your-password" autocomplete="current-password" placeholder="{{ __('Mot de passe') }}" required>
            </div>
            <button type="button" class="toggle-password ri-eye-line cursor-pointer position-absolute end-0 top-50 translate-middle-y me-16 text-secondary-light border-0 bg-transparent p-0" data-toggle="#your-password" aria-label="{{ __('Afficher le mot de passe') }}"></button>
        </div>
        @error('password')<div class="text-danger-main text-sm mb-16">{{ $message }}</div>@enderror

        <div class="d-flex justify-content-between gap-2">
            <div class="form-check style-check d-flex align-items-center">
                <input wire:model="remember" class="form-check-input border border-neutral-300" type="checkbox" id="remember">
                <label class="form-check-label" for="remember">{{ __('Se souvenir de moi') }}</label>
            </div>
            <a href="{{ route('password.request') }}" class="text-primary-600 fw-medium" wire:navigate>{{ __('Mot de passe oublié ?') }}</a>
        </div>

        <button type="submit" wire:loading.attr="disabled" class="btn btn-primary text-sm btn-sm px-12 py-16 w-100 radius-12 mt-32">
            <span wire:loading.remove>{{ __('Se connecter') }}</span>
            <span wire:loading>{{ __('Connexion...') }}</span>
        </button>

        <div class="mt-32 center-border-horizontal text-center">
            <span class="bg-base z-1 px-4">{{ __('Ou continuer avec') }}</span>
        </div>

        <div class="mt-32 d-flex align-items-center gap-3">
            <a href="{{ route('social.redirect', 'google') }}" class="fw-semibold text-primary-light py-16 px-24 w-50 border radius-12 text-md d-flex align-items-center justify-content-center gap-12 line-height-1 bg-hover-primary-50">
                <iconify-icon icon="logos:google-icon" class="text-xl line-height-1" aria-hidden="true"></iconify-icon>
                Google
            </a>
            <a href="{{ route('social.redirect', 'github') }}" class="fw-semibold text-primary-light py-16 px-24 w-50 border radius-12 text-md d-flex align-items-center justify-content-center gap-12 line-height-1 bg-hover-primary-50">
                <iconify-icon icon="mdi:github" class="text-xl line-height-1" aria-hidden="true"></iconify-icon>
                GitHub
            </a>
        </div>

        <div class="mt-32 text-center text-sm">
            <p class="mb-8">{{ __('Pas de compte ?') }} <a href="{{ route('register') }}" class="text-primary-600 fw-semibold" wire:navigate>{{ __('Créer un compte') }}</a></p>
            <a href="{{ route('magic-link.request') }}" class="text-primary-600 fw-medium" wire:navigate>{{ __('Connexion sans mot de passe (code)') }}</a>
        </div>
    </form>

    @push('scripts')
    <script>
        function initializePasswordToggle(toggleSelector) {
            $(toggleSelector).on("click", function() {
                $(this).toggleClass("ri-eye-off-line");
                var input = $($(this).attr("data-toggle"));
                if (input.attr("type") === "password") {
                    input.attr("type", "text");
                } else {
                    input.attr("type", "password");
                }
            });
        }
        initializePasswordToggle(".toggle-password");
    </script>
    @endpush
</div>
