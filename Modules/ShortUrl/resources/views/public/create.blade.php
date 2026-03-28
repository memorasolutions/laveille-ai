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
                    body: JSON.stringify({ url: this.url, slug: this.slug || undefined })
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
        }
    }" style="max-width: 680px; margin: 0 auto;">

        {{-- Input URL --}}
        <div style="display: flex !important; gap: 8px; margin-bottom: 12px;">
            <input type="url" x-model="url" @keydown.enter="shorten()"
                placeholder="{{ __('Collez votre lien ici...') }}"
                style="flex: 1 !important; height: 52px; border: 2px solid #D1D5DB; border-radius: 12px; padding: 0 16px; font-size: 16px; outline: none; transition: border-color .2s;"
                onfocus="this.style.borderColor='var(--c-primary, #0B7285)'"
                onblur="this.style.borderColor='#D1D5DB'">
            <button @click="shorten()" :disabled="loading"
                style="height: 52px; padding: 0 28px; background: var(--c-primary, #0B7285); color: #fff; border: none; border-radius: 12px; font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; font-size: 15px; cursor: pointer; white-space: nowrap; transition: background .2s;"
                onmouseover="this.style.background='#096474'" onmouseout="this.style.background='var(--c-primary, #0B7285)'">
                <span x-show="!loading">⚡ {{ __('Raccourcir') }}</span>
                <span x-show="loading" x-cloak>⏳</span>
            </button>
        </div>

        {{-- Slug personnalisé (membres seulement) --}}
        @auth
        <div style="margin-bottom: 12px;" x-show="!result">
            <div style="display: flex !important; align-items: center !important; gap: 8px;">
                <span style="font-size: 13px; color: var(--c-text-muted, #6E7687); white-space: nowrap;">veille.la/</span>
                <input type="text" x-model="slug" placeholder="{{ __('slug-personnalise (optionnel)') }}"
                    style="flex: 1 !important; height: 38px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 12px; font-size: 14px;">
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
                <button @click="copyLink()"
                    :style="copied ? 'background:#10B981;color:#fff;' : 'background:var(--c-primary, #0B7285);color:#fff;'"
                    style="border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 13px; cursor: pointer; transition: all .2s; font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif);"
                    onmouseover="if(!this.__x) this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    <span x-show="!copied">📋 {{ __('Copier le lien') }}</span>
                    <span x-show="copied" x-cloak>✅ {{ __('Copié !') }}</span>
                </button>
                {{-- Télécharger QR --}}
                <button @click="downloadQR()"
                    style="background: #1A1D23; color: #fff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 13px; cursor: pointer; font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif);"
                    onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                    ⬇️ {{ __('Télécharger QR') }}
                </button>
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
    <div class="text-center" style="margin-top: 24px; padding: 24px; background: #F9FAFB; border-radius: 16px; border: 1px dashed #D1D5DB;">
        <h3 style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; color: var(--c-dark, #1A1D23); margin-bottom: 6px; font-size: 1.1rem;">{{ __('Envie de plus ?') }}</h3>
        <p style="color: var(--c-text-muted, #6E7687); margin-bottom: 14px; font-size: 13px;">{{ __('Liens permanents, slugs personnalisés, analytics 90 jours, mot de passe, QR personnalisé...') }}</p>
        <button type="button" @click="$dispatch('open-auth-modal', { message: '{{ __('Créez un compte gratuit pour débloquer toutes les fonctionnalités.') }}' })"
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
                        style="background: none; border: none; color: #9CA3AF; font-size: 14px; cursor: pointer; padding: 2px 6px;"
                        title="{{ __('Retirer') }}">✕</button>
                </div>
            </div>
        </template>
    </div>

    {{-- Avantages --}}
    <div class="row" style="margin-top: 48px; display: flex !important; flex-wrap: wrap !important; justify-content: center !important; gap: 20px;">
        <div class="col-md-4 col-sm-12" style="text-align: center; padding: 24px;">
            <div style="width: 64px; height: 64px; border-radius: 16px; background: var(--c-primary-light, #F0FAFB); display: inline-flex !important; align-items: center !important; justify-content: center !important; margin-bottom: 12px;">
                <span style="font-size: 28px;">✅</span>
            </div>
            <h3 style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; font-size: 1.1rem; color: var(--c-dark, #1A1D23); margin-bottom: 6px;">{{ __('100% gratuit') }}</h3>
            <p style="color: var(--c-text-muted, #6E7687); font-size: 14px;">{{ __('Aucune limite pour les membres. Pas de pub.') }}</p>
        </div>
        <div class="col-md-4 col-sm-12" style="text-align: center; padding: 24px;">
            <div style="width: 64px; height: 64px; border-radius: 16px; background: var(--c-primary-light, #F0FAFB); display: inline-flex !important; align-items: center !important; justify-content: center !important; margin-bottom: 12px;">
                <span style="font-size: 28px;">📱</span>
            </div>
            <h3 style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; font-size: 1.1rem; color: var(--c-dark, #1A1D23); margin-bottom: 6px;">{{ __('QR code inclus') }}</h3>
            <p style="color: var(--c-text-muted, #6E7687); font-size: 14px;">{{ __('Chaque lien a son QR code téléchargeable.') }}</p>
        </div>
        <div class="col-md-4 col-sm-12" style="text-align: center; padding: 24px;">
            <div style="width: 64px; height: 64px; border-radius: 16px; background: var(--c-primary-light, #F0FAFB); display: inline-flex !important; align-items: center !important; justify-content: center !important; margin-bottom: 12px;">
                <span style="font-size: 28px;">📊</span>
            </div>
            <h3 style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; font-size: 1.1rem; color: var(--c-dark, #1A1D23); margin-bottom: 6px;">{{ __('Statistiques détaillées') }}</h3>
            <p style="color: var(--c-text-muted, #6E7687); font-size: 14px;">{{ __('Clics, pays, appareils, sources — tout en temps réel.') }}</p>
        </div>
    </div>

{{-- CTA supprimé d'ici — déplacé plus haut après le formulaire --}}

</div>
</section>

@push('scripts')
<script src="https://unpkg.com/qr-code-styling@1.6.0-rc.1/lib/qr-code-styling.js"></script>
@endpush
@endsection
