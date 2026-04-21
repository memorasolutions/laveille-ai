<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Raccourcir un lien') . ' - ' . config('app.name'))
@section('meta_description', __('Raccourcissez vos liens gratuitement avec veille.la. QR code, statistiques et liens personnalisés pour les membres.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Raccourcir un lien')])
@endsection

@section('content')
<section class="section-padding" style="padding-top: 20px;">
<div class="container">

    {{-- Message flash (lien expiré/supprimé redirigé ici) --}}
    @if(session('warning'))
        <div style="background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 12px; padding: 14px 20px; margin-bottom: 20px; color: #92400E; font-size: 14px; text-align: center;">
            ⚠️ {{ session('warning') }}
        </div>
    @endif

    {{-- Hero --}}
    <div class="text-center" style="margin-bottom: 32px;">
        <div style="width: 80px; height: 80px; border-radius: 20px; background: var(--c-primary-light, #F0FAFB); display: inline-flex !important; align-items: center !important; justify-content: center !important; margin-bottom: 16px;">
            <span style="font-size: 36px;">🔗</span>
        </div>
        <h1 style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 800; font-size: 2rem; color: var(--c-dark, #1A1D23); margin-bottom: 8px;">
            {{ __('Raccourcissez vos liens') }}
        </h1>
        <p style="font-size: 1.1rem; color: var(--c-text-muted, #6E7687);">
            {{ __('Service gratuit propulsé par') }} <a href="https://laveille.ai" style="color:var(--c-primary, #0B7285);font-weight:700;text-decoration:none;">laveille.ai</a>
        </p>
    </div>

    {{-- Formulaire --}}
    <div x-data="{
        url: '',
        slug: '',
        title: '',
        description: '',
        password: '',
        expires_at: '',
        max_clicks: '',
        utm_source: '',
        utm_medium: '',
        utm_campaign: '',
        og_title: '',
        og_description: '',
        og_image: '',
        domain_id: '',
        optionsOpen: '',
        loading: false,
        result: null,
        error: '',
        copied: false,
        history: JSON.parse(localStorage.getItem('shorturl_history') || '[]'),

        saveToHistory(data) {
            const entry = {
                short_url: data.short_url,
                slug: data.slug,
                original_url: this.url,
                created_at: new Date().toISOString(),
                expires_at: data.expires_at
            };
            this.history.unshift(entry);
            if (this.history.length > 20) this.history = this.history.slice(0, 20);
            localStorage.setItem('shorturl_history', JSON.stringify(this.history));
        },

        removeFromHistory(index) {
            this.history.splice(index, 1);
            localStorage.setItem('shorturl_history', JSON.stringify(this.history));
        },

        async shorten() {
            this.error = '';
            this.result = null;
            this.copied = false;
            if (!this.url) { this.error = '{{ __('Entrez une URL à raccourcir.') }}'; return; }
            this.loading = true;
            try {
                const res = await fetch('{{ route('shorturl.store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        url: this.url,
                        slug: this.slug || undefined,
                        title: this.title || undefined,
                        description: this.description || undefined,
                        password: this.password || undefined,
                        expires_at: this.expires_at || undefined,
                        max_clicks: this.max_clicks || undefined,
                        utm_source: this.utm_source || undefined,
                        utm_medium: this.utm_medium || undefined,
                        utm_campaign: this.utm_campaign || undefined,
                        og_title: this.og_title || undefined,
                        og_description: this.og_description || undefined,
                        og_image: this.og_image || undefined,
                        domain_id: this.domain_id || undefined
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.result = data;
                    this.saveToHistory(data);
                } else {
                    this.error = data.errors?.url?.[0] || data.message || '{{ __('Une erreur est survenue.') }}';
                }
            } catch (e) {
                this.error = '{{ __('Erreur réseau. Réessayez.') }}';
            }
            this.loading = false;
        },

        copyLink() {
            navigator.clipboard.writeText(this.result.short_url);
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        },

        qrInstance: null,

        generateQR() {
            if (!this.result?.short_url || typeof QRCodeStyling === 'undefined') return;
            this.$refs.qrPreview.innerHTML = '';
            this.qrInstance = new QRCodeStyling({
                width: 180, height: 180,
                data: this.result.short_url,
                dotsOptions: { color: '#0B7285', type: 'rounded' },
                cornersSquareOptions: { color: '#0B7285', type: 'extra-rounded' },
                backgroundOptions: { color: '#ffffff' },
                imageOptions: { crossOrigin: 'anonymous', margin: 4 }
            });
            this.qrInstance.append(this.$refs.qrPreview);
        },

        downloadQR() {
            if (this.qrInstance) this.qrInstance.download({ name: 'veille-la-qr', extension: 'png' });
        },

        init() {
            this.$watch('result', (val) => { if (val) this.$nextTick(() => this.generateQR()); });
            // Pré-remplir depuis localStorage (vient de l'outil liens Google)
            const pending = localStorage.getItem('pendingShortUrl');
            if (pending) { this.url = pending; localStorage.removeItem('pendingShortUrl'); }
        }
    }" style="max-width: 680px; margin: 0 auto;">

        {{-- Input URL --}}
        <div style="display: flex !important; gap: 8px; margin-bottom: 12px;">
            <input type="url" x-model="url" x-ref="urlInput" @keydown.enter="shorten()"
                placeholder="{{ __('Collez votre lien ici...') }}"
                style="flex: 1 !important; height: 52px; border: 2px solid #D1D5DB; border-radius: 12px; padding: 0 16px; font-size: 16px; outline: none; transition: border-color .2s;"
                onfocus="this.style.borderColor='var(--c-primary, #0B7285)'"
                onblur="this.style.borderColor='#D1D5DB'">
            <button @click="shorten()" :disabled="loading"
                style="height: 52px; padding: 0 28px; background: var(--c-primary, #0B7285); color: #fff; border: none; border-radius: 0.5rem; font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; font-size: 15px; cursor: pointer; white-space: nowrap; transition: background .2s;"
                onmouseover="this.style.background='var(--c-primary-hover, #064E5C)'" onmouseout="this.style.background='var(--c-primary, #0B7285)'">
                <span x-show="!loading">⚡ {{ __('Raccourcir') }}</span>
                <span x-show="loading" x-cloak>⏳</span>
            </button>
        </div>

        {{-- Options membres (slug + accordéons avancés) --}}
        @auth
        <div x-show="!result" x-cloak style="background: #fff; border: 2px solid #E5E7EB; border-radius: 16px; padding: 20px; margin-bottom: 16px; margin-top: 4px;">
            <div style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--c-text-muted, #6E7687); margin-bottom: 14px;">{{ __('Options membres') }}</div>
            {{-- Domaine + slug fusionnés --}}
            <div style="display: flex !important; align-items: center !important; gap: 0; margin-bottom: 12px;">
                @if(isset($domains) && $domains->count() > 1)
                <select x-model="domain_id" style="height: 40px; padding: 0 8px; background: #F3F4F6; border: 1px solid #D1D5DB; border-right: none; border-radius: 8px 0 0 8px; font-size: 13px; color: var(--c-text-muted, #6E7687); -webkit-appearance: none; -moz-appearance: none; appearance: none; cursor: pointer; min-width: 110px; text-align: center; font-weight: 600;">
                    @foreach($domains as $domain)
                        <option value="{{ $domain->id }}" {{ $domain->is_default ? 'selected' : '' }}>{{ $domain->domain }}/</option>
                    @endforeach
                </select>
                @else
                <span style="height: 40px; padding: 0 10px; background: #F3F4F6; border: 1px solid #D1D5DB; border-right: none; border-radius: 8px 0 0 8px; font-size: 13px; color: var(--c-text-muted, #6E7687); display: flex !important; align-items: center !important;">veille.la/</span>
                @endif
                <input type="text" x-model="slug" placeholder="{{ __('slug-personnalise (optionnel)') }}"
                    @input="slug = slug.normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/\s+/g,'-').replace(/[^a-zA-Z0-9_-]/g,'').replace(/-{2,}/g,'-').toLowerCase()"
                    style="flex: 1 !important; height: 40px; border: 1px solid #D1D5DB; border-radius: 0 8px 8px 0; padding: 0 12px; font-size: 14px;">
            </div>
            {{-- Titre + description --}}
            <input type="text" x-model="title" placeholder="{{ __('Titre (optionnel)') }}"
                style="width: 100%; height: 40px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 12px; font-size: 14px; margin-bottom: 8px;">
            <textarea x-model="description" placeholder="{{ __('Note personnelle (optionnel)') }}" rows="2"
                style="width: 100%; border: 1px solid #D1D5DB; border-radius: 8px; padding: 8px 12px; font-size: 14px; resize: vertical; margin-bottom: 12px;"></textarea>

            {{-- Accordéon sécurité --}}
            <div style="border: 1px solid #E5E7EB; border-radius: 12px; margin-bottom: 12px; overflow: hidden;">
                <div @click="optionsOpen = optionsOpen === 'security' ? '' : 'security'" style="padding: 14px 18px; cursor: pointer; display: flex !important; justify-content: space-between !important; align-items: center !important; user-select: none; background: #F9FAFB; min-height: 48px;">
                    <span style="font-weight: 600; font-size: 14px; color: var(--c-dark, #1A1D23);">🔒 {{ __('Sécurité et expiration') }}</span>
                    <span x-text="optionsOpen === 'security' ? '▲' : '▼'" style="font-size: 11px; color: var(--c-text-muted, #6E7687);"></span>
                </div>
                <div x-show="optionsOpen === 'security'" x-transition x-cloak style="padding: 16px 18px; border-top: 1px solid #E5E7EB;">
                    <input type="password" x-model="password" placeholder="{{ __('Mot de passe (optionnel)') }}"
                        style="width: 100%; height: 38px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 12px; font-size: 13px; margin-bottom: 8px;">
                    <div style="display: flex !important; gap: 8px; flex-wrap: wrap !important;">
                        <div style="flex: 1 !important; min-width: 140px;">
                            <label style="font-size: 11px; font-weight: 600; color: var(--c-text-muted, #6E7687);">{{ __('Expiration') }}</label>
                            <input type="datetime-local" x-model="expires_at" style="width: 100%; height: 38px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 8px; font-size: 13px;">
                        </div>
                        <div style="flex: 1 !important; min-width: 140px;">
                            <label style="font-size: 11px; font-weight: 600; color: var(--c-text-muted, #6E7687);">{{ __('Max. clics') }}</label>
                            <input type="number" x-model="max_clicks" min="1" placeholder="100" style="width: 100%; height: 38px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 8px; font-size: 13px;">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Accordéon UTM --}}
            <div style="border: 1px solid #E5E7EB; border-radius: 12px; margin-bottom: 12px; overflow: hidden;">
                <div @click="optionsOpen = optionsOpen === 'utm' ? '' : 'utm'" style="padding: 14px 18px; cursor: pointer; display: flex !important; justify-content: space-between !important; align-items: center !important; user-select: none; background: #F9FAFB; min-height: 48px;">
                    <span style="display: flex !important; align-items: center !important; gap: 6px;">
                        <span style="font-weight: 600; font-size: 14px; color: var(--c-dark, #1A1D23);">📊 {{ __('Tracking UTM') }}</span>
                        <span @click.stop="$dispatch('open-help-utm')" style="display: inline-flex !important; align-items: center !important; justify-content: center !important; width: 18px; height: 18px; border-radius: 50%; background: #E5E7EB; color: #6B7280; font-size: 11px; font-weight: 700; cursor: help; flex-shrink: 0;" title="{{ __('Qu\'est-ce que c\'est ?') }}">?</span>
                    </span>
                    <span x-text="optionsOpen === 'utm' ? '▲' : '▼'" style="font-size: 11px; color: var(--c-text-muted, #6E7687);"></span>
                </div>
                <div x-show="optionsOpen === 'utm'" x-transition x-cloak style="padding: 16px 18px; border-top: 1px solid #E5E7EB;">
                    <div style="display: flex !important; gap: 8px; flex-wrap: wrap !important;">
                        <input type="text" x-model="utm_source" placeholder="{{ __('Source (ex: newsletter)') }}" style="flex: 1 !important; min-width: 120px; height: 38px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 8px; font-size: 13px;">
                        <input type="text" x-model="utm_medium" placeholder="{{ __('Medium (ex: email)') }}" style="flex: 1 !important; min-width: 120px; height: 38px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 8px; font-size: 13px;">
                        <input type="text" x-model="utm_campaign" placeholder="{{ __('Campagne') }}" style="flex: 1 !important; min-width: 120px; height: 38px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 8px; font-size: 13px;">
                    </div>
                </div>
            </div>

            {{-- Accordéon preview social --}}
            <div style="border: 1px solid #E5E7EB; border-radius: 12px; margin-bottom: 12px; overflow: hidden;">
                <div @click="optionsOpen = optionsOpen === 'og' ? '' : 'og'" style="padding: 14px 18px; cursor: pointer; display: flex !important; justify-content: space-between !important; align-items: center !important; user-select: none; background: #F9FAFB; min-height: 48px;">
                    <span style="display: flex !important; align-items: center !important; gap: 6px;">
                        <span style="font-weight: 600; font-size: 14px; color: var(--c-dark, #1A1D23);">🌐 {{ __('Preview social') }}</span>
                        <span @click.stop="$dispatch('open-help-og')" style="display: inline-flex !important; align-items: center !important; justify-content: center !important; width: 18px; height: 18px; border-radius: 50%; background: #E5E7EB; color: #6B7280; font-size: 11px; font-weight: 700; cursor: help; flex-shrink: 0;" title="{{ __('Qu\'est-ce que c\'est ?') }}">?</span>
                    </span>
                    <span x-text="optionsOpen === 'og' ? '▲' : '▼'" style="font-size: 11px; color: var(--c-text-muted, #6E7687);"></span>
                </div>
                <div x-show="optionsOpen === 'og'" x-transition x-cloak style="padding: 16px 18px; border-top: 1px solid #E5E7EB;">
                    <input type="text" x-model="og_title" placeholder="{{ __('Titre OpenGraph') }}" style="width: 100%; height: 38px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 8px; font-size: 13px; margin-bottom: 8px;">
                    <textarea x-model="og_description" placeholder="{{ __('Description OpenGraph') }}" rows="2" style="width: 100%; border: 1px solid #D1D5DB; border-radius: 8px; padding: 8px; font-size: 13px; resize: vertical; margin-bottom: 8px;"></textarea>
                    <input type="url" x-model="og_image" placeholder="{{ __('URL image OpenGraph') }}" style="width: 100%; height: 38px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 8px; font-size: 13px;">
                    <template x-if="og_image">
                        <div style="margin-top: 8px; text-align: center;">
                            <img :src="og_image" alt="" style="max-height: 80px; border-radius: 6px; border: 1px solid #E5E7EB;" loading="lazy">
                        </div>
                    </template>
                </div>
            </div>
        </div>
        @endauth

        {{-- Erreur --}}
        <div x-show="error" x-cloak style="background: #FEF2F2; border: 1px solid #FECACA; border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; color: #DC2626; font-size: 14px;">
            ⚠️ <span x-text="error"></span>
        </div>

        {{-- Résultat --}}
        <div x-show="result" x-cloak x-transition style="background: #F0FDF4; border: 2px solid #BBF7D0; border-radius: 16px; padding: 24px; text-align: center; margin-bottom: 24px;">
            <p style="font-size: 13px; color: #16A34A; margin-bottom: 8px; font-weight: 600;">
                ✅ {{ __('Lien raccourci avec succès !') }}
            </p>
            <a :href="result?.short_url" target="_blank"
                style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-size: 1.5rem; font-weight: 800; color: var(--c-primary, #0B7285); text-decoration: none; word-break: break-all; display: block; margin-bottom: 16px;"
                x-text="result?.short_url"></a>

            {{-- QR code preview inline --}}
            <div x-ref="qrPreview" style="margin-bottom: 16px;"></div>

            <div style="display: flex !important; justify-content: center !important; flex-wrap: wrap !important; gap: 10px;">
                {{-- Copier --}}
                <a href="javascript:void(0)" @click="copyLink()"
                    :style="'background:' + (copied ? '#10B981' : '#1A1D23') + ';color:#fff;-webkit-appearance:none;text-decoration:none;display:inline-block;padding:10px 20px;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;transition:all .2s;font-family:var(--f-heading,\'Plus Jakarta Sans\',sans-serif);'"
                    onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                    <span x-show="!copied">📋 {{ __('Copier le lien') }}</span>
                    <span x-show="copied" x-cloak>✅ {{ __('Copié !') }}</span>
                </a>
                {{-- Télécharger QR --}}
                <a href="javascript:void(0)" @click="downloadQR()"
                    style="-webkit-appearance:none;text-decoration:none;display:inline-block;background:#1A1D23;color:#fff;padding:10px 20px;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;font-family:var(--f-heading,'Plus Jakarta Sans',sans-serif);"
                    onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                    ⬇️ {{ __('Télécharger QR') }}
                </a>
                {{-- Personnaliser QR --}}
                @if(Route::has('tools.show'))
                <a :href="'{{ route('tools.show', 'code-qr') }}?url=' + encodeURIComponent(result?.short_url)"
                    style="background: #1A1D23; color: #fff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 13px; text-decoration: none; display: inline-block; font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif);"
                    onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                    🎨 {{ __('Personnaliser le QR') }}
                </a>
                @endif
                {{-- Stats --}}
                <a :href="'{{ url('/raccourcir') }}/' + result?.slug + '/stats'"
                    style="background: #1A1D23; color: #fff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 13px; text-decoration: none; display: inline-block; font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif);"
                    onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                    📊 {{ __('Statistiques') }}
                </a>
                {{-- Créer un autre lien --}}
                <a href="#" @click.prevent="result = null; url = ''; copied = false; $nextTick(() => $refs.urlInput?.focus())"
                    style="background:#0B7285;color:#fff;-webkit-appearance:none;text-decoration:none;display:inline-block;padding:10px 20px;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;font-family:var(--f-heading,'Plus Jakarta Sans',sans-serif);"
                    onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                    + {{ __('Créer un autre lien') }}
                </a>
            </div>

            {{-- Message anonyme : expiration + sauvegarde stats --}}
            <template x-if="result?.is_anonymous">
                <div style="margin-top: 16px;">
                    <div style="background: #F0F9FF; border: 1px solid #BAE6FD; border-radius: 10px; padding: 12px 16px; font-size: 13px; color: #0369A1; margin-bottom: 8px;">
                        📌 {{ __('Sauvegardez cette page pour retrouver vos statistiques. Vos liens récents sont aussi conservés ci-dessous.') }}
                    </div>
                    <div style="background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 10px; padding: 12px 16px; font-size: 13px; color: #92400E;">
                        ⏰ {{ __('Ce lien expire dans 30 jours.') }}
                        <button type="button" @click="$dispatch('open-auth-modal', { message: '{{ __('Connectez-vous pour des liens permanents, un tableau de bord et plus de fonctionnalités.') }}' })"
                            style="background: var(--c-primary, #0B7285); color: #fff; border: none; padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; margin-left: 8px;">
                            {{ __('Créer un compte gratuit') }}
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- CTA membres (visible pour les visiteurs non connectés) --}}
    @guest
    <div x-data class="text-center" style="margin-top: 24px; padding: 24px; background: #F9FAFB; border-radius: 16px; border: 1px dashed #D1D5DB;">
        <h3 style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; color: var(--c-dark, #1A1D23); margin-bottom: 6px; font-size: 1.1rem;">{{ __('Envie de plus ?') }}</h3>
        <p style="color: var(--c-text-muted, #6E7687); margin-bottom: 14px; font-size: 13px;">{{ __('Liens permanents, slugs personnalises, analytics 90 jours, mot de passe, QR personnalise...') }}</p>
        <button type="button" @click="$dispatch('open-auth-modal', { message: '{{ __('Creez un compte gratuit pour debloquer toutes les fonctionnalites.') }}' })"
            style="background: var(--c-primary, #0B7285); color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; transition: background .2s;"
            onmouseover="this.style.background='#096474'" onmouseout="this.style.background='var(--c-primary, #0B7285)'">
            {{ __('Créer un compte gratuit') }}
        </button>
    </div>
    @endguest

    {{-- Historique liens récents (localStorage) --}}
    <div x-show="history.length > 0" x-cloak style="margin-top: 32px;">
        <div style="display: flex !important; justify-content: space-between !important; align-items: center !important; margin-bottom: 12px;">
            <h3 style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; font-size: 1rem; color: var(--c-dark, #1A1D23); margin: 0;">
                🕐 {{ __('Mes liens récents') }}
            </h3>
            <span style="font-size: 12px; color: var(--c-text-muted, #6E7687);">{{ __('Conservés dans votre navigateur') }}</span>
        </div>
        <template x-for="(item, index) in history" :key="index">
            <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; padding: 12px 16px; margin-bottom: 8px; display: flex !important; justify-content: space-between !important; align-items: center !important; flex-wrap: wrap !important; gap: 8px;">
                <div style="flex: 1 !important; min-width: 200px;">
                    <a :href="item.short_url" target="_blank"
                        style="font-weight: 700; font-size: 14px; color: var(--c-primary, #0B7285); text-decoration: none;"
                        x-text="item.short_url"></a>
                    <div style="font-size: 12px; color: var(--c-text-muted, #6E7687); margin-top: 2px;">
                        <span x-text="item.original_url?.substring(0, 50) + (item.original_url?.length > 50 ? '...' : '')"></span>
                    </div>
                </div>
                <div style="display: flex !important; gap: 6px; align-items: center !important;">
                    <a :href="'{{ url('/raccourcir') }}/' + item.slug + '/stats'"
                        style="background: var(--c-primary, #0B7285); color: #fff; padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; text-decoration: none;">
                        📊 {{ __('Stats') }}
                    </a>
                    <button @click="navigator.clipboard.writeText(item.short_url)"
                        style="background: #F3F4F6; color: var(--c-dark, #1A1D23); border: none; padding: 5px 10px; border-radius: 6px; font-size: 11px; cursor: pointer;">
                        📋
                    </button>
                    <button @click="removeFromHistory(index)"
                        style="background: none; border: none; color: #6B7280; font-size: 14px; cursor: pointer; padding: 2px 6px;"
                        title="{{ __('Retirer') }}">✕</button>
                </div>
            </div>
        </template>
    </div>

    {{-- Avantages --}}
    @php
        $features = [
            ['icon' => '✅', 'title' => __('100% gratuit'), 'desc' => __('Aucune limite pour les membres. Pas de pub.')],
            ['icon' => '📱', 'title' => __('QR code inclus'), 'desc' => __('Chaque lien a son QR code telechargeable et personnalisable.')],
            ['icon' => '📊', 'title' => __('Statistiques detaillees'), 'desc' => __('Clics, pays, appareils, sources — tout en temps reel.')],
            ['icon' => '🔗', 'title' => __('Slug personnalise'), 'desc' => __('Choisissez votre propre alias memorable.')],
            ['icon' => '🌐', 'title' => __('Multi-domaines'), 'desc' => __('veille.la, go3.ca — choisissez votre domaine.')],
            ['icon' => '🔒', 'title' => __('Protection par mot de passe'), 'desc' => __('Restreignez l\'acces a vos liens.')],
            ['icon' => '⏰', 'title' => __('Expiration et limites'), 'desc' => __('Date d\'expiration et nombre max de clics.')],
            ['icon' => '📈', 'title' => __('Tracking UTM'), 'desc' => __('Source, medium, campagne — integre a Google Analytics.')],
            ['icon' => '🖼️', 'title' => __('Preview social'), 'desc' => __('Personnalisez le titre et l\'image pour les reseaux sociaux.')],
        ];
    @endphp
    <div class="row" style="margin-top: 48px; display: flex !important; flex-wrap: wrap !important; justify-content: center !important; gap: 0;">
        @foreach($features as $f)
        <div class="col-md-4 col-sm-6 col-xs-12" style="text-align: center; padding: 20px 16px;">
            <div style="width: 56px; height: 56px; border-radius: 14px; background: var(--c-primary-light, #F0FAFB); display: inline-flex !important; align-items: center !important; justify-content: center !important; margin-bottom: 10px;">
                <span style="font-size: 24px;">{{ $f['icon'] }}</span>
            </div>
            <h3 style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; font-size: 1rem; color: var(--c-dark, #1A1D23); margin-bottom: 4px;">{{ $f['title'] }}</h3>
            <p style="color: var(--c-text-muted, #6E7687); font-size: 13px; margin: 0;">{{ $f['desc'] }}</p>
        </div>
        @endforeach
    </div>

{{-- CTA supprimé d'ici — déplacé plus haut après le formulaire --}}

</div>
</section>

{{-- Modale aide UTM --}}
<div x-data="{ open: false }" x-on:open-help-utm.window="open = true">
    <template x-teleport="body">
        <div x-show="open" x-cloak x-transition.opacity style="position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,0.5);display:flex!important;align-items:center!important;justify-content:center!important;padding:20px;" @click.self="open = false">
            <div style="background:#fff;border-radius:16px;max-width:520px;width:100%;max-height:80vh;overflow-y:auto;padding:28px;position:relative;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
                <button @click="open = false" style="position:absolute;top:12px;right:12px;background:none!important;border:none!important;font-size:20px;cursor:pointer;color:#6B7280;padding:4px;" aria-label="{{ __('Fermer') }}">✕</button>
                <h3 style="font-family:var(--f-heading);font-weight:700;font-size:1.2rem;margin:0 0 16px;color:var(--c-dark);">📊 {{ __('À quoi sert le suivi UTM ?') }}</h3>
                <p style="color:#4B5563;line-height:1.7;margin-bottom:12px;">{{ __('Le suivi UTM, c\'est comme mettre une petite étiquette invisible au bout d\'une adresse web. Ça sert à savoir précisément d\'où viennent les gens qui cliquent sur vos liens.') }}</p>
                <p style="color:#4B5563;line-height:1.7;margin-bottom:12px;">{{ __('Imaginons que vous partagez le même article sur Facebook et dans votre infolettre. Sans UTM, vous verrez que vous avez eu 100 visites, mais vous ne saurez pas quel canal a le mieux fonctionné. Avec les UTM, vous pourrez voir que 80 clics viennent de Facebook et 20 du courriel.') }}</p>
                <ul style="color:#4B5563;line-height:1.8;margin-bottom:12px;padding-left:20px;">
                    <li><strong>{{ __('Source') }} :</strong> {{ __('le nom de la plateforme (ex: Facebook, Google, infolettre)') }}</li>
                    <li><strong>{{ __('Medium') }} :</strong> {{ __('le type de canal (ex: social, courriel, publicité)') }}</li>
                    <li><strong>{{ __('Campagne') }} :</strong> {{ __('le nom de votre promotion (ex: vente_printemps)') }}</li>
                </ul>
                <p style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:12px;color:#166534;font-weight:600;font-size:13px;">{{ __('Si vous ne savez pas ce que c\'est, vous n\'en avez pas besoin. Laissez ces champs vides — votre lien fonctionnera très bien quand même !') }}</p>
            </div>
        </div>
    </template>
</div>

{{-- Modale aide preview social --}}
<div x-data="{ open: false }" x-on:open-help-og.window="open = true">
    <template x-teleport="body">
        <div x-show="open" x-cloak x-transition.opacity style="position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,0.5);display:flex!important;align-items:center!important;justify-content:center!important;padding:20px;" @click.self="open = false">
            <div style="background:#fff;border-radius:16px;max-width:520px;width:100%;max-height:80vh;overflow-y:auto;padding:28px;position:relative;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
                <button @click="open = false" style="position:absolute;top:12px;right:12px;background:none!important;border:none!important;font-size:20px;cursor:pointer;color:#6B7280;padding:4px;" aria-label="{{ __('Fermer') }}">✕</button>
                <h3 style="font-family:var(--f-heading);font-weight:700;font-size:1.2rem;margin:0 0 16px;color:var(--c-dark);">🌐 {{ __('Personnaliser l\'aperçu de partage') }}</h3>
                <p style="color:#4B5563;line-height:1.7;margin-bottom:12px;">{{ __('Avez-vous déjà remarqué que lorsque vous collez un lien dans Facebook, LinkedIn ou même par SMS, une petite carte apparaît automatiquement avec une image, un titre et un court texte ?') }}</p>
                <p style="color:#4B5563;line-height:1.7;margin-bottom:12px;">{{ __('Par défaut, les réseaux sociaux vont fouiller sur votre page pour essayer de deviner quoi afficher. Le problème, c\'est que le résultat est parfois un peu croche : l\'image est coupée ou le texte n\'est pas accrocheur.') }}</p>
                <p style="color:#4B5563;line-height:1.7;margin-bottom:12px;">{{ __('En remplissant ces champs, c\'est vous qui décidez de l\'allure de votre lien :') }}</p>
                <ul style="color:#4B5563;line-height:1.8;margin-bottom:12px;padding-left:20px;">
                    <li><strong>{{ __('Titre') }} :</strong> {{ __('le texte en gras qui doit donner envie de cliquer') }}</li>
                    <li><strong>{{ __('Description') }} :</strong> {{ __('les deux ou trois lignes qui expliquent le contenu') }}</li>
                    <li><strong>{{ __('Image') }} :</strong> {{ __('la photo ou le graphique qui servira de vignette') }}</li>
                </ul>
                <p style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:12px;color:#1E40AF;font-weight:600;font-size:13px;">{{ __('C\'est l\'outil idéal pour s\'assurer que vos partages ont l\'air professionnels et attrayants sur les réseaux sociaux.') }}</p>
            </div>
        </div>
    </template>
</div>

@push('scripts')
<script src="https://unpkg.com/qr-code-styling@1.6.0-rc.1/lib/qr-code-styling.js"></script>
@endpush
@endsection
