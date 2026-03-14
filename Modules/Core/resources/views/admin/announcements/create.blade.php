<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('breadcrumbs')
<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.announcements.index') }}">Annonces</a></li>
        <li class="breadcrumb-item active" aria-current="page">Creer</li>
    </ol>
</nav>
@endsection

@section('title', 'Creer une annonce')

@section('content')
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Nouvelle annonce</h5></div>
        <div class="card-body">
            <form action="{{ route('admin.announcements.store') }}" method="POST">
                @csrf
                @include('core::admin.announcements._form')
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">Creer</button>
                </div>
            </form>
        </div>
    </div>
@endsection
