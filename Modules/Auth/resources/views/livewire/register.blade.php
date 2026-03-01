<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    <h1 class="auth-title">{{ __('Créer un compte') }}</h1>
    <p class="auth-subtitle">{{ __('Entrez vos informations pour commencer') }}</p>

    <form wire:submit="register">
        <div style="margin-bottom:1.25rem;">
            <label for="register-name" class="auth-label">{{ __('Nom complet') }}</label>
            <div class="auth-input-group">
                <div class="auth-input-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <input wire:model="name" type="text" id="register-name" autocomplete="name" class="auth-input" placeholder="{{ __('Nom complet') }}" required autofocus>
            </div>
            @error('name')<p class="auth-error">{{ $message }}</p>@enderror
        </div>

        <div style="margin-bottom:1.25rem;">
            <label for="register-email" class="auth-label">{{ __('Courriel') }}</label>
            <div class="auth-input-group">
                <div class="auth-input-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                </div>
                <input wire:model="email" type="email" id="register-email" autocomplete="email" class="auth-input" placeholder="{{ __('votre@courriel.com') }}" required>
            </div>
            @error('email')<p class="auth-error">{{ $message }}</p>@enderror
        </div>

        <div x-data="{
                pwd: '',
                get strength() { if (!this.pwd) return 0; let s = 0; if (this.pwd.length >= 8) s++; if (/[A-Z]/.test(this.pwd)) s++; if (/[0-9]/.test(this.pwd)) s++; if (/[^A-Za-z0-9]/.test(this.pwd)) s++; return s; },
                get label() { return ['', '{{ __('Faible') }}', '{{ __('Moyen') }}', '{{ __('Fort') }}', '{{ __('Très fort') }}'][this.strength] ?? ''; },
                get barColor() { return ['', '#dc2626', '#f59e0b', '#3b82f6', '#22c55e'][this.strength] ?? ''; }
            }" style="margin-bottom:1.25rem;">
            <label for="register-password" class="auth-label">{{ __('Mot de passe') }}</label>
            <div class="auth-input-group">
                <div class="auth-input-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 10a2 2 0 0 0-2 2c0 1.02-.1 2.51-.26 4"/><path d="M14 13.12c0 2.38 0 6.38-1 8.88"/><path d="M17.29 21.02c.12-.6.43-2.3.5-3.02"/><path d="M2 12a10 10 0 0 1 18-6"/><path d="M2 16h.01"/><path d="M21.8 16c.2-2 .131-5.354 0-6"/><path d="M5 19.5C5.5 18 6 15 6 12a6 6 0 0 1 .34-2"/><path d="M8.65 22c.21-.66.45-1.32.57-2"/><path d="M9 6.8a6 6 0 0 1 9 5.2v2"/></svg>
                </div>
                <input wire:model="password" x-model="pwd" type="password" id="register-password" class="auth-input" style="padding-inline-end:3rem;" placeholder="{{ __('Mot de passe') }}" required>
                <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility('register-password')" aria-label="{{ __('Afficher le mot de passe') }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            <div x-show="pwd.length > 0" x-cloak style="margin-top:0.75rem;">
                <div style="height:4px;background:#e5e7eb;border-radius:2px;overflow:hidden;margin-bottom:0.5rem;">
                    <div :style="'width:' + (strength * 25) + '%;background:' + barColor + ';height:100%;transition:width 0.3s,background 0.3s;'" role="progressbar" :aria-valuenow="strength * 25" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:0.85rem;font-weight:500;" x-text="label"></span>
                    <div style="display:flex;gap:0.75rem;font-size:0.8rem;color:#6b7280;">
                        <span :style="pwd.length >= 8 ? 'color:#22c55e' : ''">{{ __('8+ caract.') }}</span>
                        <span :style="/[A-Z]/.test(pwd) ? 'color:#22c55e' : ''">{{ __('Majuscule') }}</span>
                        <span :style="/[0-9]/.test(pwd) ? 'color:#22c55e' : ''">{{ __('Chiffre') }}</span>
                    </div>
                </div>
            </div>
            <p style="margin-top:0.5rem;font-size:0.85rem;color:#6b7280;">{{ __('Minimum 8 caractères requis') }}</p>
            @error('password')<p class="auth-error">{{ $message }}</p>@enderror
        </div>

        <div style="margin-bottom:1.25rem;">
            <label for="register-password-confirm" class="auth-label">{{ __('Confirmer le mot de passe') }}</label>
            <div class="auth-input-group">
                <div class="auth-input-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <input wire:model="password_confirmation" type="password" id="register-password-confirm" class="auth-input" placeholder="{{ __('Confirmer le mot de passe') }}" required>
            </div>
        </div>

        <button type="submit" class="auth-btn" wire:loading.attr="disabled">
            <span wire:loading.remove>{{ __('Créer le compte') }}</span>
            <span wire:loading>{{ __('Création...') }}</span>
        </button>
    </form>

    <div style="text-align:center;margin-top:1.5rem;">
        <p class="auth-text-muted" style="margin-bottom:0;">{{ __('Déjà un compte ?') }} <a href="{{ route('login') }}" class="auth-link" wire:navigate>{{ __('Se connecter') }}</a></p>
    </div>

    @push('scripts')
    <script>
    function togglePasswordVisibility(id) {
        var input = document.getElementById(id);
        var btn = input.parentElement.querySelector('.toggle-password-btn');
        if (input.type === 'password') {
            input.type = 'text';
            btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>';
        } else {
            input.type = 'password';
            btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
        }
    }
    </script>
    @endpush
</div>
