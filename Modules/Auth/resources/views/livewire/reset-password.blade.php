<div>
    <h4 class="mb-12">{{ __('Réinitialiser le mot de passe') }}</h4>
    <p class="mb-32 text-secondary-light text-lg">{{ __('Choisissez un nouveau mot de passe pour votre compte.') }}</p>

    <form wire:submit="resetPassword">
        <div class="icon-field mb-16">
            <span class="icon top-50 translate-middle-y">
                <iconify-icon icon="mage:email"></iconify-icon>
            </span>
            <input wire:model="email" type="email" class="form-control h-56-px bg-neutral-50 radius-12 @error('email') is-invalid @enderror" required>
        </div>
        @error('email')<div class="text-danger-main text-sm mb-16">{{ $message }}</div>@enderror

        <div class="position-relative mb-16">
            <div class="icon-field">
                <span class="icon top-50 translate-middle-y">
                    <iconify-icon icon="solar:lock-password-outline"></iconify-icon>
                </span>
                <input wire:model="password" type="password" class="form-control h-56-px bg-neutral-50 radius-12 @error('password') is-invalid @enderror" id="new-password" placeholder="{{ __('Nouveau mot de passe') }}" required>
            </div>
            <span class="toggle-password ri-eye-line cursor-pointer position-absolute end-0 top-50 translate-middle-y me-16 text-secondary-light" data-toggle="#new-password"></span>
        </div>
        @error('password')<div class="text-danger-main text-sm mb-16">{{ $message }}</div>@enderror

        <div class="icon-field mb-16">
            <span class="icon top-50 translate-middle-y">
                <iconify-icon icon="solar:lock-outline"></iconify-icon>
            </span>
            <input wire:model="password_confirmation" type="password" class="form-control h-56-px bg-neutral-50 radius-12" placeholder="{{ __('Confirmer le mot de passe') }}" required>
        </div>

        <button type="submit" wire:loading.attr="disabled" class="btn btn-primary text-sm btn-sm px-12 py-16 w-100 radius-12 mt-32">
            <span wire:loading.remove>{{ __('Réinitialiser') }}</span>
            <span wire:loading>{{ __('Réinitialisation...') }}</span>
        </button>
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
