<div>
    <h2 class="h2 text-dark">{{ __('Créer un compte') }}</h2>
    <p class="mt-2 text-muted">{{ __('Entrez vos informations pour commencer') }}</p>
    <form wire:submit="register" class="mt-5">
        <div class="mb-4">
            <label class="form-label">{{ __('Nom complet') }}</label>
            <div class="input-group @error('name') is-invalid @enderror">
                <span class="input-group-text">
                    <i data-lucide="user" aria-hidden="true"></i>
                </span>
                <input wire:model="name" type="text" class="form-control @error('name') is-invalid @enderror" placeholder="{{ __('Nom complet') }}" required autofocus>
            </div>
            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="mb-4">
            <label class="form-label">{{ __('Courriel') }}</label>
            <div class="input-group @error('email') is-invalid @enderror">
                <span class="input-group-text">
                    <i data-lucide="at-sign" aria-hidden="true"></i>
                </span>
                <input wire:model="email" type="email" class="form-control @error('email') is-invalid @enderror" placeholder="{{ __('Courriel') }}" required>
            </div>
            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div x-data="{
                pwd: '',
                get strength() { if (!this.pwd) return 0; let s = 0; if (this.pwd.length >= 8) s++; if (/[A-Z]/.test(this.pwd)) s++; if (/[0-9]/.test(this.pwd)) s++; if (/[^A-Za-z0-9]/.test(this.pwd)) s++; return s; },
                get label() { return ['', '{{ __('Faible') }}', '{{ __('Moyen') }}', '{{ __('Fort') }}', '{{ __('Très fort') }}'][this.strength] ?? ''; },
                get color() { return ['', 'bg-danger', 'bg-warning', 'bg-info', 'bg-success'][this.strength] ?? ''; }
            }" class="mb-4">
            <label class="form-label">{{ __('Mot de passe') }}</label>
            <div class="input-group @error('password') is-invalid @enderror">
                <span class="input-group-text">
                    <i data-lucide="fingerprint" aria-hidden="true"></i>
                </span>
                <input wire:model="password" x-model="pwd" type="password" class="form-control @error('password') is-invalid @enderror" id="your-password" placeholder="{{ __('Mot de passe') }}" required>
                <button type="button" class="btn btn-outline-secondary toggle-password" data-toggle="#your-password" aria-label="{{ __('Afficher le mot de passe') }}">
                    <i data-lucide="eye" class="toggle-password-icon"></i>
                </button>
            </div>
            <div x-show="pwd.length > 0" class="mt-3">
                <div class="progress mb-2" style="height: 4px;">
                    <div class="progress-bar" :class="color" role="progressbar" :style="'width:' + (strength * 25) + '%'" :aria-valuenow="strength * 25" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small fw-medium" x-text="label"></span>
                    <div class="d-flex gap-3 small text-muted">
                        <span :class="pwd.length >= 8 ? 'text-success' : ''">{{ __('8+ caract.') }}</span>
                        <span :class="/[A-Z]/.test(pwd) ? 'text-success' : ''">{{ __('Majuscule') }}</span>
                        <span :class="/[0-9]/.test(pwd) ? 'text-success' : ''">{{ __('Chiffre') }}</span>
                    </div>
                </div>
            </div>
            <p class="mt-2 small text-muted">{{ __('Minimum 8 caractères requis') }}</p>
            @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="mb-4">
            <label class="form-label">{{ __('Confirmer le mot de passe') }}</label>
            <div class="input-group">
                <span class="input-group-text">
                    <i data-lucide="lock" aria-hidden="true"></i>
                </span>
                <input wire:model="password_confirmation" type="password" class="form-control" placeholder="{{ __('Confirmer le mot de passe') }}" required>
            </div>
        </div>
        <button type="submit" wire:loading.attr="disabled" class="btn btn-primary w-100 py-3 mb-4">
            <span wire:loading.remove>{{ __('Créer le compte') }}</span>
            <span wire:loading>{{ __('Création...') }}</span>
        </button>
        <div class="text-center">
            <p class="mb-0 text-muted">{{ __('Déjà un compte ?') }} <a href="{{ route('login') }}" class="text-decoration-none text-primary fw-semibold" wire:navigate>{{ __('Se connecter') }}</a></p>
        </div>
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
