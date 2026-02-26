@extends('backoffice::layouts.admin', ['title' => 'Plugins', 'subtitle' => 'Configuration'])

@section('content')
<div class="row row-deck row-cards">
    @forelse($plugins ?? [] as $plugin)
    <div class="col-sm-6 col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar bg-primary-lt me-3">
                        <i class="ti ti-puzzle"></i>
                    </span>
                    <div>
                        <h3 class="card-title mb-0">{{ $plugin['name'] ?? '' }}</h3>
                        <span class="text-muted small">v{{ $plugin['version'] ?? '1.0' }}</span>
                    </div>
                </div>
                <p class="text-muted">{{ $plugin['description'] ?? 'Aucune description' }}</p>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="badge bg-{{ ($plugin['enabled'] ?? false) ? 'success' : 'secondary' }}">
                        {{ ($plugin['enabled'] ?? false) ? 'Actif' : 'Inactif' }}
                    </span>
                    <form action="{{ route('admin.plugins.toggle', $plugin['name'] ?? '') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm {{ ($plugin['enabled'] ?? false) ? 'btn-outline-danger' : 'btn-outline-success' }}">
                            {{ ($plugin['enabled'] ?? false) ? 'Désactiver' : 'Activer' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="ti ti-puzzle-off mb-2 d-block" style="font-size: 2rem;"></i>
                Aucun plugin disponible
            </div>
        </div>
    </div>
    @endforelse
</div>
@endsection
