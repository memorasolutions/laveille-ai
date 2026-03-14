<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Boîte de réception'))

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item">{{ __('Intelligence artificielle') }}</li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Boîte de réception') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
        <i data-lucide="inbox" class="icon-md text-primary"></i>{{ __('Boîte de réception') }}
    </h4>
</div>

<form method="GET" class="mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <select name="channel_id" class="form-select form-select-sm">
                <option value="">{{ __('Tous les canaux') }}</option>
                @foreach($channels as $channel)
                <option value="{{ $channel->id }}" {{ request('channel_id') == $channel->id ? 'selected' : '' }}>{{ $channel->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="direction" class="form-select form-select-sm">
                <option value="">{{ __('Toutes directions') }}</option>
                <option value="inbound" {{ request('direction') == 'inbound' ? 'selected' : '' }}>{{ __('Entrant') }}</option>
                <option value="outbound" {{ request('direction') == 'outbound' ? 'selected' : '' }}>{{ __('Sortant') }}</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select form-select-sm">
                <option value="">{{ __('Tous statuts') }}</option>
                <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>{{ __('Reçu') }}</option>
                <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>{{ __('Envoyé') }}</option>
                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>{{ __('Échoué') }}</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-primary btn-sm" type="submit">
                <i data-lucide="filter" style="width:14px;height:14px;"></i> {{ __('Filtrer') }}
            </button>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>{{ __('Canal') }}</th>
                    <th>{{ __('Direction') }}</th>
                    <th>{{ __('De / À') }}</th>
                    <th>{{ __('Sujet') }}</th>
                    <th>{{ __('Ticket') }}</th>
                    <th>{{ __('Date') }}</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $typeColors = ['email' => 'primary', 'whatsapp' => 'success', 'telegram' => 'info', 'sms' => 'warning'];
                @endphp
                @forelse($messages as $msg)
                <tr>
                    <td>
                        {{ $msg->channel->name ?? '-' }}
                        @if($msg->channel)
                        <span class="badge bg-{{ $typeColors[$msg->channel->type] ?? 'secondary' }} ms-1">{{ $msg->channel->type }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $msg->direction === 'inbound' ? 'info' : 'success' }}">
                            {{ $msg->direction === 'inbound' ? __('Entrant') : __('Sortant') }}
                        </span>
                    </td>
                    <td>{{ $msg->direction === 'inbound' ? $msg->sender : $msg->recipient }}</td>
                    <td>{{ $msg->subject ?? __('Sans sujet') }}</td>
                    <td>
                        @if($msg->ticket_id && $msg->ticket)
                        <a href="{{ route('admin.ai.tickets.show', $msg->ticket) }}" class="btn btn-sm btn-outline-primary">
                            #{{ $msg->ticket->id }}
                        </a>
                        @else
                        <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>{{ $msg->occurred_at?->format('d/m/Y H:i') ?? $msg->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">{{ __('Aucun message.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($messages->hasPages())
    <div class="card-footer">{{ $messages->links() }}</div>
    @endif
</div>
@endsection
