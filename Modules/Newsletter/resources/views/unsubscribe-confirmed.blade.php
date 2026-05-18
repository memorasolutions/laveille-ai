<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Vous êtes désabonné') . ' - ' . config('app.name'))
@section('meta_description', __('Confirmation de votre désabonnement de l\'infolettre La veille de Stef.'))

@push('head')
    <meta name="robots" content="noindex,nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<style>
    .lv-unsub-wrap { max-width: 720px; margin: 0 auto; padding: 32px 16px 64px; }
    .lv-unsub-card {
        background: #ffffff;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        padding: 24px 24px 28px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .lv-unsub-hero {
        text-align: center;
        padding: 32px 16px 24px;
    }
    .lv-unsub-hero h1 {
        color: #064E5A;
        font-size: 1.85rem;
        line-height: 1.2;
        margin: 0 0 12px;
        font-weight: 700;
    }
    .lv-unsub-hero p { color: #374151; font-size: 1.02rem; line-height: 1.55; margin: 0 auto; max-width: 540px; }
    .lv-unsub-alert {
        background: #D1FAE5;
        border-left: 4px solid #065F46;
        color: #064E3B;
        padding: 14px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 0.97rem;
    }
    .lv-unsub-card h2 {
        color: #064E5A;
        font-size: 1.25rem;
        margin: 0 0 14px;
        font-weight: 700;
    }
    .lv-unsub-card p.help { color: #4B5563; font-size: 0.95rem; line-height: 1.5; margin: 0 0 16px; }
    .lv-radio-list { list-style: none; padding: 0; margin: 0 0 14px; }
    .lv-radio-item { display: flex; align-items: flex-start; gap: 10px; padding: 10px 12px; border: 1px solid #E5E7EB; border-radius: 8px; margin-bottom: 8px; cursor: pointer; transition: background 120ms ease, border-color 120ms ease; }
    .lv-radio-item:hover { background: #F0F4F8; border-color: #064E5A; }
    .lv-radio-item input[type="radio"] { margin-top: 3px; min-width: 18px; min-height: 18px; accent-color: #064E5A; }
    .lv-radio-item span { font-size: 0.97rem; color: #1F2937; line-height: 1.4; }
    .lv-textarea {
        width: 100%;
        min-height: 90px;
        padding: 10px 12px;
        border: 1px solid #D1D5DB;
        border-radius: 8px;
        font-size: 0.97rem;
        font-family: inherit;
        color: #1F2937;
        background: #FAFAFA;
        resize: vertical;
        margin-bottom: 14px;
    }
    .lv-textarea:focus { outline: 2px solid #064E5A; outline-offset: 2px; background: #ffffff; border-color: #064E5A; }
    .lv-btn-row { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    .lv-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 44px;
        padding: 10px 18px;
        border-radius: 8px;
        font-size: 0.97rem;
        font-weight: 600;
        font-family: inherit;
        text-decoration: none;
        cursor: pointer;
        transition: background 120ms ease, transform 80ms ease;
        border: 1px solid transparent;
        line-height: 1.2;
    }
    .lv-btn-primary { background: #064E5A; color: #ffffff; }
    .lv-btn-primary:hover, .lv-btn-primary:focus-visible { background: #053842; color: #ffffff; }
    .lv-btn-accent { background: #9A2A06; color: #ffffff; }
    .lv-btn-accent:hover, .lv-btn-accent:focus-visible { background: #7A2105; color: #ffffff; }
    .lv-btn-secondary { background: #ffffff; color: #064E5A; border-color: #064E5A; }
    .lv-btn-secondary:hover, .lv-btn-secondary:focus-visible { background: #F0F4F8; color: #053842; }
    .lv-btn-link { background: none; color: #064E5A; text-decoration: underline; padding: 10px 8px; }
    .lv-btn-link:hover, .lv-btn-link:focus-visible { color: #053842; text-decoration: none; }
    .lv-btn:focus-visible { outline: 2px solid #9A2A06; outline-offset: 2px; }
    .lv-btn:disabled { opacity: 0.6; cursor: not-allowed; }
    .lv-alt-grid { display: grid; gap: 10px; }
    @media (min-width: 560px) {
        .lv-alt-grid { grid-template-columns: 1fr 1fr 1fr; }
    }
    .lv-alt-btn {
        text-align: left;
        padding: 14px 16px;
        border-radius: 8px;
        background: #F0F4F8;
        color: #1F2937;
        border: 1px solid #E5E7EB;
        cursor: pointer;
        font-family: inherit;
        font-size: 0.95rem;
        line-height: 1.4;
        min-height: 44px;
        transition: background 120ms ease, border-color 120ms ease;
    }
    .lv-alt-btn:hover, .lv-alt-btn:focus-visible { background: #E0E7EC; border-color: #064E5A; }
    .lv-alt-btn strong { display: block; color: #064E5A; font-weight: 700; margin-bottom: 4px; font-size: 1rem; }
    .lv-alt-btn span { color: #4B5563; font-size: 0.88rem; }
    .lv-footer-links { text-align: center; margin-top: 28px; font-size: 0.92rem; color: #6B7280; }
    .lv-footer-links a { color: #064E5A; text-decoration: underline; margin: 0 8px; }
    .lv-spinner {
        width: 14px; height: 14px;
        border: 2px solid rgba(255,255,255,0.4);
        border-top-color: #ffffff;
        border-radius: 50%;
        animation: lvSpin 700ms linear infinite;
        display: inline-block;
    }
    @keyframes lvSpin { to { transform: rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) {
        .lv-spinner { animation: none; }
    }
</style>
@endpush

@section('content')
<div class="lv-unsub-wrap"
     x-data="lvUnsubSurvey({
        feedbackUrl: @js(route('newsletter.feedback', ['token' => $subscriber->token])),
        pauseUrl: @js(route('newsletter.pause', ['token' => $subscriber->token])),
        frequencyUrl: @js(route('newsletter.frequency', ['token' => $subscriber->token])),
        resubscribeUrl: @js(route('newsletter.resubscribe', ['token' => $subscriber->token])),
     })">

    <header class="lv-unsub-hero">
        <h1>{{ __('Vous êtes désabonné') }}</h1>
        <p>
            {{ __('Confirmation effectuée le') }}
            <strong>{{ ($subscriber->unsubscribed_at ?? now())->translatedFormat('j F Y \à H\hi') }}</strong>.
            {{ __('Vous ne recevrez plus de courriels de la part de La veille de Stef.') }}
        </p>
    </header>

    <div class="lv-unsub-alert" role="status" aria-live="polite">
        {{ __('Votre désabonnement est effectif immédiatement. Aucune autre action n\'est requise.') }}
    </div>

    {{-- Survey feedback (optionnel) --}}
    <section class="lv-unsub-card" aria-labelledby="lv-survey-title" x-show="!feedbackSent" x-cloak>
        <h2 id="lv-survey-title">{{ __('Pourquoi partez-vous ?') }}</h2>
        <p class="help">{{ __('Votre retour nous aide à améliorer l\'infolettre. Optionnel.') }}</p>

        <form @submit.prevent="submitFeedback" novalidate>
            <ul class="lv-radio-list" role="radiogroup" aria-labelledby="lv-survey-title">
                <li>
                    <label class="lv-radio-item">
                        <input type="radio" name="reason" value="too_frequent" x-model="reason">
                        <span>{{ __('Trop de courriels') }}</span>
                    </label>
                </li>
                <li>
                    <label class="lv-radio-item">
                        <input type="radio" name="reason" value="not_relevant" x-model="reason">
                        <span>{{ __('Contenu peu pertinent pour moi') }}</span>
                    </label>
                </li>
                <li>
                    <label class="lv-radio-item">
                        <input type="radio" name="reason" value="no_value" x-model="reason">
                        <span>{{ __('Je trouve plus de valeur ailleurs') }}</span>
                    </label>
                </li>
                <li>
                    <label class="lv-radio-item">
                        <input type="radio" name="reason" value="life_change" x-model="reason">
                        <span>{{ __('Ma situation a changé') }}</span>
                    </label>
                </li>
                <li>
                    <label class="lv-radio-item">
                        <input type="radio" name="reason" value="other" x-model="reason">
                        <span>{{ __('Autre raison') }}</span>
                    </label>
                </li>
            </ul>

            <label for="lv-feedback-textarea" class="visually-hidden">{{ __('Commentaire libre (optionnel)') }}</label>
            <textarea id="lv-feedback-textarea"
                      class="lv-textarea"
                      x-model="feedback"
                      maxlength="1000"
                      placeholder="{{ __('Un commentaire à partager ? (optionnel, 1000 caractères max)') }}"></textarea>

            <div class="lv-btn-row">
                <button type="submit" class="lv-btn lv-btn-primary" :disabled="!reason || submitting">
                    <template x-if="submitting"><span class="lv-spinner" aria-hidden="true"></span></template>
                    <span x-text="submitting ? @js(__('Envoi…')) : @js(__('Envoyer le retour'))"></span>
                </button>
                <button type="button" class="lv-btn lv-btn-link" @click="feedbackSent = true">
                    {{ __('Passer cette étape') }}
                </button>
            </div>
        </form>
    </section>

    <div class="lv-unsub-card" x-show="feedbackSent" x-cloak role="status" aria-live="polite">
        <p style="margin:0;color:#064E3B;">
            <strong>{{ __('Merci !') }}</strong>
            {{ __('Votre retour nous aidera à améliorer l\'infolettre.') }}
        </p>
    </div>

    {{-- Alternatives au désabonnement --}}
    <section class="lv-unsub-card" aria-labelledby="lv-alt-title">
        <h2 id="lv-alt-title">{{ __('Avant de partir, essayez plutôt…') }}</h2>
        <p class="help">{{ __('Vous pouvez reprendre votre abonnement à tout moment.') }}</p>

        <div class="lv-alt-grid">
            <button type="button" class="lv-alt-btn" @click="pause(30)" :disabled="busy">
                <strong>{{ __('Mettre en pause') }}</strong>
                <span>{{ __('30 jours sans courriel, puis reprise automatique.') }}</span>
            </button>
            <button type="button" class="lv-alt-btn" @click="setFrequency('monthly')" :disabled="busy">
                <strong>{{ __('Recevoir moins souvent') }}</strong>
                <span>{{ __('1 courriel par mois seulement.') }}</span>
            </button>
            <button type="button" class="lv-alt-btn" @click="resubscribe()" :disabled="busy">
                <strong>{{ __('Me réabonner') }}</strong>
                <span>{{ __('Annuler ce désabonnement et continuer.') }}</span>
            </button>
        </div>
    </section>

    <p class="lv-footer-links">
        <a href="{{ url('/') }}">{{ __('Retour à l\'accueil') }}</a>
        <a href="{{ route('newsletter.archive') }}">{{ __('Archives de l\'infolettre') }}</a>
        @if(\Illuminate\Support\Facades\Route::has('legal.privacy'))
            <a href="{{ route('legal.privacy') }}">{{ __('Confidentialité') }}</a>
        @endif
    </p>
</div>

<script>
    function lvUnsubSurvey(config) {
        return {
            reason: '',
            feedback: '',
            submitting: false,
            busy: false,
            feedbackSent: false,
            csrf() {
                const el = document.querySelector('meta[name="csrf-token"]');
                return el ? el.getAttribute('content') : '';
            },
            toast(message, variant) {
                window.dispatchEvent(new CustomEvent('toast-show', {
                    detail: { message: message, variant: variant || 'success', duration: 4500 }
                }));
            },
            async postJson(url, payload) {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload || {}),
                });
                let data = {};
                try { data = await response.json(); } catch (e) { data = {}; }
                return { ok: response.ok, status: response.status, data: data };
            },
            async submitFeedback() {
                if (!this.reason || this.submitting) return;
                this.submitting = true;
                const res = await this.postJson(config.feedbackUrl, { reason: this.reason, feedback: this.feedback });
                this.submitting = false;
                if (res.ok) {
                    this.feedbackSent = true;
                    this.toast(res.data.message || @js(__('Merci pour votre retour.')), 'success');
                } else {
                    this.toast(res.data.message || @js(__('Erreur, réessayez.')), 'error');
                }
            },
            async pause(days) {
                if (this.busy) return;
                this.busy = true;
                const res = await this.postJson(config.pauseUrl, { days: days });
                this.busy = false;
                if (res.ok) {
                    this.toast(res.data.message || @js(__('Abonnement mis en pause.')), 'success');
                } else {
                    this.toast(res.data.message || @js(__('Erreur, réessayez.')), 'error');
                }
            },
            async setFrequency(frequency) {
                if (this.busy) return;
                this.busy = true;
                const res = await this.postJson(config.frequencyUrl, { frequency: frequency });
                this.busy = false;
                if (res.ok) {
                    this.toast(res.data.message || @js(__('Préférence mise à jour.')), 'success');
                } else {
                    this.toast(res.data.message || @js(__('Erreur, réessayez.')), 'error');
                }
            },
            async resubscribe() {
                if (this.busy) return;
                this.busy = true;
                const res = await this.postJson(config.resubscribeUrl, {});
                this.busy = false;
                if (res.ok) {
                    this.toast(res.data.message || @js(__('Réabonnement confirmé.')), 'success');
                } else {
                    this.toast(res.data.message || @js(__('Erreur, réessayez.')), 'error');
                }
            },
        };
    }
</script>
@endsection
