@extends('backoffice::layouts.admin', ['title' => 'Plans SaaS', 'subtitle' => 'Gestion'])

@section('content')

<div class="card h-100 p-0 radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center flex-wrap gap-3 justify-content-end">
        <a href="{{ route('admin.plans.create') }}" class="btn btn-primary text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2">
            <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon> Ajouter
        </a>
    </div>
    <div class="card-body p-24">
        @livewire('backoffice-plans-table')
    </div>
</div>

@endsection
