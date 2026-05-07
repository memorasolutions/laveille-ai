<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.user-frontend')

@section('user-content')

{{-- Header --}}
<div style="display: flex !important; justify-content: space-between !important; align-items: center !important; flex-wrap: wrap !important; gap: 12px; margin-bottom: 24px;">
    <div>
        <h2 style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 800; color: var(--c-dark, #1A1D23); margin: 0 0 4px;">
            🔗 {{ __('Nouveau lien raccourci') }}
        </h2>
        <span style="font-size: 13px; color: var(--c-text-muted, #6E7687);">{{ __('Configurez votre lien avec les options avancées') }}</span>
    </div>
    <a href="{{ route('shorturl.user.index') }}"
        style="background: #F3F4F6; color: var(--c-dark, #1A1D23); padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; text-decoration: none;">
        ← {{ __('Retour') }}
    </a>
</div>

<form method="POST" action="{{ route('shorturl.user.store') }}"
    x-data="{
        active: 'basic',
        scraping: false,
        scraped: false,
        ogImage: '{{ old('og_image', '') }}',
        tagInput: '',
        tags: @json(old('tags', [])),
        tagSuggestions: [],

        tagHashCode(str) {
            let hash = 0;
            for (let i = 0; i < str.length; i++) { hash = str.charCodeAt(i) + ((hash << 5) - hash); hash = hash & hash; }
            return Math.abs(hash);
        },

        addTag(text) {
            text = text ? text.trim() : '';
            if (!text || text.length > 30 || this.tags.length >= 10) return;
            if (!/^[a-zA-Z0-9\u00C0-\u00FF\s\-_]+$/.test(text)) return;
            if (this.tags.some(t => t.toLowerCase() === text.toLowerCase())) return;
            this.tags.push(text);
            this.tagInput = '';
            this.tagSuggestions = [];
        },

        removeTag(index) { this.tags.splice(index, 1); },

        async fetchTagSuggestions() {
            let q = this.tagInput ? this.tagInput.trim() : '';
            if (q.length < 1) { this.tagSuggestions = []; return; }
            try {
                let res = await fetch('/user/liens/tags-suggest?q=' + encodeURIComponent(q));
                if (res.ok) {
                    let data = await res.json();
                    let current = this.tags.map(t => t.toLowerCase());
                    this.tagSuggestions = data.filter(s => !current.includes(s.toLowerCase()));
                }
            } catch (e) { this.tagSuggestions = []; }
        },

        toggle(section) {
            this.active = this.active === section ? '' : section;
        },

        async scrapeMeta() {
            const url = document.getElementById('original_url').value;
            if (!url) return;
            this.scraping = true;
            this.scraped = false;
            try {
                const res = await fetch('{{ route('shorturl.user.scrape-meta') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({ url })
                });
                const data = await res.json();
                if (data.title && !document.getElementById('title').value) document.getElementById('title').value = data.title;
                if (data.og_title && !document.getElementById('og_title').value) document.getElementById('og_title').value = data.og_title;
                if (data.og_description && !document.getElementById('og_description').value) document.getElementById('og_description').value = data.og_description;
                if (data.og_image) {
                    if (!document.getElementById('og_image').value) document.getElementById('og_image').value = data.og_image;
                    this.ogImage = data.og_image;
                }
                this.scraped = true;
            } catch (e) {
                console.error(e);
            }
            this.scraping = false;
        }
    }">
    @csrf

    {{-- Section 1 : Informations de base --}}
    <div style="background: #fff; border: 2px solid #E5E7EB; border-radius: 12px; margin-bottom: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <div @click="toggle('basic')" style="cursor: pointer; user-select: none;"
            :style="'padding:18px 24px;display:flex;justify-content:space-between;align-items:center;background:#F0F1F3;min-height:56px;box-sizing:border-box;line-height:1.4;' + (active === 'basic' ? 'border-bottom:1px solid #E5E7EB;' : '')">
            <span style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; font-size: 15px; color: var(--c-dark, #1A1D23);">
                📝 {{ __('Informations de base') }}
            </span>
            <span x-text="active === 'basic' ? '▲' : '▼'" style="font-size: 12px; color: var(--c-text-muted, #6E7687);"></span>
        </div>
        <div x-show="active === 'basic'" x-transition x-cloak style="padding: 20px;">
            {{-- URL --}}
            <div style="margin-bottom: 16px;">
                <label for="original_url" style="font-weight: 600; font-size: 13px; color: var(--c-dark, #1A1D23); margin-bottom: 6px; display: block;">
                    {{ __('URL de destination') }} <span style="color: #DC2626;">*</span>
                </label>
                <div style="display: flex !important; gap: 8px;">
                    <input type="url" id="original_url" name="original_url" value="{{ old('original_url') }}" required
                        placeholder="https://exemple.com/mon-lien-tres-long"
                        style="flex: 1 !important; height: 44px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 12px; font-size: 14px;">
                    <button type="button" @click="scrapeMeta()" :disabled="scraping"
                        style="height: 44px; padding: 0 16px; background: #F3F4F6; color: var(--c-dark, #1A1D23); border: 1px solid #D1D5DB; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; white-space: nowrap;">
                        <span x-show="!scraping">🔍 {{ __('Extraire les infos') }}</span>
                        <span x-show="scraping" x-cloak>⏳</span>
                    </button>
                </div>
                @error('original_url')
                    <div style="color: #DC2626; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>

            {{-- Slug --}}
            <div style="margin-bottom: 16px;">
                <label for="slug" style="font-weight: 600; font-size: 13px; color: var(--c-dark, #1A1D23); margin-bottom: 6px; display: block;">
                    {{ __('Slug personnalisé (optionnel)') }}
                </label>
                <div style="display: flex !important; align-items: center !important; gap: 0;">
                    <span style="height: 44px; padding: 0 12px; background: #F3F4F6; border: 1px solid #D1D5DB; border-right: none; border-radius: 8px 0 0 8px; font-size: 13px; color: var(--c-text-muted, #6E7687); display: flex !important; align-items: center !important;">veille.la/</span>
                    <input type="text" id="slug" name="slug" value="{{ old('slug') }}" placeholder="mon-slug-perso"
                        style="flex: 1 !important; height: 44px; border: 1px solid #D1D5DB; border-radius: 0 8px 8px 0; padding: 0 12px; font-size: 14px;">
                </div>
                @error('slug')
                    <div style="color: #DC2626; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>

            {{-- Titre --}}
            <div style="margin-bottom: 16px;">
                <label for="title" style="font-weight: 600; font-size: 13px; color: var(--c-dark, #1A1D23); margin-bottom: 6px; display: block;">
                    {{ __('Titre (optionnel)') }}
                </label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" placeholder="{{ __('Titre du lien') }}"
                    style="width: 100%; height: 44px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 12px; font-size: 14px;">
                @error('title')
                    <div style="color: #DC2626; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label for="description" style="font-weight: 600; font-size: 13px; color: var(--c-dark, #1A1D23); margin-bottom: 6px; display: block;">
                    {{ __('Description (optionnel)') }}
                </label>
                <textarea id="description" name="description" rows="3" placeholder="{{ __('Note personnelle sur ce lien') }}"
                    style="width: 100%; border: 1px solid #D1D5DB; border-radius: 8px; padding: 10px 12px; font-size: 14px; resize: vertical;">{{ old('description') }}</textarea>
                @error('description')
                    <div style="color: #DC2626; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- Section : Tags --}}
    <div style="background: #fff; border: 2px solid #E5E7EB; border-radius: 12px; margin-bottom: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <div @click="toggle('tags')" style="cursor: pointer; user-select: none;"
            :style="'padding:18px 24px;display:flex;justify-content:space-between;align-items:center;background:#F0F1F3;min-height:56px;box-sizing:border-box;line-height:1.4;' + (active === 'tags' ? 'border-bottom:1px solid #E5E7EB;' : '')">
            <span style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; font-size: 15px; color: var(--c-dark, #1A1D23);">
                {{ __('Tags (optionnel)') }}
            </span>
            <span x-text="active === 'tags' ? '&#9650;' : '&#9660;'" style="font-size: 12px; color: var(--c-text-muted, #6E7687);"></span>
        </div>
        <div x-show="active === 'tags'" x-transition x-cloak style="padding: 20px;">
            <label for="tag-input" style="font-weight: 600; font-size: 13px; color: var(--c-dark, #1A1D23); margin-bottom: 6px; display: block;">
                {{ __('Ajouter des tags') }}
                <span style="font-weight: 400; color: var(--c-text-muted, #6E7687); font-size: 12px;">({{ __('max 10, 30 caractères chacun') }})</span>
            </label>
            <div style="position: relative;" @click.outside="tagSuggestions = []">
                <input type="text" id="tag-input" x-model="tagInput"
                    @input.debounce.300ms="fetchTagSuggestions()"
                    @keydown.enter.prevent="addTag(tagInput)"
                    @keydown.escape="tagSuggestions = []"
                    maxlength="30"
                    :disabled="tags.length >= 10"
                    :placeholder="tags.length >= 10 ? '{{ __('Maximum atteint') }}' : '{{ __('Saisir un tag et appuyer sur Entrée...') }}'"
                    autocomplete="off"
                    aria-label="{{ __('Champ de saisie pour ajouter un tag') }}"
                    style="width: 100%; height: 44px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 12px; font-size: 14px;">
                <div x-show="tagSuggestions.length > 0" x-transition
                    style="position: absolute; z-index: 50; width: 100%; background: #fff; border: 1px solid #D1D5DB; border-top: none; border-radius: 0 0 8px 8px; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1);"
                    role="listbox" aria-label="{{ __('Suggestions de tags') }}">
                    <template x-for="suggestion in tagSuggestions" :key="suggestion">
                        <div @click="addTag(suggestion); tagSuggestions = []"
                            style="padding: 10px 12px; cursor: pointer; font-size: 14px; transition: background .15s;"
                            onmouseover="this.style.background='#F0FAFB'" onmouseout="this.style.background='#fff'"
                            role="option" x-text="suggestion"></div>
                    </template>
                </div>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px;">
                <template x-for="(tag, index) in tags" :key="'tag-' + index">
                    <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;"
                          :style="'background-color: hsl(' + (tagHashCode(tag) % 360) + ', 65%, 92%); color: hsl(' + (tagHashCode(tag) % 360) + ', 55%, 35%);'">
                        <span x-text="tag"></span>
                        <button type="button" @click="removeTag(index)"
                            :aria-label="'{{ __('Supprimer le tag') }} ' + tag"
                            style="background: none; border: none; cursor: pointer; font-size: 14px; font-weight: 700; padding: 0 0 0 2px; line-height: 1; opacity: 0.7;"
                            onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'">&times;</button>
                        <input type="hidden" name="tags[]" :value="tag">
                    </span>
                </template>
            </div>
            <template x-if="tags.length > 0">
                <p style="color: var(--c-text-muted, #6E7687); font-size: 12px; margin-top: 6px;">
                    <span x-text="tags.length"></span>/10 {{ __('tags utilisés') }}
                </p>
            </template>
            @error('tags')
                <div style="color: #DC2626; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
            @enderror
            @error('tags.*')
                <div style="color: #DC2626; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Section 2 : Securite et expiration --}}
    <div style="background: #fff; border: 2px solid #E5E7EB; border-radius: 12px; margin-bottom: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <div @click="toggle('security')" style="cursor: pointer; user-select: none;"
            :style="'padding:18px 24px;display:flex;justify-content:space-between;align-items:center;background:#F0F1F3;min-height:56px;box-sizing:border-box;line-height:1.4;' + (active === 'security' ? 'border-bottom:1px solid #E5E7EB;' : '')">
            <span style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; font-size: 15px; color: var(--c-dark, #1A1D23);">
                🔒 {{ __('Sécurité et expiration') }}
            </span>
            <span x-text="active === 'security' ? '▲' : '▼'" style="font-size: 12px; color: var(--c-text-muted, #6E7687);"></span>
        </div>
        <div x-show="active === 'security'" x-transition x-cloak style="padding: 20px;">
            <div style="margin-bottom: 16px;">
                <label for="password" style="font-weight: 600; font-size: 13px; color: var(--c-dark, #1A1D23); margin-bottom: 6px; display: block;">
                    🔑 {{ __('Mot de passe (optionnel)') }}
                </label>
                <input type="password" id="password" name="password" placeholder="{{ __('Proteger l\'acces au lien') }}"
                    style="width: 100%; height: 44px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 12px; font-size: 14px;">
                @error('password')
                    <div style="color: #DC2626; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <div style="display: flex !important; gap: 16px; flex-wrap: wrap !important;">
                <div style="flex: 1 !important; min-width: 200px;">
                    <label for="expires_at" style="font-weight: 600; font-size: 13px; color: var(--c-dark, #1A1D23); margin-bottom: 6px; display: block;">
                        📅 {{ __('Expiration (optionnel)') }}
                    </label>
                    <input type="datetime-local" id="expires_at" name="expires_at" value="{{ old('expires_at') }}"
                        style="width: 100%; height: 44px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 12px; font-size: 14px;">
                    @error('expires_at')
                        <div style="color: #DC2626; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>
                <div style="flex: 1 !important; min-width: 200px;">
                    <label for="max_clicks" style="font-weight: 600; font-size: 13px; color: var(--c-dark, #1A1D23); margin-bottom: 6px; display: block;">
                        🎯 {{ __('Max. clics (optionnel)') }}
                    </label>
                    <input type="number" id="max_clicks" name="max_clicks" min="1" value="{{ old('max_clicks') }}" placeholder="100"
                        style="width: 100%; height: 44px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 12px; font-size: 14px;">
                    @error('max_clicks')
                        <div style="color: #DC2626; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Section 3 : Tracking UTM --}}
    <div style="background: #fff; border: 2px solid #E5E7EB; border-radius: 12px; margin-bottom: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <div @click="toggle('utm')" style="cursor: pointer; user-select: none;"
            :style="'padding:18px 24px;display:flex;justify-content:space-between;align-items:center;background:#F0F1F3;min-height:56px;box-sizing:border-box;line-height:1.4;' + (active === 'utm' ? 'border-bottom:1px solid #E5E7EB;' : '')">
            <span style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; font-size: 15px; color: var(--c-dark, #1A1D23);">
                📊 {{ __('Tracking UTM') }}
            </span>
            <span x-text="active === 'utm' ? '▲' : '▼'" style="font-size: 12px; color: var(--c-text-muted, #6E7687);"></span>
        </div>
        <div x-show="active === 'utm'" x-transition x-cloak style="padding: 20px;">
            <p style="font-size: 13px; color: var(--c-text-muted, #6E7687); margin-bottom: 16px;">
                {{ __('Les parametres UTM seront ajoutes automatiquement a l\'URL de destination pour le suivi dans Google Analytics.') }}
            </p>
            <div style="display: flex !important; gap: 12px; flex-wrap: wrap !important;">
                <div style="flex: 1 !important; min-width: 180px;">
                    <label for="utm_source" style="font-weight: 600; font-size: 13px; color: var(--c-dark, #1A1D23); margin-bottom: 6px; display: block;">{{ __('Source') }}</label>
                    <input type="text" id="utm_source" name="utm_source" value="{{ old('utm_source') }}" placeholder="newsletter"
                        style="width: 100%; height: 44px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 12px; font-size: 14px;">
                    @error('utm_source')
                        <div style="color: #DC2626; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>
                <div style="flex: 1 !important; min-width: 180px;">
                    <label for="utm_medium" style="font-weight: 600; font-size: 13px; color: var(--c-dark, #1A1D23); margin-bottom: 6px; display: block;">{{ __('Medium') }}</label>
                    <input type="text" id="utm_medium" name="utm_medium" value="{{ old('utm_medium') }}" placeholder="email"
                        style="width: 100%; height: 44px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 12px; font-size: 14px;">
                    @error('utm_medium')
                        <div style="color: #DC2626; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>
                <div style="flex: 1 !important; min-width: 180px;">
                    <label for="utm_campaign" style="font-weight: 600; font-size: 13px; color: var(--c-dark, #1A1D23); margin-bottom: 6px; display: block;">{{ __('Campagne') }}</label>
                    <input type="text" id="utm_campaign" name="utm_campaign" value="{{ old('utm_campaign') }}" placeholder="promo-mars"
                        style="width: 100%; height: 44px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 12px; font-size: 14px;">
                    @error('utm_campaign')
                        <div style="color: #DC2626; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Section 4 : Preview social --}}
    <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; margin-bottom: 24px; overflow: hidden;">
        <div @click="toggle('preview')" style="cursor: pointer; user-select: none;"
            :style="'padding:18px 24px;display:flex;justify-content:space-between;align-items:center;background:#F0F1F3;min-height:56px;box-sizing:border-box;line-height:1.4;' + (active === 'preview' ? 'border-bottom:1px solid #E5E7EB;' : '')">
            <span style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; font-size: 15px; color: var(--c-dark, #1A1D23);">
                🌐 {{ __('Preview social et miniature') }}
            </span>
            <span x-text="active === 'preview' ? '▲' : '▼'" style="font-size: 12px; color: var(--c-text-muted, #6E7687);"></span>
        </div>
        <div x-show="active === 'preview'" x-transition x-cloak style="padding: 20px;">
            <div x-show="scraped" x-cloak style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; font-size: 13px; color: #16A34A;">
                ✨ {{ __('Données extraites automatiquement de l\'URL') }}
            </div>

            <div style="margin-bottom: 16px;">
                <label for="og_title" style="font-weight: 600; font-size: 13px; color: var(--c-dark, #1A1D23); margin-bottom: 6px; display: block;">
                    {{ __('Titre OpenGraph') }}
                </label>
                <input type="text" id="og_title" name="og_title" value="{{ old('og_title') }}" placeholder="{{ __('Titre pour les réseaux sociaux') }}"
                    style="width: 100%; height: 44px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 12px; font-size: 14px;">
                @error('og_title')
                    <div style="color: #DC2626; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <div style="margin-bottom: 16px;">
                <label for="og_description" style="font-weight: 600; font-size: 13px; color: var(--c-dark, #1A1D23); margin-bottom: 6px; display: block;">
                    {{ __('Description OpenGraph') }}
                </label>
                <textarea id="og_description" name="og_description" rows="3" placeholder="{{ __('Description pour les réseaux sociaux') }}"
                    style="width: 100%; border: 1px solid #D1D5DB; border-radius: 8px; padding: 10px 12px; font-size: 14px; resize: vertical;">{{ old('og_description') }}</textarea>
                @error('og_description')
                    <div style="color: #DC2626; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <div style="margin-bottom: 16px;">
                <label for="og_image" style="font-weight: 600; font-size: 13px; color: var(--c-dark, #1A1D23); margin-bottom: 6px; display: block;">
                    {{ __('Image OpenGraph (URL)') }}
                </label>
                <input type="url" id="og_image" name="og_image" value="{{ old('og_image') }}" placeholder="https://exemple.com/image.jpg"
                    x-model="ogImage"
                    style="width: 100%; height: 44px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 12px; font-size: 14px;">
                @error('og_image')
                    <div style="color: #DC2626; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>

            {{-- Preview miniature --}}
            <div x-show="ogImage" x-cloak style="background: #F9FAFB; border-radius: 8px; padding: 12px; text-align: center;">
                <p style="font-size: 12px; color: var(--c-text-muted, #6E7687); margin-bottom: 8px;">{{ __('Aperçu de la miniature') }}</p>
                <img :src="ogImage" alt="{{ __('Apercu') }}" style="max-height: 120px; max-width: 100%; border-radius: 6px; border: 1px solid #E5E7EB;" loading="lazy">
            </div>
        </div>
    </div>

    {{-- Submit --}}
    <div style="display: flex !important; justify-content: center !important; gap: 12px;">
        <button type="submit"
            style="background: var(--c-primary, #064E5A); color: #fff; border: none; padding: 12px 32px; border-radius: 10px; font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; font-size: 15px; cursor: pointer; transition: background .2s;"
            onmouseover="this.style.background='#096474'" onmouseout="this.style.background='var(--c-primary, #064E5A)'">
            🔗 {{ __('Créer le lien') }}
        </button>
    </div>
</form>

@endsection
