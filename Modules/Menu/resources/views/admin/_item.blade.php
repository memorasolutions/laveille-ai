<div class="menu-item" data-id="{{ $item->id }}" data-type="{{ $item->type }}" data-linkable-type="{{ $item->linkable_type }}" data-linkable-id="{{ $item->linkable_id }}">
    <div class="item-header">
        <i data-lucide="grip-vertical" class="text-muted"></i>
        <span class="item-title flex-grow-1 fw-medium">{{ $item->title }}</span>
        <span class="badge bg-light text-dark">{{ $item->type }}</span>
        <button type="button" class="btn btn-sm p-0" onclick="toggleItemBody(this)"><i data-lucide="chevron-down"></i></button>
    </div>
    <div class="item-body">
        <div class="row g-2">
            <div class="col-6">
                <label class="form-label small">Titre</label>
                <input type="text" class="form-control form-control-sm field-title" value="{{ $item->title }}" onchange="this.closest('.menu-item').querySelector('.item-title').textContent=this.value">
            </div>
            <div class="col-6">
                <label class="form-label small">URL</label>
                <input type="text" class="form-control form-control-sm field-url" value="{{ $item->url }}" {{ $item->type !== 'custom' ? 'readonly' : '' }}>
            </div>
            <div class="col-4">
                <label class="form-label small">Cible</label>
                <select class="form-select form-select-sm field-target">
                    <option value="_self" {{ $item->target === '_self' ? 'selected' : '' }}>Même fenêtre</option>
                    <option value="_blank" {{ $item->target === '_blank' ? 'selected' : '' }}>Nouvel onglet</option>
                </select>
            </div>
            <div class="col-4">
                <label class="form-label small">Icône</label>
                <input type="text" class="form-control form-control-sm field-icon" value="{{ $item->icon }}" placeholder="lucide icon name">
            </div>
            <div class="col-4">
                <label class="form-label small">Classes CSS</label>
                <input type="text" class="form-control form-control-sm field-css" value="{{ $item->css_classes }}">
            </div>
            <div class="col-12 d-flex justify-content-between align-items-center mt-2">
                <div class="form-check form-switch">
                    <input class="form-check-input field-enabled" type="checkbox" {{ $item->enabled ? 'checked' : '' }}>
                    <label class="form-check-label small">Activé</label>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(this)">
                    <i data-lucide="trash-2"></i> Supprimer
                </button>
            </div>
        </div>
    </div>
    <div class="children menu-sortable">
        @foreach($allItems->where('parent_id', $item->id)->sortBy('order') as $child)
            @include('menu::admin._item', ['item' => $child, 'allItems' => $allItems])
        @endforeach
    </div>
</div>
