<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Notifications', 'subtitle' => 'Liste'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Notifications') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="bell" class="icon-md text-primary"></i>{{ __('Notifications') }}</h4>
</div>

{{-- Diffuser une alerte système --}}
<div class="card mb-3">
    <div class="card-header">
        <h5 class="mb-0 fw-semibold">{{ __('Diffuser une alerte système') }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.notifications.broadcast') }}" method="POST">
            @csrf
            <div class="d-flex flex-wrap gap-3">
                <div style="width:12rem;">
                    <label class="form-label fw-semibold small">{{ __('Niveau') }}</label>
                    <select name="level"
                            class="form-select form-select-sm @error('level') is-invalid @enderror">
                        <option value="info" {{ old('level') === 'info' ? 'selected' : '' }}>{{ __('Information') }}</option>
                        <option value="warning" {{ old('level') === 'warning' ? 'selected' : '' }}>{{ __('Avertissement') }}</option>
                        <option value="critical" {{ old('level') === 'critical' ? 'selected' : '' }}>{{ __('Critique') }}</option>
                    </select>
                    @error('level')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="flex-grow-1" style="min-width:260px;">
                    <label class="form-label fw-semibold small">
                        {{ __('Message') }} <span class="text-danger">*</span>
                    </label>
                    <textarea name="message" rows="2"
                              class="form-control form-control-sm @error('message') is-invalid @enderror"
                              placeholder="{{ __('Message à diffuser à tous les utilisateurs...') }}">{{ old('message') }}</textarea>
                    @error('message')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2">
                    <i data-lucide="bell"></i>
                    {{ __('Diffuser à tous les utilisateurs') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Liste des notifications --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="fw-semibold small text-muted">{{ __('Type') }}</th>
                        <th class="fw-semibold small text-muted">{{ __('Message') }}</th>
                        <th class="fw-semibold small text-muted">{{ __('Date') }}</th>
                        <th class="fw-semibold small text-muted">{{ __('Statut') }}</th>
                        <th class="fw-semibold small text-muted text-center">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notifications as $notification)
                        <tr class="{{ $notification->read_at ? '' : 'table-primary bg-opacity-25' }}">
                            <td class="align-middle">
                                @if($notification->read_at)
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                        {{ class_basename($notification->type) }}
                                    </span>
                                @else
                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                        {{ class_basename($notification->type) }}
                                    </span>
                                @endif
                            </td>
                            <td class="align-middle">
                                <p class="fw-semibold small text-body mb-0">{{ $notification->data['title'] ?? 'Notification' }}</p>
                                <p class="small text-muted mb-0">{{ $notification->data['message'] ?? '' }}</p>
                            </td>
                            <td class="align-middle small text-muted text-nowrap">
                                {{ $notification->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="align-middle">
                                @if($notification->read_at)
                                    <span class="small text-muted">{{ __('Lu') }}</span>
                                @else
                                    <span class="small fw-semibold text-primary">{{ __('Non lu') }}</span>
                                @endif
                            </td>
                            <td class="align-middle text-center">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm d-inline-flex align-items-center justify-content-center"
                                            style="width:36px;height:36px;"
                                            type="button"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                        <i data-lucide="more-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <form action="{{ route('admin.notifications.destroy', $notification->id) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                        onclick="return confirm('{{ __('Supprimer cette notification ?') }}')"
                                                        class="dropdown-item text-danger d-flex align-items-center gap-2">
                                                    <i data-lucide="trash-2"></i>
                                                    {{ __('Supprimer') }}
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i data-lucide="bell" class="icon-xxl text-muted mb-3 d-block mx-auto" style="width:48px;height:48px;opacity:0.3;"></i>
                                <p class="small fw-medium text-muted mb-0">{{ __('Aucune notification') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($notifications->hasPages())
            <div class="px-3 py-3 border-top">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>

@endsection
