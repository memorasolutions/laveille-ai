@extends('backoffice::themes.backend.layouts.admin')
@section('title', 'Champs personnalisés')

@section('content')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item active" aria-current="page">Champs personnalisés</li>
    </ol>
</nav>
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
    <h4 class="mb-3 mb-md-0">Champs personnalisés</h4>
    <a href="{{ route('admin.custom-fields.create') }}" class="btn btn-primary btn-icon-text">
        <i class="btn-icon-prepend" data-lucide="plus"></i> Nouveau champ
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@forelse($definitions as $modelType => $group)
    <div class="card grid-margin">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">{{ \Modules\CustomFields\Models\CustomFieldDefinition::MODEL_TYPES[$modelType] ?? ucfirst($modelType) }}</h6>
            <span class="badge bg-primary">{{ $group->count() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Clé</th>
                        <th>Type</th>
                        <th class="text-center">Requis</th>
                        <th class="text-center">Actif</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($group as $definition)
                        <tr>
                            <td>{{ $definition->name }}</td>
                            <td><code>{{ $definition->key }}</code></td>
                            <td><span class="badge bg-info" style="font-size:10px;">{{ ucfirst($definition->type) }}</span></td>
                            <td class="text-center">
                                <span class="badge bg-{{ $definition->is_required ? 'success' : 'secondary' }}">{{ $definition->is_required ? 'Oui' : 'Non' }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $definition->is_active ? 'success' : 'secondary' }}">{{ $definition->is_active ? 'Oui' : 'Non' }}</span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('admin.custom-fields.edit', $definition) }}" class="btn btn-sm btn-outline-warning p-1" title="Modifier">
                                        <i data-lucide="edit" style="width:14px;height:14px;"></i>
                                    </a>
                                    <form action="{{ route('admin.custom-fields.destroy', $definition) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="if(confirm('Supprimer ce champ ?')) this.form.submit()" title="Supprimer">
                                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="card">
        <div class="card-body text-center py-5 text-muted">
            <i data-lucide="layers" style="width:48px;height:48px;" class="mb-3 d-block mx-auto"></i>
            <h5>Aucun champ personnalisé</h5>
            <p>Ajoutez des champs pour enrichir vos articles et pages.</p>
            <a href="{{ route('admin.custom-fields.create') }}" class="btn btn-primary mt-2">
                <i data-lucide="plus" style="width:16px;height:16px;"></i> Créer un champ
            </a>
        </div>
    </div>
@endforelse
@endsection
