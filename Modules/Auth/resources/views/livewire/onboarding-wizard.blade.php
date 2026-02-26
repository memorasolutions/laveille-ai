<div>
@if(!$dismissed && auth()->user()->needsOnboarding())
<div class="card radius-8 border border-primary-100 mb-24">
    <div class="progress" style="height: 4px; border-radius: 8px 8px 0 0">
        <div class="progress-bar bg-primary-600" role="progressbar" style="width: {{ ($step / 5) * 100 }}%; transition: width 0.3s ease"></div>
    </div>

    <div class="card-body p-24">
        <div class="d-flex justify-content-between align-items-center mb-16">
            <span class="text-sm text-secondary-light fw-medium">
                {{ __('Étape') }} {{ min($step + 1, 5) }} / 5
            </span>
            <button wire:click="complete" type="button" class="btn btn-sm btn-outline-secondary radius-8">
                {{ __('Ignorer') }}
            </button>
        </div>

        {{-- Étape 0 : Bienvenue --}}
        @if($step === 0)
        <div class="text-center py-24">
            <div class="mb-16">
                <iconify-icon icon="solar:hand-shake-outline" class="text-primary-600" style="font-size: 48px"></iconify-icon>
            </div>
            <h5 class="fw-semibold mb-8">{{ __('Bienvenue sur') }} {{ config('app.name') }} !</h5>
            <p class="text-secondary-light mb-24">{{ __('Configurons votre compte en quelques étapes simples.') }}</p>
            <button wire:click="completeStep(1)" class="btn btn-primary radius-8 px-20">
                {{ __('Commencer') }}
            </button>
        </div>

        {{-- Étape 1 : Profil --}}
        @elseif($step === 1)
        <div>
            <h5 class="fw-semibold mb-16">
                <iconify-icon icon="solar:user-outline" class="text-primary-600 me-8"></iconify-icon>
                {{ __('Complétez votre profil') }}
            </h5>
            <form wire:submit="saveProfile">
                <div class="mb-16">
                    <label for="onboarding-bio" class="form-label fw-medium text-secondary-light mb-8">{{ __('Bio') }}</label>
                    <textarea wire:model="bio" id="onboarding-bio" class="form-control radius-8" rows="3" placeholder="{{ __('Décrivez-vous en quelques mots...') }}" maxlength="500"></textarea>
                    @error('bio') <span class="text-danger-main text-sm">{{ $message }}</span> @enderror
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <button type="submit" class="btn btn-primary radius-8 px-20">
                        {{ __('Enregistrer') }}
                    </button>
                    <button wire:click="skipToStep(2)" type="button" class="btn btn-link text-secondary-light text-sm">
                        {{ __('Passer cette étape') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- Étape 2 : Vérification email --}}
        @elseif($step === 2)
        <div>
            <h5 class="fw-semibold mb-16">
                <iconify-icon icon="solar:letter-outline" class="text-primary-600 me-8"></iconify-icon>
                {{ __('Vérification du courriel') }}
            </h5>
            @if(auth()->user()->hasVerifiedEmail())
            <div class="alert alert-success radius-8 d-flex align-items-center gap-8 mb-16">
                <iconify-icon icon="solar:check-circle-outline" class="text-success-main" style="font-size: 20px"></iconify-icon>
                <span>{{ __('Votre courriel est vérifié !') }}</span>
            </div>
            <button wire:click="completeStep(3)" class="btn btn-primary radius-8 px-20">
                {{ __('Continuer') }}
            </button>
            @else
            <div class="alert alert-warning radius-8 d-flex align-items-center gap-8 mb-16">
                <iconify-icon icon="solar:info-circle-outline" class="text-warning-main" style="font-size: 20px"></iconify-icon>
                <span>{{ __('Veuillez vérifier votre adresse courriel.') }}</span>
            </div>
            <form action="{{ route('verification.send') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-warning radius-8 px-20">
                    {{ __('Renvoyer le courriel') }}
                </button>
            </form>
            <button wire:click="skipToStep(3)" type="button" class="btn btn-link text-secondary-light text-sm ms-8">
                {{ __('Passer') }}
            </button>
            @endif
        </div>

        {{-- Étape 3 : Tour des fonctionnalités --}}
        @elseif($step === 3)
        <div>
            <h5 class="fw-semibold mb-16">
                <iconify-icon icon="solar:compass-outline" class="text-primary-600 me-8"></iconify-icon>
                {{ __('Découvrez les fonctionnalités') }}
            </h5>
            <div class="row g-12 mb-16">
                <div class="col-md-4">
                    <a href="{{ route('user.articles.index') }}" class="card radius-8 h-100 text-decoration-none border hover-border-primary-600" style="transition: border-color 0.2s">
                        <div class="card-body text-center p-16">
                            <iconify-icon icon="solar:document-text-outline" class="text-primary-600 mb-8" style="font-size: 32px"></iconify-icon>
                            <h6 class="fw-semibold mb-4">{{ __('Articles') }}</h6>
                            <p class="text-sm text-secondary-light mb-0">{{ __('Gérez votre contenu') }}</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('user.api-tokens') }}" class="card radius-8 h-100 text-decoration-none border hover-border-primary-600" style="transition: border-color 0.2s">
                        <div class="card-body text-center p-16">
                            <iconify-icon icon="solar:key-outline" class="text-primary-600 mb-8" style="font-size: 32px"></iconify-icon>
                            <h6 class="fw-semibold mb-4">{{ __('Tokens API') }}</h6>
                            <p class="text-sm text-secondary-light mb-0">{{ __('Intégrations') }}</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('user.subscription') }}" class="card radius-8 h-100 text-decoration-none border hover-border-primary-600" style="transition: border-color 0.2s">
                        <div class="card-body text-center p-16">
                            <iconify-icon icon="solar:tag-price-outline" class="text-primary-600 mb-8" style="font-size: 32px"></iconify-icon>
                            <h6 class="fw-semibold mb-4">{{ __('Abonnement') }}</h6>
                            <p class="text-sm text-secondary-light mb-0">{{ __('Gérez votre plan') }}</p>
                        </div>
                    </a>
                </div>
            </div>
            <button wire:click="completeStep(4)" class="btn btn-primary radius-8 px-20">
                {{ __('Continuer') }}
            </button>
        </div>

        {{-- Étape 4 : Choisir un plan --}}
        @elseif($step === 4)
        <div class="text-center py-16">
            <div class="mb-16">
                <iconify-icon icon="solar:star-outline" class="text-warning-main" style="font-size: 48px"></iconify-icon>
            </div>
            <h5 class="fw-semibold mb-8">{{ __('Choisissez votre plan') }}</h5>
            <p class="text-secondary-light mb-24">{{ __('Sélectionnez l\'abonnement adapté à vos besoins.') }}</p>
            <div class="d-flex justify-content-center gap-12">
                <a href="{{ route('pricing') }}" class="btn btn-primary radius-8 px-20">
                    {{ __('Voir les tarifs') }}
                </a>
                <button wire:click="complete" type="button" class="btn btn-outline-primary radius-8 px-20">
                    {{ __('Terminer') }}
                </button>
            </div>
        </div>

        {{-- Étape 5+ : Terminé (ne devrait pas s'afficher car dismissed) --}}
        @else
        <div class="text-center py-16">
            <iconify-icon icon="solar:check-circle-outline" class="text-success-main" style="font-size: 48px"></iconify-icon>
            <h5 class="fw-semibold mt-16">{{ __('Configuration terminée !') }}</h5>
        </div>
        @endif
    </div>
</div>
@endif
</div>
