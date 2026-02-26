@extends('backoffice::layouts.admin', ['title' => 'Notifications push', 'subtitle' => 'Envoyer'])

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible mb-3">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row mb-4">
    <div class="col-12">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-primary-lt text-primary avatar">
                            <i class="ti ti-bell"></i>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">
                            {{ $count }} abonnement{{ $count > 1 ? 's' : '' }} actif{{ $count > 1 ? 's' : '' }}
                        </div>
                        <div class="text-muted">Utilisateurs abonnés aux notifications push</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="ti ti-send me-2"></i> Envoyer une notification
        </h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.push-notifications.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label required">Titre</label>
                <input type="text" name="title" class="form-control" required maxlength="100" placeholder="Titre de la notification">
            </div>

            <div class="mb-3">
                <label class="form-label required">Message</label>
                <textarea name="body" class="form-control" rows="3" required maxlength="500" placeholder="Corps du message..."></textarea>
                <small class="text-muted">Maximum 500 caractères.</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Lien (optionnel)</label>
                <input type="url" name="url" class="form-control" placeholder="https://...">
            </div>

            <div class="mb-4">
                <label class="form-label">Destinataires</label>
                <select name="role" class="form-select">
                    <option value="">Tous les utilisateurs</option>
                    @foreach(\Spatie\Permission\Models\Role::all() as $role)
                        <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="ti ti-send me-1"></i> Envoyer
            </button>
        </form>
    </div>
</div>

@endsection
