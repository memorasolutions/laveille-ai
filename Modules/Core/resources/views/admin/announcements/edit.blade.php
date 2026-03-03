@extends('backoffice::themes.backend.layouts.admin')

@section('breadcrumbs')
<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.announcements.index') }}">Annonces</a></li>
        <li class="breadcrumb-item active" aria-current="page">Modifier</li>
    </ol>
</nav>
@endsection

@section('title', 'Modifier une annonce')

@section('content')
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Modifier l'annonce</h5></div>
        <div class="card-body">
            <form action="{{ route('admin.announcements.update', $announcement) }}" method="POST">
                @csrf
                @method('PUT')
                @include('core::admin.announcements._form', ['announcement' => $announcement])
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">Mettre a jour</button>
                </div>
            </form>
        </div>
    </div>
@endsection
