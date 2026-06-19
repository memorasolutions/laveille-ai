{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends('backoffice::layouts.admin')

@section('content')
<a href="{{ route('admin.academy.courses.index') }}"
   class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-6">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
    </svg>
    Retour à la liste
</a>

{{-- ── Formulaire principal ──────────────────────────────────────────────────── --}}
<div class="rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10 p-6 bg-white">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
        Modifier : <span class="font-normal">{{ $course->title }}</span>
    </h1>

    <form action="{{ route('admin.academy.courses.update', $course) }}" method="POST">
        @csrf
        @method('PUT')

        @include('academy::admin.courses.partials.form', ['course' => $course])

        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('admin.academy.courses.index') }}"
               class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                Annuler
            </a>
            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                Enregistrer les modifications
            </button>
        </div>
    </form>
</div>

{{-- ── Structure du cours ─────────────────────────────────────────────────────── --}}
<div class="mt-8 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10 p-6 bg-white">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Structure du cours</h2>

    @if($course->chapters->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Aucun chapitre pour le moment.</p>
    @else
        <div class="space-y-4 mb-6">
            @foreach($course->chapters->sortBy('position') as $chapter)
                @include('academy::admin.courses.partials.chapter-row', compact('chapter', 'course'))
            @endforeach
        </div>
    @endif

    {{-- Ajouter un chapitre --}}
    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ajouter un chapitre</p>
        <form action="{{ route('admin.academy.chapters.store', $course) }}" method="POST" class="flex gap-2">
            @csrf
            <input type="text" name="title" placeholder="Titre du chapitre" required
                   class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                Ajouter
            </button>
        </form>
    </div>
</div>

{{-- ── Instructeurs ───────────────────────────────────────────────────────────── --}}
<div class="mt-8 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10 p-6 bg-white">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Instructeurs &amp; équipe</h2>

    @if($course->courseRoles->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Aucun rôle assigné.</p>
    @else
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 mb-6">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Utilisateur</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rôle</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($course->courseRoles as $cr)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            {{ $cr->user?->name ?? 'Utilisateur #'.$cr->user_id }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $roleColor = match($cr->role) {
                                    'owner'      => 'bg-orange-100 text-orange-800',
                                    'instructor' => 'bg-blue-100 text-blue-800',
                                    'assistant'  => 'bg-purple-100 text-purple-800',
                                    'editor'     => 'bg-green-100 text-green-800',
                                    default      => 'bg-gray-100 text-gray-700',
                                };
                                $roleLabel = match($cr->role) {
                                    'owner'      => 'Propriétaire',
                                    'instructor' => 'Instructeur',
                                    'assistant'  => 'Assistant(e)',
                                    'editor'     => 'Éditeur',
                                    default      => $cr->role,
                                };
                            @endphp
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $roleColor }}">{{ $roleLabel }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($cr->role !== 'owner')
                                <form action="{{ route('admin.academy.course-roles.destroy', $cr) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm dark:text-red-400">
                                        Retirer
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Ajouter un rôle --}}
    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ajouter un membre à l'équipe</p>
        <form action="{{ route('admin.academy.course-roles.store', $course) }}" method="POST"
              class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">ID utilisateur</label>
                <input type="number" name="user_id" required min="1"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                @error('user_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Rôle</label>
                <select name="role" required
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    <option value="instructor">Instructeur</option>
                    <option value="assistant">Assistant(e)</option>
                    <option value="editor">Éditeur</option>
                </select>
            </div>
            <div>
                <button type="submit"
                        class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Ajouter
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
