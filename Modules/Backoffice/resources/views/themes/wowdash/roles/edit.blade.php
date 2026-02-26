@extends('backoffice::layouts.admin', ['title' => 'Rôles', 'subtitle' => 'Modifier'])

@section('content')

<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Modifier le rôle : {{ $role->name }}</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.roles.update', $role) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-20">
                <label class="form-label fw-semibold text-primary-light text-sm mb-8">Nom du rôle <span class="text-danger-main">*</span></label>
                <input type="text" name="name" class="form-control radius-8 @error('name') is-invalid @enderror" value="{{ old('name', $role->name) }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-20">
                <label class="form-label fw-semibold text-primary-light text-sm mb-8">Permissions</label>
                @error('permissions') <div class="text-danger-main text-sm mb-8">{{ $message }}</div> @enderror

                @foreach($categories as $catKey => $category)
                    @if(collect($category['permissions'])->pluck('model')->filter()->isNotEmpty())
                    <div class="card border mb-12">
                        <div class="card-header py-8 bg-neutral-50">
                            <h6 class="mb-0 text-capitalize text-sm">{{ $category['label'] }}</h6>
                        </div>
                        <div class="card-body py-12">
                            <div class="d-flex flex-wrap gap-3">
                                @foreach($category['permissions'] as $permName => $permMeta)
                                    @if($permMeta['model'])
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permMeta['model']->id }}" id="perm_{{ $permMeta['model']->id }}" @checked(in_array($permMeta['model']->id, old('permissions', $role->permissions->pluck('id')->toArray())))>
                                        <label class="form-check-label text-sm" for="perm_{{ $permMeta['model']->id }}">{{ $permMeta['label'] }}</label>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>

            <div class="d-flex gap-3 mt-24">
                <button type="submit" class="btn btn-primary-600">Enregistrer</button>
                <a href="{{ route('admin.roles.index') }}" class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-40 py-11 radius-8">Annuler</a>
            </div>
        </form>
    </div>
</div>

@endsection
