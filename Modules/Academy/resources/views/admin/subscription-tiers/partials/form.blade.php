{{-- Partial : champs communs créer/éditer un palier d'abonnement --}}
{{-- $tier : null (création) ou instance SubscriptionTier (édition) ; $featureKeys : config('academy.subscription_tier_feature_keys') --}}
@php $t = $tier; @endphp

<div class="grid grid-cols-1 gap-6">

    {{-- Nom --}}
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom <span class="text-red-500">*</span></label>
        <input type="text" id="name" name="name" value="{{ old('name', $t?->name) }}" required
               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        @error('name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>

    {{-- Description --}}
    <div>
        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
        <textarea id="description" name="description" rows="3"
                  class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">{{ old('description', $t?->description) }}</textarea>
        @error('description') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Prix (données locales, PAS Stripe) --}}
        <div>
            <label for="price_cents" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Prix <span class="text-gray-400 font-normal">(en cents, ex : 2999 = 29,99&thinsp;$ – laisser vide = gratuit)</span>
            </label>
            <input type="number" id="price_cents" name="price_cents" value="{{ old('price_cents', $t?->price_cents) }}" min="0"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
            @error('price_cents') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        {{-- Période de facturation --}}
        <div>
            <label for="billing_period" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Période <span class="text-red-500">*</span></label>
            <select id="billing_period" name="billing_period"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                <option value="monthly" {{ old('billing_period', $t?->billing_period ?? 'monthly') === 'monthly' ? 'selected' : '' }}>Mensuelle</option>
                <option value="yearly" {{ old('billing_period', $t?->billing_period) === 'yearly' ? 'selected' : '' }}>Annuelle</option>
            </select>
            @error('billing_period') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        {{-- Sièges max (paliers organisation) --}}
        <div>
            <label for="max_seats" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Sièges max <span class="text-gray-400 font-normal">(organisation – vide = non applicable)</span>
            </label>
            <input type="number" id="max_seats" name="max_seats" value="{{ old('max_seats', $t?->max_seats) }}" min="1"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
            @error('max_seats') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Stripe Price ID — à remplir PLUS TARD par l'admin, jamais généré par le code --}}
    <div>
        <label for="stripe_price_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            ID prix Stripe <span class="text-gray-400 font-normal">(optionnel – à coller quand un vrai prix existe dans Stripe)</span>
        </label>
        <input type="text" id="stripe_price_id" name="stripe_price_id" value="{{ old('stripe_price_id', $t?->stripe_price_id) }}"
               placeholder="price_xxx"
               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        @error('stripe_price_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>

    {{-- Fonctionnalités débloquées à ce palier --}}
    <div>
        <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fonctionnalités débloquées à ce palier</span>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
            Une fonctionnalité reste soumise à son drapeau global <code>academy.*_enabled</code> : cette liste ne fait
            que RESTREINDRE davantage, jamais réactiver quelque chose de désactivé globalement.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3">
            @php $selected = old('features', $t?->features ?? []); @endphp
            @foreach($featureKeys as $key => $label)
                <label for="feature_{{ $key }}" class="flex items-center gap-3 min-h-[24px]">
                    <input type="checkbox" id="feature_{{ $key }}" name="features[]" value="{{ $key }}"
                           {{ in_array($key, $selected, true) ? 'checked' : '' }}
                           class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700">
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                </label>
            @endforeach
        </div>
        @error('features') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Palier par défaut --}}
        <div>
            <label for="is_default" class="flex items-center gap-3 min-h-[24px]">
                <input type="checkbox" id="is_default" name="is_default" value="1"
                       {{ old('is_default', $t?->is_default ?? false) ? 'checked' : '' }}
                       class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Palier par défaut (Freemium)</span>
            </label>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Un seul palier par défaut à la fois – cocher ici décoche automatiquement les autres.</p>
        </div>

        {{-- Actif --}}
        <div>
            <label for="is_active" class="flex items-center gap-3 min-h-[24px]">
                <input type="checkbox" id="is_active" name="is_active" value="1"
                       {{ old('is_active', $t?->is_active ?? true) ? 'checked' : '' }}
                       class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Actif</span>
            </label>
        </div>

        {{-- Ordre d'affichage --}}
        <div>
            <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ordre d'affichage</label>
            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $t?->sort_order ?? 0) }}" min="0"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
            @error('sort_order') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>
    </div>

</div>
