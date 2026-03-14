<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
@if(!$dismissed && auth()->user()->needsOnboarding())
<div class="card rounded-2 border border-primary border-opacity-25 mb-4">
    <div class="progress" style="height: 4px; border-radius: 8px 8px 0 0">
        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ ($step / 5) * 100 }}%; transition: width 0.3s ease"></div>
    </div>

    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="small text-muted fw-medium">
                {{ __('Étape') }} {{ min($step + 1, 5) }} / 5
            </span>
            <button wire:click="complete" type="button" class="btn btn-sm btn-outline-secondary rounded-2">
                {{ __('Ignorer') }}
            </button>
        </div>

        {{-- Étape 0 : Bienvenue --}}
        @if($step === 0)
        <div class="text-center py-4">
            <div class="mb-3">
                <i data-lucide="handshake" class="text-primary" style="width:48px;height:48px;"></i>
            </div>
            <h5 class="fw-semibold mb-2">{{ __('Bienvenue sur') }} {{ config('app.name') }} !</h5>
            <p class="text-muted mb-4">{{ __('Configurons votre compte en quelques étapes simples.') }}</p>
            <button wire:click="completeStep(1)" class="btn btn-primary rounded-2 px-3">
                {{ __('Commencer') }}
            </button>
        </div>

        {{-- Étape 1 : Profil --}}
        @elseif($step === 1)
        <div>
            <h5 class="fw-semibold mb-3">
                <i data-lucide="user" class="text-primary me-2"></i>
                {{ __('Complétez votre profil') }}
            </h5>
            <form wire:submit="saveProfile">
                <div class="mb-3">
                    <label for="onboarding-bio" class="form-label fw-medium text-muted mb-2">{{ __('Bio') }}</label>
                    <textarea wire:model="bio" id="onboarding-bio" class="form-control rounded-2" rows="3" placeholder="{{ __('Décrivez-vous en quelques mots...') }}" maxlength="500"></textarea>
                    @error('bio') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <button type="submit" class="btn btn-primary rounded-2 px-3">
                        {{ __('Enregistrer') }}
                    </button>
                    <button wire:click="skipToStep(2)" type="button" class="btn btn-link text-muted small">
                        {{ __('Passer cette étape') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- Étape 2 : Vérification email --}}
        @elseif($step === 2)
        <div>
            <h5 class="fw-semibold mb-3">
                <i data-lucide="mail" class="text-primary me-2"></i>
                {{ __('Vérification du courriel') }}
            </h5>
            @if(auth()->user()->hasVerifiedEmail())
            <div class="alert alert-success rounded-2 d-flex align-items-center gap-2 mb-3">
                <i data-lucide="check-circle" class="text-success"></i>
                <span>{{ __('Votre courriel est vérifié !') }}</span>
            </div>
            <button wire:click="completeStep(3)" class="btn btn-primary rounded-2 px-3">
                {{ __('Continuer') }}
            </button>
            @else
            <div class="alert alert-warning rounded-2 d-flex align-items-center gap-2 mb-3">
                <i data-lucide="info" class="text-warning"></i>
                <span>{{ __('Veuillez vérifier votre adresse courriel.') }}</span>
            </div>
            <form action="{{ route('verification.send') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-warning rounded-2 px-3">
                    {{ __('Renvoyer le courriel') }}
                </button>
            </form>
            <button wire:click="skipToStep(3)" type="button" class="btn btn-link text-muted small ms-2">
                {{ __('Passer') }}
            </button>
            @endif
        </div>

        {{-- Étape 3 : Tour des fonctionnalités --}}
        @elseif($step === 3)
        <div>
            <h5 class="fw-semibold mb-3">
                <i data-lucide="compass" class="text-primary me-2"></i>
                {{ __('Découvrez les fonctionnalités') }}
            </h5>
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <a href="{{ route('user.articles.index') }}" class="card rounded-2 h-100 text-decoration-none border" style="transition: border-color 0.2s">
                        <div class="card-body text-center p-3">
                            <i data-lucide="file-text" class="text-primary mb-2"></i>
                            <h6 class="fw-semibold mb-1">{{ __('Articles') }}</h6>
                            <p class="small text-muted mb-0">{{ __('Gérez votre contenu') }}</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('user.api-tokens') }}" class="card rounded-2 h-100 text-decoration-none border" style="transition: border-color 0.2s">
                        <div class="card-body text-center p-3">
                            <i data-lucide="key" class="text-primary mb-2"></i>
                            <h6 class="fw-semibold mb-1">{{ __('Tokens API') }}</h6>
                            <p class="small text-muted mb-0">{{ __('Intégrations') }}</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('user.subscription') }}" class="card rounded-2 h-100 text-decoration-none border" style="transition: border-color 0.2s">
                        <div class="card-body text-center p-3">
                            <i data-lucide="tag" class="text-primary mb-2"></i>
                            <h6 class="fw-semibold mb-1">{{ __('Abonnement') }}</h6>
                            <p class="small text-muted mb-0">{{ __('Gérez votre plan') }}</p>
                        </div>
                    </a>
                </div>
            </div>
            <button wire:click="completeStep(4)" class="btn btn-primary rounded-2 px-3">
                {{ __('Continuer') }}
            </button>
        </div>

        {{-- Étape 4 : Choisir un plan --}}
        @elseif($step === 4)
        <div class="text-center py-3">
            <div class="mb-3">
                <i data-lucide="star" class="text-warning" style="width:48px;height:48px;"></i>
            </div>
            <h5 class="fw-semibold mb-2">{{ __('Choisissez votre plan') }}</h5>
            <p class="text-muted mb-4">{{ __('Sélectionnez l\'abonnement adapté à vos besoins.') }}</p>
            <div class="d-flex justify-content-center gap-2">
                <a href="{{ route('user.subscription') }}" class="btn btn-primary rounded-2 px-3">
                    {{ __('Voir les tarifs') }}
                </a>
                <button wire:click="complete" type="button" class="btn btn-outline-primary rounded-2 px-3">
                    {{ __('Terminer') }}
                </button>
            </div>
        </div>

        {{-- Étape 5+ : Terminé --}}
        @else
        <div class="text-center py-3">
            <i data-lucide="check-circle" class="text-success" style="width:48px;height:48px;"></i>
            <h5 class="fw-semibold mt-3">{{ __('Configuration terminée !') }}</h5>
        </div>
        @endif
    </div>
</div>
@endif
</div>
