@extends('backoffice::themes.backend.layouts.admin')

@section('breadcrumbs')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item active" aria-current="page">Annonces</li>
    </ol>
</nav>
@endsection

@section('title', 'Annonces')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Annonces et changelog</h1>
        <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary">
            <i data-lucide="plus" class="icon-sm me-1"></i> Ajouter
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.announcements.index') }}" class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i data-lucide="search" class="icon-sm"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Rechercher..."
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="type" class="form-select">
                        <option value="">Tous les types</option>
                        <option value="feature" {{ request('type') == 'feature' ? 'selected' : '' }}>Nouveaute</option>
                        <option value="improvement" {{ request('type') == 'improvement' ? 'selected' : '' }}>Amelioration</option>
                        <option value="fix" {{ request('type') == 'fix' ? 'selected' : '' }}>Correctif</option>
                        <option value="announcement" {{ request('type') == 'announcement' ? 'selected' : '' }}>Annonce</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">Filtrer</button>
                </div>
            </form>

            @if($announcements->isEmpty())
                <div class="text-center py-5">
                    <i data-lucide="megaphone" style="width:48px;height:48px" class="text-muted mb-3 d-block mx-auto"></i>
                    <h5 class="text-muted">Aucune annonce.</h5>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Type</th>
                                <th>Version</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($announcements as $announcement)
                                <tr>
                                    <td>{{ $announcement->title }}</td>
                                    <td><span class="badge {{ $announcement->typeBadgeClass() }}">{{ $announcement->typeLabel() }}</span></td>
                                    <td>{{ $announcement->version ?: '-' }}</td>
                                    <td>
                                        <span class="badge {{ $announcement->is_published ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $announcement->is_published ? 'Publie' : 'Brouillon' }}
                                        </span>
                                    </td>
                                    <td>{{ $announcement->created_at->format('d/m/Y') }}</td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.announcements.edit', $announcement) }}" class="btn btn-outline-primary">
                                                <i data-lucide="pencil" class="icon-xs"></i>
                                            </a>
                                            <form action="{{ route('admin.announcements.destroy', $announcement) }}"
                                                  method="POST" class="d-inline"
                                                  onsubmit="return confirm('Supprimer cette annonce ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i data-lucide="trash-2" class="icon-xs"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $announcements->links() }}</div>
            @endif
        </div>
    </div>
@endsection
