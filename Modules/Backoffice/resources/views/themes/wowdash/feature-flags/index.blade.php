@extends('backoffice::layouts.admin', ['title' => 'Feature Flags', 'subtitle' => 'Gestion'])

@section('content')

<div class="card h-100 p-0 radius-12">
    {{-- Principe ADHD: header avec aide contextuelle --}}
    <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center justify-content-end">
        <button type="button" class="btn btn-outline-neutral-600 btn-sm radius-8 d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#helpFeatureFlagsModal">
            <iconify-icon icon="solar:question-circle-outline" class="text-lg"></iconify-icon>
            Aide
        </button>
    </div>
    <div class="card-body p-24">
        @livewire('backoffice-feature-flags-table')
    </div>
</div>

{{-- Principe ADHD: aide contextuelle - explication complète pour débutants --}}
<div class="modal fade" id="helpFeatureFlagsModal" tabindex="-1" aria-labelledby="helpFeatureFlagsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content radius-8">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold d-flex align-items-center gap-2" id="helpFeatureFlagsLabel">
                    <iconify-icon icon="solar:flag-outline" class="text-primary-600"></iconify-icon>
                    Qu'est-ce qu'un Feature Flag ?
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                {{-- Section 1 : Définition --}}
                <div class="mb-20">
                    <h6 class="fw-semibold mb-12 d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:lightbulb-outline" class="text-warning-main"></iconify-icon>
                        En un mot
                    </h6>
                    <p class="text-secondary-light mb-0">
                        Un <strong>Feature Flag</strong> (ou « drapeau de fonctionnalité ») est un interrupteur ON/OFF
                        qui vous permet d'<strong>activer ou désactiver une fonctionnalité</strong> de votre application
                        sans toucher au code et sans redéployer.
                    </p>
                </div>

                {{-- Section 2 : Analogie --}}
                <div class="mb-20 p-16 bg-neutral-50 radius-8">
                    <h6 class="fw-semibold mb-8 d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:home-outline" class="text-info-main"></iconify-icon>
                        Pensez-y comme un interrupteur de lumière
                    </h6>
                    <p class="text-secondary-light mb-0">
                        Imaginez votre maison : chaque pièce a un interrupteur. Vous pouvez allumer le salon
                        sans toucher à la cuisine. Les Feature Flags fonctionnent pareil : chaque fonctionnalité
                        a son propre interrupteur.
                    </p>
                </div>

                {{-- Section 3 : Exemples concrets --}}
                <div class="mb-20">
                    <h6 class="fw-semibold mb-12 d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:checklist-minimalistic-outline" class="text-success-main"></iconify-icon>
                        Exemples concrets
                    </h6>
                    <div class="d-flex flex-column gap-12">
                        <div class="d-flex align-items-start gap-12">
                            <span class="badge bg-success-focus text-success-main px-8 py-4 mt-1">ON</span>
                            <div>
                                <strong class="text-sm">Mode blog activé</strong>
                                <p class="text-secondary-light text-sm mb-0">Les visiteurs voient la section blog sur votre site.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-12">
                            <span class="badge bg-danger-focus text-danger-main px-8 py-4 mt-1">OFF</span>
                            <div>
                                <strong class="text-sm">Mode blog désactivé</strong>
                                <p class="text-secondary-light text-sm mb-0">La section blog disparaît du site. Le contenu reste en base de données, rien n'est perdu.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-12">
                            <span class="badge bg-success-focus text-success-main px-8 py-4 mt-1">ON</span>
                            <div>
                                <strong class="text-sm">Inscription ouverte</strong>
                                <p class="text-secondary-light text-sm mb-0">Les nouveaux utilisateurs peuvent créer un compte.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 4 : Comment utiliser --}}
                <div class="mb-20">
                    <h6 class="fw-semibold mb-12 d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:mouse-circle-outline" class="text-primary-600"></iconify-icon>
                        Comment ça marche ?
                    </h6>
                    <ol class="text-secondary-light ps-20 mb-0">
                        <li class="mb-8">Trouvez le flag que vous voulez modifier dans la liste ci-dessous.</li>
                        <li class="mb-8">Cliquez sur le <strong>bouton toggle</strong> (interrupteur) pour l'activer ou le désactiver.</li>
                        <li class="mb-8">Le changement prend effet <strong>immédiatement</strong>, sans besoin de redémarrer quoi que ce soit.</li>
                        <li>Pour revenir en arrière, cliquez simplement à nouveau sur le toggle.</li>
                    </ol>
                </div>

                {{-- Section 5 : Quand utiliser --}}
                <div class="p-16 bg-primary-50 radius-8">
                    <h6 class="fw-semibold mb-8 d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:shield-check-outline" class="text-primary-600"></iconify-icon>
                        Pourquoi c'est utile ?
                    </h6>
                    <ul class="text-secondary-light ps-20 mb-0">
                        <li class="mb-4"><strong>Tester</strong> une nouvelle fonctionnalité sans risque.</li>
                        <li class="mb-4"><strong>Désactiver</strong> rapidement quelque chose qui pose problème.</li>
                        <li class="mb-4"><strong>Lancer</strong> progressivement une fonctionnalité.</li>
                        <li><strong>Personnaliser</strong> l'expérience selon vos besoins du moment.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary-600 radius-8" data-bs-dismiss="modal">J'ai compris</button>
            </div>
        </div>
    </div>
</div>

@endsection
