@extends('backoffice::layouts.admin')

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-2 text-gray-800">DupeModel</h1>
        <p class="mb-4">Liste des dupe-models</p>
        <a href="{{ route('core.dupe-models.create') }}" class="btn btn-primary btn-sm mb-4">Créer un DupeModel</a>
        
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dupeModels as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                
                                <td>
                                    <a href="{{ route('core.dupe-models.edit', $item->id) }}" class="btn btn-warning btn-sm">Modifier</a>
                                    <form action="{{ route('core.dupe-models.destroy', $item->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                 <div class="d-flex justify-content-center">
                    {{ $dupeModels->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection