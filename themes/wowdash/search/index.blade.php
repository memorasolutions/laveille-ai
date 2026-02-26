@extends('backoffice::layouts.admin')

@section('title', 'Recherche')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('backoffice.dashboard') }}">Tableau de bord</a></li>
            <li class="breadcrumb-item active" aria-current="page">Recherche</li>
        </ol>
    </nav>

    @if (!empty($q))
        <h6 class="mb-4">Résultats pour "{{ $q }}"</h6>

        <div class="card mb-4">
            <div class="card-header">
                Utilisateurs trouvés
            </div>
            <div class="card-body">
                @if ($users->count())
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Courriel</th>
                                    <th>Rôles</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    <tr>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->roles->pluck('name')->join(', ') }}</td>
                                        <td>
                                            <a href="{{ route('backoffice.users.edit', $user->id) }}" class="btn btn-sm btn-primary">Modifier</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted">Aucun utilisateur trouvé.</p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                Rôles trouvés
            </div>
            <div class="card-body">
                @if ($roles->count())
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Guard</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roles as $role)
                                    <tr>
                                        <td>{{ $role->name }}</td>
                                        <td>{{ $role->guard_name }}</td>
                                        <td>
                                            <a href="{{ route('backoffice.roles.edit', $role->id) }}" class="btn btn-sm btn-primary">Modifier</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted">Aucun rôle trouvé.</p>
                @endif
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-header">
                Rechercher
            </div>
            <div class="card-body">
                <form action="{{ route('backoffice.search') }}" method="GET">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control" placeholder="Rechercher...">
                        <button class="btn btn-primary" type="submit">Rechercher</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
