@extends('backoffice::layouts.admin', ['title' => 'Sauvegardes', 'subtitle' => 'Gestion'])

@section('content')

<div class="card h-100 p-0 radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center flex-wrap gap-3 justify-content-end">
        <form action="{{ route('admin.backups.run') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-sm btn-primary-600 d-flex align-items-center gap-2">
                <iconify-icon icon="solar:cloud-download-outline" class="icon text-xl"></iconify-icon> Lancer une sauvegarde
            </button>
        </form>
    </div>
    <div class="card-body">
        @if(count($backups) === 0)
            <div class="text-center py-40">
                <iconify-icon icon="solar:cloud-check-outline" class="text-6xl text-secondary-light mb-16"></iconify-icon>
                <p class="text-secondary-light">Aucune sauvegarde disponible.</p>
                <p class="text-sm text-secondary-light">Cliquez sur "Lancer une sauvegarde" pour créer votre première sauvegarde.</p>
            </div>
        @else
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <thead>
                        <tr>
                            <th>Nom du fichier</th>
                            <th>Taille</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($backups as $backup)
                        <tr>
                            <td>{{ $backup['name'] }}</td>
                            <td>{{ number_format($backup['size'] / 1024 / 1024, 2) }} MB</td>
                            <td>{{ \Carbon\Carbon::createFromTimestamp($backup['date'])->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="w-40-px h-40-px bg-neutral-200 text-secondary-light rounded-circle d-flex justify-content-center align-items-center" data-bs-toggle="dropdown">
                                        <iconify-icon icon="tabler:dots-vertical" class="icon text-xl"></iconify-icon>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end p-12">
                                        <a href="{{ route('admin.backups.download', ['path' => $backup['path']]) }}" class="dropdown-item d-flex align-items-center gap-2">
                                            <iconify-icon icon="solar:download-outline" class="icon"></iconify-icon> Télécharger
                                        </a>
                                        <form method="POST" action="{{ route('admin.backups.delete') }}">
                                            @csrf @method('DELETE')
                                            <input type="hidden" name="path" value="{{ $backup['path'] }}">
                                            <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-danger-600" onclick="return confirm('Supprimer cette sauvegarde ?')">
                                                <iconify-icon icon="fluent:delete-24-regular" class="icon"></iconify-icon> Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="alert alert-info mt-16" role="alert">
    <iconify-icon icon="solar:info-circle-outline" class="icon text-xl me-1"></iconify-icon>
    Les sauvegardes sont gérées par <strong>spatie/laravel-backup</strong> et s'exécutent en arrière-plan via la file d'attente Laravel.
</div>

@endsection
