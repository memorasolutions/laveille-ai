<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Modifier le tag', 'subtitle' => 'Blog'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.blog.tags.index') }}">Tags</a></li>
        <li class="breadcrumb-item active" aria-current="page">Modifier</li>
    </ol>
</nav>

@if($errors->any())
    <div class="alert alert-danger mb-4">
        <ul class="mb-0">
            @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <h5 class="fw-bold mb-0">Modifier : {{ $tag->name }}</h5>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('admin.blog.tags.update', $tag) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name', $tag->name) }}" required maxlength="100">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $tag->description) }}</textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="color" class="form-label fw-semibold">Couleur</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" class="form-control form-control-color" id="color" name="color"
                                   value="{{ old('color', $tag->color) }}" style="width:50px;height:38px">
                            <span class="text-muted" id="color-hex">{{ old('color', $tag->color) }}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Slug</label>
                        <input type="text" class="form-control bg-light text-muted" value="{{ $tag->slug }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Articles</label>
                        <p class="mb-0"><span class="badge bg-primary">{{ $tag->articles()->count() }}</span> articles associés</p>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('admin.blog.tags.index') }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>

@push('plugin-scripts')
<script>
document.getElementById('color').addEventListener('input', function() {
    document.getElementById('color-hex').textContent = this.value;
});
</script>
@endpush

@endsection
