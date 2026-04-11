<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.user-frontend')

@section('title', __('Connexion') . ' - ' . config('app.name'))

@section('user-content')
<div style="max-width:520px;margin:40px auto;background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.08);padding:36px">
    <h2 style="margin:0 0 8px;font-size:22px;text-align:center">{{ __('Bienvenue sur') }} <strong>laveille.ai</strong></h2>
    <p style="text-align:center;color:#666;font-size:14px;margin:0 0 24px">{{ __('Pas de mot de passe requis — un code de connexion vous sera envoyé par courriel.') }}</p>

    @if(session('status'))
        <div class="alert alert-success" style="border-radius:8px">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" style="border-radius:8px">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('magic-link.request') }}">
        @csrf
        <div class="form-group" style="margin-bottom:14px">
            <input type="email" name="email" class="form-control" required autofocus autocomplete="email"
                   placeholder="votre@courriel.com" value="{{ old('email') }}"
                   style="border-radius:8px;height:44px;font-size:15px">
        </div>
        <button type="submit" class="btn btn-block"
                style="background:#0B7285;color:#fff;border:none;border-radius:8px;height:44px;font-size:15px;font-weight:600;width:100%">
            {{ __('Recevoir mon code d\'accès') }}
        </button>
    </form>

    <hr style="margin:28px 0 20px;border-color:#eee">

    <p style="font-size:14px;color:#555;margin:0 0 12px;font-weight:600">{{ __('En vous connectant, vous pourrez :') }}</p>
    <ul style="list-style:none;padding:0;margin:0;font-size:14px;color:#444;line-height:2.2">
        <li>🔗 {{ __('Gérer vos liens courts et leurs statistiques') }}</li>
        <li>⏰ {{ __('Prolonger l\'expiration de vos liens') }}</li>
        <li>📊 {{ __('Consulter vos votes et contributions') }}</li>
        <li>💾 {{ __('Sauvegarder vos favoris') }}</li>
        <li>📰 {{ __('Personnaliser vos notifications') }}</li>
    </ul>
</div>
@endsection
