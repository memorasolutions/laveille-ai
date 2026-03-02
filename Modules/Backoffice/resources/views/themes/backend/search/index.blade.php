<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Recherche', 'subtitle' => 'Administration'])

@section('breadcrumbs')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item active" aria-current="page">Recherche</li>
    </ol>
</nav>
@endsection

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Recherche') }}</li>
    </ol>
</nav>

{{-- Search Form --}}
<div class="card mb-4">
    <div class="card-header py-3 px-4">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="search" class="icon-md text-primary"></i>
            <h4 class="fw-bold mb-0">{{ __('Recherche globale') }}</h4>
        </div>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('admin.search') }}" method="GET">
            <div class="d-flex flex-wrap gap-4 align-items-end">
                <div class="flex-grow-1" style="min-width:200px">
                    <label class="form-label fw-medium mb-2">{{ __('Rechercher') }}</label>
                    <div class="position-relative">
                        <input type="text"
                            name="q"
                            value="{{ $q }}"
                            class="form-control"
                            placeholder="{{ __('Utilisateurs, articles, paramètres...') }}"
                            autofocus>
                        <i data-lucide="search" class="icon-sm text-muted position-absolute top-50 translate-middle-y" style="right:0.75rem;pointer-events:none;"></i>
                    </div>
                </div>
                <div style="width:12rem">
                    <label class="form-label fw-medium mb-2">{{ __('Type') }}</label>
                    <select name="type" class="form-select">
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
                <div>
                    <button type="submit" class="btn btn-primary px-4">
                        {{ __('Rechercher') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@if(!empty($q))

{{-- Results Summary --}}
<div class="d-flex align-items-center gap-3 mb-4">
    <h5 class="fw-semibold mb-0">
        {{ __('Résultats pour') }} : <span class="text-primary">{{ $q }}</span>
    </h5>
    <span class="badge bg-primary bg-opacity-10 text-primary">
        {{ $totalCount }} {{ __('résultats') }}
    </span>
</div>

{{-- Users --}}
@if(($type ?? 'all') === 'all' || $type === 'users')
<div class="card mb-4">
    <div class="card-header py-3 px-4">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="users" class="icon-md text-primary"></i>
            <h4 class="fw-bold mb-0">{{ __('Utilisateurs') }} ({{ $users->count() }})</h4>
        </div>
    </div>
    @if($users->isEmpty())
        <div class="card-body p-4">
            <p class="text-muted small fst-italic mb-0">{{ __('Aucun résultat') }}</p>
        </div>
    @else
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small">{{ __('Nom') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small">{{ __('Courriel') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small">{{ __('Rôle') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small text-center">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr>
                            <td class="py-3 px-4 fw-semibold">{{ $user->name }}</td>
                            <td class="py-3 px-4 text-muted small">{{ $user->email }}</td>
                            <td class="py-3 px-4">
                                <span class="badge bg-primary bg-opacity-10 text-primary">
                                    {{ $user->roles->first()?->name ?? '-' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <a href="{{ route('admin.users.edit', $user) }}"
                                   class="btn btn-sm btn-outline-success d-inline-flex align-items-center justify-content-center rounded-circle"
                                   style="width:32px;height:32px;"
                                   title="{{ __('Modifier') }}">
                                    <i data-lucide="pencil" class="icon-sm"></i>
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
<div class="card mb-4">
    <div class="card-header py-3 px-4">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="shield" class="icon-md text-warning"></i>
            <h4 class="fw-bold mb-0">{{ __('Rôles') }} ({{ $roles->count() }})</h4>
        </div>
    </div>
    @if($roles->isEmpty())
        <div class="card-body p-4">
            <p class="text-muted small fst-italic mb-0">{{ __('Aucun résultat') }}</p>
        </div>
    @else
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small">{{ __('Nom') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small">{{ __('Guard') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small text-center">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $role)
                        <tr>
                            <td class="py-3 px-4 fw-semibold">{{ $role->name }}</td>
                            <td class="py-3 px-4 text-muted small">{{ $role->guard_name }}</td>
                            <td class="py-3 px-4 text-center">
                                <a href="{{ route('admin.roles.edit', $role) }}"
                                   class="btn btn-sm btn-outline-success d-inline-flex align-items-center justify-content-center rounded-circle"
                                   style="width:32px;height:32px;"
                                   title="{{ __('Modifier') }}">
                                    <i data-lucide="pencil" class="icon-sm"></i>
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
<div class="card mb-4">
    <div class="card-header py-3 px-4">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="file-text" class="icon-md text-success"></i>
            <h4 class="fw-bold mb-0">{{ __('Articles') }} ({{ $articles->count() }})</h4>
        </div>
    </div>
    @if($articles->isEmpty())
        <div class="card-body p-4">
            <p class="text-muted small fst-italic mb-0">{{ __('Aucun résultat') }}</p>
        </div>
    @else
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small">{{ __('Titre') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small">{{ __('Statut') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small">{{ __('Date') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small text-center">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($articles as $article)
                        <tr>
                            <td class="py-3 px-4 fw-semibold">{{ $article->title }}</td>
                            <td class="py-3 px-4">
                                @if($article->status === 'published')
                                    <span class="badge bg-success bg-opacity-10 text-success">{{ __('Publié') }}</span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning">{{ __('Brouillon') }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-muted small">{{ $article->created_at?->format('d/m/Y') }}</td>
                            <td class="py-3 px-4 text-center">
                                <a href="{{ route('admin.blog.articles.edit', $article) }}"
                                   class="btn btn-sm btn-outline-success d-inline-flex align-items-center justify-content-center rounded-circle"
                                   style="width:32px;height:32px;"
                                   title="{{ __('Modifier') }}">
                                    <i data-lucide="pencil" class="icon-sm"></i>
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
<div class="card mb-4">
    <div class="card-header py-3 px-4">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="settings" class="icon-md text-info"></i>
            <h4 class="fw-bold mb-0">{{ __('Paramètres') }} ({{ $settings->count() }})</h4>
        </div>
    </div>
    @if($settings->isEmpty())
        <div class="card-body p-4">
            <p class="text-muted small fst-italic mb-0">{{ __('Aucun résultat') }}</p>
        </div>
    @else
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small">{{ __('Clé') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small">{{ __('Valeur') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small">{{ __('Groupe') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small text-center">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($settings as $setting)
                        <tr>
                            <td class="py-3 px-4"><code class="badge bg-primary bg-opacity-10 text-primary font-monospace">{{ $setting->key }}</code></td>
                            <td class="py-3 px-4 text-muted small">{{ Str::limit((string) $setting->value, 50) }}</td>
                            <td class="py-3 px-4">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $setting->group ?? 'general' }}</span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <a href="{{ route('admin.settings.edit', $setting) }}"
                                   class="btn btn-sm btn-outline-success d-inline-flex align-items-center justify-content-center rounded-circle"
                                   style="width:32px;height:32px;"
                                   title="{{ __('Modifier') }}">
                                    <i data-lucide="pencil" class="icon-sm"></i>
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
<div class="card mb-4">
    <div class="card-header py-3 px-4">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="layout-grid" class="icon-md" style="color:#8B5CF6;"></i>
            <h4 class="fw-bold mb-0">{{ __('Pages') }} ({{ $pages->count() }})</h4>
        </div>
    </div>
    @if($pages->isEmpty())
        <div class="card-body p-4">
            <p class="text-muted small fst-italic mb-0">{{ __('Aucun résultat') }}</p>
        </div>
    @else
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small">{{ __('Titre') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small">{{ __('Statut') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small text-center">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pages as $page)
                        <tr>
                            <td class="py-3 px-4 fw-semibold">{{ $page->title }}</td>
                            <td class="py-3 px-4">
                                @if($page->status === 'published')
                                    <span class="badge bg-success bg-opacity-10 text-success">{{ __('Publié') }}</span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning">{{ __('Brouillon') }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                <a href="{{ route('admin.pages.edit', $page) }}"
                                   class="btn btn-sm btn-outline-success d-inline-flex align-items-center justify-content-center rounded-circle"
                                   style="width:32px;height:32px;"
                                   title="{{ __('Modifier') }}">
                                    <i data-lucide="pencil" class="icon-sm"></i>
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
<div class="card mb-4">
    <div class="card-header py-3 px-4">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="tag" class="icon-md" style="color:#EC4899;"></i>
            <h4 class="fw-bold mb-0">{{ __('Catégories') }} ({{ $categories->count() }})</h4>
        </div>
    </div>
    @if($categories->isEmpty())
        <div class="card-body p-4">
            <p class="text-muted small fst-italic mb-0">{{ __('Aucun résultat') }}</p>
        </div>
    @else
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small">{{ __('Nom') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small">{{ __('Description') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small text-center">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $category)
                        <tr>
                            <td class="py-3 px-4 fw-semibold">{{ $category->name }}</td>
                            <td class="py-3 px-4 text-muted small">{{ Str::limit($category->description ?? '', 50) }}</td>
                            <td class="py-3 px-4 text-center">
                                <a href="{{ route('admin.blog.categories.edit', $category) }}"
                                   class="btn btn-sm btn-outline-success d-inline-flex align-items-center justify-content-center rounded-circle"
                                   style="width:32px;height:32px;"
                                   title="{{ __('Modifier') }}">
                                    <i data-lucide="pencil" class="icon-sm"></i>
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
<div class="card">
    <div class="card-header py-3 px-4">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="star" class="icon-md text-danger"></i>
            <h4 class="fw-bold mb-0">{{ __('Plans') }} ({{ $plans->count() }})</h4>
        </div>
    </div>
    @if($plans->isEmpty())
        <div class="card-body p-4">
            <p class="text-muted small fst-italic mb-0">{{ __('Aucun résultat') }}</p>
        </div>
    @else
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small">{{ __('Nom') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small">{{ __('Prix') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small">{{ __('Statut') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body text-uppercase small text-center">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plans as $plan)
                        <tr>
                            <td class="py-3 px-4 fw-semibold">{{ $plan->name }}</td>
                            <td class="py-3 px-4">{{ number_format($plan->price, 2) }} $</td>
                            <td class="py-3 px-4">
                                @if($plan->is_active)
                                    <span class="badge bg-success bg-opacity-10 text-success">{{ __('Actif') }}</span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ __('Inactif') }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                <a href="{{ route('admin.plans.edit', $plan) }}"
                                   class="btn btn-sm btn-outline-success d-inline-flex align-items-center justify-content-center rounded-circle"
                                   style="width:32px;height:32px;"
                                   title="{{ __('Modifier') }}">
                                    <i data-lucide="pencil" class="icon-sm"></i>
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
