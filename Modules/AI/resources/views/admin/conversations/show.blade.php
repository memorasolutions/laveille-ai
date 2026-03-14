<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Détail conversation'))

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item">{{ __('Intelligence artificielle') }}</li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ai.conversations.index') }}">{{ __('Conversations') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Détail') }}</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ $conversation->title ?? __('Sans titre') }}</h4>
    <a href="{{ route('admin.ai.conversations.index') }}" class="btn btn-outline-secondary btn-sm">
        <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> {{ __('Retour') }}
    </a>
</div>

{{-- Metadata --}}
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-4">{{ __('UUID') }}</dt>
                    <dd class="col-sm-8"><code>{{ $conversation->uuid }}</code></dd>

                    <dt class="col-sm-4">{{ __('Utilisateur') }}</dt>
                    <dd class="col-sm-8">{{ $conversation->user->name ?? __('Visiteur') }}</dd>

                    <dt class="col-sm-4">{{ __('Statut') }}</dt>
                    <dd class="col-sm-8">
                        @php
                            $badgeClass = match($conversation->status->value) {
                                'ai_active' => 'bg-primary',
                                'waiting_human' => 'bg-warning text-dark',
                                'human_active' => 'bg-success',
                                'closed' => 'bg-secondary',
                                default => 'bg-light text-dark',
                            };
                            $statusLabel = match($conversation->status->value) {
                                'ai_active' => __('IA active'),
                                'waiting_human' => __('En attente humain'),
                                'human_active' => __('Humain actif'),
                                'closed' => __('Fermée'),
                                default => $conversation->status->value,
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                    </dd>

                    <dt class="col-sm-4">{{ __('Modèle') }}</dt>
                    <dd class="col-sm-8"><span class="badge bg-light text-dark border">{{ $conversation->model ?? '-' }}</span></dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-4">{{ __('Tokens') }}</dt>
                    <dd class="col-sm-8">{{ number_format($conversation->tokens_used) }}</dd>

                    <dt class="col-sm-4">{{ __('Coût estimé') }}</dt>
                    <dd class="col-sm-8">{{ $conversation->cost_estimate ? number_format((float) $conversation->cost_estimate, 4) . ' $' : '-' }}</dd>

                    <dt class="col-sm-4">{{ __('Créée le') }}</dt>
                    <dd class="col-sm-8">{{ $conversation->created_at->format('d/m/Y H:i') }}</dd>

                    @if($conversation->closed_at)
                    <dt class="col-sm-4">{{ __('Fermée le') }}</dt>
                    <dd class="col-sm-8">{{ $conversation->closed_at->format('d/m/Y H:i') }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>

{{-- Messages --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('Messages') }} ({{ $conversation->messages->count() }})</h5>
    </div>
    <div class="card-body">
        @forelse($conversation->messages as $message)
        @php
            $roleValue = $message->role->value;
            $roleConfig = match($roleValue) {
                'system' => ['label' => __('Système'), 'bg' => 'bg-info bg-opacity-10 border-start border-info border-3', 'badge' => 'bg-info'],
                'user' => ['label' => __('Utilisateur'), 'bg' => 'bg-light border-start border-primary border-3', 'badge' => 'bg-primary'],
                'assistant' => ['label' => __('Assistant IA'), 'bg' => 'bg-primary bg-opacity-10 border-start border-primary border-3', 'badge' => 'bg-primary bg-opacity-75'],
                'agent' => ['label' => __('Agent humain'), 'bg' => 'bg-success bg-opacity-10 border-start border-success border-3', 'badge' => 'bg-success'],
                default => ['label' => $roleValue, 'bg' => '', 'badge' => 'bg-secondary'],
            };
        @endphp
        <div class="p-3 rounded mb-3 {{ $roleConfig['bg'] }}">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="badge {{ $roleConfig['badge'] }}">{{ $roleConfig['label'] }}</span>
                <small class="text-muted">
                    {{ $message->created_at->format('d/m/Y H:i') }}
                    @if($message->tokens)
                    <span class="ms-2">{{ $message->tokens }} tokens</span>
                    @endif
                </small>
            </div>
            <div style="white-space: pre-wrap;">{{ $message->content }}</div>
        </div>
        @empty
        <p class="text-muted text-center py-4">{{ __('Aucun message dans cette conversation.') }}</p>
        @endforelse
    </div>
</div>
@endsection
