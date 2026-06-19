{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends('backoffice::layouts.admin')

@section('content')
<a href="{{ route('admin.academy.courses.edit', $lesson->chapter->course) }}"
   class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-6">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
    </svg>
    Retour au cours
</a>

{{-- ── Formulaire leçon ──────────────────────────────────────────────────────── --}}
<div class="rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10 p-6 bg-white">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-white mb-1">Modifier la leçon</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
        Cours : <span class="font-medium">{{ $lesson->chapter->course->title }}</span>
        &rsaquo; Chapitre : <span class="font-medium">{{ $lesson->chapter->title }}</span>
    </p>

    <form action="{{ route('admin.academy.lessons.update', $lesson) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titre <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title', $lesson->title) }}" required
                       class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                @error('title') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug <span class="text-red-500">*</span></label>
                <input type="text" name="slug" value="{{ old('slug', $lesson->slug) }}" required
                       class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                @error('slug') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Résumé</label>
                <textarea name="summary" rows="3"
                          class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">{{ old('summary', $lesson->summary) }}</textarea>
                @error('summary') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Durée estimée (minutes)</label>
                    <input type="number" name="estimated_minutes" value="{{ old('estimated_minutes', $lesson->estimated_minutes) }}" min="1"
                           class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    @error('estimated_minutes') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Position <span class="text-red-500">*</span></label>
                    <input type="number" name="position" value="{{ old('position', $lesson->position) }}" required min="1"
                           class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    @error('position') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('admin.academy.courses.edit', $lesson->chapter->course) }}"
               class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                Annuler
            </a>
            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                Enregistrer
            </button>
        </div>
    </form>
</div>

{{-- ── Éléments de la leçon ─────────────────────────────────────────────────── --}}
<div class="mt-8 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10 p-6 bg-white">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Éléments de la leçon</h2>

    @if($lesson->lessonItems->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Aucun élément pour le moment.</p>
    @else
        <div class="overflow-x-auto mb-6">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Titre</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Contenu</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($lesson->lessonItems->sortBy('position') as $item)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $item->position }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $typeColor = match($item->type) {
                                        'video' => 'bg-blue-100 text-blue-800',
                                        'quiz'  => 'bg-purple-100 text-purple-800',
                                        'doc'   => 'bg-green-100 text-green-800',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $typeColor }}">
                                    {{ strtoupper($item->type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $item->title }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                @if($item->type === 'video')
                                    {{ $item->payload['player_url'] ?? '—' }}
                                @elseif($item->type === 'quiz')
                                    Clé : {{ $item->payload['qt_bank_key'] ?? '—' }} | Score min : {{ $item->payload['passing_score'] ?? '—' }}%
                                @elseif($item->type === 'doc')
                                    {{ Str::limit(strip_tags($item->payload['content'] ?? ''), 60) }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form action="{{ route('admin.academy.lesson-items.destroy', $item) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-red-600 hover:text-red-800 text-sm dark:text-red-400">
                                        Supprimer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Formulaire ajout élément --}}
    <div class="pt-4 border-t border-gray-200 dark:border-gray-700"
         x-data="{ itemType: 'video' }">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Ajouter un élément</p>

        <form action="{{ route('admin.academy.lesson-items.store', $lesson) }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 gap-4">

                {{-- Type --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type <span class="text-red-500">*</span></label>
                        <select name="type" x-model="itemType" required
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            <option value="video">Vidéo</option>
                            <option value="quiz">Quiz</option>
                            <option value="doc">Document</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titre <span class="text-red-500">*</span></label>
                        <input type="text" name="title" required
                               class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                </div>

                {{-- Champs vidéo --}}
                <div x-show="itemType === 'video'" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL lecteur <span class="text-red-500">*</span></label>
                        <input type="url" name="player_url"
                               placeholder="https://www.youtube.com/embed/…"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Durée (secondes)</label>
                        <input type="number" name="duration_seconds" min="1"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                </div>

                {{-- Champs quiz --}}
                <div x-show="itemType === 'quiz'" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Clé de banque de quiz <span class="text-red-500">*</span></label>
                        <input type="text" name="qt_bank_key"
                               placeholder="ex : qt-quotient-techno"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Score minimal (%) <span class="text-red-500">*</span></label>
                        <input type="number" name="passing_score" min="0" max="100" value="70"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    </div>
                </div>

                {{-- Champs doc --}}
                <div x-show="itemType === 'doc'" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contenu</label>
                    <textarea name="content" rows="5"
                              class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"></textarea>
                </div>

                <div class="flex items-center gap-4">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="is_required" value="1"
                               class="rounded border-gray-300 text-indigo-600">
                        Élément obligatoire
                    </label>
                    <button type="submit"
                            class="ml-auto rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        Ajouter l'élément
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
