@extends('backoffice::layouts.admin', ['title' => 'Catégories cookies', 'subtitle' => 'Gestion'])

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title">Liste des catégories</h3>
        <a href="{{ route('admin.cookie-categories.create') }}" class="btn btn-primary btn-sm">
            <i class="ti ti-plus me-1"></i> Créer
        </a>
    </div>
    <div class="card-body p-0">

        @if (session('success'))
            <div class="alert alert-success alert-dismissible m-3">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible m-3">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-vcenter table-hover mb-0">
                <thead>
                    <tr>
                        <th>Ordre</th>
                        <th>Nom</th>
                        <th>Label</th>
                        <th>Obligatoire</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <td class="text-muted">{{ $category->order }}</td>
                            <td><code>{{ $category->name }}</code></td>
                            <td>{{ $category->label }}</td>
                            <td>
                                @if ($category->required)
                                    <span class="badge bg-primary-lt text-primary">Oui</span>
                                @else
                                    <span class="badge bg-secondary-lt text-secondary">Non</span>
                                @endif
                            </td>
                            <td>
                                @if ($category->is_active)
                                    <span class="badge bg-success-lt text-success">Oui</span>
                                @else
                                    <span class="badge bg-danger-lt text-danger">Non</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('admin.cookie-categories.edit', $category) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-pencil me-1"></i> Modifier
                                    </a>
                                    @unless($category->required)
                                        <form action="{{ route('admin.cookie-categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette catégorie ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="ti ti-trash me-1"></i> Supprimer
                                            </button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
