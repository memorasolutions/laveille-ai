<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => $team->name, 'subtitle' => 'Équipes'])

@section('breadcrumbs')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.teams.index') }}">Equipes</a></li>
        <li class="breadcrumb-item active" aria-current="page">Details</li>
    </ol>
</nav>
@endsection

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.teams.index') }}">Équipes</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $team->name }}</li>
    </ol>
</nav>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
@endif

{{-- ===========================
     SECTION 1 : Infos équipe
     =========================== --}}
<div class="card mb-4">
    <div class="card-header py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">
            <i data-lucide="users" class="me-2"></i>{{ $team->name }}
        </h5>
        <a href="{{ route('admin.teams.edit', $team) }}" class="btn btn-sm btn-outline-primary">
            <i data-lucide="pencil" class="me-1"></i> Modifier
        </a>
    </div>
    <div class="card-body p-4">
        <div class="row">
            <div class="col-md-8">
                @if($team->description)
                    <p class="text-muted mb-0">{{ $team->description }}</p>
                @else
                    <p class="text-muted fst-italic mb-0">Aucune description renseignée.</p>
                @endif
            </div>
            <div class="col-md-4">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted fw-normal small">Propriétaire</dt>
                    <dd class="col-7">
                        <div class="d-flex align-items-center gap-1">
                            <i data-lucide="crown" class="text-warning" style="width:14px;height:14px"></i>
                            <span class="fw-semibold">{{ $team->owner->name ?? '—' }}</span>
                        </div>
                    </dd>

                    <dt class="col-5 text-muted fw-normal small">Membres</dt>
                    <dd class="col-7">
                        <span class="badge bg-primary rounded-pill">{{ $team->members->count() }}</span>
                    </dd>

                    <dt class="col-5 text-muted fw-normal small">Créée le</dt>
                    <dd class="col-7 text-muted small">{{ $team->created_at->format('d/m/Y') }}</dd>

                    <dt class="col-5 text-muted fw-normal small">Modifiée le</dt>
                    <dd class="col-7 text-muted small">{{ $team->updated_at->format('d/m/Y') }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

{{-- ===========================
     SECTION 2 : Membres
     =========================== --}}
<div class="card mb-4">
    <div class="card-header py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">
            <i data-lucide="user" class="me-2"></i>Membres
            <span class="badge bg-secondary ms-1 fw-normal" style="font-size:.75rem">{{ $team->members->count() }}</span>
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" aria-label="Membres de l'équipe {{ $team->name }}">
                <thead>
                    <tr>
                        <th scope="col">Membre</th>
                        <th scope="col" class="d-none d-md-table-cell">E-mail</th>
                        <th scope="col" class="d-none d-sm-table-cell" style="width:160px">Rôle</th>
                        <th scope="col" class="text-end" style="width:120px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($team->members as $member)
                    @php
                        $isOwner = $member->id === $team->owner_id;
                        $memberRole = $member->pivot->role ?? 'member';
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($isOwner)
                                    <i data-lucide="crown" class="text-warning" style="width:15px;height:15px" title="Propriétaire"></i>
                                @else
                                    <i data-lucide="user" class="text-muted" style="width:15px;height:15px"></i>
                                @endif
                                <span class="fw-semibold">{{ $member->name }}</span>
                                @if($isOwner)
                                    <span class="badge bg-warning text-dark ms-1">Propriétaire</span>
                                @endif
                            </div>
                        </td>
                        <td class="text-muted d-none d-md-table-cell">{{ $member->email }}</td>
                        <td class="d-none d-sm-table-cell">
                            @if($isOwner)
                                <span class="badge bg-warning text-dark">
                                    <i data-lucide="crown" style="width:12px;height:12px"></i> Propriétaire
                                </span>
                            @else
                                <form action="{{ route('admin.teams.members.role', [$team, $member]) }}"
                                      method="POST"
                                      class="d-flex align-items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role"
                                            class="form-select form-select-sm"
                                            aria-label="Rôle de {{ $member->name }}"
                                            onchange="this.form.submit()">
                                        <option value="admin" @selected($memberRole === 'admin')>
                                            Administrateur
                                        </option>
                                        <option value="member" @selected($memberRole === 'member')>
                                            Membre
                                        </option>
                                    </select>
                                </form>
                            @endif
                        </td>
                        <td class="text-end">
                            @if(!$isOwner)
                                <form action="{{ route('admin.teams.members.remove', [$team, $member]) }}"
                                      method="POST"
                                      class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            aria-label="Retirer {{ $member->name }} de l'équipe"
                                            title="Retirer ce membre"
                                            onclick="return confirm('Retirer {{ addslashes($member->name) }} de l\'équipe ?')">
                                        <i data-lucide="user-minus"></i>
                                    </button>
                                </form>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            Aucun membre dans cette équipe.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ===========================
     SECTION 3 : Invitations en attente
     =========================== --}}
