<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Canaux'))

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item">{{ __('Intelligence artificielle') }}</li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Canaux') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
        <i data-lucide="radio" class="icon-md text-primary"></i>{{ __('Canaux') }}
    </h4>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createChannelModal">
        <i data-lucide="plus" style="width:14px;height:14px;"></i> {{ __('Nouveau canal') }}
    </button>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
</div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>{{ __('Nom') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Secret webhook') }}</th>
                    <th>{{ __('Actif') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($channels as $channel)
                @php
                    $typeColors = ['email' => 'primary', 'whatsapp' => 'success', 'telegram' => 'info', 'sms' => 'warning'];
                @endphp
                <tr>
                    <td>{{ $channel->name }}</td>
                    <td><span class="badge bg-{{ $typeColors[$channel->type] ?? 'secondary' }}">{{ $channel->type }}</span></td>
                    <td><code class="text-muted">{{ Str::limit($channel->inbound_secret, 8, '...') }}</code></td>
                    <td>
                        <span class="badge bg-{{ $channel->is_active ? 'success' : 'secondary' }}">
                            {{ $channel->is_active ? __('Oui') : __('Non') }}
                        </span>
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editChannelModal{{ $channel->id }}">
                            <i data-lucide="edit-2" style="width:14px;height:14px;"></i>
                        </button>
                        <form method="POST" action="{{ route('admin.ai.channels.toggle', $channel) }}" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-outline-{{ $channel->is_active ? 'warning' : 'success' }}">
                                <i data-lucide="{{ $channel->is_active ? 'pause' : 'play' }}" style="width:14px;height:14px;"></i>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.ai.channels.destroy', $channel) }}" class="d-inline" onsubmit="return confirm('{{ __('Supprimer ce canal ?') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                            </button>
                        </form>
                    </td>
                </tr>

                {{-- Edit modal --}}
                <div class="modal fade" id="editChannelModal{{ $channel->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('admin.ai.channels.update', $channel) }}">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title">{{ __('Modifier le canal') }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Nom') }} *</label>
                                        <input type="text" class="form-control" name="name" value="{{ $channel->name }}" required maxlength="255">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Type') }} *</label>
                                        <select class="form-select" name="type" required>
                                            @foreach(['email', 'whatsapp', 'telegram', 'sms'] as $t)
                                            <option value="{{ $t }}" {{ $channel->type === $t ? 'selected' : '' }}>{{ $t }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
                                    <button type="submit" class="btn btn-primary">{{ __('Enregistrer') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">{{ __('Aucun canal configuré.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Create modal --}}
<div class="modal fade" id="createChannelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.ai.channels.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Nouveau canal') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Nom') }} *</label>
                        <input type="text" class="form-control" name="name" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Type') }} *</label>
                        <select class="form-select" name="type" required>
                            <option value="">{{ __('Sélectionnez un type') }}</option>
                            <option value="email">Email</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="telegram">Telegram</option>
                            <option value="sms">SMS</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Créer') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
