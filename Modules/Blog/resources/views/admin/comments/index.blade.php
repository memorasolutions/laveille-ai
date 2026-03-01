@extends('backoffice::layouts.admin', ['title' => 'Commentaires', 'subtitle' => 'Blog'])

@section('content')
<div class="card h-100 p-0">
    <div class="card-header border-bottom py-3 px-4 d-flex align-items-center flex-wrap gap-3 justify-content-between">
        <h6 class="mb-0 d-flex align-items-center gap-2">
            <i data-lucide="message-circle"></i>
            Modération des commentaires
        </h6>
    </div>
    <div class="card-body p-4">
        @livewire('backoffice-comments-table')
    </div>
</div>
@endsection
