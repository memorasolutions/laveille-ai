<div>
    <h4 class="mb-12">{{ __('Mot de passe oublié') }}</h4>
    <p class="mb-32 text-secondary-light text-lg">{{ __('Entrez votre courriel et nous vous enverrons un lien de réinitialisation.') }}</p>

    @if($status)
        <div class="alert alert-success radius-8 mb-20">{{ $status }}</div>
    @endif

    <form wire:submit="sendResetLink">
        <div class="icon-field">
            <span class="icon top-50 translate-middle-y">
                <iconify-icon icon="mage:email"></iconify-icon>
            </span>
            <input wire:model="email" type="email" class="form-control h-56-px bg-neutral-50 radius-12 @error('email') is-invalid @enderror" placeholder="{{ __('Courriel') }}" required autofocus>
        </div>
        @error('email')<div class="text-danger-main text-sm mt-8">{{ $message }}</div>@enderror

        <button type="submit" wire:loading.attr="disabled" class="btn btn-primary text-sm btn-sm px-12 py-16 w-100 radius-12 mt-32">
            <span wire:loading.remove>{{ __('Envoyer le lien') }}</span>
            <span wire:loading>{{ __('Envoi...') }}</span>
        </button>

        <div class="text-center mt-24">
            <a href="{{ route('login') }}" class="text-primary-600 fw-bold" wire:navigate>{{ __('Retour à la connexion') }}</a>
        </div>
    </form>
</div>
