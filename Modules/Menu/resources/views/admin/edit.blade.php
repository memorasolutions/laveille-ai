<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', 'Modifier : ' . $menu->name)
@section('content')
<div class="page-content">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">Modifier : {{ $menu->name }}</h4>
        <a href="{{ route('admin.menus.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i> Retour
        </a>
    </div>

    <div class="row">
        {{-- LEFT: Add items + Settings --}}
        <div class="col-lg-4">
            {{-- Add item panel --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0">Ajouter un élément</h5></div>
                <div class="card-body">
                    <ul class="nav nav-tabs nav-tabs-sm" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-custom">Lien</button></li>
                        @if(!empty($linkableOptions['pages']))
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-pages">Pages</button></li>
                        @endif
                        @if(!empty($linkableOptions['categories']))
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-categories">Catégories</button></li>
                        @endif
                    </ul>
                    <div class="tab-content pt-3">
                        {{-- Custom link --}}
                        <div class="tab-pane fade show active" id="tab-custom">
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm" id="addTitle" placeholder="Titre">
                            </div>
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm" id="addUrl" placeholder="https://..." value="#">
                            </div>
                            <button type="button" class="btn btn-sm btn-primary w-100" onclick="addCustomItem()">
                                <i data-lucide="plus"></i> Ajouter
                            </button>
                        </div>
                        {{-- Pages --}}
                        @if(!empty($linkableOptions['pages']))
                        <div class="tab-pane fade" id="tab-pages">
                            <div class="list-group list-group-flush mb-2" style="max-height:200px;overflow-y:auto">
                                @foreach($linkableOptions['pages'] as $page)
                                <label class="list-group-item list-group-item-action py-1 px-2">
                                    <input type="checkbox" class="form-check-input me-1 page-cb" value="{{ $page->id }}" data-title="{{ $page->title }}" data-slug="/pages/{{ $page->slug }}"> {{ $page->title }}
                                </label>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-sm btn-primary w-100" onclick="addPageItems()">
                                <i data-lucide="plus"></i> Ajouter la sélection
                            </button>
                        </div>
                        @endif
                        {{-- Categories --}}
                        @if(!empty($linkableOptions['categories']))
                        <div class="tab-pane fade" id="tab-categories">
                            <div class="list-group list-group-flush mb-2" style="max-height:200px;overflow-y:auto">
                                @foreach($linkableOptions['categories'] as $cat)
                                <label class="list-group-item list-group-item-action py-1 px-2">
                                    <input type="checkbox" class="form-check-input me-1 cat-cb" value="{{ $cat->id }}" data-name="{{ $cat->name }}" data-slug="/blog/category/{{ $cat->slug }}"> {{ $cat->name }}
                                </label>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-sm btn-primary w-100" onclick="addCategoryItems()">
                                <i data-lucide="plus"></i> Ajouter la sélection
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Menu settings --}}
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Paramètres</h5></div>
                <div class="card-body">
                    <form action="{{ route('admin.menus.update', $menu) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-2">
                            <label class="form-label">Nom</label>
                            <input type="text" class="form-control form-control-sm @error('name') is-invalid @enderror" name="name" value="{{ old('name', $menu->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Emplacement</label>
                            <select class="form-select form-select-sm" name="location">
                                <option value="">— Aucun —</option>
                                @foreach($locations as $key => $label)
                                <option value="{{ $key }}" {{ $menu->location == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $menu->is_active ? 'checked' : '' }}>
                                <label class="form-check-label">Actif</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i data-lucide="save"></i> Enregistrer
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- RIGHT: Menu structure --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Structure du menu</h5>
                    <button type="button" class="btn btn-sm btn-success" id="btnSaveItems" onclick="saveItems()">
                        <i data-lucide="save"></i> Enregistrer l'ordre
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Glissez-déposez pour réorganiser. Indentez pour créer des sous-menus.</p>
                    <div id="menu-items" class="menu-sortable">
                        @foreach($menu->allItems->whereNull('parent_id')->sortBy('order') as $item)
                            @include('menu::admin._item', ['item' => $item, 'allItems' => $menu->allItems])
                        @endforeach
                    </div>
                    @if($menu->allItems->isEmpty())
                    <div class="text-center py-4 text-muted" id="emptyState">
                        <i data-lucide="list" class="icon-lg mb-2"></i>
                        <p>Ajoutez des éléments depuis le panneau de gauche.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('plugin-styles')
