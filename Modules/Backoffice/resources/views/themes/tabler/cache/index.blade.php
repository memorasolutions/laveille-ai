@extends('backoffice::layouts.admin', ['title' => 'Cache', 'subtitle' => 'Outils'])

@section('content')
<div class="row row-deck row-cards">
    @foreach([
        ['route' => 'admin.cache.clear-app',    'icon' => 'ti-database',  'title' => 'Cache application', 'desc' => "Vider le cache de l'application"],
        ['route' => 'admin.cache.clear-config', 'icon' => 'ti-settings',  'title' => 'Cache config',       'desc' => 'Vider le cache de configuration'],
        ['route' => 'admin.cache.clear-route',  'icon' => 'ti-route',     'title' => 'Cache routes',       'desc' => 'Vider le cache des routes'],
        ['route' => 'admin.cache.clear-view',   'icon' => 'ti-eye',       'title' => 'Cache vues',         'desc' => 'Vider le cache des vues compilées'],
    ] as $cache)
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-3 text-primary">
                    <i class="ti {{ $cache['icon'] }}" style="font-size: 2rem;"></i>
                </div>
                <h3 class="card-title">{{ $cache['title'] }}</h3>
                <p class="text-muted">{{ $cache['desc'] }}</p>
                <form action="{{ route($cache['route']) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="ti ti-trash me-1"></i> Vider
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
