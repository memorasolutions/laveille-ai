@extends('auth::layouts.app')

@section('title', __('Mon profil'))

@section('content')

<div class="mb-4">
    <h1 class="fw-semibold mb-1">{{ __('Mon profil') }}</h1>
    <p class="text-muted mb-0">{{ __('Gérez vos informations personnelles et votre sécurité.') }}</p>
</div>

<div class="row gy-4">

    {{-- Colonne gauche : avatar + stats --}}
    <div class="col-lg-4">

        <div class="card mb-4">
            <div class="card-body text-center py-4">
                @if($user->avatar)
                    <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}"
                         class="rounded-circle mb-3 object-fit-cover border"
                         style="width:96px;height:96px;border-width:3px!important;border-color:#dbe9fe!important;">
                @else
                    <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center fw-bold text-white"
                         style="width:96px;height:96px;background:#487FFF;font-size:36px;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
                <h5 class="fw-semibold mb-1">{{ $user->name }}</h5>
                <p class="text-muted text-sm mb-2">{{ $user->email }}</p>
                @if($user->bio)
                    <p class="text-sm text-muted mb-2 fst-italic">{{ $user->bio }}</p>
                @endif
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    @foreach($user->getRoleNames() as $role)
                        <span class="badge text-sm fw-semibold px-2 py-1 rounded-1 bg-primary bg-opacity-10 text-primary">
                            {{ ucfirst($role) }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title fw-semibold text-base mb-0">{{ __('Activité') }}</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2 text-sm">
                    <span class="text-muted">{{ __('Membre depuis') }}</span>
                    <span class="fw-semibold">{{ $user->created_at->format('d M Y') }}</span>
                </div>
                <div class="d-flex justify-content-between text-sm">
                    <span class="text-muted">{{ __('Articles publiés') }}</span>
                    <span class="fw-semibold">
                        {{ \Modules\Blog\Models\Article::where('user_id', $user->id)->where('status', 'published')->count() }}
                    </span>
                </div>
            </div>
        </div>

    </div>

    {{-- Colonne droite : formulaires --}}
    <div class="col-lg-8">

        {{-- Informations personnelles --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title fw-semibold mb-0">{{ __('Informations personnelles') }}</h5>
            </div>
            <div class="card-body">
                @if(session('success'))
                <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
                    <i data-lucide="check-circle"></i>
                    {{ session('success') }}
                </div>
                @endif

                <form method="POST" action="{{ route('user.profile.update') }}" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="row gy-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-medium text-muted">{{ __('Nom complet') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                   class="form-control rounded-2 @error('name') is-invalid @enderror">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-medium text-muted">{{ __('Adresse courriel') }} <span class="text-danger">*</span></label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                   class="form-control rounded-2 @error('email') is-invalid @enderror">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium text-muted">{{ __('Biographie') }}</label>
                        <textarea name="bio" rows="3"
                                  class="form-control rounded-2 @error('bio') is-invalid @enderror"
                                  placeholder="{{ __('Quelques mots sur vous...') }}">{{ old('bio', $user->bio) }}</textarea>
                        @error('bio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">{{ __('Max. 500 caractères') }}</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-medium text-muted">{{ __('Photo de profil') }}</label>
                        <div class="d-flex align-items-center gap-3">
                            @if($user->avatar)
                                <img src="{{ Storage::url($user->avatar) }}"
                                     class="rounded-circle object-fit-cover"
                                     style="width:48px;height:48px;" alt="Avatar actuel">
                            @else
                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                     style="width:48px;height:48px;background:#dbe9fe;color:#487FFF;font-size:18px;">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                            @endif
                            <div class="flex-grow-1">
                                <input type="file" name="avatar" accept="image/*" class="form-control rounded-2">
                                @error('avatar')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                <div class="form-text">{{ __('PNG, JPG, WebP - max. 2 Mo') }}</div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary rounded-2">{{ __('Mettre à jour le profil') }}</button>
                </form>
            </div>
        </div>

        {{-- Mot de passe --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title fw-semibold mb-0">{{ __('Changer le mot de passe') }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('user.password.update') }}">
                    @csrf @method('PUT')
                    <div class="row gy-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-medium text-muted">{{ __('Mot de passe actuel') }} <span class="text-danger">*</span></label>
                            <input type="password" name="current_password" required
                                   class="form-control rounded-2 @error('current_password') is-invalid @enderror">
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium text-muted">{{ __('Nouveau mot de passe') }} <span class="text-danger">*</span></label>
                            <input type="password" name="password" required minlength="8"
                                   class="form-control rounded-2">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium text-muted">{{ __('Confirmer') }} <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" required
                                   class="form-control rounded-2">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-primary rounded-2">{{ __('Modifier le mot de passe') }}</button>
                </form>
            </div>
        </div>

        {{-- Notifications push --}}
        @if(\Modules\Settings\Facades\Settings::get('push.web_push_enabled', false))
        <div class="card mb-4" x-data="pushToggle()">
            <div class="card-header">
                <h5 class="card-title fw-semibold mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="bell-ring" class="text-primary"></i>
                    {{ __('Notifications push') }}
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">{{ __('Recevez des notifications directement dans votre navigateur, même lorsque le site est fermé.') }}</p>
                <template x-if="!supported">
                    <p class="text-warning text-sm">{{ __('Les notifications push ne sont pas supportées par votre navigateur.') }}</p>
                </template>
                <template x-if="supported && permission === 'denied'">
                    <p class="text-danger text-sm">{{ __('Les notifications sont bloquées. Modifiez les paramètres de votre navigateur pour les autoriser.') }}</p>
                </template>
                <template x-if="supported && permission !== 'denied'">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="fw-medium" x-text="subscribed ? '{{ __('Activées') }}' : '{{ __('Désactivées') }}'"></span>
                        <div class="form-check form-switch switch-primary">
                            <input type="checkbox" class="form-check-input" role="switch"
                                   :checked="subscribed" @change="toggle()" :disabled="loading">
                        </div>
                    </div>
                </template>
                <p x-show="message" x-text="message" class="text-sm mt-2 text-success" x-cloak></p>
            </div>
        </div>
        <script>
        function pushToggle() {
            return {
                supported: 'PushManager' in window && 'serviceWorker' in navigator,
                permission: typeof Notification !== 'undefined' ? Notification.permission : 'denied',
                subscribed: false,
                loading: false,
                message: '',
                async init() {
                    if (!this.supported) return;
                    try {
                        const reg = await navigator.serviceWorker.ready;
                        const sub = await reg.pushManager.getSubscription();
                        this.subscribed = !!sub;
                    } catch(e) {}
                },
                async toggle() {
                    this.loading = true;
                    this.message = '';
                    try {
                        if (this.subscribed) {
                            const reg = await navigator.serviceWorker.ready;
                            const sub = await reg.pushManager.getSubscription();
                            if (sub) {
                                await fetch('/api/v1/push-subscriptions', {
                                    method: 'DELETE',
                                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''},
                                    credentials: 'same-origin',
                                    body: JSON.stringify({endpoint: sub.endpoint})
                                });
                                await sub.unsubscribe();
                            }
                            this.subscribed = false;
                            this.message = '{{ __("Notifications push désactivées.") }}';
                        } else {
                            if (Notification.permission === 'default') {
                                const p = await Notification.requestPermission();
                                if (p !== 'granted') { this.permission = p; this.loading = false; return; }
                            }
                            this.permission = Notification.permission;
                            const vapidKey = '{{ \Modules\Settings\Facades\Settings::get("push.vapid_public_key") }}';
                            const padding = '='.repeat((4 - vapidKey.length % 4) % 4);
                            const b64 = (vapidKey + padding).replace(/-/g, '+').replace(/_/g, '/');
                            const raw = atob(b64);
                            const arr = new Uint8Array(raw.length);
                            for (let i = 0; i < raw.length; ++i) arr[i] = raw.charCodeAt(i);

                            const reg = await navigator.serviceWorker.ready;
                            const sub = await reg.pushManager.subscribe({userVisibleOnly: true, applicationServerKey: arr});
                            const key = sub.getKey('p256dh');
                            const auth = sub.getKey('auth');
                            await fetch('/api/v1/push-subscriptions', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''},
                                credentials: 'same-origin',
                                body: JSON.stringify({
                                    endpoint: sub.endpoint,
                                    keys: {
                                        p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(key))),
                                        auth: btoa(String.fromCharCode.apply(null, new Uint8Array(auth)))
                                    }
                                })
                            });
                            this.subscribed = true;
                            this.message = '{{ __("Notifications push activées !") }}';
                        }
                    } catch(e) { this.message = '{{ __("Erreur lors de la modification.") }}'; }
                    this.loading = false;
                }
            };
        }
        </script>
        @endif

        {{-- Préférences de notification --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title fw-semibold mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="bell-ring" class="text-primary"></i>
                    {{ __('Préférences de notification') }}
                </h5>
            </div>
            <div class="card-body">
                @if(session('success') && str_contains(session('success'), 'notification'))
                <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
                    <i data-lucide="check-circle"></i>
                    {{ session('success') }}
                </div>
                @endif

                <p class="text-muted mb-3">{{ __('Choisissez la fréquence de réception de vos notifications par courriel.') }}</p>

                <form method="POST" action="{{ route('user.notifications.updateFrequency') }}">
                    @csrf @method('PUT')
                    <div class="list-group mb-3">
                        <label class="list-group-item d-flex align-items-start gap-3 p-3 cursor-pointer">
                            <input class="form-check-input mt-1 flex-shrink-0" type="radio" name="notification_frequency"
                                   value="immediate" {{ old('notification_frequency', $user->notification_frequency) == 'immediate' ? 'checked' : '' }}>
                            <div>
                                <div class="fw-semibold">{{ __('Immédiate') }}</div>
                                <small class="text-muted">{{ __('Recevoir chaque notification par courriel dès qu\'elle arrive') }}</small>
                            </div>
                        </label>
                        <label class="list-group-item d-flex align-items-start gap-3 p-3 cursor-pointer">
                            <input class="form-check-input mt-1 flex-shrink-0" type="radio" name="notification_frequency"
                                   value="daily" {{ old('notification_frequency', $user->notification_frequency) == 'daily' ? 'checked' : '' }}>
                            <div>
                                <div class="fw-semibold">{{ __('Résumé quotidien') }}</div>
                                <small class="text-muted">{{ __('Un seul courriel par jour avec toutes vos notifications') }}</small>
                            </div>
                        </label>
                        <label class="list-group-item d-flex align-items-start gap-3 p-3 cursor-pointer">
                            <input class="form-check-input mt-1 flex-shrink-0" type="radio" name="notification_frequency"
                                   value="weekly" {{ old('notification_frequency', $user->notification_frequency) == 'weekly' ? 'checked' : '' }}>
                            <div>
                                <div class="fw-semibold">{{ __('Résumé hebdomadaire') }}</div>
                                <small class="text-muted">{{ __('Un seul courriel par semaine avec toutes vos notifications') }}</small>
                            </div>
                        </label>
                    </div>
                    @error('notification_frequency')<div class="text-danger text-sm mb-2">{{ $message }}</div>@enderror
                    <button type="submit" class="btn btn-primary rounded-2">{{ __('Enregistrer') }}</button>
                </form>
            </div>
        </div>

        {{-- Export données RGPD --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title fw-semibold mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="download" class="text-primary"></i>
                    {{ __('Exporter mes données') }}
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">{{ __('Téléchargez une copie de vos données personnelles (profil, articles, tokens) au format JSON.') }}</p>
                <a href="{{ route('user.export-data') }}" class="btn btn-outline-primary rounded-2">
                    <i data-lucide="file-down"></i>
                    {{ __('Exporter mes données (JSON)') }}
                </a>
            </div>
        </div>

        {{-- Double authentification --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title fw-semibold mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="shield-check" class="text-primary"></i>
                    {{ __('Double authentification (2FA)') }}
                </h5>
            </div>
            <div class="card-body">
                @if($user->hasEnabledTwoFactor())
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-2 text-sm">
                            <span class="badge fw-semibold px-3 py-2 rounded-1 bg-success bg-opacity-10 text-success">
                                <i data-lucide="shield-check"></i>
                                {{ __('Double authentification activée') }}
                            </span>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('user.two-factor.recovery-codes') }}"
                               class="btn btn-sm btn-outline-secondary rounded-2">
                                <i data-lucide="key"></i> {{ __('Codes de secours') }}
                            </a>
                            <form action="{{ route('user.two-factor.disable') }}" method="POST"
                                  onsubmit="return confirm('{{ __('Désactiver la double authentification ?') }}')">
                                @csrf
                                <input type="hidden" name="password" id="disable-2fa-password">
                                <button type="button"
                                        onclick="var p=prompt('{{ __('Confirmez votre mot de passe pour désactiver le 2FA :') }}'); if(p){document.getElementById('disable-2fa-password').value=p; this.form.submit();}"
                                        class="btn btn-sm btn-danger rounded-2">
                                    <i data-lucide="shield-off"></i> {{ __('Désactiver') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <span class="badge fw-semibold px-3 py-2 rounded-1 bg-secondary bg-opacity-10 text-secondary">
                            {{ __('Double authentification désactivée') }}
                        </span>
                        <a href="{{ route('user.two-factor.setup') }}" class="btn btn-primary rounded-2">
                            <i data-lucide="shield-check"></i> {{ __('Activer le 2FA') }}
                        </a>
                    </div>
                @endif
                @error('password')
                    <div class="text-danger text-sm mt-2">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- Journal d'activité --}}
        <div class="card mb-4">
            <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                        <i data-lucide="history" class="text-primary"></i>
                    </div>
                    <div>
                        <h6 class="fw-semibold mb-1">{{ __('Journal d\'activité') }}</h6>
                        <p class="text-muted text-sm mb-0">{{ __('Consultez l\'historique de vos actions.') }}</p>
                    </div>
                </div>
                <a href="{{ route('user.activity') }}" class="btn btn-outline-secondary rounded-2">
                    <i data-lucide="list"></i> {{ __('Voir mon activité') }}
                </a>
            </div>
        </div>

        {{-- Sessions actives --}}
        <div class="card mb-4">
            <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;">
                        <i data-lucide="monitor-smartphone" class="text-primary"></i>
                    </div>
                    <div>
                        <h6 class="fw-semibold mb-1">{{ __('Sessions actives') }}</h6>
                        <p class="text-muted text-sm mb-0">{{ __('Gérez les appareils connectés à votre compte.') }}</p>
                    </div>
                </div>
                <a href="{{ route('user.sessions') }}" class="btn btn-outline-secondary rounded-2">
                    <i data-lucide="shield"></i> {{ __('Gérer mes sessions') }}
                </a>
            </div>
        </div>

        {{-- Suppression de compte --}}
        <div class="card border border-danger border-opacity-25">
            <div class="card-header">
                <h5 class="card-title fw-semibold mb-0 text-danger d-flex align-items-center gap-2">
                    <i data-lucide="trash-2"></i>
                    {{ __('Supprimer mon compte') }}
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">{{ __('Cette action est irréversible. Toutes vos données seront définitivement supprimées.') }}</p>

                @if(session('delete_error'))
                    <div class="alert alert-danger mb-3">{{ session('delete_error') }}</div>
                @endif

                <form method="POST" action="{{ route('user.account.delete') }}"
                      onsubmit="return confirm('{{ __('Êtes-vous sûr ? Cette action est IRRÉVERSIBLE.') }}');">
                    @csrf @method('DELETE')
                    <div class="mb-3">
                        <label class="form-label fw-medium text-muted">{{ __('Confirmez avec votre mot de passe') }}</label>
                        <input type="password" name="password" required
                               placeholder="{{ __('Votre mot de passe actuel') }}"
                               class="form-control rounded-2 @error('password') is-invalid @enderror"
                               style="max-width:320px;">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-danger rounded-2">
                        <i data-lucide="trash-2"></i>
                        {{ __('Supprimer définitivement mon compte') }}
                    </button>
                </form>
            </div>
        </div>

    </div>

</div>

@endsection
