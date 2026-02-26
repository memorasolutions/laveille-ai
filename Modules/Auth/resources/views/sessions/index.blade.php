@extends('auth::layouts.app')

@section('title', __('Sessions actives'))

@section('content')

<div class="d-flex align-items-center gap-12 mb-20">
    <a href="{{ route('user.profile') }}" class="btn btn-outline-secondary radius-8">
        <iconify-icon icon="solar:arrow-left-outline"></iconify-icon>
    </a>
    <h1 class="fw-semibold mb-0">{{ __('Sessions actives') }}</h1>
</div>

@if(session('success'))
<div class="alert alert-success d-flex align-items-center gap-2 mb-20">
    <iconify-icon icon="solar:check-circle-outline"></iconify-icon>
    {{ session('success') }}
</div>
@endif

<div class="d-flex flex-column gap-12 mb-24">
    @forelse($sessions as $session)
    <div class="card">
        <div class="card-body d-flex align-items-center justify-content-between gap-16 flex-wrap">
            <div class="d-flex align-items-center gap-12">
                <div class="w-44-px h-44-px bg-primary-100 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                    <iconify-icon icon="solar:laptop-outline" class="text-primary-600 text-xl"></iconify-icon>
                </div>
                <div>
                    <p class="fw-semibold mb-4">{{ $session['parsed_agent']['browser'] }} {{ __('sur') }} {{ $session['parsed_agent']['os'] }}</p>
                    <p class="text-sm text-secondary-light mb-0">
                        IP : {{ $session['ip_address'] ?? __('Inconnu') }} &middot; {{ $session['last_activity_formatted'] }}
                    </p>
                </div>
            </div>
            <div class="flex-shrink-0">
                @if($session['is_current'])
                    <span class="badge text-sm fw-semibold px-16 py-9 radius-4 bg-success-focus text-success-main">
                        <iconify-icon icon="solar:check-circle-outline"></iconify-icon> {{ __('Session actuelle') }}
                    </span>
                @else
                    <form action="{{ route('user.sessions.revoke', $session['id']) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-danger-600 radius-8">
                            <iconify-icon icon="solar:close-circle-outline"></iconify-icon> {{ __('Révoquer') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="card">
        <div class="card-body py-40 text-center text-secondary-light">
            <iconify-icon icon="solar:laptop-outline" class="text-5xl mb-12 d-block"></iconify-icon>
            <p class="mb-0">{{ __('Aucune session active.') }}</p>
        </div>
    </div>
    @endforelse
</div>

{{-- Révoquer toutes les autres sessions --}}
<div class="card border border-danger-focus">
    <div class="card-header">
        <h5 class="card-title fw-semibold text-lg mb-0 text-danger-main d-flex align-items-center gap-2">
            <iconify-icon icon="solar:logout-3-outline"></iconify-icon>
            {{ __('Révoquer toutes les autres sessions') }}
        </h5>
    </div>
    <div class="card-body">
        <p class="text-secondary-light mb-16">{{ __('Déconnectez tous les autres appareils. Confirmez avec votre mot de passe.') }}</p>
        <form method="POST" action="{{ route('user.sessions.revoke-others') }}">
            @csrf
            <div class="d-flex align-items-start gap-12 flex-wrap">
                <div class="flex-grow-1">
                    <input type="password" name="password" required
                           placeholder="{{ __('Votre mot de passe actuel') }}"
                           class="form-control radius-8 @error('password') is-invalid @enderror">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-danger-600 radius-8 flex-shrink-0">
                    <iconify-icon icon="solar:logout-3-outline"></iconify-icon> {{ __('Révoquer tout') }}
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
