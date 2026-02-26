@extends('backoffice::layouts.admin', ['title' => 'Catégories cookies', 'subtitle' => 'Gestion'])

@section('content')
<div class="d-flex justify-content-end align-items-center mb-24">
    <a href="{{ route('admin.cookie-categories.create') }}" class="btn btn-primary text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2">
        <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon> Créer
    </a>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="card radius-12">
    <div class="card-body p-0">
        <table class="table bordered-table sm-table mb-0">
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
                        <td>{{ $category->order }}</td>
                        <td><code>{{ $category->name }}</code></td>
                        <td>{{ $category->label }}</td>
                        <td>
                            <span class="badge {{ $category->required ? 'bg-primary' : 'bg-secondary-light text-secondary' }}">{{ $category->required ? 'Oui' : 'Non' }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $category->is_active ? 'bg-success' : 'bg-danger' }}">{{ $category->is_active ? 'Oui' : 'Non' }}</span>
                        </td>
                        <td>
                            <a href="{{ route('admin.cookie-categories.edit', $category) }}" class="btn btn-sm btn-outline-primary me-1">Modifier</a>
                            @unless($category->required)
                            <form action="{{ route('admin.cookie-categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette catégorie ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                            </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
