<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.user-frontend')
@section('title', __('Clés d\'accès').' - '.config('app.name'))
@section('user-content')
<h2 style="font-family: var(--f-heading, inherit); font-weight: 700; margin: 0 0 5px;">{{ __('Clés d\'accès') }}</h2>
<p style="color: #777; margin: 0 0 25px;">{{ __('Gérez vos clés d\'accès (passkeys) pour vous connecter sans mot de passe.') }}</p>
<div class="panel panel-default">
    <div class="panel-body" style="padding: 25px 15px;">
        @livewire('passkeys')
    </div>
</div>
@endsection
