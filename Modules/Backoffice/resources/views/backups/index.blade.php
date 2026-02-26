@extends('backoffice::layouts.admin')

@section('page-title', 'Sauvegardes')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
        <i class="ti ti-database-backup mr-2 text-indigo-600"></i>Sauvegardes
    </h2>
    <form method="POST" action="{{ route('admin.backups.run') }}">
        @csrf
        <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
            <i class="ti ti-player-play"></i> Lancer une sauvegarde
        </button>
    </form>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
    @if(count($backups) === 0)
        <div class="text-center py-12">
            <i class="ti ti-archive text-5xl text-gray-300 dark:text-gray-600 block mb-3"></i>
            <p class="text-gray-500 dark:text-gray-400 text-sm">Aucune sauvegarde disponible.</p>
            <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Cliquez sur "Lancer une sauvegarde" pour créer votre première sauvegarde.</p>
        </div>
    @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nom du fichier</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Taille</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($backups as $backup)
                <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $backup['name'] }}</td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ number_format($backup['size'] / 1024 / 1024, 2) }} MB</td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::createFromTimestamp($backup['date'])->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3">
                        <form method="POST" action="{{ route('admin.backups.delete') }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="path" value="{{ $backup['path'] }}">
                            <button type="submit"
                                    onclick="return confirm('Supprimer cette sauvegarde ?')"
                                    class="text-red-600 hover:text-red-700 dark:text-red-400 text-sm font-medium">
                                Supprimer
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="mt-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-sm text-blue-700 dark:text-blue-300">
    <i class="ti ti-info-circle mr-2"></i>Les sauvegardes sont gérées par <strong>spatie/laravel-backup</strong> et s'exécutent en arrière-plan via la file d'attente Laravel.
</div>
@endsection