<div class="card mb-4">
    <div class="card-header py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">
            <i data-lucide="mail" class="me-2"></i>Invitations en attente
            @if($team->pendingInvitations->count() > 0)
                <span class="badge bg-warning text-dark ms-1 fw-normal" style="font-size:.75rem">
                    {{ $team->pendingInvitations->count() }}
                </span>
            @endif
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" aria-label="Invitations en attente">
                <thead>
                    <tr>
                        <th scope="col">E-mail invité</th>
                        <th scope="col" class="d-none d-sm-table-cell" style="width:160px">Rôle proposé</th>
                        <th scope="col" class="d-none d-md-table-cell" style="width:140px">Invitée le</th>
                        <th scope="col" class="d-none d-md-table-cell" style="width:140px">Expire le</th>
                        <th scope="col" class="text-end" style="width:100px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($team->pendingInvitations as $invitation)
                    @php
                        $expired = $invitation->expires_at && $invitation->expires_at->isPast();
                    @endphp
                    <tr class="{{ $expired ? 'table-warning' : '' }}">
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i data-lucide="mail" class="text-muted" style="width:15px;height:15px"></i>
                                {{ $invitation->email }}
                                @if($expired)
                                    <span class="badge bg-warning text-dark ms-1">Expirée</span>
                                @endif
                            </div>
                        </td>
                        <td class="d-none d-sm-table-cell">
                            @if($invitation->role === 'admin')
                                <span class="badge bg-danger">
                                    <i data-lucide="shield" style="width:12px;height:12px"></i> Administrateur
                                </span>
                            @else
                                <span class="badge bg-secondary">
                                    <i data-lucide="user" style="width:12px;height:12px"></i> Membre
                                </span>
                            @endif
                        </td>
                        <td class="text-muted small d-none d-md-table-cell">
                            {{ $invitation->created_at->format('d/m/Y à H\hi') }}
                        </td>
                        <td class="small d-none d-md-table-cell {{ $expired ? 'text-danger fw-semibold' : 'text-muted' }}">
                            @if($invitation->expires_at)
                                {{ $invitation->expires_at->format('d/m/Y à H\hi') }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <form action="{{ route('admin.teams.invitations.cancel', [$team, $invitation]) }}"
                                  method="POST"
                                  class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="btn btn-sm btn-outline-danger"
                                        aria-label="Annuler l'invitation pour {{ $invitation->email }}"
                                        title="Annuler l'invitation"
                                        onclick="return confirm('Annuler l\'invitation envoyée à {{ addslashes($invitation->email) }} ?')">
                                    <i data-lucide="x"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i data-lucide="check-circle" class="me-1" style="width:16px;height:16px"></i>
                            Aucune invitation en attente.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ===========================
     SECTION 4 : Inviter un membre
     =========================== --}}
<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <h5 class="fw-bold mb-0">
            <i data-lucide="user-plus" class="me-2"></i>Inviter un membre
        </h5>
    </div>
    <div class="card-body p-4">
        @if($errors->hasBag('invite') || $errors->has('email') || $errors->has('role'))
            <div class="alert alert-danger mb-4" role="alert">
                <ul class="mb-0">
                    @foreach(($errors->getBag('invite') ?? $errors)->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.teams.invite', $team) }}" method="POST" novalidate>
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="invite_email" class="form-label fw-semibold">
                        Adresse e-mail <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <input type="email"
                           class="form-control @error('email') is-invalid @enderror"
                           id="invite_email"
                           name="email"
                           value="{{ old('email') }}"
                           required
                           placeholder="prenom.nom@exemple.com"
                           aria-required="true"
                           autocomplete="off">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label for="invite_role" class="form-label fw-semibold">Rôle</label>
                    <select class="form-select @error('role') is-invalid @enderror"
                            id="invite_role"
                            name="role"
                            aria-label="Rôle de l'invité">
                        <option value="member" @selected(old('role', 'member') === 'member')>Membre</option>
                        <option value="admin" @selected(old('role') === 'admin')>Administrateur</option>
                    </select>
                    @error('role')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i data-lucide="user-plus" class="me-1"></i> Envoyer l'invitation
                    </button>
                </div>
            </div>
            <div class="form-text mt-2">
                <i data-lucide="info" style="width:13px;height:13px"></i>
                Un e-mail d'invitation sera envoyé. Le lien expire après 7 jours.
            </div>
        </form>
    </div>
</div>

@endsection
