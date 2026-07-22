{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
<div>
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
                            {{ format_date($notification->created_at, 'datetime') }}
                        </td>
                        <td class="align-middle">
                            @if($notification->read_at)
                                <span class="small text-muted">{{ __('Lu') }}</span>
                            @else
                                <span class="small fw-semibold text-primary">{{ __('Non lu') }}</span>
                            @endif
                        </td>
                        <td class="align-middle text-center">
                            @can('manage_notifications')
                                <button
                                    wire:click="deleteNotification('{{ $notification->id }}')"
                                    wire:confirm="{{ __('Supprimer cette notification ?') }}"
                                    type="button"
                                    class="btn btn-light btn-sm d-inline-flex align-items-center justify-content-center text-danger"
                                    style="width:36px;height:36px;"
                                    title="{{ __('Supprimer') }}"
                                >
                                    <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                </button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <i data-lucide="bell" class="d-block mx-auto mb-2" style="width:48px;height:48px;opacity:0.3;"></i>
                            <p class="small fw-medium text-muted mb-0">{{ __('Aucune notification') }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($notifications->hasPages())
        <div class="px-3 py-3 border-top">
            @include('backoffice::partials.infinite-scroll', ['paginator' => $notifications])
        </div>
    @endif
</div>
