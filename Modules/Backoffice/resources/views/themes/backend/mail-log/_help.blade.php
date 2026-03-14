<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        Le <strong>{{ __('journal des emails') }}</strong> est l'historique complet de tous les emails
        envoyés par votre application : confirmations de compte, réinitialisations de mot de passe,
        notifications, newsletters, etc.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="info" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Informations enregistrées') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Envoyé') }}</span>
            <div>
                <strong class="small">{{ __('Email transmis avec succès') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Destinataire, sujet, classe d\'envoi et horodatage sont enregistrés.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-danger mt-1">{{ __('Échoué') }}</span>
            <div>
                <strong class="small">{{ __('Échec de transmission') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('L\'email n\'a pas pu être envoyé - vérifiez la configuration SMTP.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Pourquoi c\'est utile ?') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Vérifier qu\'un') }} <strong>{{ __('email de confirmation') }}</strong> {{ __('a bien été envoyé à un utilisateur') }}</li>
        <li class="mb-1">{{ __('Diagnostiquer pourquoi un utilisateur') }} <strong>{{ __('n\'a pas reçu') }}</strong> {{ __('son email') }}</li>
        <li class="mb-1">{{ __('Prouver l\'envoi pour') }} <strong>{{ __('audit ou conformité') }}</strong></li>
        <li>{{ __('Identifier des patterns d\'') }}<strong>{{ __('échec répétitif') }}</strong></li>
    </ul>
</div>

{{-- Section 4 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Bon à savoir') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Pour des raisons de confidentialité, le') }} <strong>{{ __('corps complet des emails') }}</strong>
        {{ __('n\'est pas conservé dans ce journal - seulement les métadonnées (destinataire, sujet, statut).') }}
        {{ __('Ce journal est conservé à des fins d\'audit et ne se vide pas automatiquement.') }}
    </p>
</div>
