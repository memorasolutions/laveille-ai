<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', 'Widgets')

@section('content')
<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item active" aria-current="page">Widgets</li>
    </ol>
</nav>
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
    <h4 class="mb-3 mb-md-0">Widgets</h4>
    <a href="{{ route('admin.widgets.create') }}" class="btn btn-primary btn-icon-text">
        <i class="btn-icon-prepend" data-lucide="plus"></i> Nouveau widget
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    @foreach($widgetsByZone as $zone => $widgets)
        <div class="col-lg-4 col-md-6 grid-margin stretch-card">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">{{ \Modules\Widget\Models\Widget::ZONE_LABELS[$zone] ?? ucfirst($zone) }}</h6>
                    <span class="badge bg-secondary">{{ $widgets->count() }}</span>
                </div>
                <div class="card-body p-0">
                    @if($widgets->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i data-lucide="layout" style="width:32px;height:32px;" class="mb-2 d-block mx-auto"></i>
                            Aucun widget
                        </div>
                    @else
                        <ul class="list-group list-group-flush sortable-zone" id="zone-{{ $zone }}">
                            @foreach($widgets as $widget)
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3" data-id="{{ $widget->id }}">
                                    <div class="d-flex align-items-center">
                                        <i data-lucide="grip-vertical" style="width:16px;height:16px;cursor:grab;" class="drag-handle text-muted me-2"></i>
                                        <div>
                                            <span class="{{ $widget->is_active ? '' : 'text-muted text-decoration-line-through' }}">{{ $widget->title }}</span>
                                            <span class="badge bg-info ms-1" style="font-size:10px;">{{ \Modules\Widget\Models\Widget::TYPE_LABELS[$widget->type] ?? $widget->type }}</span>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('admin.widgets.edit', $widget) }}" class="btn btn-sm btn-outline-warning p-1" title="Modifier">
                                            <i data-lucide="edit" style="width:14px;height:14px;"></i>
                                        </a>
                                        <form action="{{ route('admin.widgets.destroy', $widget) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="if(confirm('Supprimer ce widget ?')) this.form.submit()" title="Supprimer">
                                                <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection

@push('scripts')
<script src="{{ asset('build/nobleui/plugins/sortablejs/Sortable.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.sortable-zone').forEach(function(el) {
        new Sortable(el, {
            animation: 150,
            handle: '.drag-handle',
            onEnd: function() {
                var zone = el.id.replace('zone-', '');
                var order = Array.from(el.children).map(function(li) { return parseInt(li.dataset.id); });
                fetch('{{ route("admin.widgets.reorder") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ zone: zone, order: order })
                });
            }
        });
    });
});
</script>
@endpush
