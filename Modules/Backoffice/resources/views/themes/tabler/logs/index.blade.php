@extends('backoffice::layouts.admin', ['title' => 'Journaux application', 'subtitle' => 'Outils'])

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Fichiers de log</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Fichier</th>
                    <th>Taille</th>
                    <th>Modifié le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs ?? [] as $log)
                <tr>
                    <td><i class="ti ti-file-text me-2 text-muted"></i>{{ $log['name'] ?? '' }}</td>
                    <td>{{ $log['size'] ?? '' }}</td>
                    <td class="text-muted">{{ $log['modified'] ?? '' }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.logs.show', ['file' => $log['name'] ?? '']) }}" class="btn btn-sm btn-outline-primary" title="Voir">
                                <i class="ti ti-eye"></i>
                            </a>
                            <a href="{{ route('admin.logs.download', ['file' => $log['name'] ?? '']) }}" class="btn btn-sm btn-outline-success" title="Télécharger">
                                <i class="ti ti-download"></i>
                            </a>
                            <form action="{{ route('admin.logs.destroy', ['file' => $log['name'] ?? '']) }}" method="POST" onsubmit="return confirm('Supprimer ce fichier de log ?')">
                                @csrf
                                @method('DELETE')
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
                        <i class="ti ti-file-off mb-2 d-block" style="font-size: 2rem;"></i>
                        Aucun fichier de log
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
