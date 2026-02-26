@extends('backoffice::layouts.admin', ['title' => 'Pages statiques', 'subtitle' => 'CMS'])

@section('content')

<div class="card">
    <div class="card-header block sm:py-6 py-5 sm:px-[1.875rem] px-4 border-b border-border">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h4 class="text-xl font-semibold text-heading">Pages statiques</h4>
            <a href="{{ route('admin.pages.create') }}" class="btn btn-primary flex items-center gap-1.5 text-sm px-4 py-2 rounded-lg">
                <i class="fa fa-plus w-4 h-4"></i>
                Nouvelle page
            </a>
        </div>
    </div>
    <div class="sm:p-[1.875rem] p-4">
        @livewire('static-pages-table')
    </div>
</div>

@endsection
