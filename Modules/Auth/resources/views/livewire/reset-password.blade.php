<div>
    <h2 class="text-xl font-semibold text-center mb-6">Réinitialiser le mot de passe</h2>

    <form wire:submit="resetPassword">
        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Courriel</label>
            <input wire:model="email" type="email" id="email" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('email') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe</label>
            <input wire:model="password" type="password" id="password" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('password') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
            <input wire:model="password_confirmation" type="password" id="password_confirmation" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition"
                wire:loading.attr="disabled">
            <span wire:loading.remove>Réinitialiser</span>
            <span wire:loading>Réinitialisation...</span>
        </button>
    </form>
</div>
