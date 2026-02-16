<div>
    <h2 class="text-xl font-semibold text-center mb-4">Mot de passe oublié</h2>
    <p class="text-sm text-gray-600 text-center mb-6">
        Entrez votre courriel et nous vous enverrons un lien de réinitialisation.
    </p>

    @if ($status)
        <div class="mb-4 text-sm text-green-600 bg-green-50 p-3 rounded">
            {{ $status }}
        </div>
    @endif

    <form wire:submit="sendResetLink">
        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Courriel</label>
            <input wire:model="email" type="email" id="email" required autofocus
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('email') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition"
                wire:loading.attr="disabled">
            <span wire:loading.remove>Envoyer le lien</span>
            <span wire:loading>Envoi...</span>
        </button>
    </form>

    <p class="text-center text-sm text-gray-600 mt-4">
        <a href="{{ route('login') }}" class="text-blue-600 hover:underline" wire:navigate>Retour à la connexion</a>
    </p>
</div>
