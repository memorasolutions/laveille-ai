<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Nouvelle équipe'), 'subtitle' => __('Équipes')])

@section('breadcrumbs')
<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.teams.index') }}">{{ __('Équipes') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Nouvelle équipe') }}</li>
    </ol>
</nav>
@endsection

@section('content')

@if($errors->any())
    <div class="alert alert-danger mb-4" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <h5 class="fw-bold mb-0">
            <i data-lucide="users" class="me-2"></i>{{ __('Nouvelle équipe') }}
        </h5>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('admin.teams.store') }}" method="POST" novalidate>
            @csrf

            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">
                            {{ __('Nom de l\'équipe') }} <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name') }}"
                               required
                               maxlength="255"
                               placeholder="{{ __('Ex. : Équipe marketing') }}"
                               aria-required="true"
                               autocomplete="off">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">{{ __('Description') }}</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description"
                                  name="description"
                                  rows="4"
                                  maxlength="1000"
                                  placeholder="{{ __('Décrivez l\'objectif ou le rôle de cette équipe…') }}">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">{{ __('Maximum 1 000 caractères.') }}</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="alert alert-info d-flex gap-2 align-items-start" role="note">
                        <i data-lucide="info" style="width:18px;height:18px;flex-shrink:0;margin-top:2px"></i>
                        <div>
                            <strong>{{ __('À savoir') }}</strong>
                            <p class="mb-0 mt-1 small">
                                {{ __('Vous devenez automatiquement propriétaire de l\'équipe à la création.') }}
                                {{ __('Vous pourrez inviter des membres depuis la page de détail.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-2">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="check" class="me-1"></i> {{ __('Créer l\'équipe') }}
                </button>
                <a href="{{ route('admin.teams.index') }}" class="btn btn-outline-secondary">
                    {{ __('Annuler') }}
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
