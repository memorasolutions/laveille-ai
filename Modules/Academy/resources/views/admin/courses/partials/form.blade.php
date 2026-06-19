{{-- Partial : champs communs créer/éditer un cours --}}
{{-- $course : null (création) ou instance Course (édition) --}}
@php $c = $course; @endphp

<div class="grid grid-cols-1 gap-6">

    {{-- Titre --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titre <span class="text-red-500">*</span></label>
        <input type="text" name="title" value="{{ old('title', $c?->title) }}" required
               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        @error('title') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>

    {{-- Sous-titre --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sous-titre</label>
        <input type="text" name="subtitle" value="{{ old('subtitle', $c?->subtitle) }}"
               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        @error('subtitle') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>

    {{-- Résumé --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Résumé court</label>
        <textarea name="summary" rows="3"
                  class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">{{ old('summary', $c?->summary) }}</textarea>
        @error('summary') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>

    {{-- Description longue --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description complète</label>
        <textarea name="description" rows="5"
                  class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">{{ old('description', $c?->description) }}</textarea>
        @error('description') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Langue --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Langue <span class="text-red-500">*</span></label>
            <select name="language"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                <option value="fr-CA" {{ old('language', $c?->language ?? 'fr-CA') === 'fr-CA' ? 'selected' : '' }}>Français (Canada)</option>
                <option value="en-CA" {{ old('language', $c?->language) === 'en-CA' ? 'selected' : '' }}>Anglais (Canada)</option>
            </select>
            @error('language') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        {{-- Niveau --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Niveau <span class="text-red-500">*</span></label>
            <select name="level"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                <option value="intro" {{ old('level', $c?->level ?? 'intro') === 'intro' ? 'selected' : '' }}>Débutant</option>
                <option value="inter" {{ old('level', $c?->level) === 'inter' ? 'selected' : '' }}>Intermédiaire</option>
                <option value="avance" {{ old('level', $c?->level) === 'avance' ? 'selected' : '' }}>Avancé</option>
            </select>
            @error('level') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        {{-- Durée --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Durée (minutes)</label>
            <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $c?->duration_minutes) }}" min="1"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
            @error('duration_minutes') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Visibilité --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Visibilité <span class="text-red-500">*</span></label>
            <select name="visibility"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                <option value="public"   {{ old('visibility', $c?->visibility ?? 'public') === 'public'   ? 'selected' : '' }}>Public</option>
                <option value="unlisted" {{ old('visibility', $c?->visibility) === 'unlisted' ? 'selected' : '' }}>Non répertorié</option>
                <option value="private"  {{ old('visibility', $c?->visibility) === 'private'  ? 'selected' : '' }}>Privé</option>
            </select>
            @error('visibility') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        {{-- Type d'accès --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type d'accès <span class="text-red-500">*</span></label>
            <select name="access_type"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                <option value="free"              {{ old('access_type', $c?->access_type ?? 'free') === 'free'              ? 'selected' : '' }}>Gratuit</option>
                <option value="paid_one_time"     {{ old('access_type', $c?->access_type) === 'paid_one_time'     ? 'selected' : '' }}>Achat unique</option>
                <option value="paid_subscription" {{ old('access_type', $c?->access_type) === 'paid_subscription' ? 'selected' : '' }}>Abonnement</option>
            </select>
            @error('access_type') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Prix en cents --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Prix <span class="text-gray-400 font-normal">(en cents, ex : 2999 = 29,99&thinsp;$)</span>
            </label>
            <input type="number" name="price_cents" value="{{ old('price_cents', $c?->price_cents) }}" min="0"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
            @error('price_cents') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        {{-- Devise --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Devise</label>
            <input type="text" name="currency" value="{{ old('currency', $c?->currency ?? 'CAD') }}" maxlength="3"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        </div>

        {{-- Stripe Price ID --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ID prix Stripe</label>
            <input type="text" name="stripe_price_id" value="{{ old('stripe_price_id', $c?->stripe_price_id) }}"
                   placeholder="price_xxx"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
            @error('stripe_price_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>
    </div>

    @if($c)
        {{-- Statut (modification uniquement) --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Statut <span class="text-red-500">*</span></label>
            <select name="status"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                <option value="draft"     {{ old('status', $c->status) === 'draft'     ? 'selected' : '' }}>Brouillon</option>
                <option value="published" {{ old('status', $c->status) === 'published' ? 'selected' : '' }}>Publié</option>
                <option value="archived"  {{ old('status', $c->status) === 'archived'  ? 'selected' : '' }}>Archivé</option>
            </select>
            @error('status') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>
    @endif

</div>
