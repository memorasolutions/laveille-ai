<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Créer un DupeModel</h1>
    
    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('core.dupe-models.store') }}" method="POST">
                @csrf
                @include('core::dupe-models._fields')
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                 <a href="{{ route('core.dupe-models.index') }}" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</div>
@endsection