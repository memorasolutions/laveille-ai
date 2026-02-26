@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Rôles', 'subtitle' => $role->name])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">{{ __('Rôles') }}</a></li>
        <li class="breadcrumb-item active">{{ $role->name }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="shield" class="icon-md text-primary"></i>{{ __('Rôle :') }} {{ $role->name }}</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
            <i data-lucide="pencil"></i> {{ __('Modifier') }}
        </a>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-sm btn-light d-inline-flex align-items-center gap-2">
            <i data-lucide="arrow-left"></i> {{ __('Retour') }}
        </a>
    </div>
</div>

<div class="row g-4">

    {{-- Infos du rôle --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body d-flex flex-column align-items-center text-center py-4">
                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold mb-3"
                     style="width:80px;height:80px;font-size:1.5rem;">
                    <i data-lucide="shield" style="width:36px;height:36px;"></i>
                </div>
                <h5 class="fw-semibold mb-1">{{ $role->name }}</h5>
                <p class="text-muted small mb-2">{{ __('Guard') }} : {{ $role->guard_name }}</p>
                <span class="badge bg-primary bg-opacity-10 text-primary fw-medium px-3 py-2">
                    {{ $role->permissions->count() }} {{ __('permissions') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Permissions par catégorie --}}
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-2 py-3">
                <i data-lucide="key" class="icon-md text-primary"></i>
                <h5 class="fw-semibold mb-0">{{ __('Permissions assignées') }}</h5>
            </div>
            <div class="card-body">
                @if($role->permissions->count())
                    @php
                        $permCategories = [
                            'contenu' => ['label' => 'Contenu', 'icon' => 'file-text', 'prefixes' => ['manage_articles', 'manage_comments', 'manage_categories', 'manage_pages', 'manage_media', 'manage_seo']],
                            'utilisateurs' => ['label' => 'Utilisateurs', 'icon' => 'users', 'prefixes' => ['manage_users', 'manage_roles', 'manage_newsletter', 'manage_campaigns', 'manage_notifications']],
                            'configuration' => ['label' => 'Configuration', 'icon' => 'settings', 'prefixes' => ['manage_settings', 'manage_branding', 'manage_themes', 'manage_translations', 'manage_feature_flags', 'manage_plans', 'manage_webhooks']],
                            'outils' => ['label' => 'Outils', 'icon' => 'wrench', 'prefixes' => ['manage_backups', 'manage_activity_logs', 'manage_exports', 'manage_imports', 'manage_api']],
                            'acces' => ['label' => 'Accès', 'icon' => 'eye', 'prefixes' => ['view_admin_panel', 'view_dashboard', 'view_health', 'view_logs', 'view_horizon', 'view_telescope']],
                        ];
                        $rolePermNames = $role->permissions->pluck('name')->toArray();
                    @endphp

                    <div class="d-flex flex-column gap-3">
                        @foreach($permCategories as $catKey => $cat)
                            @php
                                $matchedPerms = array_intersect($cat['prefixes'], $rolePermNames);
                            @endphp
                            @if(count($matchedPerms) > 0)
                            <div class="border rounded">
                                <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom bg-light bg-opacity-50">
                                    <i data-lucide="{{ $cat['icon'] }}" class="icon-sm text-primary"></i>
                                    <h6 class="fw-semibold mb-0">{{ $cat['label'] }}</h6>
                                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill ms-auto">{{ count($matchedPerms) }}</span>
                                </div>
                                <div class="p-3">
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($matchedPerms as $permName)
                                            <span class="badge bg-primary bg-opacity-10 text-primary fw-medium px-2 py-1">
                                                {{ $permName }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endif
                        @endforeach

                        @php
                            $allKnown = collect($permCategories)->pluck('prefixes')->flatten()->toArray();
                            $uncategorized = array_diff($rolePermNames, $allKnown);
                        @endphp
                        @if(count($uncategorized) > 0)
                        <div class="border rounded">
                            <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom bg-light bg-opacity-50">
                                <i data-lucide="puzzle" class="icon-sm text-muted"></i>
                                <h6 class="fw-semibold mb-0">{{ __('Autres') }}</h6>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill ms-auto">{{ count($uncategorized) }}</span>
                            </div>
                            <div class="p-3">
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($uncategorized as $permName)
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary fw-medium px-2 py-1">
                                            {{ $permName }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                @else
                    <div class="text-center py-4">
                        <i data-lucide="shield-off" class="icon-xl text-muted mb-3" style="width:48px;height:48px;"></i>
                        <p class="text-muted fst-italic mb-0">{{ __('Aucune permission assignée à ce rôle.') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>

@endsection
