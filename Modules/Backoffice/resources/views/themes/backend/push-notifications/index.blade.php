<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Notifications push', 'subtitle' => 'Envoyer'])

@section('breadcrumbs')
<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item active" aria-current="page">Notifications push</li>
    </ol>
</nav>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
        <i data-lucide="check-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
        {{ session('success') }}
    </div>
@endif

{{-- Stat abonnements --}}
<div class="card mb-4">
    <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center rounded bg-primary bg-opacity-10 flex-shrink-0"
                 style="width:48px;height:48px;">
                <i data-lucide="bell" style="width:24px;height:24px;" class="text-primary"></i>
            </div>
            <div>
                <p class="fs-4 fw-bold mb-0">{{ $count }}</p>
                <p class="text-muted small mb-0">abonnement{{ $count > 1 ? 's' : '' }} actif{{ $count > 1 ? 's' : '' }} - Utilisateurs abonnés aux notifications push</p>
            </div>
        </div>
    </div>
</div>

{{-- Formulaire d'envoi --}}
<div class="card">
    <div class="card-header border-bottom py-3 px-4">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="send" style="width:20px;height:20px;" class="text-primary"></i>
            <h5 class="mb-0 fw-semibold">Envoyer une notification</h5>
        </div>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.push-notifications.store') }}">
            @csrf

            <div class="row g-4">

                <div class="col-12">
                    <label class="form-label fw-medium">
                        Titre <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="title"
                        class="form-control"
                        required maxlength="100" placeholder="Titre de la notification">
                </div>

                <div class="col-12">
                    <label class="form-label fw-medium">
                        Message <span class="text-danger">*</span>
                    </label>
                    <textarea name="body"
                        class="form-control"
                        rows="3" required maxlength="500" placeholder="Corps du message..."></textarea>
                    <div class="form-text">Maximum 500 caractères.</div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-medium">Lien (optionnel)</label>
                    <input type="url" name="url"
                        class="form-control"
                        placeholder="https://...">
                </div>

                <div class="col-12">
                    <label class="form-label fw-medium">Destinataires</label>
                    <select name="role" class="form-select">
                        <option value="">Tous les utilisateurs</option>
                        @foreach(\Spatie\Permission\Models\Role::all() as $role)
                            <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                </div>

            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <i data-lucide="send" style="width:16px;height:16px;"></i>
                    Envoyer
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
