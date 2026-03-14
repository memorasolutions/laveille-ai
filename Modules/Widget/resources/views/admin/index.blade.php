<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', __('Widgets'))

@section('content')
<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Widgets') }}</li>
    </ol>
</nav>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="layout-grid" class="icon-md text-primary"></i>{{ __('Widgets') }}</h4>
    <div class="d-flex gap-2">
        <x-backoffice::help-modal id="helpWidgetsModal" :title="__('Qu\'est-ce qu\'un widget ?')" icon="layout-grid" :buttonLabel="__('Aide')">
            @include('widget::admin._help')
        </x-backoffice::help-modal>
        <a href="{{ route('admin.widgets.create') }}" class="btn btn-primary btn-icon-text">
            <i class="btn-icon-prepend" data-lucide="plus"></i> {{ __('Nouveau widget') }}
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Fermer') }}"></button>
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
                            {{ __('Aucun widget') }}
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
                                        <a href="{{ route('admin.widgets.edit', $widget) }}" class="btn btn-sm btn-outline-warning p-1" title="{{ __('Modifier') }}">
                                            <i data-lucide="edit" style="width:14px;height:14px;"></i>
                                        </a>
                                        <form action="{{ route('admin.widgets.destroy', $widget) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="if(confirm('{{ __("Supprimer ce widget ?") }}')) this.form.submit()" title="{{ __('Supprimer') }}">
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
