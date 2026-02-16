<div>
    <h2 class="text-xl font-semibold text-center mb-6">Connexion</h2>

    @if (session('status'))
        <div class="mb-4 text-sm text-green-600 bg-green-50 p-3 rounded">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="authenticate">
        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Courriel</label>
            <input wire:model="email" type="email" id="email" required autofocus
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('email') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
            <input wire:model="password" type="password" id="password" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('password') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4 flex items-center justify-between">
            <label class="flex items-center">
                <input wire:model="remember" type="checkbox" class="rounded border-gray-300 text-blue-600">
                <span class="ml-2 text-sm text-gray-600">Se souvenir de moi</span>
            </label>
            <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:underline" wire:navigate>
                Mot de passe oublié ?
            </a>
        </div>

        <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition"
                wire:loading.attr="disabled">
            <span wire:loading.remove>Se connecter</span>
            <span wire:loading>Connexion...</span>
        </button>
    </form>

    <p class="text-center text-sm text-gray-600 mt-4">
        Pas de compte ?
        <a href="{{ route('register') }}" class="text-blue-600 hover:underline" wire:navigate>Créer un compte</a>
    </p>
</div>
