{{-- Partial : une ligne chapitre avec leçons imbriquées --}}
<div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">

    {{-- En-tête du chapitre : titre inline + suppression --}}
    <div class="flex items-center gap-2 mb-3">
        <form action="{{ route('admin.academy.chapters.update', $chapter) }}" method="POST" class="flex-1 flex gap-2">
            @csrf
            @method('PUT')
            <input type="text" name="title" value="{{ $chapter->title }}" required
                   class="flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
            <button type="submit"
                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                Renommer
            </button>
        </form>

        <form action="{{ route('admin.academy.chapters.destroy', $chapter) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="rounded-lg border border-red-300 px-3 py-1.5 text-xs text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400">
                Supprimer
            </button>
        </form>
    </div>

    {{-- Liste des leçons --}}
    @if($chapter->lessons->isEmpty())
        <p class="text-xs text-gray-400 dark:text-gray-500 mb-3 ml-1">Aucune leçon.</p>
    @else
        <ul class="space-y-1 mb-3 ml-2">
            @foreach($chapter->lessons->sortBy('position') as $lesson)
                <li class="flex items-center gap-2">
                    <span class="text-xs text-gray-400">{{ $lesson->position }}.</span>
                    <a href="{{ route('admin.academy.lessons.edit', $lesson) }}"
                       class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 hover:underline">
                        {{ $lesson->title }}
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Ajouter une leçon dans ce chapitre --}}
    <form action="{{ route('admin.academy.lessons.store', $chapter) }}" method="POST"
          class="flex gap-2 mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
        @csrf
        <input type="text" name="title" placeholder="Nouvelle leçon…" required
               class="flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        <button type="submit"
                class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">
            + Leçon
        </button>
    </form>
</div>
