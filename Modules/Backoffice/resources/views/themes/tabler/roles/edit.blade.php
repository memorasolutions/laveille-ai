@extends('backoffice::layouts.admin', ['title' => 'Rôles', 'subtitle' => 'Modifier'])

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Modifier le rôle : {{ $role->name }}</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.roles.update', $role) }}" method="POST">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label required">Nom du rôle</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $role->name) }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Permissions</label>
                @php $rolePermIds = $role->permissions->pluck('id')->toArray(); @endphp
                @foreach($categories as $catKey => $category)
                    @if(collect($category['permissions'])->pluck('model')->filter()->isNotEmpty())
                    <div class="card border mb-2">
                        <div class="card-header py-2 bg-light">
                            <h4 class="card-title mb-0 text-capitalize">{{ $category['label'] }}</h4>
                        </div>
                        <div class="card-body py-2">
                            <div class="d-flex flex-wrap gap-3">
                                @foreach($category['permissions'] as $permName => $permMeta)
                                    @if($permMeta['model'])
                                    <label class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permMeta['model']->id }}" {{ in_array($permMeta['model']->id, old('permissions', $rolePermIds)) ? 'checked' : '' }}>
                                        <span class="form-check-label">{{ $permMeta['label'] }}</span>
                                    </label>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i> Enregistrer</button>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-danger">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
