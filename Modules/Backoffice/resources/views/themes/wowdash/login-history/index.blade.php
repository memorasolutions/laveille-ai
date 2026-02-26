@extends('backoffice::layouts.admin', ['title' => $title, 'subtitle' => $subtitle])

@section('content')
<div class="card h-100 p-0 radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center flex-wrap gap-3 justify-content-between">
        <h6 class="mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:shield-keyhole-outline" class="icon text-xl"></iconify-icon>
            {{ __('Tentatives de connexion') }} ({{ $attempts->total() }})
        </h6>
    </div>
    <div class="card-body p-0">
        @if($attempts->isEmpty())
            <div class="text-center py-40">
                <iconify-icon icon="solar:shield-check-outline" class="text-6xl text-secondary-light mb-16"></iconify-icon>
                <p class="text-secondary-light">{{ __('Aucune tentative de connexion enregistrée.') }}</p>
            </div>
        @else
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Utilisateur') }}</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('IP') }}</th>
                            <th>{{ __('Navigateur') }}</th>
                            <th>{{ __('Statut') }}</th>
                            <th>{{ __('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attempts as $attempt)
                            <tr>
                                <td>{{ $attempt->user?->name ?? '-' }}</td>
                                <td>{{ $attempt->email }}</td>
                                <td><code class="text-primary-600 text-sm">{{ $attempt->ip_address }}</code></td>
                                <td class="text-sm text-secondary-light">{{ \Illuminate\Support\Str::limit($attempt->user_agent, 40) }}</td>
                                <td>
                                    <span class="badge {{ $attempt->status === 'success' ? 'bg-success-focus text-success-main' : 'bg-danger-focus text-danger-main' }}">
                                        {{ $attempt->status === 'success' ? __('Succès') : __('Échec') }}
                                    </span>
                                </td>
                                <td class="text-sm text-secondary-light">{{ $attempt->logged_in_at->format('Y-m-d H:i:s') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center px-24 py-16">
                <span class="text-secondary-light text-sm">{{ $attempts->total() }} {{ __('entrée(s)') }}</span>
                {{ $attempts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
