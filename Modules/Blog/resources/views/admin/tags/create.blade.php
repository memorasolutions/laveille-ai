<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin', ['title' => 'Nouveau tag', 'subtitle' => 'Blog'])
@section('content')
<div class="card"><div class="card-body">
    <form action="{{ route('admin.blog.tags.store') }}" method="POST">@csrf
        <div class="mb-3"><label class="form-label">Nom *</label><input type="text" name="name" class="form-control" value="{{ old('name') }}" required></div>
        <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea></div>
        <div class="mb-3"><label class="form-label">Couleur</label><input type="color" name="color" value="{{ old('color', '#6366f1') }}"></div>
        <button class="btn btn-primary">Enregistrer</button>
        <a href="{{ route('admin.blog.tags.index') }}" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div></div>
@endsection
