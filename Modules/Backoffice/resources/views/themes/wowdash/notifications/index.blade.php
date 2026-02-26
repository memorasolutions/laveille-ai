@extends('backoffice::layouts.admin', ['title' => 'Notifications', 'subtitle' => 'Liste'])

@section('content')

<div class="card h-100 p-0 radius-12 mb-24">
    <div class="card-header border-bottom bg-base py-16 px-24">
        <h6 class="mb-0">Diffuser une alerte système</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.notifications.broadcast') }}" method="POST">
            @csrf
            <div class="row gy-3">
                <div class="col-md-3">
                    <label class="form-label">Niveau</label>
                    <select name="level" class="form-select @error('level') is-invalid @enderror">
                        <option value="info" {{ old('level') === 'info' ? 'selected' : '' }}>Information</option>
                        <option value="warning" {{ old('level') === 'warning' ? 'selected' : '' }}>Avertissement</option>
                        <option value="critical" {{ old('level') === 'critical' ? 'selected' : '' }}>Critique</option>
                    </select>
                    @error('level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-9">
                    <label class="form-label">Message <span class="text-danger-main">*</span></label>
                    <textarea name="message" rows="2" class="form-control @error('message') is-invalid @enderror" placeholder="Message à diffuser à tous les utilisateurs...">{{ old('message') }}</textarea>
                    @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="d-flex gap-3 mt-24">
                <button type="submit" class="btn btn-primary-600">
                    <iconify-icon icon="solar:bell-bing-outline" class="icon text-xl me-1"></iconify-icon>
                    Diffuser à tous les utilisateurs
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card h-100 p-0 radius-12">
    <div class="card-body p-0">
        <div class="table-responsive scroll-sm">
            <table class="table bordered-table sm-table mb-0">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notifications as $notification)
                        <tr class="{{ $notification->read_at ? '' : 'bg-primary-50' }}">
                            <td>
                                <span class="badge bg-{{ $notification->read_at ? 'neutral-200 text-neutral-600' : 'primary-100 text-primary-600' }}">
                                    {{ class_basename($notification->type) }}
                                </span>
                            </td>
                            <td>
                                <p class="fw-semibold mb-1">{{ $notification->data['title'] ?? 'Notification' }}</p>
                                <p class="text-sm text-secondary-light mb-0">{{ $notification->data['message'] ?? '' }}</p>
                            </td>
                            <td>{{ $notification->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @if($notification->read_at)
                                    <span class="text-secondary-light">Lu</span>
                                @else
                                    <span class="text-primary-600 fw-semibold">Non lu</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button type="button" class="w-40-px h-40-px bg-neutral-200 text-secondary-light rounded-circle d-flex justify-content-center align-items-center" data-bs-toggle="dropdown">
                                        <iconify-icon icon="tabler:dots-vertical" class="icon text-xl"></iconify-icon>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end p-12">
                                        <form action="{{ route('admin.notifications.destroy', $notification->id) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-danger-600" onclick="return confirm('Supprimer cette notification ?')">
                                                <iconify-icon icon="fluent:delete-24-regular" class="icon"></iconify-icon> Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary-light py-20">Aucune notification</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($notifications->hasPages())
        <div class="card-footer">
            {{ $notifications->links() }}
        </div>
    @endif
</div>

@endsection
