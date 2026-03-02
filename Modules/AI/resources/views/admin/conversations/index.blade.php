@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Conversations IA'))

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item">{{ __('Intelligence artificielle') }}</li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Conversations') }}</li>
    </ol>
</nav>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
</div>
@endif

{{-- KPI Cards --}}
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                    <i data-lucide="brain" class="text-primary" style="width:24px;height:24px;"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">{{ __('IA active') }}</h6>
                    <h3 class="mb-0">{{ $statusCounts['ai_active'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3">
                    <i data-lucide="clock" class="text-warning" style="width:24px;height:24px;"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">{{ __('En attente humain') }}</h6>
                    <h3 class="mb-0">{{ $statusCounts['waiting_human'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                    <i data-lucide="user-check" class="text-success" style="width:24px;height:24px;"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">{{ __('Humain actif') }}</h6>
                    <h3 class="mb-0">{{ $statusCounts['human_active'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-secondary bg-opacity-10 p-3 me-3">
                    <i data-lucide="x-circle" class="text-secondary" style="width:24px;height:24px;"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">{{ __('Fermées') }}</h6>
                    <h3 class="mb-0">{{ $statusCounts['closed'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.ai.conversations.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="status" class="form-label">{{ __('Statut') }}</label>
                <select class="form-select" id="status" name="status">
                    <option value="">{{ __('Tous les statuts') }}</option>
                    <option value="ai_active" {{ request('status') === 'ai_active' ? 'selected' : '' }}>{{ __('IA active') }}</option>
                    <option value="waiting_human" {{ request('status') === 'waiting_human' ? 'selected' : '' }}>{{ __('En attente humain') }}</option>
                    <option value="human_active" {{ request('status') === 'human_active' ? 'selected' : '' }}>{{ __('Humain actif') }}</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>{{ __('Fermées') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="date_from" class="form-label">{{ __('Date de début') }}</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label">{{ __('Date de fin') }}</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary me-2">
                    <i data-lucide="filter" style="width:14px;height:14px;"></i> {{ __('Filtrer') }}
                </button>
                <a href="{{ route('admin.ai.conversations.index') }}" class="btn btn-outline-secondary">
                    <i data-lucide="x" style="width:14px;height:14px;"></i> {{ __('Réinitialiser') }}
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Conversations Table --}}
<div class="card">
    <div class="card-body">
        @if($conversations->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('Utilisateur') }}</th>
                        <th>{{ __('Titre') }}</th>
                        <th>{{ __('Statut') }}</th>
                        <th>{{ __('Messages') }}</th>
                        <th>{{ __('Modèle') }}</th>
                        <th>{{ __('Tokens') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($conversations as $conversation)
                    <tr>
                        <td>{{ $conversation->id }}</td>
                        <td>
                            @if($conversation->user)
                                {{ $conversation->user->name }}
                            @else
                                <span class="text-muted">{{ __('Visiteur') }}</span>
                            @endif
                        </td>
                        <td title="{{ $conversation->title }}">{{ Str::limit($conversation->title, 50) }}</td>
                        <td>
                            @php
                                $badgeClass = match($conversation->status->value ?? $conversation->status) {
                                    'ai_active' => 'bg-primary',
                                    'waiting_human' => 'bg-warning text-dark',
                                    'human_active' => 'bg-success',
                                    'closed' => 'bg-secondary',
                                    default => 'bg-light text-dark',
                                };
                                $statusLabel = match($conversation->status->value ?? $conversation->status) {
                                    'ai_active' => __('IA active'),
                                    'waiting_human' => __('En attente humain'),
                                    'human_active' => __('Humain actif'),
                                    'closed' => __('Fermée'),
                                    default => $conversation->status,
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                        </td>
                        <td>{{ $conversation->messages_count }}</td>
                        <td><small class="text-muted">{{ $conversation->model ?? '-' }}</small></td>
                        <td>
                            @if($conversation->tokens_used)
                                {{ number_format($conversation->tokens_used) }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td title="{{ $conversation->created_at->format('d/m/Y H:i') }}">{{ $conversation->created_at->diffForHumans() }}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.ai.conversations.show', $conversation) }}" class="btn btn-outline-primary" title="{{ __('Voir') }}">
                                    <i data-lucide="eye" style="width:14px;height:14px;"></i>
                                </a>
                                <form action="{{ route('admin.ai.conversations.destroy', $conversation) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Fermer cette conversation ?') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" title="{{ __('Fermer') }}">
                                        <i data-lucide="x" style="width:14px;height:14px;"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ $conversations->links() }}
        </div>
        @else
        <div class="text-center py-5">
            <i data-lucide="message-square" style="width:48px;height:48px;" class="text-muted mb-3 d-block mx-auto"></i>
            <h5 class="text-muted">{{ __('Aucune conversation trouvée') }}</h5>
            <p class="text-muted">{{ __('Aucune conversation ne correspond à vos critères.') }}</p>
            @if(request()->hasAny(['status', 'date_from', 'date_to']))
            <a href="{{ route('admin.ai.conversations.index') }}" class="btn btn-primary mt-2">{{ __('Réinitialiser les filtres') }}</a>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
