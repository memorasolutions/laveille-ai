<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Notifications push'), 'subtitle' => __('Envoyer')])

@section('breadcrumbs')
<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Notifications push') }}</li>
    </ol>
</nav>
@endsection

@section('content')

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="smartphone" class="icon-md text-primary"></i>{{ __('Notifications push') }}</h4>
    <x-backoffice::help-modal id="helpPushNotificationsModal" :title="__('Notifications push')" icon="smartphone" :buttonLabel="__('Aide')">
        @include('backoffice::themes.backend.push-notifications._help')
    </x-backoffice::help-modal>
</div>

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
                <p class="text-muted small mb-0">{{ trans_choice('{0,1} abonnement actif|[2,*] abonnements actifs', $count) }} - {{ __('Utilisateurs abonnés aux notifications push') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Formulaire d'envoi --}}
<div class="card">
    <div class="card-header border-bottom py-3 px-4">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="send" style="width:20px;height:20px;" class="text-primary"></i>
            <h5 class="mb-0 fw-semibold">{{ __('Envoyer une notification') }}</h5>
        </div>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.push-notifications.store') }}">
            @csrf

            <div class="row g-4">

                <div class="col-12">
                    <label class="form-label fw-medium">
                        {{ __('Titre') }} <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="title"
                        class="form-control"
                        required maxlength="100" placeholder="{{ __('Titre de la notification') }}">
                </div>

                <div class="col-12">
                    <label class="form-label fw-medium">
                        {{ __('Message') }} <span class="text-danger">*</span>
                    </label>
                    <textarea name="body"
                        class="form-control"
                        rows="3" required maxlength="500" placeholder="{{ __('Corps du message...') }}"></textarea>
                    <div class="form-text">{{ __('Maximum 500 caractères.') }}</div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-medium">{{ __('Lien (optionnel)') }}</label>
                    <input type="url" name="url"
                        class="form-control"
                        placeholder="https://...">
                </div>

                <div class="col-12">
                    <label class="form-label fw-medium">{{ __('Destinataires') }}</label>
                    <select name="role" class="form-select">
                        <option value="">{{ __('Tous les utilisateurs') }}</option>
                        @foreach(\Spatie\Permission\Models\Role::all() as $role)
                            <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                </div>

            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <i data-lucide="send" style="width:16px;height:16px;"></i>
                    {{ __('Envoyer') }}
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