<style>
.menu-sortable { min-height: 50px; }
.menu-item { background: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: 0.375rem; margin-bottom: 0.5rem; }
.menu-item .item-header { padding: 0.5rem 0.75rem; cursor: grab; display: flex; align-items: center; gap: 0.5rem; }
.menu-item .item-header:active { cursor: grabbing; }
.menu-item .item-body { padding: 0.5rem 0.75rem; border-top: 1px solid var(--bs-border-color); display: none; }
.menu-item .item-body.show { display: block; }
.menu-item .children { padding-left: 1.5rem; margin-top: 0.5rem; min-height: 5px; }
.sortable-ghost { opacity: 0.4; }
.sortable-drag { opacity: 0.9; box-shadow: 0 4px 12px rgba(0,0,0,.15); }
</style>
@endpush

@push('custom-scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initSortable(document.getElementById('menu-items'));
    lucide.createIcons();
});

function initSortable(el) {
    if (!el) return;
    new Sortable(el, {
        group: 'menu',
        animation: 150,
        fallbackOnBody: true,
        swapThreshold: 0.65,
        handle: '.item-header',
        onEnd: function() {
            document.querySelectorAll('.menu-item .children').forEach(c => initSortable(c));
        }
    });
    el.querySelectorAll(':scope > .menu-item > .children').forEach(c => initSortable(c));
}

function toggleItemBody(btn) {
    const body = btn.closest('.menu-item').querySelector('.item-body');
    body.classList.toggle('show');
    btn.querySelector('[data-lucide]').setAttribute('data-lucide', body.classList.contains('show') ? 'chevron-up' : 'chevron-down');
    lucide.createIcons();
}

function removeItem(btn) {
    if (confirm('Supprimer cet élément ?')) {
        btn.closest('.menu-item').remove();
        checkEmpty();
    }
}

function checkEmpty() {
    const container = document.getElementById('menu-items');
    const empty = document.getElementById('emptyState');
    if (empty) empty.style.display = container.children.length ? 'none' : 'block';
}

function createItemHtml(data) {
    const id = data.id || '';
    const tpl = `
    <div class="menu-item" data-id="${id}" data-type="${data.type}" data-linkable-type="${data.linkable_type||''}" data-linkable-id="${data.linkable_id||''}">
        <div class="item-header">
            <i data-lucide="grip-vertical" class="text-muted"></i>
            <span class="item-title flex-grow-1 fw-medium">${data.title}</span>
            <span class="badge bg-light text-dark">${data.type}</span>
            <button type="button" class="btn btn-sm p-0" onclick="toggleItemBody(this)"><i data-lucide="chevron-down"></i></button>
        </div>
        <div class="item-body">
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label small">Titre</label>
                    <input type="text" class="form-control form-control-sm field-title" value="${data.title}" onchange="this.closest('.menu-item').querySelector('.item-title').textContent=this.value">
                </div>
                <div class="col-6">
                    <label class="form-label small">URL</label>
                    <input type="text" class="form-control form-control-sm field-url" value="${data.url||''}" ${data.type!=='custom'?'readonly':''}>
                </div>
                <div class="col-4">
                    <label class="form-label small">Cible</label>
                    <select class="form-select form-select-sm field-target">
                        <option value="_self" ${data.target==='_self'?'selected':''}>Même fenêtre</option>
                        <option value="_blank" ${data.target==='_blank'?'selected':''}>Nouvel onglet</option>
                    </select>
                </div>
                <div class="col-4">
                    <label class="form-label small">Icône</label>
                    <input type="text" class="form-control form-control-sm field-icon" value="${data.icon||''}" placeholder="lucide icon name">
                </div>
                <div class="col-4">
                    <label class="form-label small">Classes CSS</label>
                    <input type="text" class="form-control form-control-sm field-css" value="${data.css_classes||''}">
                </div>
                <div class="col-12 d-flex justify-content-between align-items-center mt-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input field-enabled" type="checkbox" ${data.enabled!==false?'checked':''}>
                        <label class="form-check-label small">Activé</label>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(this)">
                        <i data-lucide="trash-2"></i> Supprimer
                    </button>
                </div>
            </div>
        </div>
        <div class="children menu-sortable"></div>
    </div>`;
    return tpl;
}

