@extends('backoffice::themes.backend.layouts.admin')
@section('title', 'Message de ' . $contactMessage->name)
@section('content')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.contact-messages.index') }}">Messages</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $contactMessage->name }}</li>
    </ol>
</nav>
<div class="page-content">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">Message de contact</h4>
        <a href="{{ route('admin.contact-messages.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i> Retour
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0" style="text-transform: none">{{ $contactMessage->subject }}</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4" style="white-space: pre-wrap;">{{ $contactMessage->message }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informations</h5>
                </div>
                <div class="card-body">
                    <dl class="mb-0">
                        <dt class="small text-muted">Nom</dt>
                        <dd>{{ $contactMessage->name }}</dd>

                        <dt class="small text-muted">Email</dt>
                        <dd>
                            <a href="mailto:{{ $contactMessage->email }}">{{ $contactMessage->email }}</a>
                        </dd>

                        <dt class="small text-muted">Date</dt>
                        <dd>{{ $contactMessage->created_at->format('d/m/Y à H:i') }}</dd>

                        <dt class="small text-muted">Statut</dt>
                        <dd>
                            @if($contactMessage->isNew())
                                <span class="badge bg-primary">Non lu</span>
                            @else
                                <span class="badge bg-secondary">Lu</span>
                                <small class="text-muted d-block">{{ $contactMessage->read_at?->diffForHumans() }}</small>
                            @endif
                        </dd>

                        @if($contactMessage->ip_address)
                        <dt class="small text-muted">Adresse IP</dt>
                        <dd class="text-muted small">{{ $contactMessage->ip_address }}</dd>
                        @endif
                    </dl>
                </div>
                <div class="card-footer">
                    <a href="mailto:{{ $contactMessage->email }}?subject=Re: {{ urlencode($contactMessage->subject) }}" class="btn btn-primary btn-sm w-100 mb-2">
                        <i data-lucide="reply"></i> Répondre par email
                    </a>
                    <form action="{{ route('admin.contact-messages.destroy', $contactMessage) }}" method="POST" onsubmit="return confirm('Supprimer ce message ?');">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="if(confirm('Supprimer ce message ?')) this.closest('form').submit()">
                            <i data-lucide="trash-2"></i> Supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
