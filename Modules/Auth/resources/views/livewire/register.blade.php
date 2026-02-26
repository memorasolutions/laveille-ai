<div>
    <h4 class="mb-12">{{ __('Créer un compte') }}</h4>
    <p class="mb-32 text-secondary-light text-lg">{{ __('Entrez vos informations pour commencer') }}</p>

    <form wire:submit="register">
        <div class="icon-field mb-16">
            <span class="icon top-50 translate-middle-y">
                <iconify-icon icon="f7:person"></iconify-icon>
            </span>
            <input wire:model="name" type="text" class="form-control h-56-px bg-neutral-50 radius-12 @error('name') is-invalid @enderror" placeholder="{{ __('Nom complet') }}" required autofocus>
        </div>
        @error('name')<div class="text-danger-main text-sm mb-16">{{ $message }}</div>@enderror

        <div class="icon-field mb-16">
            <span class="icon top-50 translate-middle-y">
                <iconify-icon icon="mage:email"></iconify-icon>
            </span>
            <input wire:model="email" type="email" class="form-control h-56-px bg-neutral-50 radius-12 @error('email') is-invalid @enderror" placeholder="{{ __('Courriel') }}" required>
        </div>
        @error('email')<div class="text-danger-main text-sm mb-16">{{ $message }}</div>@enderror

        <div class="mb-20" x-data="{
            pwd: '',
            get strength() {
                if (!this.pwd) return 0;
                let s = 0;
                if (this.pwd.length >= 8) s++;
                if (/[A-Z]/.test(this.pwd)) s++;
                if (/[0-9]/.test(this.pwd)) s++;
                if (/[^A-Za-z0-9]/.test(this.pwd)) s++;
                return s;
            },
            get label() { return ['', '{{ __('Faible') }}', '{{ __('Moyen') }}', '{{ __('Fort') }}', '{{ __('Très fort') }}'][this.strength] ?? ''; },
            get color() { return ['', 'bg-danger', 'bg-warning', 'bg-primary', 'bg-success'][this.strength] ?? ''; }
        }">
            <div class="position-relative">
                <div class="icon-field">
                    <span class="icon top-50 translate-middle-y">
                        <iconify-icon icon="solar:lock-password-outline"></iconify-icon>
                    </span>
                    <input wire:model="password" x-model="pwd" type="password" class="form-control h-56-px bg-neutral-50 radius-12 @error('password') is-invalid @enderror" id="your-password" placeholder="{{ __('Mot de passe') }}" required>
                </div>
                <span class="toggle-password ri-eye-line cursor-pointer position-absolute end-0 top-50 translate-middle-y me-16 text-secondary-light" data-toggle="#your-password"></span>
            </div>
            <div x-show="pwd.length > 0" class="mt-8">
                <div class="progress mb-4" style="height:4px;">
                    <div class="progress-bar rounded-pill transition"
                         :class="color"
                         :style="'width:' + (strength * 25) + '%'"></div>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-xs fw-medium" x-text="label"></span>
                    <div class="d-flex gap-12 text-xs text-secondary-light">
                        <span :class="pwd.length >= 8 ? 'text-success-600' : ''">{{ __('8+ caract.') }}</span>
                        <span :class="/[A-Z]/.test(pwd) ? 'text-success-600' : ''">{{ __('Majuscule') }}</span>
                        <span :class="/[0-9]/.test(pwd) ? 'text-success-600' : ''">{{ __('Chiffre') }}</span>
                    </div>
                </div>
            </div>
            <span class="mt-12 text-sm text-secondary-light">{{ __('Minimum 8 caractères requis') }}</span>
            @error('password')<div class="text-danger-main text-sm mt-4">{{ $message }}</div>@enderror
        </div>

        <div class="icon-field mb-16">
            <span class="icon top-50 translate-middle-y">
                <iconify-icon icon="solar:lock-outline"></iconify-icon>
            </span>
            <input wire:model="password_confirmation" type="password" class="form-control h-56-px bg-neutral-50 radius-12" placeholder="{{ __('Confirmer le mot de passe') }}" required>
        </div>

        <button type="submit" wire:loading.attr="disabled" class="btn btn-primary text-sm btn-sm px-12 py-16 w-100 radius-12 mt-32">
            <span wire:loading.remove>{{ __('Créer le compte') }}</span>
            <span wire:loading>{{ __('Création...') }}</span>
        </button>

        <div class="mt-32 text-center text-sm">
            <p class="mb-0">{{ __('Déjà un compte ?') }} <a href="{{ route('login') }}" class="text-primary-600 fw-semibold" wire:navigate>{{ __('Se connecter') }}</a></p>
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
