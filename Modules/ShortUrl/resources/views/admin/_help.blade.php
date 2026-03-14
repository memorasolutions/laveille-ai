<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {{ __('Les') }} <strong>{{ __('liens courts') }}</strong> {{ __('vous permettent de') }}
        <strong>{{ __('raccourcir n\'importe quelle URL') }}</strong> {{ __('et de suivre précisément le nombre de clics reçus.') }}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="settings" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1"><strong>{{ __('Raccourcir') }}</strong> {{ __(':') }} {{ __('transformez une URL longue en lien court mémorable.') }}</li>
        <li class="mb-1"><strong>{{ __('Personnaliser') }}</strong> {{ __(':') }} {{ __('choisissez votre propre slug (ex: /s/promo-ete).') }}</li>
        <li><strong>{{ __('Statistiques') }}</strong> {{ __(':') }} {{ __('suivez les clics en temps réel depuis la page de détail.') }}</li>
    </ul>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="clipboard-check" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Cas d\'usage') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">{{ __('Social') }}</span>
            <div>
                <strong class="small">{{ __('Réseaux sociaux') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Partagez des liens propres et courts sur Twitter, Instagram ou LinkedIn.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-warning text-dark mt-1">{{ __('Email') }}</span>
            <div>
                <strong class="small">{{ __('Emails marketing') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Mesurez l\'engagement de chaque lien dans vos campagnes d\'emailing.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-info mt-1">{{ __('QR Code') }}</span>
            <div>
                <strong class="small">{{ __('QR codes et publicités') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Intégrez un lien court dans un QR code imprimé ou une publicité en ligne.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Chaque clic est enregistré avec la') }} <strong>{{ __('date') }}</strong>, {{ __('l\'') }}<strong>{{ __('adresse IP') }}</strong> {{ __('et le') }} <strong>{{ __('référent') }}</strong>.</li>
        <li class="mb-1">{{ __('Vous pouvez désactiver un lien sans le supprimer pour conserver l\'historique des clics.') }}</li>
        <li>{{ __('Un lien peut avoir une date d\'expiration pour les offres limitées dans le temps.') }}</li>
    </ul>
</div>
