<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Commentaires', 'subtitle' => 'Blog'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.blog.articles.index') }}">Blog</a></li>
        <li class="breadcrumb-item active" aria-current="page">Commentaires</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="message-circle" class="icon-md text-primary"></i>{{ __('Commentaires') }}</h4>
    <x-backoffice::help-modal id="helpCommentsModal" :title="__('Modération des commentaires')" icon="message-circle" :buttonLabel="__('Aide')">
        @include('blog::themes.backend.admin.comments._help')
    </x-backoffice::help-modal>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
@endif

<div class="card">
    <div class="card-header py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">
            <i data-lucide="message-square" class="me-2" style="width:20px;height:20px"></i>
            Modération des commentaires
        </h5>
    </div>
    <div class="card-body p-4">
        @livewire('backoffice-comments-table')
    </div>
</div>

@endsection
