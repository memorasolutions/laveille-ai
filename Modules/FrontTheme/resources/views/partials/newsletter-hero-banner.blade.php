<section id="newsletter-hero" class="newsletter-hero-banner"
         x-data="{
             submitted: false,
             submitting: false,
             email: '',
             async submit() {
                 if (this.submitting) return;
                 this.submitting = true;
                 try {
                     const res = await fetch('{{ route('newsletter.subscribe') }}', {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                             'Accept': 'application/json',
                             'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                             'X-Requested-With': 'XMLHttpRequest'
                         },
                         body: JSON.stringify({ email: this.email, source: 'hero-homepage' })
                     });
                     const data = await res.json().catch(() => ({}));
                     if (res.ok) {
                         this.submitted = true;
                         window.dispatchEvent(new CustomEvent('toast-show', {
                             detail: {
                                 variant: 'success',
                                 message: data.message || @js(__('Vérifiez votre courriel pour confirmer votre abonnement.')),
                                 duration: 6000
                             }
                         }));
                     } else if (res.status === 422) {
                         const msg = (data.errors && data.errors.email && data.errors.email[0]) || @js(__('Adresse courriel invalide.'));
                         window.dispatchEvent(new CustomEvent('toast-show', {
                             detail: { variant: 'error', message: msg, duration: 6000 }
                         }));
                     } else {
                         window.dispatchEvent(new CustomEvent('toast-show', {
                             detail: {
                                 variant: 'error',
                                 message: data.message || @js(__('Une erreur est survenue. Veuillez réessayer.')),
                                 duration: 6000
                             }
                         }));
                     }
                 } catch (e) {
                     window.dispatchEvent(new CustomEvent('toast-show', {
                         detail: {
                             variant: 'error',
                             message: @js(__('Connexion impossible. Vérifiez votre réseau et réessayez.')),
                             duration: 6000
                         }
                     }));
                 } finally {
                     this.submitting = false;
                 }
             }
         }"
         style="padding: 80px 24px; margin: 80px auto; max-width: 1200px; background: linear-gradient(to bottom, #FFF8F1, #FFFDF9); scroll-margin-top: 100px; border-radius: 16px;">
    <div style="max-width: 800px; margin: 0 auto; text-align: center;">
        <h2 style="font-size: 1.8rem; margin-bottom: 12px;">{{ __('Veille IA Québec - chaque mercredi dans votre boîte') }}</h2>
        <p style="font-size: 1.1rem; margin-bottom: 24px;">{{ __('Rejoignez 50+ professionnels qui suivent la transformation IA du Québec - 5 min de lecture hebdo, 0 spam, gratuit') }}</p>

        <template x-if="!submitted">
            <form @submit.prevent="submit"
                  action="{{ route('newsletter.subscribe') }}" method="POST"
                  style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-bottom: 16px;">
                @csrf
                <input type="hidden" name="source" value="hero-homepage">
                <input type="email" name="email" x-model="email" required
                       aria-label="{{ __('Votre adresse e-mail') }}"
                       :disabled="submitting"
                       style="flex: 1; min-width: 280px; padding: 12px 16px; border: 1px solid #ccc; border-radius: 6px; font-size: 1rem;">
                <button type="submit" :disabled="submitting"
                        style="background-color: #F97316; color: white; border: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 1rem; white-space: nowrap; min-height: 48px;">
                    <span x-show="!submitting">{{ __('S’inscrire') }}</span>
                    <span x-show="submitting" x-cloak>{{ __('Envoi...') }}</span>
                </button>
            </form>
        </template>

        <template x-if="submitted">
            <div role="status" aria-live="polite" x-init="$nextTick(() => $el.querySelector('h3')?.focus())"
                 style="background: #ECFDF5; border: 1px solid #065F46; border-radius: 12px; padding: 28px 24px; max-width: 560px; margin: 0 auto 16px;">
                <div aria-hidden="true" style="font-size: 36px; line-height: 1; color: #065F46;">✓</div>
                <h3 tabindex="-1" style="font-size: 1.2rem; margin: 12px 0 8px; color: #064E3B; outline: none;">{{ __('Vérifiez votre courriel !') }}</h3>
                <p style="margin: 0; color: #064E3B;">{{ __('Un courriel de confirmation a été envoyé à') }} <strong x-text="email"></strong>. {{ __('Pensez à regarder dans vos courriers indésirables (spams) si vous ne le voyez pas dans quelques minutes.') }}</p>
            </div>
        </template>

        <p style="font-size: 0.875rem; color: #666;">{{ __('Conforme Loi 25 + RGPD - Désabonnement 1-clic') }}</p>
    </div>
</section>
