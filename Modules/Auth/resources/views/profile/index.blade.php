<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.user-frontend')

@section('title', __('Mon profil') . ' - ' . config('app.name'))

@section('user-content')

<h2 style="font-family: var(--f-heading, inherit); font-weight: 700; margin: 0 0 5px;">{{ __('Mon profil') }}</h2>
<p style="color: #777; margin: 0 0 25px;">{{ __('Gérez vos informations personnelles.') }}</p>

<div class="row">

    {{-- Colonne gauche : avatar + infos --}}
    <div class="col-md-4" style="margin-bottom: 20px;">
        <div class="panel panel-default">
            <div class="panel-body" style="text-align: center; padding: 25px 15px;">
                @if($user->avatar)
                    <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}"
                         style="width: 96px; height: 96px; border-radius: 50%; object-fit: cover; border: 3px solid #dbe9fe; margin-bottom: 15px;">
                @else
                    <div style="width: 96px; height: 96px; border-radius: 50%; background: #337ab7; color: #fff; display: flex !important; align-items: center !important; justify-content: center !important; font-weight: 700; font-size: 36px; margin: 0 auto 15px;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
                <h4 style="font-weight: 700; margin: 0 0 3px;">{{ $user->name }}</h4>
                <p style="color: #777; font-size: 13px; margin: 0 0 8px;">{{ $user->email }}</p>
                @if($user->bio)
                    <p style="color: #999; font-style: italic; font-size: 13px; margin: 0 0 10px;">{{ $user->bio }}</p>
                @endif
                @foreach($user->getRoleNames() as $role)
                    <span class="label label-primary" style="margin-right: 3px;">{{ ucfirst($role) }}</span>
                @endforeach
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading"><h3 class="panel-title">{{ __('Activité') }}</h3></div>
            <div class="panel-body">
                <div style="display: flex !important; justify-content: space-between !important; margin-bottom: 8px; font-size: 13px;">
                    <span style="color: #777;">{{ __('Membre depuis') }}</span>
                    <strong>{{ $user->created_at->format('d M Y') }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Colonne droite : formulaires --}}
    <div class="col-md-8">

        {{-- Informations personnelles --}}
        <div class="panel panel-default" style="margin-bottom: 20px;">
            <div class="panel-heading"><h3 class="panel-title">{{ __('Informations personnelles') }}</h3></div>
            <div class="panel-body">
                <form method="POST" action="{{ route('user.profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    <div class="row">
                        <div class="col-sm-6" style="margin-bottom: 15px;">
                            <label class="control-label">{{ __('Nom complet') }} <span style="color: #d9534f;">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                   class="form-control {{ $errors->has('name') ? 'has-error' : '' }}">
                            @if($errors->has('name'))<p class="help-block" style="color: #d9534f;">{{ $errors->first('name') }}</p>@endif
                        </div>
                        <div class="col-sm-6" style="margin-bottom: 15px;">
                            <label class="control-label">{{ __('Adresse courriel') }} <span style="color: #d9534f;">*</span></label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email"
                                   class="form-control {{ $errors->has('email') ? 'has-error' : '' }}">
                            @if($errors->has('email'))<p class="help-block" style="color: #d9534f;">{{ $errors->first('email') }}</p>@endif
                        </div>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label class="control-label">{{ __('Biographie') }}</label>
                        <textarea name="bio" rows="3" class="form-control"
                                  placeholder="{{ __('Quelques mots sur vous...') }}">{{ old('bio', $user->bio) }}</textarea>
                        @if($errors->has('bio'))<p class="help-block" style="color: #d9534f;">{{ $errors->first('bio') }}</p>@endif
                        <p class="help-block">{{ __('Max. 500 caractères') }}</p>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <x-core::file-upload
                            name="avatar"
                            :label="__('Photo de profil')"
                            accept="image/*"
                            :max-size="2"
                            :max-width="800"
                            :current-image="$user->avatar ? asset('storage/' . $user->avatar) : ''"
                            :help-text="__('PNG, JPG, WebP - max. 2 Mo')"
                        />
                        @if($errors->has('avatar'))<p class="help-block" style="color: #d9534f;">{{ $errors->first('avatar') }}</p>@endif
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('Mettre à jour le profil') }}</button>
                </form>
            </div>
        </div>

        {{-- Préférences de notification --}}
        <div class="panel panel-default" style="margin-bottom: 20px;">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-bell"></i> {{ __('Préférences de notification') }}</h3>
            </div>
            <div class="panel-body">
                <p style="color: #777; margin-bottom: 15px;">{{ __('Choisissez la fréquence de réception de vos notifications par courriel.') }}</p>
                <form method="POST" action="{{ route('user.notifications.updateFrequency') }}">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    <div class="radio" style="margin-bottom: 10px;">
                        <label style="font-weight: 400;">
                            <input type="radio" name="notification_frequency" value="immediate"
                                   {{ old('notification_frequency', $user->notification_frequency) == 'immediate' ? 'checked' : '' }}>
                            <strong>{{ __('Immédiate') }}</strong> — <small style="color: #777;">{{ __('Recevoir chaque notification par courriel dès qu\'elle arrive') }}</small>
                        </label>
                    </div>
                    <div class="radio" style="margin-bottom: 10px;">
                        <label style="font-weight: 400;">
                            <input type="radio" name="notification_frequency" value="daily"
                                   {{ old('notification_frequency', $user->notification_frequency) == 'daily' ? 'checked' : '' }}>
                            <strong>{{ __('Résumé quotidien') }}</strong> — <small style="color: #777;">{{ __('Un seul courriel par jour') }}</small>
                        </label>
                    </div>
                    <div class="radio" style="margin-bottom: 15px;">
                        <label style="font-weight: 400;">
                            <input type="radio" name="notification_frequency" value="weekly"
                                   {{ old('notification_frequency', $user->notification_frequency) == 'weekly' ? 'checked' : '' }}>
                            <strong>{{ __('Résumé hebdomadaire') }}</strong> — <small style="color: #777;">{{ __('Un seul courriel par semaine') }}</small>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('Enregistrer') }}</button>
                </form>
            </div>
        </div>

        {{-- Export données RGPD --}}
        <div class="panel panel-default" style="margin-bottom: 20px;">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-download"></i> {{ __('Exporter mes données') }}</h3>
            </div>
            <div class="panel-body">
                <p style="color: #777; margin-bottom: 15px;">{{ __('Téléchargez une copie de vos données personnelles au format JSON.') }}</p>
                <a href="{{ route('user.export-data') }}" class="btn btn-default">
                    <i class="fa fa-file-text-o"></i> {{ __('Exporter mes données (JSON)') }}
                </a>
            </div>
        </div>

        {{-- Suppression de compte --}}
        <div class="panel panel-danger" style="margin-bottom: 20px;">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-trash"></i> {{ __('Supprimer mon compte') }}</h3>
            </div>
            <div class="panel-body">
                <p style="color: #777; margin-bottom: 15px;">{{ __('Cette action est irréversible. Toutes vos données seront définitivement supprimées.') }}</p>

                @if(session('delete_error'))
                    <div class="alert alert-danger" style="margin-bottom: 15px;">{{ session('delete_error') }}</div>
                @endif

                <form method="POST" action="{{ route('user.account.delete') }}"
                      onsubmit="return confirm('{{ __('Êtes-vous sûr ? Cette action est IRRÉVERSIBLE.') }}');">
                    @csrf
                    <input type="hidden" name="_method" value="DELETE">
                    <div style="margin-bottom: 15px;">
                        <label class="control-label">{{ __('Confirmez avec votre mot de passe') }}</label>
                        <input type="password" name="password" required
                               placeholder="{{ __('Votre mot de passe actuel') }}"
                               class="form-control" style="max-width: 320px;">
                        @if($errors->has('password'))<p class="help-block" style="color: #d9534f;">{{ $errors->first('password') }}</p>@endif
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa fa-trash"></i> {{ __('Supprimer définitivement mon compte') }}
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

@endsection
