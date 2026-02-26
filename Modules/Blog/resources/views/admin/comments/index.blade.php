@extends('backoffice::layouts.admin', ['title' => 'Commentaires', 'subtitle' => 'Blog'])

@section('content')
<div class="card h-100 p-0 radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center flex-wrap gap-3 justify-content-between">
        <h6 class="mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:chat-line-bold" class="icon text-xl"></iconify-icon>
            Modération des commentaires
        </h6>
    </div>
    <div class="card-body p-24">
        @livewire('backoffice-comments-table')
    </div>
</div>
@endsection
