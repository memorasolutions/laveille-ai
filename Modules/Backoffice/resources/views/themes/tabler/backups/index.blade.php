@extends('backoffice::layouts.admin', ['title' => 'Sauvegardes', 'subtitle' => 'Gestion'])

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title">Sauvegardes</h3>
        <div class="d-flex gap-2">
            <form action="{{ route('admin.backups.run') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="ti ti-plus me-1"></i> Nouvelle sauvegarde
                </button>
            </form>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Fichier</th>
                    <th>Taille</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($backups ?? [] as $backup)
                <tr>
                    <td><i class="ti ti-archive me-2 text-muted"></i>{{ $backup['name'] ?? '' }}</td>
                    <td>{{ $backup['size'] ?? '' }}</td>
                    <td class="text-muted">{{ $backup['date'] ?? '' }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.backups.download', ['file' => $backup['name'] ?? '']) }}" class="btn btn-sm btn-outline-primary" title="Télécharger">
                                <i class="ti ti-download"></i>
                            </a>
                            <form action="{{ route('admin.backups.delete', ['file' => $backup['name'] ?? '']) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette sauvegarde ?')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        <i class="ti ti-archive-off mb-2 d-block" style="font-size: 2rem;"></i>
                        Aucune sauvegarde disponible
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
