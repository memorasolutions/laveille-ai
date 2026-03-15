<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Section 1 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
        {{ __('En un mot') }}
    </h6>
    <p class="text-muted small">
        {!! __('Les <strong>Témoignages</strong> permettent de collecter et d\'afficher les <strong>avis et témoignages de vos clients</strong> sur votre site public pour renforcer la confiance et la crédibilité.') !!}
    </p>
</div>

{{-- Section 2 --}}
<div class="p-3 bg-light rounded mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="list-checks" class="text-info" style="width:16px;height:16px;"></i>
        {{ __('Fonctionnalités') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Ajoutez, modérez et mettez en avant les meilleurs témoignages. Gérez la note (1 à 5 étoiles), la photo de l\'auteur et l\'ordre d\'affichage par glisser-déposer.') }}
    </p>
</div>

{{-- Section 3 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
        <i data-lucide="eye" class="text-success" style="width:16px;height:16px;"></i>
        {{ __('Affichage sur le site') }}
    </h6>
    <div class="d-flex flex-column gap-2">
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-success mt-1">{{ __('Approuvé') }}</span>
            <div>
                <strong class="small">{{ __('Visible sur le site public') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Les témoignages approuvés apparaissent automatiquement dans la section témoignages de votre site.') }}</p>
            </div>
        </div>
        <div class="d-flex align-items-start gap-3">
            <span class="badge bg-warning text-dark mt-1">{{ __('En attente') }}</span>
            <div>
                <strong class="small">{{ __('En cours de modération') }}</strong>
                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Le témoignage est enregistré mais ne s\'affiche pas encore sur le site. Approuvez-le depuis le formulaire d\'édition.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Section 4 --}}
<div class="mb-4">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Comment ça marche ?') }}
    </h6>
    <ol class="text-muted small ps-3 mb-0">
        <li class="mb-1">{{ __('Cliquez sur') }} <strong>{{ __('Nouveau témoignage') }}</strong> {{ __('pour en ajouter un manuellement.') }}</li>
        <li class="mb-1">{{ __('Remplissez le nom, le titre, le contenu et la note en étoiles.') }}</li>
        <li class="mb-1">{{ __('Cochez') }} <strong>{{ __('Approuvé') }}</strong> {{ __('pour le rendre visible sur le site.') }}</li>
        <li>{{ __('Glissez-déposez les entrées pour définir l\'ordre d\'affichage.') }}</li>
    </ol>
</div>

{{-- Section 5 --}}
<div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
        <i data-lucide="badge-check" class="text-primary" style="width:16px;height:16px;"></i>
        {{ __('Astuce crédibilité') }}
    </h6>
    <p class="text-muted small mb-0">
        {{ __('Ajoutez une photo de profil et le nom de l\'entreprise du client pour renforcer la crédibilité du témoignage. Un témoignage avec photo et titre professionnel est 3x plus convaincant qu\'un texte seul.') }}
    </p>
</div>
