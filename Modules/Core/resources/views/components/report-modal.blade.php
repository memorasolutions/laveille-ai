{{-- Composant réutilisable : bouton signalement avec modale centrée
     Usage: @include('core::components.report-modal', ['reportUrl' => route(...), 'csrfToken' => csrf_token()])
     Source unique de vérité pour le signalement de contenu sur toute la plateforme.
--}}
<div x-data="{ showReport: false, reason: '', details: '', sending: false, done: false }" style="display:inline;">
    <button @click="showReport = true" type="button" class="ct-btn ct-btn-ghost ct-btn-xs" title="{{ __('Signaler') }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
    </button>

    {{-- Modale signalement --}}
    <div x-cloak @click.self="showReport = false"
         :style="showReport ? 'position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;' : 'display:none'">
        <div @click.stop style="background:#fff;border-radius:16px;padding:24px;max-width:440px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <h4 style="font-weight:700;color:var(--c-dark);margin:0;">{{ __('Signaler ce contenu') }}</h4>
                <button @click="showReport = false" style="background:none;border:none;font-size:20px;cursor:pointer;color:#374151;">&times;</button>
            </div>

            <template x-if="!done">
                <div>
                    <p style="color:var(--c-text-muted);font-size:14px;margin-bottom:16px;">{{ __('Veuillez indiquer la raison de votre signalement.') }}</p>

                    <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px;">
                        <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;font-size:14px;" :style="reason==='spam' && 'border-color:var(--c-primary);background:var(--c-primary-light)'">
                            <input type="radio" x-model="reason" value="spam" style="accent-color:var(--c-primary);"> {{ __('Spam ou contenu promotionnel') }}
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;font-size:14px;" :style="reason==='inappropriate' && 'border-color:var(--c-primary);background:var(--c-primary-light)'">
                            <input type="radio" x-model="reason" value="inappropriate" style="accent-color:var(--c-primary);"> {{ __('Contenu inapproprié ou offensant') }}
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;font-size:14px;" :style="reason==='inaccurate' && 'border-color:var(--c-primary);background:var(--c-primary-light)'">
                            <input type="radio" x-model="reason" value="inaccurate" style="accent-color:var(--c-primary);"> {{ __('Information inexacte ou trompeuse') }}
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;font-size:14px;" :style="reason==='broken' && 'border-color:var(--c-primary);background:var(--c-primary-light)'">
                            <input type="radio" x-model="reason" value="broken" style="accent-color:var(--c-primary);"> {{ __('Lien brisé ou ressource indisponible') }}
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;font-size:14px;" :style="reason==='other' && 'border-color:var(--c-primary);background:var(--c-primary-light)'">
                            <input type="radio" x-model="reason" value="other" style="accent-color:var(--c-primary);"> {{ __('Autre') }}
                        </label>
                    </div>

                    <div style="margin-bottom:16px;">
                        <label style="font-size:13px;font-weight:600;display:block;margin-bottom:4px;">{{ __('Détails (optionnel)') }}</label>
                        <textarea x-model="details" rows="2" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:14px;resize:vertical;" placeholder="{{ __('Précisez votre signalement...') }}"></textarea>
                    </div>

                    <div style="display:flex;gap:8px;justify-content:flex-end;">
                        <button @click="showReport = false" class="ct-btn ct-btn-ghost ct-btn-sm">{{ __('Annuler') }}</button>
                        <button @click="if(!reason){return}; sending=true; var tk=document.querySelector('meta[name=csrf-token]')?.content||'{{ $csrfToken }}'; fetch('{{ $reportUrl }}', {method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':tk,'Accept':'application/json'},body:JSON.stringify({reason:reason,details:details})}).then(()=>{done=true;sending=false})"
                                :disabled="!reason || sending"
                                class="ct-btn ct-btn-danger ct-btn-sm" :style="(!reason || sending) && 'opacity:0.5;cursor:not-allowed'">
                            <span x-show="!sending">{{ __('Envoyer le signalement') }}</span>
                            <span x-show="sending">{{ __('Envoi...') }}</span>
                        </button>
                    </div>
                </div>
            </template>

            <template x-if="done">
                <div style="text-align:center;padding:20px 0;">
                    <div style="font-size:36px;margin-bottom:8px;">✅</div>
                    <p style="font-weight:600;color:var(--c-dark);">{{ __('Signalement envoyé') }}</p>
                    <p style="color:var(--c-text-muted);font-size:14px;">{{ __('Notre équipe examinera ce contenu. Merci.') }}</p>
                    <button @click="showReport = false" class="ct-btn ct-btn-primary ct-btn-sm" style="margin-top:12px;">{{ __('Fermer') }}</button>
                </div>
            </template>
        </div>
    </div>
</div>
