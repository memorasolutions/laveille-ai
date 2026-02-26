@extends('backoffice::layouts.admin', ['title' => 'Corbeille', 'subtitle' => 'Outils'])
@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title">Éléments supprimés</h3>
        <form action="{{ route('admin.trash.index') }}" method="POST" onsubmit="return confirm('Vider définitivement la corbeille ?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="ti ti-trash me-1"></i> Vider la corbeille</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Type</th><th>Nom</th><th>Supprimé le</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($trashedItems ?? [] as $item)
                <tr>
                    <td><span class="badge bg-secondary">{{ $item['type'] ?? '' }}</span></td>
                    <td>{{ $item['name'] ?? $item['title'] ?? '' }}</td>
                    <td class="text-muted">{{ $item['deleted_at'] ?? '' }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <form action="{{ route('admin.trash.restore', ['type' => $item['type'] ?? '', 'id' => $item['id'] ?? 0]) }}" method="POST">@csrf<button type="submit" class="btn btn-sm btn-outline-success" title="Restaurer"><i class="ti ti-arrow-back-up"></i></button></form>
                            <form action="{{ route('admin.trash.force-delete', ['type' => $item['type'] ?? '', 'id' => $item['id'] ?? 0]) }}" method="POST" onsubmit="return confirm('Supprimer définitivement ?')">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="ti ti-trash"></i></button></form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted">La corbeille est vide</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
