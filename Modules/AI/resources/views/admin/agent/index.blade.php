<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Agent dashboard'))

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item">{{ __('Intelligence artificielle') }}</li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Agent dashboard') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="headphones" class="icon-md text-primary"></i>{{ __('Dashboard agent') }}</h4>
    <x-backoffice::help-modal id="helpAgentModal" :title="__('Dashboard agent')" icon="headphones" :buttonLabel="__('Aide')">
        @include('ai::admin.agent._help')
    </x-backoffice::help-modal>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
</div>
@endif

<div class="row">
    {{-- LEFT: Conversations lists --}}
    <div class="col-md-5">
        {{-- Waiting --}}
        <h6 class="text-muted text-uppercase mb-3">
            <i data-lucide="clock" style="width:16px;height:16px;"></i>
            {{ __('En attente') }}
            <span class="badge bg-warning text-dark ms-1">{{ $waitingConversations->count() }}</span>
        </h6>

        @forelse($waitingConversations as $conv)
        <div class="card mb-2 border-start border-warning border-3">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <strong>{{ $conv->user->name ?? __('Visiteur') }}</strong>
                        <br><small class="text-muted">{{ Str::limit($conv->title, 40) }}</small>
                    </div>
                    <small class="text-muted">{{ $conv->created_at->diffForHumans() }}</small>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">{{ $conv->messages->count() }} {{ __('messages') }}</small>
                        <a href="{{ route('admin.ai.agent.show', $conv) }}" class="btn btn-outline-primary btn-sm ms-2"><i data-lucide="eye" style="width:14px;height:14px;"></i></a>
                    </div>
                    <form action="{{ route('admin.ai.agent.claim', $conv) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i data-lucide="user-plus" style="width:14px;height:14px;"></i> {{ __('Prendre en charge') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="alert alert-light">{{ __('Aucune conversation en attente.') }}</div>
        @endforelse

        {{-- My conversations --}}
        <h6 class="text-muted text-uppercase mb-3 mt-4">
            <i data-lucide="message-circle" style="width:16px;height:16px;"></i>
            {{ __('Mes conversations') }}
            <span class="badge bg-primary ms-1">{{ $myConversations->count() }}</span>
        </h6>

        @forelse($myConversations as $conv)
        <div class="card mb-2 border-start border-primary border-3">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <strong>{{ $conv->user->name ?? __('Visiteur') }}</strong>
                        <br><small class="text-muted">{{ Str::limit($conv->title, 40) }}</small>
                    </div>
                    <small class="text-muted">{{ $conv->updated_at->diffForHumans() }}</small>
                </div>

                <div class="text-end mb-2">
                    <a href="{{ route('admin.ai.agent.show', $conv) }}" class="btn btn-outline-primary btn-sm"><i data-lucide="maximize-2" style="width:14px;height:14px;"></i> {{ __('Détail') }}</a>
                </div>
                {{-- Last messages preview --}}
                <div class="bg-light rounded p-2 mb-2" style="max-height:120px;overflow-y:auto;font-size:0.85rem;">
                    @foreach($conv->messages->sortByDesc('created_at')->take(5)->reverse() as $msg)
                    <div class="mb-1 {{ $msg->role->value === 'user' ? 'text-primary' : 'text-secondary' }}">
                        <strong>{{ $msg->role->value === 'user' ? __('Utilisateur') : __('Agent') }} :</strong>
                        {{ Str::limit($msg->content, 60) }}
                    </div>
                    @endforeach
                </div>

                {{-- Quick reply --}}
                <form action="{{ route('admin.ai.agent.reply', $conv) }}" method="POST" class="mb-2">
                    @csrf
                    <div class="input-group input-group-sm">
                        <input type="text" name="message" class="form-control" placeholder="{{ __('Répondre...') }}" required maxlength="2000">
                        <button class="btn btn-outline-primary" type="submit">
                            <i data-lucide="send" style="width:14px;height:14px;"></i>
                        </button>
                    </div>
                </form>

                <div class="d-flex justify-content-between">
                    <form action="{{ route('admin.ai.agent.release', $conv) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">{{ __('Relâcher') }}</button>
                    </form>
                    <form action="{{ route('admin.ai.agent.close', $conv) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-success btn-sm">
                            <i data-lucide="check" style="width:14px;height:14px;"></i> {{ __('Fermer') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="alert alert-light">{{ __('Aucune conversation active.') }}</div>
        @endforelse
    </div>

    {{-- RIGHT: Detail panel --}}
    <div class="col-md-7">
        <div class="card" style="min-height:400px;">
            <div class="card-body d-flex flex-column justify-content-center align-items-center text-muted">
                <i data-lucide="message-square" style="width:48px;height:48px;" class="mb-3 opacity-25"></i>
                <h5>{{ __('Agent dashboard') }}</h5>
                <p>{{ __('Utilisez les listes à gauche pour gérer vos conversations.') }}</p>
                <p class="small">{{ __('L\'intégration temps réel avec Echo/Reverb sera activée prochainement.') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
