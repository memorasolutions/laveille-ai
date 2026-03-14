<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Nouveau ticket'))

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ai.tickets.index') }}">{{ __('Tickets') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Nouveau ticket') }}</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><strong>{{ __('Créer un ticket') }}</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.ai.tickets.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('Titre') }} *</label>
                        <input type="text" name="title" class="form-control" required maxlength="255" value="{{ old('title') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Description') }} *</label>
                        <textarea name="description" class="form-control" rows="5" required>{{ old('description') }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Priorité') }} *</label>
                            <select name="priority" class="form-select" required>
                                @foreach($priorities as $priority)
                                <option value="{{ $priority->value }}">{{ __($priority->value) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Catégorie') }}</label>
                            <input type="text" name="category" class="form-control" maxlength="100" value="{{ old('category') }}">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Agent assigné') }}</label>
                            <select name="agent_id" class="form-select">
                                <option value="">{{ __('-- Aucun --') }}</option>
                                @foreach($agents as $agent)
                                <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Politique SLA') }}</label>
                            <select name="sla_policy_id" class="form-select">
                                <option value="">{{ __('-- Aucune --') }}</option>
                                @foreach($slaPolicies as $sla)
                                <option value="{{ $sla->id }}">{{ $sla->name }} ({{ $sla->resolution_hours }}h)</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @if($tags->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label">{{ __('Tags') }}</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($tags as $tag)
                            <div class="form-check">
                                <input type="checkbox" name="tags[]" value="{{ $tag->id }}" class="form-check-input" id="tag{{ $tag->id }}">
                                <label class="form-check-label" for="tag{{ $tag->id }}">{{ $tag->name }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="plus" style="width:14px;height:14px;"></i> {{ __('Créer le ticket') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
