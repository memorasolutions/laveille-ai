@extends('backoffice::layouts.admin', ['title' => 'Pages statiques', 'subtitle' => 'CMS'])

@section('content')

<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-end gap-3">
        <a href="{{ route('admin.pages.create') }}" class="btn btn-sm btn-primary-600 d-flex align-items-center gap-2">
            <iconify-icon icon="ic:baseline-plus" class="icon text-xl"></iconify-icon> Nouvelle page
        </a>
    </div>
    <div class="card-body">
        @livewire('static-pages-table')
    </div>
</div>

@endsection
