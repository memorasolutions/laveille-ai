{{--
    @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
    @project memora/laravel-saas-boilerplate

    Vue Livewire SrsReviewer - session de révision espacée, une carte à la fois.
    Charte : teal #064E5A. Contrastes WCAG AAA (texte foncé sur fonds clairs,
    blanc sur #064E5A = 7.4:1). Aucune popup native ; clavier : Espace révèle,
    1-4 évaluent. DRY : reprend l'esprit « carte plein écran » du DeckPlayer.
--}}
<div
    class="srs-reviewer"
    x-data="{
        init() {
            window.addEventListener('keydown', (e) => {
                if (e.target.matches('input, textarea')) return;
                if ((e.key === ' ' || e.key === 'Enter') && ! @js($revealed) && $wire.currentCard) {
                    e.preventDefault(); $wire.reveal();
                } else if (@js($revealed)) {
                    if (e.key === '1') $wire.rate('again');
                    if (e.key === '2') $wire.rate('hard');
                    if (e.key === '3') $wire.rate('good');
                    if (e.key === '4') $wire.rate('easy');
                }
            });
        }
    }"
    style="max-width:720px;margin:0 auto;padding:24px 16px;"
>
    @php($card = $this->currentCard)

    @if($card === null)
        {{-- File vide : soit rien de dû, soit session terminée. --}}
        <div
            role="status"
            style="text-align:center;padding:48px 24px;border:1px solid #cbd5e1;border-radius:16px;background:#f8fafc;"
        >
            <p style="font-size:48px;margin:0 0 12px;" aria-hidden="true">🎉</p>
            <h1 style="font-size:22px;color:#064E5A;margin:0 0 8px;font-weight:700;">
                @if($reviewedThisSession > 0)
                    Bravo, révision terminée !
                @else
                    Aucune carte à réviser pour l'instant
                @endif
            </h1>
            <p style="color:#334155;margin:0 0 20px;">
                @if($reviewedThisSession > 0)
                    Vous avez révisé {{ $reviewedThisSession }} {{ $reviewedThisSession > 1 ? 'cartes' : 'carte' }}. Revenez plus tard pour la prochaine session.
                @else
                    Complétez une leçon pour générer des cartes, ou repassez quand des cartes seront dues.
                @endif
            </p>
            <a
                href="{{ route('academy.dashboard') }}"
                style="display:inline-block;background:#064E5A;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:10px;font-weight:600;min-height:44px;line-height:20px;"
            >
                Retour à mon espace
            </a>
        </div>
    @else
        {{-- Compteur de file --}}
        <p
            aria-live="polite"
            style="text-align:center;color:#334155;font-size:14px;margin:0 0 16px;font-weight:600;"
        >
            {{ $this->dueCards->count() }} {{ $this->dueCards->count() > 1 ? 'cartes dues' : 'carte due' }}
        </p>

        {{-- Carte plein écran (recto + verso au dévoilement) --}}
        <div
            style="border:2px solid #064E5A;border-radius:16px;background:#ffffff;box-shadow:0 4px 16px rgba(6,78,90,.10);overflow:hidden;"
        >
            <div style="padding:32px 28px;min-height:180px;display:flex;flex-direction:column;justify-content:center;">
                <p style="text-transform:uppercase;letter-spacing:.06em;font-size:12px;color:#0f6b7a;margin:0 0 12px;font-weight:700;">Question</p>
                <div style="font-size:20px;line-height:1.5;color:#0f172a;font-weight:600;">
                    {{ $card->front }}
                </div>
            </div>

            @if($revealed)
                <div style="padding:24px 28px 32px;border-top:1px solid #e2e8f0;background:#f0fdfa;">
                    <p style="text-transform:uppercase;letter-spacing:.06em;font-size:12px;color:#0f6b7a;margin:0 0 12px;font-weight:700;">Réponse</p>
                    <div style="font-size:17px;line-height:1.6;color:#0f172a;">
                        {{ $card->back ?? 'Aucune réponse détaillée pour cette carte : évaluez selon votre rappel du concept.' }}
                    </div>
                </div>
            @endif
        </div>

        {{-- Actions --}}
        <div style="margin-top:20px;">
            @if(! $revealed)
                <button
                    type="button"
                    wire:click="reveal"
                    style="width:100%;background:#064E5A;color:#ffffff;border:none;padding:14px 24px;border-radius:12px;font-size:16px;font-weight:600;cursor:pointer;min-height:48px;"
                >
                    Afficher la réponse
                    <span style="font-weight:400;opacity:.85;font-size:13px;"> (Espace)</span>
                </button>
            @else
                <p style="text-align:center;color:#334155;font-size:14px;margin:0 0 12px;font-weight:600;">Comment avez-vous répondu ?</p>
                <div
                    role="group"
                    aria-label="Auto-évaluation de la carte"
                    style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;"
                >
                    <button type="button" wire:click="rate('again')"
                        style="background:#7f1d1d;color:#ffffff;border:none;padding:14px;border-radius:12px;font-size:15px;font-weight:600;cursor:pointer;min-height:48px;">
                        À revoir <span style="font-weight:400;opacity:.85;">(1)</span>
                    </button>
                    <button type="button" wire:click="rate('hard')"
                        style="background:#92400e;color:#ffffff;border:none;padding:14px;border-radius:12px;font-size:15px;font-weight:600;cursor:pointer;min-height:48px;">
                        Difficile <span style="font-weight:400;opacity:.85;">(2)</span>
                    </button>
                    <button type="button" wire:click="rate('good')"
                        style="background:#064E5A;color:#ffffff;border:none;padding:14px;border-radius:12px;font-size:15px;font-weight:600;cursor:pointer;min-height:48px;">
                        Correct <span style="font-weight:400;opacity:.85;">(3)</span>
                    </button>
                    <button type="button" wire:click="rate('easy')"
                        style="background:#065f46;color:#ffffff;border:none;padding:14px;border-radius:12px;font-size:15px;font-weight:600;cursor:pointer;min-height:48px;">
                        Facile <span style="font-weight:400;opacity:.85;">(4)</span>
                    </button>
                </div>
            @endif
        </div>
    @endif
</div>
