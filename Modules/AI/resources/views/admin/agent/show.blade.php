<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Conversation') . ' #' . $conversation->id)

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ai.agent.index') }}">{{ __('Agent dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Conversation') }} #{{ $conversation->id }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
        <i data-lucide="message-circle" class="icon-md text-primary"></i>
        {{ $conversation->title ?? __('Conversation') . ' #' . $conversation->id }}
    </h4>
    <div class="d-flex gap-2">
        <form action="{{ route('admin.ai.tickets.from-conversation', $conversation) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-outline-warning btn-sm">
                <i data-lucide="ticket" style="width:14px;height:14px;"></i> {{ __('Créer un ticket') }}
            </button>
        </form>
        <a href="{{ route('admin.ai.agent.index') }}" class="btn btn-outline-secondary btn-sm">
            <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> {{ __('Retour') }}
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
</div>
@endif

<div class="row">
    {{-- LEFT: Messages thread --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <strong>{{ $conversation->user->name ?? __('Visiteur') }}</strong>
                    <span class="badge bg-{{ $conversation->status->value === 'human_active' ? 'primary' : ($conversation->status->value === 'waiting_human' ? 'warning' : 'secondary') }} ms-2">
                        {{ __($conversation->status->value) }}
                    </span>
                </div>
                <small class="text-muted">{{ $conversation->created_at->format('d/m/Y H:i') }}</small>
            </div>
            <div class="card-body" style="max-height:500px;overflow-y:auto;" id="messagesContainer">
                @foreach($conversation->messages->sortBy('created_at') as $msg)
                <div class="d-flex mb-3 {{ $msg->role->value === 'user' ? '' : 'flex-row-reverse' }}">
                    <div class="px-3 py-2 rounded-3 {{ $msg->role->value === 'user' ? 'bg-light' : ($msg->role->value === 'agent' ? 'bg-primary bg-opacity-10' : 'bg-secondary bg-opacity-10') }}" style="max-width:80%;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="fw-semibold text-{{ $msg->role->value === 'user' ? 'primary' : ($msg->role->value === 'agent' ? 'success' : 'secondary') }}">
                                @switch($msg->role->value)
                                    @case('user') {{ __('Utilisateur') }} @break
                                    @case('agent') {{ __('Agent') }} @break
                                    @case('assistant') {{ __('IA') }} @break
                                    @default {{ $msg->role->value }}
                                @endswitch
                            </small>
                            <small class="text-muted ms-2">{{ $msg->created_at->format('H:i') }}</small>
                        </div>
                        <div style="white-space:pre-wrap;">{{ $msg->content }}</div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Reply form --}}
            @if($conversation->status->value === 'human_active' && (int)$conversation->agent_id === (int)auth()->id())
            <div class="card-footer">
                <form action="{{ route('admin.ai.agent.reply', $conversation) }}" method="POST">
                    @csrf
                    <div class="input-group">
                        <input type="text" name="message" class="form-control" placeholder="{{ __('Votre réponse...') }}" required maxlength="2000" id="replyInput">
                        <button class="btn btn-primary" type="submit">
                            <i data-lucide="send" style="width:16px;height:16px;"></i> {{ __('Envoyer') }}
                        </button>
                    </div>
                </form>

                {{-- Quick actions --}}
                <div class="d-flex justify-content-between mt-2">
                    <form action="{{ route('admin.ai.agent.release', $conversation) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <i data-lucide="user-minus" style="width:14px;height:14px;"></i> {{ __('Relâcher') }}
                        </button>
                    </form>
                    <form action="{{ route('admin.ai.agent.close', $conversation) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-success btn-sm">
                            <i data-lucide="check-circle" style="width:14px;height:14px;"></i> {{ __('Fermer la conversation') }}
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- RIGHT: Info + Notes + Canned replies --}}
    <div class="col-lg-4">
        {{-- Conversation info --}}
        <div class="card mb-3">
            <div class="card-header"><strong>{{ __('Informations') }}</strong></div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>{{ __('Utilisateur') }}</dt>
                    <dd>{{ $conversation->user->name ?? __('Visiteur') }} <small class="text-muted">({{ $conversation->user->email ?? '-' }})</small></dd>
                    <dt>{{ __('Statut') }}</dt>
                    <dd>{{ __($conversation->status->value) }}</dd>
                    <dt>{{ __('Messages') }}</dt>
                    <dd>{{ $conversation->messages->count() }}</dd>
                    <dt>{{ __('Créée le') }}</dt>
                    <dd>{{ $conversation->created_at->format('d/m/Y H:i') }}</dd>
                    @if($conversation->agent)
                    <dt>{{ __('Agent') }}</dt>
                    <dd>{{ $conversation->agent->name }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Canned replies --}}
        @if($cannedReplies->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header"><strong>{{ __('Réponses prédéfinies') }}</strong></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" style="max-height:200px;overflow-y:auto;">
                    @foreach($cannedReplies as $reply)
                    <button type="button" class="list-group-item list-group-item-action py-2" onclick="document.getElementById('replyInput').value = this.dataset.content" data-content="{{ $reply->content }}">
                        <small class="fw-semibold">{{ $reply->title }}</small>
                        @if($reply->shortcut)
                        <span class="badge bg-light text-muted ms-1">/{{ $reply->shortcut }}</span>
                        @endif
                    </button>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Internal notes --}}
        <div class="card mb-3">
            <div class="card-header"><strong>{{ __('Notes internes') }}</strong></div>
            <div class="card-body" style="max-height:250px;overflow-y:auto;">
                @forelse($conversation->internalNotes->sortByDesc('created_at') as $note)
                <div class="border-start border-warning border-3 ps-2 mb-2">
                    <small class="fw-semibold">{{ $note->user->name ?? '?' }}</small>
                    <small class="text-muted">- {{ $note->created_at->diffForHumans() }}</small>
                    <p class="mb-0 small">{{ $note->content }}</p>
                </div>
                @empty
                <p class="text-muted small mb-0">{{ __('Aucune note.') }}</p>
                @endforelse
            </div>
            <div class="card-footer">
                <form action="{{ route('admin.ai.agent.note', $conversation) }}" method="POST">
                    @csrf
                    <div class="input-group input-group-sm">
                        <input type="text" name="content" class="form-control" placeholder="{{ __('Ajouter une note...') }}" required maxlength="2000">
                        <button class="btn btn-outline-warning" type="submit">
                            <i data-lucide="sticky-note" style="width:14px;height:14px;"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Assignment history --}}
        @if($conversation->assignments->isNotEmpty())
        <div class="card">
            <div class="card-header"><strong>{{ __('Historique assignations') }}</strong></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($conversation->assignments->sortByDesc('created_at') as $assignment)
                    <li class="list-group-item py-2">
                        <small>
                            <strong>{{ $assignment->agent->name ?? '?' }}</strong>
                            - {{ $assignment->status }}
                            <span class="text-muted">{{ $assignment->created_at->diffForHumans() }}</span>
                        </small>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('messagesContainer');
    if (container) container.scrollTop = container.scrollHeight;
});
</script>
@endpush
@endsection
