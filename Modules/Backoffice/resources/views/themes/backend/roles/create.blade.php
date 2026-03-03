<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Rôles', 'subtitle' => 'Ajouter'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">{{ __('Rôles') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Ajouter') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="shield-plus" class="icon-md text-primary"></i>{{ __('Ajouter un rôle') }}</h4>
    <a href="{{ route('admin.roles.index') }}" class="btn btn-sm btn-light d-inline-flex align-items-center gap-2">
        <i data-lucide="arrow-left"></i> {{ __('Retour') }}
    </a>
</div>

<form action="{{ route('admin.roles.store') }}" method="POST" x-data="permissionsManager()">
    @csrf

    {{-- Nom du rôle --}}
    <div class="card mb-4">
        <div class="card-header border-bottom py-3 px-4 d-flex align-items-center gap-2">
            <i data-lucide="tag" class="icon-md text-primary"></i>
            <h4 class="fw-bold mb-0">{{ __('Informations du rôle') }}</h4>
        </div>
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label fw-medium" for="name">
                        {{ __('Nom du rôle') }} <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                           class="form-control @error('name') is-invalid @enderror"
                           placeholder="{{ __('ex: editor, moderator...') }}">
                    @error('name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="requires_password" value="1" id="requires_password" checked>
                        <label class="form-check-label" for="requires_password">{{ __('Requiert un mot de passe') }}</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Permissions avec onglets --}}
    <div class="card mb-4">
        <div class="card-header border-bottom py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-2">
                <i data-lucide="key" class="icon-md text-primary"></i>
                <h4 class="fw-bold mb-0">{{ __('Permissions') }}</h4>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2" x-text="checkedCount + '/' + totalCount + ' sélectionnées'"></span>
                <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" @click="selectAll()">
                    <i data-lucide="check-square" class="icon-sm"></i> {{ __('Tout sélectionner') }}
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" @click="deselectAll()">
                    <i data-lucide="square" class="icon-sm"></i> {{ __('Tout désélectionner') }}
                </button>
            </div>
        </div>
        @error('permissions')
            <div class="alert alert-danger m-3 d-flex align-items-center gap-2">
                <i data-lucide="alert-circle" class="icon-sm flex-shrink-0"></i>
                {{ $message }}
            </div>
        @enderror

        {{-- Onglets --}}
        <div class="card-body p-0">
            <ul class="nav nav-tabs px-4 pt-3" role="tablist">
                @foreach($categories as $catKey => $category)
                    @if(collect($category['permissions'])->pluck('model')->filter()->isNotEmpty())
                    <li class="nav-item" role="presentation">
                        <button class="nav-link d-flex align-items-center gap-2 {{ $loop->first ? 'active' : '' }}"
                                id="tab-{{ $catKey }}" data-bs-toggle="tab" data-bs-target="#pane-{{ $catKey }}"
                                type="button" role="tab" aria-controls="pane-{{ $catKey }}"
                                aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                            <i data-lucide="{{ $category['icon'] }}" class="icon-sm"></i>
                            <span>{{ $category['label'] }}</span>
                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill ms-1"
                                  x-text="categoryCount('{{ $catKey }}')"></span>
                        </button>
                    </li>
                    @endif
                @endforeach
            </ul>

            <div class="tab-content p-4">
                @foreach($categories as $catKey => $category)
                    @if(collect($category['permissions'])->pluck('model')->filter()->isNotEmpty())
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                         id="pane-{{ $catKey }}" role="tabpanel" aria-labelledby="tab-{{ $catKey }}">

                        {{-- Select all pour cet onglet --}}
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAll-{{ $catKey }}"
                                       @change="toggleCategory('{{ $catKey }}', $el.checked)"
                                       :checked="isCategoryFullyChecked('{{ $catKey }}')"
                                       :indeterminate="isCategoryPartiallyChecked('{{ $catKey }}')">
                                <label class="form-check-label fw-medium" for="selectAll-{{ $catKey }}">
                                    {{ __('Tout sélectionner dans') }} « {{ $category['label'] }} »
                                </label>
                            </div>
                        </div>

                        {{-- Grille de permissions --}}
                        <div class="row g-3">
                            @foreach($category['permissions'] as $permName => $permMeta)
                                @if($permMeta['model'])
                                <div class="col-12 col-md-6">
                                    <label class="d-block p-3 rounded border permission-card h-100"
                                           for="perm_{{ $permMeta['model']->id }}"
                                           :class="checked['{{ $permName }}'] ? 'border-primary bg-primary bg-opacity-5' : 'border-light-subtle'"
                                           style="cursor:pointer;transition:all .15s ease;">
                                        <div class="d-flex align-items-start gap-3">
                                            <input class="form-check-input mt-1 flex-shrink-0" type="checkbox"
                                                   name="permissions[]" value="{{ $permMeta['model']->id }}"
                                                   id="perm_{{ $permMeta['model']->id }}"
                                                   data-category="{{ $catKey }}" data-perm="{{ $permName }}"
                                                   x-model="checked['{{ $permName }}']"
                                                   @change="updateCounts()">
                                            <div>
                                                <div class="fw-semibold text-body">{{ $permMeta['label'] }}</div>
                                                <div class="text-muted small mt-1">{{ $permMeta['desc'] }}</div>
                                                <code class="small mt-1 d-inline-block text-primary bg-primary bg-opacity-10 px-2 py-1 rounded">{{ $permName }}</code>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="d-flex align-items-center gap-3">
        <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
            <i data-lucide="save"></i> {{ __('Enregistrer') }}
        </button>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-light d-inline-flex align-items-center gap-2">
            <i data-lucide="x"></i> {{ __('Annuler') }}
        </a>
    </div>
</form>

@push('plugin-scripts')
<script>
function permissionsManager() {
    const oldPerms = @json(old('permissions', [])).map(Number);
    const permMap = @json($permMap);

    const checked = {};
    const categoryPerms = {};

    Object.entries(permMap).forEach(([name, meta]) => {
        checked[name] = oldPerms.includes(meta.id);
        if (!categoryPerms[meta.category]) categoryPerms[meta.category] = [];
        categoryPerms[meta.category].push(name);
    });

    return {
        checked,
        categoryPerms,
        totalCount: Object.keys(checked).length,
        checkedCount: Object.values(checked).filter(Boolean).length,

        updateCounts() {
            this.checkedCount = Object.values(this.checked).filter(Boolean).length;
        },

        selectAll() {
            Object.keys(this.checked).forEach(k => this.checked[k] = true);
            this.updateCounts();
        },

        deselectAll() {
            Object.keys(this.checked).forEach(k => this.checked[k] = false);
            this.updateCounts();
        },

        toggleCategory(cat, state) {
            (this.categoryPerms[cat] || []).forEach(k => this.checked[k] = state);
            this.updateCounts();
        },

        isCategoryFullyChecked(cat) {
            return (this.categoryPerms[cat] || []).every(k => this.checked[k]);
        },

        isCategoryPartiallyChecked(cat) {
            const perms = this.categoryPerms[cat] || [];
            const count = perms.filter(k => this.checked[k]).length;
            return count > 0 && count < perms.length;
        },

        categoryCount(cat) {
            const perms = this.categoryPerms[cat] || [];
            return perms.filter(k => this.checked[k]).length + '/' + perms.length;
        }
    };
}
</script>
@endpush

@push('plugin-styles')
<style>
.permission-card:hover { border-color: var(--bs-primary) !important; background-color: rgba(var(--bs-primary-rgb), 0.03) !important; }
.permission-card:focus-within { outline: 2px solid var(--bs-primary); outline-offset: 2px; }
</style>
@endpush

@endsection
