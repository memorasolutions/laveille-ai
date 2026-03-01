<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Modifier le TestModel #{{ $testModel->id }}</h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('core.test-models.update', $testModel->id) }}" method="POST">
                @csrf
                @method('PUT')
                @include('core::test-models._fields', ['testModel' => $testModel])
                <button type="submit" class="btn btn-primary">Mettre à jour</button>
                <a href="{{ route('core.test-models.index') }}" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</div>
@endsection