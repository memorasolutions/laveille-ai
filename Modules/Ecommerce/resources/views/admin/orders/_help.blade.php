<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        Suivez vos <strong>commandes</strong> de la réception au paiement, avec <strong>factures PDF</strong> et notifications automatiques.
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="truck" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Cycle de commande') }}
    </h6>
    <p class="text-muted small mb-0">
        <strong>En attente</strong> → <strong>En traitement</strong> → <strong>Expédiée</strong> → <strong>Terminée</strong><br>
        Statuts alternatifs : <strong>Annulée</strong> ou <strong>Remboursée</strong>.
    </p>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="clipboard-check" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">1</span>
            <div>
                <strong class="small">{{ __('Voir les détails') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Produits, client, adresses et paiement.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-primary mt-1">2</span>
            <div>
                <strong class="small">{{ __('Mettre à jour le statut') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Le client est notifié automatiquement par courriel.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">3</span>
            <div>
                <strong class="small">{{ __('Télécharger la facture') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('PDF professionnel avec détails complets.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="file-text" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Factures') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Numérotation automatique avec préfixe configurable.') }}</li>
        <li class="mb-1">{{ __('Détail des articles, taxes et livraison inclus.') }}</li>
        <li>{{ __('Téléchargement PDF en un clic depuis la fiche commande.') }}</li>
    </ul>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(239,68,68,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="alert-triangle" class="text-danger" style="width:16px;height:16px;"></i>
        {{ __('Attention') }}
    </h6>
    <ul class="text-muted small ps-3 mb-0">
        <li>{{ __('Chaque changement de statut déclenche une notification automatique au client.') }}</li>
    </ul>
</div>
