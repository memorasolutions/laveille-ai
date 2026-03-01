<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Campagnes', 'subtitle' => 'Newsletter'])

@section('content')

<div class="card">
    <div class="card-header d-block py-3 px-4 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <h4 class="fw-bold mb-0 fs-5 d-flex align-items-center gap-2">
                <i data-lucide="send" class="icon-sm text-primary"></i>
                Campagnes newsletter
            </h4>
            <a href="{{ route('admin.newsletter.campaigns.create') }}"
               class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
                <i data-lucide="plus" class="icon-sm"></i>
                Nouvelle campagne
            </a>
        </div>
    </div>
    <div class="p-4">
        @livewire('backoffice-campaigns-table')
    </div>
</div>

@endsection
