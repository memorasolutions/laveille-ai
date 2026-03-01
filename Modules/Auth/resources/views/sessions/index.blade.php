<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.app')

@section('title', __('Sessions actives'))

@section('content')

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('user.profile') }}" class="btn btn-outline-secondary rounded-2">
        <i data-lucide="arrow-left"></i>
    </a>
    <h1 class="fw-semibold mb-0">{{ __('Sessions actives') }}</h1>
</div>

@if(session('success'))
<div class="alert alert-success d-flex align-items-center gap-2 mb-3">
    <i data-lucide="check-circle"></i>
    {{ session('success') }}
</div>
@endif

<div class="d-flex flex-column gap-2 mb-4">
    @forelse($sessions as $session)
    <div class="card">
        <div class="card-body d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                    <i data-lucide="monitor-smartphone" class="text-primary"></i>
                </div>
                <div>
                    <p class="fw-semibold mb-1">{{ $session['parsed_agent']['browser'] }} {{ __('sur') }} {{ $session['parsed_agent']['os'] }}</p>
                    <p class="text-sm text-muted mb-0">
                        IP : {{ $session['ip_address'] ?? __('Inconnu') }} &middot; {{ $session['last_activity_formatted'] }}
                    </p>
                </div>
            </div>
            <div class="flex-shrink-0">
                @if($session['is_current'])
                    <span class="badge fw-semibold bg-success bg-opacity-10 text-success rounded-1 px-3 py-2">
                        <i data-lucide="check-circle"></i> {{ __('Session actuelle') }}
                    </span>
                @else
                    <form action="{{ route('user.sessions.revoke', $session['id']) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-danger rounded-2">
                            <i data-lucide="x-circle"></i> {{ __('Révoquer') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="card">
        <div class="card-body py-5 text-center text-muted">
            <i data-lucide="monitor-smartphone" class="d-block mx-auto mb-2" style="width:48px;height:48px;"></i>
            <p class="mb-0">{{ __('Aucune session active.') }}</p>
        </div>
    </div>
    @endforelse
</div>

{{-- Révoquer toutes les autres sessions --}}
<div class="card border border-danger border-opacity-25">
    <div class="card-header">
        <h5 class="card-title fw-semibold mb-0 text-danger d-flex align-items-center gap-2">
            <i data-lucide="log-out"></i>
            {{ __('Révoquer toutes les autres sessions') }}
        </h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">{{ __('Déconnectez tous les autres appareils. Confirmez avec votre mot de passe.') }}</p>
        <form method="POST" action="{{ route('user.sessions.revoke-others') }}">
            @csrf
            <div class="d-flex align-items-start gap-2 flex-wrap">
                <div class="flex-grow-1">
                    <input type="password" name="password" required
                           placeholder="{{ __('Votre mot de passe actuel') }}"
                           class="form-control rounded-2 @error('password') is-invalid @enderror">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-danger rounded-2 flex-shrink-0">
                    <i data-lucide="log-out"></i> {{ __('Révoquer tout') }}
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
