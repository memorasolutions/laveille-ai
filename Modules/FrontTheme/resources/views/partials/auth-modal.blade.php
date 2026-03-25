{{-- Auth modal OTP : composant unique pour connexion rapide partout sur le site --}}
{{-- Inclure UNE SEULE FOIS dans le layout master. Ouvrir via : $dispatch('open-auth-modal', { message: 'pour voter' }) --}}
@guest
<div
    x-data="{
        open: false,
        step: 'email',
        email: '',
        code: '',
        loading: false,
        error: '',
        successMsg: '',
        message: '',

        init() {
            this.$watch('open', v => {
                if (v) {
                    this.step = 'email'; this.email = ''; this.code = ''; this.error = ''; this.successMsg = '';
                    this.$nextTick(() => this.$refs.emailInput && this.$refs.emailInput.focus());
                }
                document.body.style.overflow = v ? 'hidden' : '';
            });
        },

        async sendCode() {
            if (this.loading || !this.email) return;
            this.loading = true; this.error = '';
            try {
                const res = await fetch('{{ route('magic-link.api.send') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ email: this.email })
                });
                const d = await res.json();
                if (d.success) {
                    this.step = 'code';
                    this.successMsg = 'Code envoyé à ' + this.email;
                    this.$nextTick(() => this.$refs.codeInput && this.$refs.codeInput.focus());
                } else { this.error = d.message || 'Erreur lors de l\u0027envoi.'; }
            } catch(e) { this.error = 'Erreur réseau.'; }
            finally { this.loading = false; }
        },

        async verifyCode() {
            if (this.loading || this.code.length !== 6) return;
            this.loading = true; this.error = '';
            try {
                const res = await fetch('{{ route('magic-link.api.verify') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ email: this.email, token: this.code })
                });
                const d = await res.json();
                if (d.success) { window.location.reload(); } else { this.error = d.message || 'Code invalide.'; }
            } catch(e) { this.error = 'Erreur réseau.'; }
            finally { this.loading = false; }
        },

        onCodeInput(e) {
            this.code = e.target.value.replace(/\D/g, '').slice(0, 6);
            e.target.value = this.code;
            if (this.code.length === 6) this.verifyCode();
        }
    }"
    @open-auth-modal.window="open = true; message = $event.detail?.message || ''"
    x-show="open"
    x-cloak
    @click.self="open = false"
    @keydown.escape.window="open = false"
    style="position:fixed; top:0; left:0; right:0; bottom:0; z-index:9999; background:rgba(0,0,0,0.5);"
>
    <div @click.stop style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); max-width: 420px; width: 90%; background: #fff; border-radius: var(--r-base); padding: 32px; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">

        {{-- Header --}}
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <div style="font-family: var(--f-heading); font-size: 18px; font-weight: 700; color: var(--c-dark);">
                🔐 {{ __('Connexion rapide') }}
            </div>
            <button type="button" @click="open = false" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #9CA3AF; line-height: 1;" aria-label="{{ __('Fermer') }}">&times;</button>
        </div>

        {{-- Message dynamique --}}
        <p x-show="message" x-text="message" style="margin: 0 0 20px; color: #6B7280; font-size: 14px;"></p>
        <p x-show="!message" style="margin: 0 0 20px; color: #6B7280; font-size: 14px;">{{ __('Entrez votre courriel pour recevoir un code à 6 chiffres.') }}</p>

        {{-- Étape email --}}
        <div x-show="step === 'email'">
            <form @submit.prevent="sendCode()">
                <input x-ref="emailInput" type="email" x-model="email" placeholder="vous@exemple.com" required
                    autocomplete="email" aria-label="{{ __('Adresse courriel') }}"
                    style="width: 100%; height: 44px; padding: 0 14px; border: 1px solid #E5E7EB; border-radius: var(--r-base); font-size: 15px; outline: none; box-sizing: border-box; margin-bottom: 12px;"
                    @focus="$el.style.borderColor = 'var(--c-primary)'" @blur="$el.style.borderColor = '#E5E7EB'">
                <div style="display: flex; gap: 10px;">
                    <button type="button" @click="open = false"
                        style="flex: 1; height: 42px; background: #F3F4F6; color: var(--c-dark); border: none; border-radius: var(--r-btn); font-weight: 600; cursor: pointer; font-size: 14px;">
                        {{ __('Annuler') }}
                    </button>
                    <button type="submit" :disabled="loading || !email"
                        :style="'flex:1;height:42px;background:var(--c-primary);color:#fff;border:none;border-radius:var(--r-btn);font-weight:600;cursor:pointer;font-size:14px;' + (loading || !email ? 'opacity:0.5;' : '')">
                        <span x-show="!loading">{{ __('Envoyer le code') }}</span>
                        <span x-show="loading">⏳ {{ __('Envoi...') }}</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Étape OTP --}}
        <div x-show="step === 'code'" x-transition>
            <div style="background: #D1FAE5; color: #065F46; padding: 10px 14px; border-radius: var(--r-base); font-size: 13px; margin-bottom: 14px;">
                ✓ <span x-text="successMsg"></span>
            </div>
            <input x-ref="codeInput" type="text" inputmode="numeric" maxlength="6" placeholder="000000"
                autocomplete="one-time-code" aria-label="{{ __('Code de connexion') }}"
                @input="onCodeInput($event)" @keydown.enter="verifyCode()"
                style="width: 100%; height: 48px; padding: 0 14px; border: 1px solid #E5E7EB; border-radius: var(--r-base); font-size: 22px; font-weight: 700; letter-spacing: 8px; text-align: center; outline: none; box-sizing: border-box; margin-bottom: 12px;"
                @focus="$el.style.borderColor = 'var(--c-primary)'" @blur="$el.style.borderColor = '#E5E7EB'">
            <div style="display: flex; gap: 10px;">
                <button type="button" @click="open = false"
                    style="flex: 1; height: 42px; background: #F3F4F6; color: var(--c-dark); border: none; border-radius: var(--r-btn); font-weight: 600; cursor: pointer; font-size: 14px;">
                    {{ __('Annuler') }}
                </button>
                <button type="button" @click="verifyCode()" :disabled="loading || code.length !== 6"
                    :style="'flex:1;height:42px;background:var(--c-primary);color:#fff;border:none;border-radius:var(--r-btn);font-weight:600;cursor:pointer;font-size:14px;' + (loading || code.length !== 6 ? 'opacity:0.5;' : '')">
                    <span x-show="!loading">{{ __('Valider') }}</span>
                    <span x-show="loading">⏳</span>
                </button>
            </div>
            <div style="text-align: center; margin-top: 10px;">
                <button type="button" @click="step = 'email'; code = ''; error = ''; successMsg = ''" :disabled="loading"
                    style="background: none; border: none; color: var(--c-primary); font-size: 12px; cursor: pointer; text-decoration: underline;">
                    {{ __('Renvoyer le code') }}
                </button>
            </div>
        </div>

        {{-- Erreur --}}
        <p x-show="error" x-cloak x-text="error" style="margin-top: 12px; color: #DC2626; font-size: 13px; text-align: center;"></p>
    </div>
</div>
@endguest
