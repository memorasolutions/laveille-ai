@extends('backoffice::layouts.admin', ['title' => 'Tableau de bord', 'subtitle' => 'Accueil'])

@section('content')

{{-- Stats row --}}
<div class="row row-deck row-cards">
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Utilisateurs</div>
                </div>
                <div class="h1 mb-0 mt-2">{{ $usersCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Utilisateurs actifs</div>
                </div>
                <div class="h1 mb-0 mt-2">{{ $activeUsersCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Articles publiés</div>
                </div>
                <div class="h1 mb-0 mt-2">{{ $publishedCount }} / {{ $articlesCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Abonnés newsletter</div>
                </div>
                <div class="h1 mb-0 mt-2">{{ $subscribersCount }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Growth section --}}
<h3 class="mt-4 mb-3"><i class="ti ti-trending-up me-1"></i> Croissance</h3>
<div class="row row-deck row-cards">
    <div class="col-sm-6 col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Nouveaux ce mois</div>
                <div class="h1 mb-0 mt-2">{{ $newUsersThisMonth }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Nouveaux cette semaine</div>
                <div class="h1 mb-0 mt-2">{{ $newUsersThisWeek }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Abonnés (30 jours)</div>
                <div class="h1 mb-0 mt-2">{{ $subscribersGrowth }}</div>
            </div>
        </div>
    </div>
</div>

{{-- System info collapsible --}}
<div class="mt-4" x-data="{ open: false }">
    <button type="button" class="btn btn-outline-secondary mb-3" @click="open = !open">
        <i class="ti ti-info-circle me-1"></i> Infos système
        <i class="ti" :class="open ? 'ti-chevron-up' : 'ti-chevron-down'" class="ms-1"></i>
    </button>
    <div x-show="open" x-transition x-cloak>
        <div class="row row-deck row-cards">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Rôles</div>
                        <div class="h2 mb-0 mt-2">{{ $rolesCount }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Environnement</div>
                        <div class="h2 mb-0 mt-2">{{ $environment }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Laravel</div>
                        <div class="h2 mb-0 mt-2">{{ $laravelVersion }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">PHP</div>
                        <div class="h2 mb-0 mt-2">{{ $phpVersion }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Maintenance mode --}}
<div class="card mt-4 {{ $isMaintenanceMode ? 'border-warning' : 'border-success' }}">
    <div class="card-body d-flex align-items-center justify-content-between">
        <div>
            <h3 class="mb-1">Mode maintenance</h3>
            <p class="text-muted mb-0">
                @if($isMaintenanceMode)
                    <span class="badge bg-warning">Activé</span> Le site est en maintenance
                @else
                    <span class="badge bg-success">En ligne</span> Le site est accessible
                @endif
            </p>
        </div>
        <form action="{{ route('admin.maintenance.toggle') }}" method="POST">
            @csrf
            <button type="submit" class="btn {{ $isMaintenanceMode ? 'btn-success' : 'btn-warning' }}">
                <i class="ti ti-{{ $isMaintenanceMode ? 'player-play' : 'player-pause' }} me-1"></i>
                {{ $isMaintenanceMode ? 'Remettre en ligne' : 'Activer maintenance' }}
            </button>
        </form>
    </div>
</div>

{{-- Recent activity table --}}
<div class="card mt-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title">Activité récente</h3>
        <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-sm btn-outline-primary">Voir tout</a>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Utilisateur</th>
                    <th>Sujet</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentActivities as $activity)
                <tr>
                    <td>{{ $activity->description }}</td>
                    <td>{{ $activity->causer?->name ?? 'Système' }}</td>
                    <td>{{ class_basename($activity->subject_type ?? '') }}</td>
                    <td class="text-muted">{{ $activity->created_at->diffForHumans() }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted">Aucune activité récente</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('backoffice::dashboard._charts')

@endsection
