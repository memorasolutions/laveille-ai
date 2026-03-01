<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin')

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-2 text-gray-800">TestModel</h1>
        <p class="mb-4">Liste des test-models</p>
        <a href="{{ route('core.test-models.create') }}" class="btn btn-primary btn-sm mb-4">Créer un TestModel</a>
        
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
                            @foreach($testModels as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                
                                <td>
                                    <a href="{{ route('core.test-models.edit', $item->id) }}" class="btn btn-warning btn-sm">Modifier</a>
                                    <form action="{{ route('core.test-models.destroy', $item->id) }}" method="POST" style="display:inline;">
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
                    {{ $testModels->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection