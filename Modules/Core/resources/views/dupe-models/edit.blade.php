@extends('backoffice::layouts.admin')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Modifier le DupeModel #{{ $dupeModel->id }}</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('core.dupe-models.update', $dupeModel->id) }}" method="POST">
                @csrf
                @method('PUT')
                @include('core::dupe-models._fields', ['dupeModel' => $dupeModel])
                <button type="submit" class="btn btn-primary">Mettre à jour</button>
                <a href="{{ route('core.dupe-models.index') }}" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</div>
@endsection