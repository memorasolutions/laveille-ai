<div>
    <h2 class="h2 text-dark">{{ __('Réinitialiser le mot de passe') }}</h2>
    <p class="mt-2 text-muted">{{ __('Choisissez un nouveau mot de passe pour votre compte.') }}</p>
    <form wire:submit="resetPassword" class="mt-5">
        <div class="mb-4">
            <label class="form-label">{{ __('Courriel') }}</label>
            <div class="input-group @error('email') is-invalid @enderror">
                <span class="input-group-text"><i data-lucide="at-sign" aria-hidden="true"></i></span>
                <input wire:model="email" type="email" class="form-control @error('email') is-invalid @enderror" required>
            </div>
            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="mb-4">
            <label class="form-label">{{ __('Nouveau mot de passe') }}</label>
            <div class="input-group @error('password') is-invalid @enderror">
                <span class="input-group-text"><i data-lucide="key-round" aria-hidden="true"></i></span>
                <input wire:model="password" type="password" class="form-control @error('password') is-invalid @enderror" id="new-password" placeholder="{{ __('Nouveau mot de passe') }}" required>
                <button type="button" class="btn btn-outline-secondary toggle-password" data-toggle="#new-password" aria-label="{{ __('Afficher le mot de passe') }}">
                    <i data-lucide="eye" class="toggle-password-icon"></i>
                </button>
            </div>
            @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="mb-4">
            <label class="form-label">{{ __('Confirmer le mot de passe') }}</label>
            <div class="input-group">
                <span class="input-group-text"><i data-lucide="lock" aria-hidden="true"></i></span>
                <input wire:model="password_confirmation" type="password" class="form-control" placeholder="{{ __('Confirmer le mot de passe') }}" required>
            </div>
        </div>
        <button type="submit" wire:loading.attr="disabled" class="btn btn-primary w-100 py-3 mb-4">
            <span wire:loading.remove>{{ __('Réinitialiser') }}</span>
            <span wire:loading>{{ __('Réinitialisation...') }}</span>
        </button>
    </form>
    @push('scripts')
    <script>
        document.querySelectorAll('.toggle-password').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var input = document.querySelector(this.getAttribute('data-toggle'));
                var icon = this.querySelector('.toggle-password-icon');
                if (input && icon) {
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.setAttribute('data-lucide', 'eye-off');
                    } else {
                        input.type = 'password';
                        icon.setAttribute('data-lucide', 'eye');
                    }
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            });
        });
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
    @endpush
</div>
