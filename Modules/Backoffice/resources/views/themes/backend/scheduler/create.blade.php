<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', __('Nouvelle tâche planifiée'))

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.scheduler') }}">{{ __('Planificateur') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Nouvelle tâche') }}</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
    <h4 class="mb-3 mb-md-0">{{ __('Nouvelle tâche planifiée') }}</h4>
</div>

<form action="{{ route('admin.scheduler.store') }}" method="POST" x-data="{ cronExpression: '{{ old('cron_expression', '') }}' }">
    @csrf
    <div class="row">
        <div class="col-lg-8 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label for="command" class="form-label">{{ __('Commande') }} <span class="text-danger">*</span></label>
                        <input type="text" name="command" id="command" class="form-control @error('command') is-invalid @enderror"
                               value="{{ old('command') }}" required aria-required="true" placeholder="Ex: app:send-reminders">
                        <div class="form-text">{{ __('Commande artisan à exécuter.') }}</div>
                        @error('command') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="cron_expression" class="form-label">{{ __('Expression cron') }} <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="cron_expression" id="cron_expression"
                                   class="form-control @error('cron_expression') is-invalid @enderror"
                                   x-model="cronExpression" required aria-required="true" placeholder="* * * * *">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                {{ __('Préréglages') }}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @foreach($cronPresets as $expression => $label)
                                    <li>
                                        <a class="dropdown-item" href="#" @click.prevent="cronExpression = '{{ $expression }}'">
                                            <code class="me-2">{{ $expression }}</code> {{ $label }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="form-text">{{ __('Format : minute heure jour mois jour-semaine') }}</div>
                        @error('cron_expression') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror"
                                  rows="3" placeholder="{{ __('Description optionnelle...') }}">{{ old('description') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">{{ __('Créer') }}</button>
                        <a href="{{ route('admin.scheduler') }}" class="btn btn-outline-secondary">{{ __('Annuler') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection
