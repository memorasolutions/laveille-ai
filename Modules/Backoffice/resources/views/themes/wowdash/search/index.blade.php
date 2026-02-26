@extends('backoffice::layouts.admin', ['title' => 'Recherche', 'subtitle' => 'Administration'])

@section('content')

{{-- Search Form --}}
<div class="card h-100 p-0 radius-12 mb-24">
    <div class="card-header border-bottom bg-base py-16 px-24">
        <h6 class="mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:magnifer-outline" class="icon text-xl text-primary-600"></iconify-icon>
            {{ __('Recherche globale') }}
        </h6>
    </div>
    <div class="card-body p-24">
        <form action="{{ route('admin.search') }}" method="GET">
            <div class="row gy-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">{{ __('Rechercher') }}</label>
                    <div class="position-relative">
                        <input type="text" name="q" value="{{ $q }}" class="form-control bg-base h-40-px w-100 radius-8 pe-40-px" placeholder="{{ __('Utilisateurs, articles, paramètres...') }}" autofocus>
                        <iconify-icon icon="ion:search-outline" class="icon position-absolute" style="top: 50%; right: 12px; transform: translateY(-50%); pointer-events: none; color: var(--text-secondary-light);"></iconify-icon>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Type') }}</label>
                    <select name="type" class="form-select radius-8 h-40-px">
                        <option value="all" {{ ($type ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('Tout') }}</option>
                        <option value="users" {{ ($type ?? '') === 'users' ? 'selected' : '' }}>{{ __('Utilisateurs') }}</option>
                        <option value="roles" {{ ($type ?? '') === 'roles' ? 'selected' : '' }}>{{ __('Rôles') }}</option>
                        <option value="articles" {{ ($type ?? '') === 'articles' ? 'selected' : '' }}>{{ __('Articles') }}</option>
                        <option value="settings" {{ ($type ?? '') === 'settings' ? 'selected' : '' }}>{{ __('Paramètres') }}</option>
                        <option value="pages" {{ ($type ?? '') === 'pages' ? 'selected' : '' }}>{{ __('Pages') }}</option>
                        <option value="plans" {{ ($type ?? '') === 'plans' ? 'selected' : '' }}>{{ __('Plans') }}</option>
                        <option value="categories" {{ ($type ?? '') === 'categories' ? 'selected' : '' }}>{{ __('Catégories') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary-600 radius-8 w-100 h-40-px" type="submit">{{ __('Rechercher') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if(!empty($q))
{{-- Results Summary --}}
<div class="d-flex align-items-center gap-2 mb-20">
    <h6 class="mb-0">{{ __('Résultats pour') }} : <strong class="text-primary-600">{{ $q }}</strong></h6>
    <span class="badge bg-primary-100 text-primary-600 px-12 py-6 radius-8">{{ $totalCount }} {{ __('résultats') }}</span>
</div>

{{-- Users --}}
@if(($type ?? 'all') === 'all' || $type === 'users')
<div class="card h-100 p-0 radius-12 mb-20">
    <div class="card-header border-bottom bg-base py-16 px-24">
        <h6 class="mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:users-group-two-rounded-outline" class="icon text-xl text-primary-600"></iconify-icon>
            {{ __('Utilisateurs') }} ({{ $users->count() }})
        </h6>
    </div>
    @if($users->isEmpty())
        <div class="card-body"><p class="text-secondary-light mb-0">{{ __('Aucun résultat') }}</p></div>
    @else
        <div class="card-body p-0">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <thead><tr><th>{{ __('Nom') }}</th><th>{{ __('Courriel') }}</th><th>{{ __('Rôle') }}</th><th class="text-center">{{ __('Actions') }}</th></tr></thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr>
                            <td class="fw-semibold">{{ $user->name }}</td>
                            <td class="text-sm text-secondary-light">{{ $user->email }}</td>
                            <td><span class="badge bg-primary-100 text-primary-600 px-8 py-4 radius-4">{{ $user->roles->first()?->name ?? '-' }}</span></td>
                            <td class="text-center">
                                <a href="{{ route('admin.users.edit', $user) }}" class="bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" title="{{ __('Modifier') }}">
                                    <iconify-icon icon="lucide:edit" class="icon text-xl"></iconify-icon>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endif

{{-- Roles --}}
@if(($type ?? 'all') === 'all' || $type === 'roles')
<div class="card h-100 p-0 radius-12 mb-20">
    <div class="card-header border-bottom bg-base py-16 px-24">
        <h6 class="mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:shield-keyhole-outline" class="icon text-xl text-warning-main"></iconify-icon>
            {{ __('Rôles') }} ({{ $roles->count() }})
        </h6>
    </div>
    @if($roles->isEmpty())
        <div class="card-body"><p class="text-secondary-light mb-0">{{ __('Aucun résultat') }}</p></div>
    @else
        <div class="card-body p-0">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <thead><tr><th>{{ __('Nom') }}</th><th>{{ __('Guard') }}</th><th class="text-center">{{ __('Actions') }}</th></tr></thead>
                    <tbody>
                        @foreach($roles as $role)
                        <tr>
                            <td class="fw-semibold">{{ $role->name }}</td>
                            <td class="text-sm text-secondary-light">{{ $role->guard_name }}</td>
                            <td class="text-center">
                                <a href="{{ route('admin.roles.edit', $role) }}" class="bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" title="{{ __('Modifier') }}">
                                    <iconify-icon icon="lucide:edit" class="icon text-xl"></iconify-icon>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endif

{{-- Articles --}}
@if(($type ?? 'all') === 'all' || $type === 'articles')
<div class="card h-100 p-0 radius-12 mb-20">
    <div class="card-header border-bottom bg-base py-16 px-24">
        <h6 class="mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:document-text-outline" class="icon text-xl text-success-600"></iconify-icon>
            {{ __('Articles') }} ({{ $articles->count() }})
        </h6>
    </div>
    @if($articles->isEmpty())
        <div class="card-body"><p class="text-secondary-light mb-0">{{ __('Aucun résultat') }}</p></div>
    @else
        <div class="card-body p-0">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <thead><tr><th>{{ __('Titre') }}</th><th>{{ __('Statut') }}</th><th>{{ __('Date') }}</th><th class="text-center">{{ __('Actions') }}</th></tr></thead>
                    <tbody>
                        @foreach($articles as $article)
                        <tr>
                            <td class="fw-semibold">{{ $article->title }}</td>
                            <td>
                                <span class="badge bg-{{ $article->status === 'published' ? 'success' : 'warning' }}-100 text-{{ $article->status === 'published' ? 'success' : 'warning' }}-600 px-8 py-4 radius-4">
                                    {{ $article->status === 'published' ? __('Publié') : __('Brouillon') }}
                                </span>
                            </td>
                            <td class="text-sm text-secondary-light">{{ $article->created_at?->format('d/m/Y') }}</td>
                            <td class="text-center">
                                <a href="{{ route('admin.blog.articles.edit', $article) }}" class="bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" title="{{ __('Modifier') }}">
                                    <iconify-icon icon="lucide:edit" class="icon text-xl"></iconify-icon>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endif

{{-- Settings --}}
@if(($type ?? 'all') === 'all' || $type === 'settings')
<div class="card h-100 p-0 radius-12 mb-20">
    <div class="card-header border-bottom bg-base py-16 px-24">
        <h6 class="mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:settings-outline" class="icon text-xl text-info-main"></iconify-icon>
            {{ __('Paramètres') }} ({{ $settings->count() }})
        </h6>
    </div>
    @if($settings->isEmpty())
        <div class="card-body"><p class="text-secondary-light mb-0">{{ __('Aucun résultat') }}</p></div>
    @else
        <div class="card-body p-0">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <thead><tr><th>{{ __('Clé') }}</th><th>{{ __('Valeur') }}</th><th>{{ __('Groupe') }}</th><th class="text-center">{{ __('Actions') }}</th></tr></thead>
                    <tbody>
                        @foreach($settings as $setting)
                        <tr>
                            <td><code class="text-primary-600 text-sm">{{ $setting->key }}</code></td>
                            <td class="text-sm text-secondary-light">{{ Str::limit((string) $setting->value, 50) }}</td>
                            <td><span class="badge bg-neutral-200 text-neutral-600 px-8 py-4 radius-4">{{ $setting->group ?? 'general' }}</span></td>
                            <td class="text-center">
                                <a href="{{ route('admin.settings.edit', $setting) }}" class="bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" title="{{ __('Modifier') }}">
                                    <iconify-icon icon="lucide:edit" class="icon text-xl"></iconify-icon>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endif

{{-- Pages --}}
@if(($type ?? 'all') === 'all' || $type === 'pages')
<div class="card h-100 p-0 radius-12 mb-20">
    <div class="card-header border-bottom bg-base py-16 px-24">
        <h6 class="mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:widget-2-outline" class="icon text-xl" style="color: #8B5CF6"></iconify-icon>
            {{ __('Pages') }} ({{ $pages->count() }})
        </h6>
    </div>
    @if($pages->isEmpty())
        <div class="card-body"><p class="text-secondary-light mb-0">{{ __('Aucun résultat') }}</p></div>
    @else
        <div class="card-body p-0">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <thead><tr><th>{{ __('Titre') }}</th><th>{{ __('Statut') }}</th><th class="text-center">{{ __('Actions') }}</th></tr></thead>
                    <tbody>
                        @foreach($pages as $page)
                        <tr>
                            <td class="fw-semibold">{{ $page->title }}</td>
                            <td>
                                <span class="badge bg-{{ $page->status === 'published' ? 'success' : 'warning' }}-100 text-{{ $page->status === 'published' ? 'success' : 'warning' }}-600 px-8 py-4 radius-4">
                                    {{ $page->status === 'published' ? __('Publié') : __('Brouillon') }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.pages.edit', $page) }}" class="bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" title="{{ __('Modifier') }}">
                                    <iconify-icon icon="lucide:edit" class="icon text-xl"></iconify-icon>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endif

{{-- Categories --}}
@if(($type ?? 'all') === 'all' || $type === 'categories')
<div class="card h-100 p-0 radius-12 mb-20">
    <div class="card-header border-bottom bg-base py-16 px-24">
        <h6 class="mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:tag-outline" class="icon text-xl" style="color: #EC4899"></iconify-icon>
            {{ __('Catégories') }} ({{ $categories->count() }})
        </h6>
    </div>
    @if($categories->isEmpty())
        <div class="card-body"><p class="text-secondary-light mb-0">{{ __('Aucun résultat') }}</p></div>
    @else
        <div class="card-body p-0">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <thead><tr><th>{{ __('Nom') }}</th><th>{{ __('Description') }}</th><th class="text-center">{{ __('Actions') }}</th></tr></thead>
                    <tbody>
                        @foreach($categories as $category)
                        <tr>
                            <td class="fw-semibold">{{ $category->name }}</td>
                            <td class="text-sm text-secondary-light">{{ Str::limit($category->description ?? '', 50) }}</td>
                            <td class="text-center">
                                <a href="{{ route('admin.blog.categories.edit', $category) }}" class="bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" title="{{ __('Modifier') }}">
                                    <iconify-icon icon="lucide:edit" class="icon text-xl"></iconify-icon>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endif

{{-- Plans --}}
@if(($type ?? 'all') === 'all' || $type === 'plans')
<div class="card h-100 p-0 radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24">
        <h6 class="mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:star-outline" class="icon text-xl text-danger-600"></iconify-icon>
            {{ __('Plans') }} ({{ $plans->count() }})
        </h6>
    </div>
    @if($plans->isEmpty())
        <div class="card-body"><p class="text-secondary-light mb-0">{{ __('Aucun résultat') }}</p></div>
    @else
        <div class="card-body p-0">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <thead><tr><th>{{ __('Nom') }}</th><th>{{ __('Prix') }}</th><th>{{ __('Statut') }}</th><th class="text-center">{{ __('Actions') }}</th></tr></thead>
                    <tbody>
                        @foreach($plans as $plan)
                        <tr>
                            <td class="fw-semibold">{{ $plan->name }}</td>
                            <td class="text-sm">{{ number_format($plan->price, 2) }} $</td>
                            <td>
                                <span class="badge bg-{{ $plan->is_active ? 'success' : 'neutral' }}-100 text-{{ $plan->is_active ? 'success' : 'neutral' }}-600 px-8 py-4 radius-4">
                                    {{ $plan->is_active ? __('Actif') : __('Inactif') }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.plans.edit', $plan) }}" class="bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" title="{{ __('Modifier') }}">
                                    <iconify-icon icon="lucide:edit" class="icon text-xl"></iconify-icon>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endif

@endif

@endsection
