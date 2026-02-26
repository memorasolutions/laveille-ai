@extends('backoffice::layouts.admin', ['title' => 'API Tokens', 'subtitle' => 'Profil'])

@section('content')

@if(session('token_value'))
<div class="alert alert-success-focus border border-success-main d-flex align-items-start gap-3 mb-20 p-16 radius-8">
    <iconify-icon icon="solar:key-outline" class="text-success-main text-2xl flex-shrink-0 mt-1"></iconify-icon>
    <div>
        <p class="mb-4 fw-semibold text-success-main">Token créé avec succès</p>
        <code class="text-neutral-900 bg-neutral-100 px-8 py-4 radius-4">{{ session('token_value') }}</code>
        <p class="mb-0 mt-8 text-sm text-neutral-600">⚠️ Copiez ce token maintenant, il ne sera plus visible.</p>
    </div>
</div>
@endif

<div class="row gy-3">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Créer un nouveau token API</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.profile.tokens.store') }}" method="POST">
                    @csrf
                    <div class="mb-20">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Nom du token <span class="text-danger-main">*</span></label>
                        <input type="text" name="name"
                               class="form-control radius-8 @error('name') is-invalid @enderror"
                               placeholder="Ex: CI/CD Pipeline" required maxlength="100">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="d-flex gap-3 mt-24">
                        <button type="submit" class="btn btn-primary-600">Créer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Tokens actifs ({{ $tokens->count() }})</h6>
            </div>
            @if($tokens->isEmpty())
                <div class="card-body">
                    <p class="text-neutral-600 mb-0">Aucun token API créé pour le moment.</p>
                </div>
            @else
                <div class="card-body p-0">
                    <table class="table bordered-table sm-table mb-0">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Créé le</th>
                                <th>Dernière utilisation</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tokens as $token)
                            <tr>
                                <td>{{ $token->name }}</td>
                                <td>{{ $token->created_at->diffForHumans() }}</td>
                                <td>{{ $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Jamais' }}</td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="w-40-px h-40-px bg-neutral-200 text-secondary-light rounded-circle d-flex justify-content-center align-items-center" data-bs-toggle="dropdown">
                                            <iconify-icon icon="tabler:dots-vertical" class="icon text-xl"></iconify-icon>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end p-12">
                                            <form action="{{ route('admin.profile.tokens.destroy', $token->id) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-danger-600" onclick="return confirm('Révoquer ce token ?')">
                                                    <iconify-icon icon="solar:shield-cross-outline" class="icon"></iconify-icon> Révoquer
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
