@extends('backoffice::layouts.admin')

@section('page-title', 'Modifier ' . $setting->key)

@section('content')
    <div class="mx-auto max-w-2xl" x-data="{
        selectedType: '{{ old('type', $setting->type ?? 'string') }}',
        selectedGroup: '{{ old('group', in_array($setting->group, ['general', 'mail', 'seo', 'branding']) ? $setting->group : '__custom') }}',
        customGroup: '{{ old('custom_group', !in_array($setting->group, ['general', 'mail', 'seo', 'branding']) ? $setting->group : '') }}'
    }">
        <div class="mb-6 flex items-center gap-3">
            <a href="{{ route('admin.settings.index') }}" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Modifier le paramètre</h2>
        </div>

        <form method="POST" action="{{ route('admin.settings.update', $setting) }}"
              class="space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
            @csrf
            @method('PUT')

            {{-- Catégorie --}}
            <div>
                <label for="group" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Catégorie</label>
                <select name="group" id="group" x-model="selectedGroup"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                    <option value="general">Général</option>
                    <option value="mail">Courriel</option>
                    <option value="seo">SEO</option>
                    <option value="branding">Marque</option>
                    <option value="__custom">Autre...</option>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Dans quel groupe ranger ce paramètre</p>
                @error('group') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Groupe personnalisé --}}
            <div x-show="selectedGroup === '__custom'" x-cloak>
                <label for="custom_group" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom du groupe</label>
                <input type="text" name="custom_group" id="custom_group" x-model="customGroup"
                       placeholder="ex: analytics, social"
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
            </div>

            {{-- Identifiant unique --}}
            <div>
                <label for="key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Identifiant unique</label>
                <input type="text" name="key" id="key" value="{{ old('key', $setting->key) }}" required
                       placeholder="ex: site_name, contact_email"
                       pattern="\S+"
                       title="Pas d'espaces autorisés"
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Nom technique du paramètre (sans espaces)</p>
                @error('key') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Type de valeur --}}
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type de valeur</label>
                <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors"
                           :class="selectedType === 'string' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-400' : 'border-gray-200 text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700'">
                        <input type="radio" name="type" value="string" x-model="selectedType" class="sr-only">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                        Texte
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors"
                           :class="selectedType === 'boolean' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-400' : 'border-gray-200 text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700'">
                        <input type="radio" name="type" value="boolean" x-model="selectedType" class="sr-only">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Oui/Non
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors"
                           :class="selectedType === 'integer' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-400' : 'border-gray-200 text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700'">
                        <input type="radio" name="type" value="integer" x-model="selectedType" class="sr-only">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                        Nombre
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors"
                           :class="selectedType === 'json' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-400' : 'border-gray-200 text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700'">
                        <input type="radio" name="type" value="json" x-model="selectedType" class="sr-only">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                        JSON
                    </label>
                </div>
                @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Valeur - adapté au type --}}
            <div>
                <label for="value" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valeur</label>

                {{-- Texte --}}
                <div x-show="selectedType === 'string'" x-cloak>
                    <input type="text" name="value" value="{{ old('value', $setting->value) }}"
                           placeholder="Saisissez la valeur..."
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                </div>

                {{-- Booléen --}}
                <div x-show="selectedType === 'boolean'" x-cloak class="mt-2">
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input type="hidden" name="value" x-bind:value="selectedType === 'boolean' ? ($refs.boolToggle?.checked ? 'true' : 'false') : ''">
                        <input type="checkbox" x-ref="boolToggle" class="peer sr-only" {{ old('value', $setting->value) === 'true' ? 'checked' : '' }}>
                        <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-2 peer-focus:ring-indigo-300 dark:bg-gray-600"></div>
                        <span class="ml-3 text-sm text-gray-600 dark:text-gray-400" x-text="$refs.boolToggle?.checked ? 'Activé' : 'Désactivé'">Désactivé</span>
                    </label>
                </div>

                {{-- Nombre --}}
                <div x-show="selectedType === 'integer'" x-cloak>
                    <input type="number" name="value" value="{{ old('value', $setting->value) }}"
                           placeholder="0"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                </div>

                {{-- JSON --}}
                <div x-show="selectedType === 'json'" x-cloak>
                    <textarea name="value" rows="4"
                              placeholder='{"cle": "valeur"}'
                              class="mt-1 block w-full rounded-lg border-gray-300 font-mono text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">{{ old('value', $setting->value) }}</textarea>
                </div>

                @error('value') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                <input type="text" name="description" id="description" value="{{ old('description', $setting->description) }}"
                       placeholder="Ex: Nom affiché du site web"
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Aide-mémoire pour comprendre ce paramètre</p>
                @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Visible publiquement --}}
            <div class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 dark:border-gray-600">
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Visible publiquement</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Rend ce paramètre accessible sans authentification</p>
                </div>
                <label class="relative inline-flex cursor-pointer items-center">
                    <input type="hidden" name="is_public" value="0">
                    <input type="checkbox" name="is_public" value="1" class="peer sr-only" {{ old('is_public', $setting->is_public) ? 'checked' : '' }}>
                    <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-2 peer-focus:ring-indigo-300 dark:bg-gray-600"></div>
                </label>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3 border-t border-gray-200 pt-6 dark:border-gray-700">
                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Enregistrer
                </button>
                <a href="{{ route('admin.settings.index') }}" class="rounded-lg bg-gray-100 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    Annuler
                </a>
            </div>
        </form>
    </div>
@endsection
