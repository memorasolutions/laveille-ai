@extends('backoffice::layouts.admin')

@section('page-title', 'Modifier le shortcode')

@section('content')
    <a href="{{ route('admin.shortcodes.index') }}" class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-6">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Retour
    </a>

    <div class="rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10 p-6 bg-white">
        <form method="POST" action="{{ route('admin.shortcodes.update', $shortcode) }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="tag" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tag</label>
                <input type="text" name="tag" id="tag" value="{{ old('tag', $shortcode->tag) }}" required pattern="[a-z][a-z0-9_]*" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                @error('tag')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom</label>
                <input type="text" name="name" id="name" value="{{ old('name', $shortcode->name) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                <textarea name="description" id="description" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">{{ old('description', $shortcode->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="html_template" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Template HTML</label>
                <textarea name="html_template" id="html_template" rows="4" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-mono dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">{{ old('html_template', $shortcode->html_template) }}</textarea>
                @error('html_template')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="parameters" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Paramètres JSON</label>
                <textarea name="parameters" id="parameters" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-mono dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">{{ old('parameters', $shortcode->parameters ? json_encode($shortcode->parameters) : '') }}</textarea>
                @error('parameters')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="has_content" value="1" {{ old('has_content', $shortcode->has_content) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm dark:border-gray-600 dark:bg-gray-700">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Contient du contenu</span>
                </label>
            </div>

            <div class="flex items-center justify-between">
                <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                    Mettre à jour
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.shortcodes.destroy', $shortcode) }}" onsubmit="return confirm('Supprimer ce shortcode ?')" class="mt-4">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700">
                Supprimer
            </button>
        </form>
    </div>
@endsection
