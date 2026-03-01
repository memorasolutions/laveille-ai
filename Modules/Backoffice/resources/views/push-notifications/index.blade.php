<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin')
@section('title', 'Notifications push')
@section('content')
<div class="container-fluid">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Notifications push</li>
        </ol>
    </nav>

    <div class="card rounded-2 mb-4">
        <div class="card-body p-3">
            <h5 class="card-title mb-0">{{ $count }} abonnement{{ $count > 1 ? 's' : '' }} actif{{ $count > 1 ? 's' : '' }}</h5>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-2">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card rounded-2">
        <div class="card-header bg-light">
            <h5 class="mb-0">Envoyer une notification</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.push-notifications.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Titre</label>
                    <input type="text" name="title" class="form-control rounded-2" required maxlength="100">
                </div>
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea name="body" class="form-control rounded-2" rows="3" required maxlength="500"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Lien (optionnel)</label>
                    <input type="url" name="url" class="form-control rounded-2" placeholder="https://...">
                </div>
                <div class="mb-4">
                    <label class="form-label">Destinataires</label>
                    <select name="role" class="form-select rounded-2">
                        <option value="">Tous les utilisateurs</option>
                        @foreach(\Spatie\Permission\Models\Role::all() as $role)
                            <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary rounded-2">Envoyer</button>
            </form>
        </div>
    </div>
</div>
@endsection
