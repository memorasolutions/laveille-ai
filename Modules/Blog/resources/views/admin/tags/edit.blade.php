@extends('backoffice::layouts.admin', ['title' => 'Modifier le tag', 'subtitle' => 'Blog'])
@section('content')
<div class="card"><div class="card-body">
    <form action="{{ route('admin.blog.tags.update', $tag) }}" method="POST">@csrf @method('PUT')
        <div class="mb-3"><label class="form-label">Nom *</label><input type="text" name="name" class="form-control" value="{{ old('name', $tag->name) }}" required></div>
        <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3">{{ old('description', $tag->description) }}</textarea></div>
        <div class="mb-3"><label class="form-label">Couleur</label><input type="color" name="color" value="{{ old('color', $tag->color) }}"></div>
        <button class="btn btn-primary">Enregistrer</button>
        <a href="{{ route('admin.blog.tags.index') }}" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div></div>
@endsection
