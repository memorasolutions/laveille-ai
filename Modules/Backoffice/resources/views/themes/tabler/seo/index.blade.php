@extends('backoffice::layouts.admin', ['title' => 'SEO', 'subtitle' => 'Meta Tags'])

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title">Meta Tags SEO</h3>
        <a href="{{ route('admin.seo.create') }}" class="btn btn-primary btn-sm">
            <i class="ti ti-plus me-1"></i> Ajouter
        </a>
    </div>
    <div class="card-body">
        @livewire('backoffice-meta-tags-table')
    </div>
</div>
@endsection
