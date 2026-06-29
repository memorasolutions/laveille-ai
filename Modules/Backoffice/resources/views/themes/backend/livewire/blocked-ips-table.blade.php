{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
<div>
    <div class="card-header border-bottom py-3 px-4">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="alert-triangle" style="width:20px;height:20px;" class="text-warning"></i>
            <h5 class="mb-0 fw-semibold">
                {{ __('IPs bloquées') }}
                <span class="text-muted fw-normal fs-6">({{ $blockedIps->total() }})</span>
            </h5>
        </div>
    </div>

    @if($blockedIps->isEmpty())
        <div class="card-body p-5 text-center">
            <i data-lucide="shield-check" style="width:64px;height:64px;" class="text-success opacity-50 mb-3"></i>
            <p class="text-muted fw-medium mb-0">{{ __('Aucune IP bloquée.') }}</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">{{ __('IP') }}</th>
                        <th>{{ __('Raison') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Expire') }}</th>
                        <th class="pe-4">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($blockedIps as $ip)
                        <tr>
                            <td class="ps-4">
                                <code class="text-primary bg-primary bg-opacity-10 px-2 py-1 rounded">{{ $ip->ip_address }}</code>
                            </td>
                            <td class="text-muted small">{{ $ip->reason ?? '-' }}</td>
                            <td>
                                @if($ip->auto_blocked)
                                    <span class="badge bg-warning bg-opacity-10 text-warning">
                                        {{ __('Auto') }}
                                    </span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger">
                                        {{ __('Manuel') }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                {{ $ip->blocked_until?->format('Y-m-d H:i') ?? __('Permanent') }}
                            </td>
                            <td class="pe-4">
                                <button
                                    wire:click="unblock({{ $ip->id }})"
                                    wire:confirm="{{ __('Débloquer cette IP ?') }}"
                                    type="button"
                                    class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1"
                                    title="{{ __('Débloquer') }}"
                                >
                                    <i data-lucide="shield-check" style="width:14px;height:14px;"></i>
                                    {{ __('Débloquer') }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-top">
            <span class="text-muted small">{{ $blockedIps->total() }} {{ __('entrée(s)') }}</span>
            @include('backoffice::partials.infinite-scroll', ['paginator' => $blockedIps])
        </div>
    @endif
</div>
