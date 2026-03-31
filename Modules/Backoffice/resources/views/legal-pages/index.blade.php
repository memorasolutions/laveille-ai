@extends('backoffice::layouts.admin', ['title' => 'Pages légales', 'subtitle' => 'Confidentialité, CGU, cookies'])

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Pages légales</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Slug</th>
                        <th>Statut</th>
                        <th>Dernière modification</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pages as $page)
                        <tr>
                            <td><strong>{{ $page->title }}</strong></td>
                            <td><code>{{ $page->slug }}</code></td>
                            <td>
                                @if($page->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>{{ $page->updated_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.legal-pages.edit', $page) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Modifier
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Aucune page légale en base de données. Les pages par défaut (Blade) sont utilisées.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
