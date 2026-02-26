@extends('backoffice::layouts.admin', ['title' => 'Shortcodes', 'subtitle' => 'Gestion'])

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title">
            <i class="ti ti-code me-2"></i> Shortcodes
        </h3>
        <a href="{{ route('admin.shortcodes.create') }}" class="btn btn-primary btn-sm">
            <i class="ti ti-plus me-1"></i> Nouveau shortcode
        </a>
    </div>
    <div class="card-body">
        @livewire('shortcodes-table')
    </div>
</div>
@endsection
