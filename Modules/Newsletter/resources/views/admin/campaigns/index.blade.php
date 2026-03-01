@extends('backoffice::layouts.admin', ['title' => 'Campagnes', 'subtitle' => 'Newsletter'])

@section('content')
<div class="card h-100 p-0">
    <div class="card-header border-bottom py-3 px-4 d-flex align-items-center flex-wrap gap-3 justify-content-between">
        <h6 class="mb-0 d-flex align-items-center gap-2">
            <i data-lucide="mail"></i>
            Campagnes newsletter
        </h6>
        <a href="{{ route('admin.newsletter.campaigns.create') }}"
           class="btn btn-primary btn-sm d-flex align-items-center gap-2">
            <i data-lucide="plus"></i>
            Nouvelle campagne
        </a>
    </div>
    <div class="card-body p-4">
        @livewire('backoffice-campaigns-table')
    </div>
</div>
@endsection
