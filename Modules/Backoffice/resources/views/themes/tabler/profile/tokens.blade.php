@extends('backoffice::layouts.admin', ['title' => 'Jetons API', 'subtitle' => 'Mon profil'])
@section('content')
<div class="card mb-3">
    <div class="card-header"><h3 class="card-title">Créer un nouveau jeton</h3></div>
    <div class="card-body">
        <form action="{{ route('admin.profile.tokens.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-8">
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Nom du jeton" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100"><i class="ti ti-plus me-1"></i> Créer</button>
                </div>
            </div>
        </form>
    </div>
</div>
@if(session('token'))
<div class="alert alert-success"><strong>Votre nouveau jeton :</strong> <code>{{ session('token') }}</code><br><small class="text-muted">Copiez-le maintenant, il ne sera plus affiché.</small></div>
@endif
<div class="card">
    <div class="card-header"><h3 class="card-title">Jetons existants</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Nom</th><th>Créé le</th><th>Dernière utilisation</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($tokens ?? [] as $token)
                <tr>
                    <td>{{ $token->name }}</td>
                    <td class="text-muted">{{ $token->created_at->format('d/m/Y H:i') }}</td>
                    <td class="text-muted">{{ $token->last_used_at?->diffForHumans() ?? 'Jamais' }}</td>
                    <td>
                        <form action="{{ route('admin.profile.tokens.destroy', $token) }}" method="POST" onsubmit="return confirm('Révoquer ce jeton ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="ti ti-trash me-1"></i> Révoquer</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted">Aucun jeton créé</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
