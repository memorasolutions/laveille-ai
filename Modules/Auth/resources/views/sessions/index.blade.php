<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.user-frontend')

@section('title', __('Sessions actives') . ' - ' . config('app.name'))

@section('user-content')

<div style="display: flex !important; justify-content: space-between !important; align-items: flex-start !important; flex-wrap: wrap !important; margin-bottom: 20px;">
    <div>
        <h2 style="font-family: var(--f-heading, inherit); font-weight: 700; margin: 0 0 5px;">{{ __('Sessions actives') }}</h2>
        <p style="color: #777; margin: 0;">{{ __('Gérez les appareils connectés à votre compte.') }}</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="border-radius: 4px; margin-bottom: 20px;">
    {{ session('success') }}
</div>
@endif

<div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px;">
    @forelse($sessions as $session)
    <div style="background: #fff; border: 1px solid #e5e5e5; border-radius: 6px; padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(11,114,133,0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 18px;">
                💻
            </div>
            <div>
                <p style="font-weight: 600; margin: 0 0 4px;">{{ $session['parsed_agent']['browser'] }} {{ __('sur') }} {{ $session['parsed_agent']['os'] }}</p>
                <p style="font-size: 12px; color: #999; margin: 0;">
                    IP : {{ $session['ip_address'] ?? __('Inconnu') }} · {{ $session['last_activity_formatted'] }}
                </p>
            </div>
        </div>
        <div style="flex-shrink: 0;">
            @if($session['is_current'])
                <span style="font-weight: 600; background: #d4edda; color: #155724; padding: 4px 12px; border-radius: 4px; font-size: 13px;">
                    {{ __('Session actuelle') }}
                </span>
            @else
                <form action="{{ route('user.sessions.revoke', $session['id']) }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn btn-xs" style="background: var(--c-danger); color: #fff; border: none; border-radius: 4px; padding: 4px 12px; font-size: 13px; cursor: pointer;">
                        {{ __('Révoquer') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
    @empty
    <div style="background: #fff; border: 1px solid #e5e5e5; border-radius: 6px; padding: 40px 16px; text-align: center; color: #999;">
        <p style="margin: 0;">{{ __('Aucune session active.') }}</p>
    </div>
    @endforelse
</div>

<div style="background: #fff; border: 1px solid #e5e5e5; border-radius: 6px; overflow: hidden;">
    <div style="padding: 12px 16px; border-bottom: 1px solid #fecaca; background: #fef2f2;">
        <h4 style="font-weight: 600; margin: 0; color: var(--c-danger); font-size: 15px;">
            {{ __('Révoquer toutes les autres sessions') }}
        </h4>
    </div>
    <div style="padding: 16px;">
        <p style="color: #777; margin: 0 0 12px; font-size: 14px;">{{ __('Déconnectez tous les autres appareils. Confirmez avec votre mot de passe.') }}</p>
        <form method="POST" action="{{ route('user.sessions.revoke-others') }}">
            @csrf
            <div style="display: flex; align-items: flex-start; gap: 8px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <input type="password" name="password" required
                           placeholder="{{ __('Votre mot de passe actuel') }}"
                           style="width: 100%; padding: 8px 12px; border: 1px solid {{ $errors->has('password') ? '#dc3545' : '#ddd' }}; border-radius: 4px; font-size: 14px;">
                    @error('password')
                        <div style="color: #dc3545; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-sm" style="background: var(--c-danger); color: #fff; border: none; border-radius: 4px; padding: 8px 16px; font-size: 14px; cursor: pointer; white-space: nowrap;">
                    {{ __('Révoquer tout') }}
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
