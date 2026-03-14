<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Tickets'))

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item">{{ __('Intelligence artificielle') }}</li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Tickets') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
        <i data-lucide="inbox" class="icon-md text-primary"></i>{{ __('Tickets') }}
    </h4>
    <a href="{{ route('admin.ai.tickets.create') }}" class="btn btn-primary btn-sm">
        <i data-lucide="plus" style="width:14px;height:14px;"></i> {{ __('Nouveau ticket') }}
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
</div>
@endif

<form method="GET" class="mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <select name="status" class="form-select form-select-sm">
                <option value="">{{ __('Tous les statuts') }}</option>
                <option value="open" {{ request('status')=='open'?'selected':'' }}>{{ __('Ouvert') }}</option>
                <option value="in_progress" {{ request('status')=='in_progress'?'selected':'' }}>{{ __('En cours') }}</option>
                <option value="waiting_customer" {{ request('status')=='waiting_customer'?'selected':'' }}>{{ __('En attente client') }}</option>
                <option value="resolved" {{ request('status')=='resolved'?'selected':'' }}>{{ __('Résolu') }}</option>
                <option value="closed" {{ request('status')=='closed'?'selected':'' }}>{{ __('Fermé') }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="priority" class="form-select form-select-sm">
                <option value="">{{ __('Toutes les priorités') }}</option>
                <option value="low" {{ request('priority')=='low'?'selected':'' }}>{{ __('Basse') }}</option>
                <option value="medium" {{ request('priority')=='medium'?'selected':'' }}>{{ __('Moyenne') }}</option>
                <option value="high" {{ request('priority')=='high'?'selected':'' }}>{{ __('Haute') }}</option>
                <option value="urgent" {{ request('priority')=='urgent'?'selected':'' }}>{{ __('Urgente') }}</option>
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
                    <th>#</th>
                    <th>{{ __('Titre') }}</th>
                    <th>{{ __('Statut') }}</th>
                    <th>{{ __('Priorité') }}</th>
                    <th>{{ __('Utilisateur') }}</th>
                    <th>{{ __('Agent') }}</th>
                    <th>{{ __('Catégorie') }}</th>
                    <th>{{ __('Échéance') }}</th>
                    <th>{{ __('Créé le') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $ticket)
                <tr>
                    <td>{{ $ticket->id }}</td>
                    <td><a href="{{ route('admin.ai.tickets.show', $ticket) }}">{{ $ticket->title }}</a></td>
                    <td>
                        @php $s = $ticket->status->value; @endphp
                        <span class="badge bg-{{ $s==='open'?'primary':($s==='in_progress'?'info':($s==='waiting_customer'?'warning':($s==='resolved'?'success':'secondary'))) }} {{ in_array($s,['waiting_customer'])?'text-dark':'' }}">
                            {{ __($ticket->status->value) }}
                        </span>
                    </td>
                    <td>
                        @php $p = $ticket->priority->value; @endphp
                        <span class="badge bg-{{ $p==='low'?'light':($p==='medium'?'info':($p==='high'?'warning':'danger')) }} {{ in_array($p,['low','high'])?'text-dark':'' }}">
                            {{ __($ticket->priority->value) }}
                        </span>
                    </td>
                    <td>{{ $ticket->user->name ?? '-' }}</td>
                    <td>{{ $ticket->agent->name ?? '-' }}</td>
                    <td>{{ $ticket->category ?? '-' }}</td>
                    <td>{{ $ticket->due_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-4">{{ __('Aucun ticket.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tickets->hasPages())
    <div class="card-footer">{{ $tickets->links() }}</div>
    @endif
</div>
@endsection
