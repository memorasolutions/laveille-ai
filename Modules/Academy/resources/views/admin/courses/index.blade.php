{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends('backoffice::layouts.admin')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Académie — Cours</h1>
        <a href="{{ route('admin.academy.courses.create') }}"
           class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
            + Nouveau cours
        </a>
    </div>

    <div class="rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10 bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Titre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Accès</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Prix</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Chapitres</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Créé le</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($courses as $course)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $course->title }}</div>
                                @if($course->subtitle)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $course->subtitle }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusColor = match($course->status) {
                                        'draft'     => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                        'published' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                        'archived'  => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                        default     => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                    };
                                    $statusLabel = match($course->status) {
                                        'draft'     => 'Brouillon',
                                        'published' => 'Publié',
                                        'archived'  => 'Archivé',
                                        default     => $course->status,
                                    };
                                @endphp
                                <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $statusColor }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ match($course->access_type) {
                                    'free'              => 'Gratuit',
                                    'paid_one_time'     => 'Achat unique',
                                    'paid_subscription' => 'Abonnement',
                                    default             => $course->access_type,
                                } }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                @if($course->price_cents)
                                    {{ number_format($course->price_cents / 100, 2, ',', '\u{202F}') }}&thinsp;$
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $course->chapters_count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $course->created_at->format('Y-m-d') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('admin.academy.courses.edit', $course) }}"
                                       class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        Modifier
                                    </a>

                                    @if($course->status !== 'archived')
                                        <form action="{{ route('admin.academy.courses.toggle-status', $course) }}"
                                              method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="{{ $course->status === 'published' ? 'text-yellow-600 hover:text-yellow-900 dark:text-yellow-400' : 'text-green-600 hover:text-green-900 dark:text-green-400' }}">
                                                {{ $course->status === 'published' ? 'Dépublier' : 'Publier' }}
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.academy.courses.archive', $course) }}"
                                              method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                                Archiver
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                Aucun cours. <a href="{{ route('admin.academy.courses.create') }}" class="text-indigo-600 hover:underline">Créer le premier cours.</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($courses->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $courses->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
