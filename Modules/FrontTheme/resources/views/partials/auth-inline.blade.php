{{-- Auth inline OTP : composant reutilisable pour connexion sans quitter la page --}}
{{-- Usage : @include('fronttheme::partials.auth-inline', ['message' => 'pour partager un screenshot']) --}}
@guest
<div x-data="{
    showAuth: false, authEmail: '', authCode: '', authSending: false, authVerifying: false, authSent: false, authError: '',
    async sendCode() {
        if (!this.authEmail || this.authSending) return;
        this.authSending = true; this.authError = '';
        try {
            const res = await fetch('{{ route('magic-link.api.send') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                body: JSON.stringify({ email: this.authEmail })
            });
            const d = await res.json();
            if (d.success) { this.authSent = true; } else { this.authError = d.message; }
        } catch(e) { this.authError = '{{ __('Erreur reseau.') }}'; }
        finally { this.authSending = false; }
    },
    async verifyCode() {
        if (!this.authCode || this.authCode.length !== 6 || this.authVerifying) return;
        this.authVerifying = true; this.authError = '';
        try {
            const res = await fetch('{{ route('magic-link.api.verify') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                body: JSON.stringify({ email: this.authEmail, token: this.authCode })
            });
            const d = await res.json();
            if (d.success) { window.location.reload(); } else { this.authError = d.message; }
        } catch(e) { this.authError = '{{ __('Erreur reseau.') }}'; }
        finally { this.authVerifying = false; }
    }
}">
    {{-- Bouton declencheur --}}
    <div x-show="!showAuth" style="text-align: center; padding: 16px; background: #F3F4F6; border-radius: var(--r-base);">
        <button type="button" @click="showAuth = true" style="background: none; border: none; color: var(--c-primary); font-weight: 600; cursor: pointer;">{{ __('Connectez-vous') }}</button> {{ $message ?? __('pour continuer.') }}
    </div>

    {{-- Formulaire auth inline --}}
    <div x-show="showAuth" x-cloak x-transition style="background: #fff; border: 2px solid var(--c-primary); border-radius: var(--r-base); padding: 20px; margin-top: 8px;">
        <div style="text-align: center; margin-bottom: 12px;">
            <span style="font-size: 24px;">🔐</span>
            <h4 style="font-family: var(--f-heading); color: var(--c-dark); margin: 4px 0 0; font-size: 15px;">{{ __('Connexion rapide') }}</h4>
        </div>

        {{-- Email --}}
        <div x-show="!authSent">
            <div style="display: flex; gap: 8px;">
                <input type="email" x-model="authEmail" placeholder="vous@exemple.com" aria-label="{{ __('Adresse courriel') }}"
                    @keydown.enter="sendCode()"
                    style="flex: 1; height: 40px; padding: 0 12px; border: 1px solid #E5E7EB; border-radius: var(--r-base); font-size: 14px; outline: none;">
                <button type="button" @click="sendCode()" :disabled="authSending || !authEmail"
                    :style="'height:40px;padding:0 16px;background:var(--c-primary);color:#fff;font-weight:600;border:none;border-radius:var(--r-btn);cursor:pointer;font-size:13px;' + (authSending || !authEmail ? 'opacity:0.5;' : '')">
                    <span x-show="!authSending">{{ __('Envoyer le code') }}</span>
                    <span x-show="authSending">⏳</span>
                </button>
            </div>
        </div>

        {{-- OTP --}}
        <div x-show="authSent" x-transition>
            <div style="background: #D1FAE5; color: #065F46; padding: 8px 12px; border-radius: var(--r-base); font-size: 12px; margin-bottom: 10px;">
                ✓ {{ __('Code envoye a') }} <strong x-text="authEmail"></strong>
            </div>
            <div style="display: flex; gap: 8px;">
                <input type="text" x-model="authCode" maxlength="6" placeholder="000000" aria-label="{{ __('Code de connexion') }}" autocomplete="one-time-code" inputmode="numeric"
                    @keydown.enter="verifyCode()" @input="if(authCode.length === 6) verifyCode()"
                    style="flex: 1; height: 40px; padding: 0 12px; border: 1px solid #E5E7EB; border-radius: var(--r-base); font-size: 18px; font-weight: 700; letter-spacing: 6px; text-align: center; outline: none;">
                <button type="button" @click="verifyCode()" :disabled="authVerifying || authCode.length !== 6"
                    :style="'height:40px;padding:0 16px;background:var(--c-primary);color:#fff;font-weight:600;border:none;border-radius:var(--r-btn);cursor:pointer;font-size:13px;' + (authVerifying || authCode.length !== 6 ? 'opacity:0.5;' : '')">
                    <span x-show="!authVerifying">{{ __('Valider') }}</span>
                    <span x-show="authVerifying">⏳</span>
                </button>
            </div>
            <button type="button" @click="authSent = false; authCode = ''" style="background: none; border: none; color: var(--c-primary); cursor: pointer; font-size: 11px; margin-top: 6px;">{{ __('Renvoyer le code') }}</button>
        </div>

        <div x-show="authError" x-cloak style="margin-top: 8px; color: #DC2626; font-size: 12px;" x-text="authError"></div>
        <div style="text-align: right; margin-top: 6px;">
            <button type="button" @click="showAuth = false" style="background: none; border: none; color: #9CA3AF; cursor: pointer; font-size: 11px;">{{ __('Annuler') }}</button>
        </div>
    </div>
</div>
@endguest
