@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Commentaires', 'subtitle' => 'Blog'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.blog.articles.index') }}">Blog</a></li>
        <li class="breadcrumb-item active" aria-current="page">Commentaires</li>
    </ol>
</nav>

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