function appendItem(data) {
    const container = document.getElementById('menu-items');
    container.insertAdjacentHTML('beforeend', createItemHtml(data));
    const newItem = container.lastElementChild;
    initSortable(newItem.querySelector('.children'));
    lucide.createIcons();
    checkEmpty();
}

function addCustomItem() {
    const title = document.getElementById('addTitle').value.trim();
    const url = document.getElementById('addUrl').value.trim();
    if (!title) { alert('Le titre est requis.'); return; }
    appendItem({ title, url: url || '#', type: 'custom', target: '_self', enabled: true });
    document.getElementById('addTitle').value = '';
    document.getElementById('addUrl').value = '#';
}

function addPageItems() {
    document.querySelectorAll('.page-cb:checked').forEach(cb => {
        appendItem({
            title: cb.dataset.title,
            url: cb.dataset.slug,
            type: 'page',
            linkable_type: 'Modules\\Pages\\Models\\StaticPage',
            linkable_id: cb.value,
            target: '_self',
            enabled: true
        });
        cb.checked = false;
    });
}

function addCategoryItems() {
    document.querySelectorAll('.cat-cb:checked').forEach(cb => {
        appendItem({
            title: cb.dataset.name,
            url: cb.dataset.slug,
            type: 'category',
            linkable_type: 'Modules\\Blog\\Models\\Category',
            linkable_id: cb.value,
            target: '_self',
            enabled: true
        });
        cb.checked = false;
    });
}

function collectItems(container, parentId) {
    const items = [];
    let order = 0;
    container.querySelectorAll(':scope > .menu-item').forEach(el => {
        items.push({
            id: el.dataset.id || null,
            title: el.querySelector('.field-title').value,
            type: el.dataset.type,
            url: el.querySelector('.field-url').value,
            linkable_type: el.dataset.linkableType || null,
            linkable_id: el.dataset.linkableId || null,
            target: el.querySelector('.field-target').value,
            icon: el.querySelector('.field-icon').value || null,
            css_classes: el.querySelector('.field-css').value || null,
            parent_id: parentId,
            order: order++,
            enabled: el.querySelector('.field-enabled').checked
        });
        const children = el.querySelector('.children');
        if (children && children.children.length) {
            items.push(...collectItems(children, el.dataset.id || '__temp_' + (order - 1)));
        }
    });
    return items;
}

function saveItems() {
    const btn = document.getElementById('btnSaveItems');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enregistrement...';

    const items = collectItems(document.getElementById('menu-items'), null);

    fetch('{{ route("admin.menus.save-items", $menu) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ items })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="save"></i> Enregistrer l\'ordre';
        lucide.createIcons();
        if (data.success) {
            showToast('Menu enregistré avec succès.', 'success');
            setTimeout(() => location.reload(), 500);
        } else {
            showToast(data.message || 'Erreur lors de l\'enregistrement.', 'danger');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="save"></i> Enregistrer l\'ordre';
        lucide.createIcons();
        showToast('Erreur réseau.', 'danger');
    });
}

function showToast(message, type) {
    const html = `<div class="toast align-items-center text-bg-${type} border-0 show" role="alert" style="position:fixed;top:1rem;right:1rem;z-index:9999">
        <div class="d-flex"><div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>`;
    document.body.insertAdjacentHTML('beforeend', html);
    setTimeout(() => document.querySelector('.toast.show:last-child')?.remove(), 3000);
}
</script>
@endpush
