@extends('backoffice::layouts.admin', ['title' => 'Tableau de bord', 'subtitle' => 'Accueil'])

@section('content')

{{-- Principe ADHD: regrouper les stats en sections logiques (max 4 par groupe) --}}
{{-- Section 1: Métriques principales - les KPI business visibles immédiatement --}}
<div class="row row-cols-xxxl-4 row-cols-lg-2 row-cols-sm-2 row-cols-1 gy-4">

    <div class="col">
        <div class="card shadow-none border bg-gradient-start-1 h-100">
            <div class="card-body p-20">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">Utilisateurs</p>
                        <h6 class="mb-0">{{ $usersCount }}</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-cyan rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="gridicons:multiple-users" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card shadow-none border bg-gradient-start-3 h-100">
            <div class="card-body p-20">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">Utilisateurs actifs</p>
                        <h6 class="mb-0">{{ $activeUsersCount }}</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-success-600 rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="solar:user-check-rounded-outline" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card shadow-none border bg-gradient-start-1 h-100">
            <div class="card-body p-20">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">Articles publiés</p>
                        <h6 class="mb-0">{{ $publishedCount }} / {{ $articlesCount }}</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-info-main rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="solar:document-text-outline" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card shadow-none border bg-gradient-start-2 h-100">
            <div class="card-body p-20">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">Abonnés newsletter</p>
                        <h6 class="mb-0">{{ $subscribersCount }}</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-success-main rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="solar:letter-outline" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Principe ADHD: section secondaire séparée visuellement - tendances temporelles --}}
<h6 class="text-secondary-light fw-semibold mt-24 mb-12">
    <iconify-icon icon="solar:graph-up-outline" class="me-1"></iconify-icon> Croissance
</h6>
<div class="row row-cols-xxxl-3 row-cols-lg-3 row-cols-sm-2 row-cols-1 gy-4">

    <div class="col">
        <div class="card shadow-none border bg-gradient-start-4 h-100">
            <div class="card-body p-20">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">Nouveaux ce mois</p>
                        <h6 class="mb-0">{{ $newUsersThisMonth }}</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-warning-main rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="solar:user-plus-rounded-outline" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card shadow-none border bg-gradient-start-5 h-100">
            <div class="card-body p-20">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">Nouveaux cette semaine</p>
                        <h6 class="mb-0">{{ $newUsersThisWeek }}</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-primary-600 rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="solar:user-check-rounded-outline" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col">
        <div class="card shadow-none border bg-gradient-start-1 h-100">
            <div class="card-body p-20">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">Abonnés (30j)</p>
                        <h6 class="mb-0">{{ $subscribersGrowth }}</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-success-600 rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="solar:letter-opened-outline" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Principe ADHD: info système repliable - zero bruit par défaut --}}
<div class="mt-24" x-data="{ open: false }">
    <button type="button" class="btn btn-outline-neutral-600 text-sm btn-sm px-16 py-8 radius-8 d-flex align-items-center gap-2"
            @click="open = !open">
        <iconify-icon icon="solar:server-outline" class="text-lg"></iconify-icon>
        Infos système
        <iconify-icon :icon="open ? 'solar:alt-arrow-up-outline' : 'solar:alt-arrow-down-outline'" class="text-lg"></iconify-icon>
    </button>
    <div x-show="open" x-transition x-cloak class="row row-cols-xxxl-4 row-cols-lg-4 row-cols-sm-2 row-cols-1 gy-4 mt-8">

        <div class="col">
            <div class="card shadow-none border h-100">
                <div class="card-body p-20">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <p class="fw-medium text-primary-light mb-1">Rôles</p>
                            <h6 class="mb-0">{{ $rolesCount }}</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-purple rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="solar:shield-user-outline" class="text-white text-2xl mb-0"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card shadow-none border h-100">
                <div class="card-body p-20">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <p class="fw-medium text-primary-light mb-1">Environnement</p>
                            <h6 class="mb-0 text-capitalize">{{ $environment }}</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-info rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="solar:server-outline" class="text-white text-2xl mb-0"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card shadow-none border h-100">
                <div class="card-body p-20">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <p class="fw-medium text-primary-light mb-1">Laravel</p>
                            <h6 class="mb-0">v{{ $laravelVersion }}</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-success-main rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="mdi:laravel" class="text-white text-2xl mb-0"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card shadow-none border h-100">
                <div class="card-body p-20">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <p class="fw-medium text-primary-light mb-1">PHP</p>
                            <h6 class="mb-0">v{{ $phpVersion }}</h6>
                        </div>
                        <div class="w-50-px h-50-px bg-warning-main rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="mdi:language-php" class="text-white text-2xl mb-0"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Maintenance mode toggle --}}
<div class="card shadow-none border mt-24 {{ $isMaintenanceMode ? 'border-warning-main' : 'border-success-main' }}">
    <div class="card-body p-20">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="w-50-px h-50-px {{ $isMaintenanceMode ? 'bg-warning-main' : 'bg-success-main' }} rounded-circle d-flex justify-content-center align-items-center">
                    <iconify-icon icon="{{ $isMaintenanceMode ? 'solar:shield-warning-outline' : 'solar:check-circle-outline' }}" class="text-white text-2xl mb-0"></iconify-icon>
                </div>
                <div>
                    <h6 class="mb-1">{{ $isMaintenanceMode ? 'Mode maintenance actif' : 'Site en ligne' }}</h6>
                    <p class="text-secondary-light mb-0 text-sm">{{ $isMaintenanceMode ? 'Le site est inaccessible aux visiteurs' : 'Le site est accessible à tous' }}</p>
                </div>
            </div>
            <form action="{{ route('admin.maintenance.toggle') }}" method="POST">
                @csrf
                <button type="submit" class="btn {{ $isMaintenanceMode ? 'btn-success-600' : 'btn-warning-600' }} text-sm btn-sm px-16 py-8 radius-8">
                    <iconify-icon icon="{{ $isMaintenanceMode ? 'solar:play-outline' : 'solar:pause-outline' }}" class="me-1"></iconify-icon>
                    {{ $isMaintenanceMode ? 'Remettre en ligne' : 'Activer maintenance' }}
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Recent activity --}}
<div class="card h-100 p-0 radius-12 mt-24">
    <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center flex-wrap gap-3 justify-content-between">
        <h6 class="mb-0">Activité récente</h6>
        <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-outline-primary-600 text-sm btn-sm px-12 py-12 radius-8">Voir tout</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive scroll-sm">
            <table class="table bordered-table sm-table mb-0">
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
                            <td>
                                @if($activity->subject_type)
                                    <span class="badge bg-primary-600">{{ class_basename($activity->subject_type) }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $activity->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-secondary-light py-20">Aucune activité récente</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('backoffice::dashboard._charts')

@endsection
