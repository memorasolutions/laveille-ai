@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Tableau de bord')])

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h1 class="fw-semibold fs-5 mb-0">{{ __('Tableau de bord') }}</h1>
    <nav aria-label="Fil d'Ariane">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ __('Tableau de bord') }}</li>
        </ol>
    </nav>
</div>

{{-- Stats cards --}}
<div class="row mb-4">
    {{-- Users --}}
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;min-width:48px;">
                    <i data-lucide="users" class="text-primary icon-md"></i>
                </div>
                <div>
                    <p class="text-muted small mb-1">{{ __('Utilisateurs') }}</p>
                    <h4 class="fw-bold mb-0">{{ \App\Models\User::count() }}</h4>
                    <p class="text-success small mb-0">+{{ \App\Models\User::where('created_at', '>=', now()->subDays(30))->count() }} {{ __('ce mois') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Articles --}}
    @if(class_exists(\Modules\Blog\app\Models\Article::class))
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;min-width:48px;">
                    <i data-lucide="file-text" class="text-success icon-md"></i>
                </div>
                <div>
                    <p class="text-muted small mb-1">{{ __('Articles') }}</p>
                    <h4 class="fw-bold mb-0">{{ \Modules\Blog\app\Models\Article::count() }}</h4>
                    <p class="text-success small mb-0">+{{ \Modules\Blog\app\Models\Article::where('created_at', '>=', now()->subDays(30))->count() }} {{ __('ce mois') }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Plans --}}
    @if(class_exists(\Modules\SaaS\app\Models\Plan::class))
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;min-width:48px;">
                    <i data-lucide="credit-card" class="text-info icon-md"></i>
                </div>
                <div>
                    <p class="text-muted small mb-1">{{ __('Plans actifs') }}</p>
                    <h4 class="fw-bold mb-0">{{ \Modules\SaaS\app\Models\Plan::where('is_active', true)->count() }}</h4>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Modules --}}
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;min-width:48px;">
                    <i data-lucide="layout-grid" class="text-warning icon-md"></i>
                </div>
                <div>
                    <p class="text-muted small mb-1">{{ __('Modules actifs') }}</p>
                    <h4 class="fw-bold mb-0">{{ count(\Nwidart\Modules\Facades\Module::allEnabled()) }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Recent activity + Quick actions --}}
<div class="row">
    <div class="col-lg-7 col-xl-8 mb-4">
        <div class="card h-100">
            <div class="card-header py-3 px-4 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="activity" class="text-primary icon-md"></i>
                <h4 class="fw-bold mb-0">{{ __('Activité récente') }}</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="py-3 px-4 fw-semibold text-body">{{ __('Utilisateur') }}</th>
                                <th class="py-3 px-4 fw-semibold text-body">{{ __('Action') }}</th>
                                <th class="py-3 px-4 fw-semibold text-body d-none d-sm-table-cell">{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(\Spatie\Activitylog\Models\Activity::latest()->limit(10)->get() as $log)
                            <tr>
                                <td class="py-3 px-4 small">{{ $log->causer?->name ?? __('Système') }}</td>
                                <td class="py-3 px-4"><span class="badge bg-primary bg-opacity-10 text-primary">{{ $log->description }}</span></td>
                                <td class="py-3 px-4 text-muted small d-none d-sm-table-cell">{{ $log->created_at->diffForHumans() }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">{{ __('Aucune activité récente') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5 col-xl-4 mb-4">
        <div class="card h-100">
            <div class="card-header py-3 px-4 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="zap" class="text-warning icon-md"></i>
                <h4 class="fw-bold mb-0">{{ __('Actions rapides') }}</h4>
            </div>
            <div class="card-body p-4">
                <div class="d-grid gap-2">
                    @can('manage_users')
                    <a href="{{ route('admin.users.create') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2">
                        <i data-lucide="user-plus" class="icon-sm"></i> {{ __('Nouvel utilisateur') }}
                    </a>
                    @endcan
                    @if(Route::has('admin.blog.articles.create'))
                    <a href="{{ route('admin.blog.articles.create') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2">
                        <i data-lucide="file-plus" class="icon-sm"></i> {{ __('Nouvel article') }}
                    </a>
                    @endif
                    @if(Route::has('admin.pages.create'))
                    <a href="{{ route('admin.pages.create') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2">
                        <i data-lucide="layout" class="icon-sm"></i> {{ __('Nouvelle page') }}
                    </a>
                    @endif
                    @can('manage_settings')
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                        <i data-lucide="settings" class="icon-sm"></i> {{ __('Paramètres') }}
                    </a>
                    @endcan
                    @can('manage_backups')
                    <a href="{{ route('admin.health') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                        <i data-lucide="activity" class="icon-sm"></i> {{ __('Santé système') }}
                    </a>
                    @if(Route::has('admin.backups.index'))
                    <a href="{{ route('admin.backups.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                        <i data-lucide="hard-drive" class="icon-sm"></i> {{ __('Sauvegardes') }}
                    </a>
                    @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>

{{-- System info row --}}
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header py-3 px-4 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="server" class="text-secondary icon-md"></i>
                <h4 class="fw-bold mb-0">{{ __('Informations système') }}</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <tbody>
                            <tr><td class="py-3 px-4 fw-medium">Laravel</td><td class="py-3 px-4">v{{ app()->version() }}</td></tr>
                            <tr><td class="py-3 px-4 fw-medium">PHP</td><td class="py-3 px-4">v{{ phpversion() }}</td></tr>
                            <tr><td class="py-3 px-4 fw-medium">{{ __('Environnement') }}</td><td class="py-3 px-4"><span class="badge {{ app()->environment('production') ? 'bg-danger' : 'bg-success' }}">{{ app()->environment() }}</span></td></tr>
                            <tr><td class="py-3 px-4 fw-medium">{{ __('Modules actifs') }}</td><td class="py-3 px-4">{{ count(\Nwidart\Modules\Facades\Module::allEnabled()) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
