<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.user-frontend')

@section('user-content')

<div x-data="shortUrlDashboard()" x-cloak>

    {{-- Header --}}
    <div style="display: flex !important; justify-content: space-between !important; align-items: center !important; flex-wrap: wrap !important; gap: 12px; margin-bottom: 24px;">
        <div>
            <h2 style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 800; color: var(--c-dark, #1A1D23); margin: 0 0 4px;">
                {{ __('Mes liens courts') }}
            </h2>
            <span style="font-size: 13px; color: var(--c-text-muted, #6E7687);" x-text="filteredLinks.length + ' {{ __('lien(s) sur') }} ' + allLinks.length" aria-live="polite"></span>
        </div>
        <a href="{{ route('shorturl.create') }}"
            style="background: var(--c-primary, #064E5A); color: #fff; padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 14px; text-decoration: none; transition: background .2s;"
            onmouseover="this.style.background='#096474'" onmouseout="this.style.background='var(--c-primary, #064E5A)'">
            + {{ __('Nouveau lien') }}
        </a>
    </div>

    {{-- Barre de recherche --}}
    <div role="search" aria-label="{{ __('Rechercher dans vos liens') }}" style="background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 14px 20px; margin-bottom: 12px;">
        <div style="position: relative;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6E7687" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); pointer-events: none;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="search"
                   x-model.debounce.300ms="searchQuery"
                   placeholder="{{ __('Rechercher par titre, URL, slug ou tag...') }}"
                   aria-label="{{ __('Rechercher dans vos liens') }}"
                   style="width: 100%; height: 44px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 12px 0 42px; font-size: 14px; box-sizing: border-box;">
        </div>
    </div>

    {{-- Barre de filtrage par tags --}}
    <template x-if="allTags.length > 0">
        <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 12px 20px; margin-bottom: 16px;" role="region" aria-label="{{ __('Filtrer par tags') }}">
            <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                <span style="font-size: 12px; font-weight: 700; color: var(--c-text-muted, #6E7687); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('Tags') }} :</span>
                <template x-for="tag in allTags" :key="tag">
                    <button type="button"
                            @click="toggleTag(tag)"
                            :style="getTagStyle(tag, activeTags.includes(tag))"
                            :aria-pressed="activeTags.includes(tag) ? 'true' : 'false'"
                            style="border: 2px solid transparent; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all .2s;"
                            :class="{ 'surl-tag-active': activeTags.includes(tag) }">
                        <span x-text="tag"></span>
                    </button>
                </template>
                <template x-if="activeTags.length > 0">
                    <button type="button" @click="clearTagFilters()"
                            style="font-size: 12px; color: #DC2626; background: none; border: none; cursor: pointer; font-weight: 600; padding: 4px 8px;"
                            aria-label="{{ __('Effacer les filtres') }}">
                        {{ __('Effacer les filtres') }}
                    </button>
                </template>
            </div>
        </div>
    </template>

    {{-- Message expiration --}}
    <div style="background: #F0FAFB; border: 1px solid #D5EDF0; border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px; color: #475569; line-height: 1.5;">
        {{ __('Vos liens raccourcis expirent automatiquement après 12 mois sans visite. Vous pouvez repousser la date d\'expiration de chaque lien à tout moment depuis cette page.') }}
    </div>

    @if($allLinks->isEmpty())
        {{-- Etat vide --}}
        <div style="text-align: center; padding: 48px 24px; background: #F9FAFB; border-radius: 16px; border: 2px dashed #D1D5DB;">
            <div style="font-size: 48px; margin-bottom: 12px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#6E7687" stroke-width="1.5" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
            </div>
            <h3 style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; color: var(--c-dark, #1A1D23); margin-bottom: 8px;">{{ __('Aucun lien pour le moment') }}</h3>
            <p style="color: var(--c-text-muted, #6E7687); margin-bottom: 20px;">{{ __('Créez votre premier lien court pour commencer à suivre vos clics.') }}</p>
            <a href="{{ route('shorturl.create') }}"
                style="display: inline-block; background: var(--c-primary, #064E5A); color: #fff; padding: 12px 28px; border-radius: 10px; font-weight: 700; text-decoration: none;">
                + {{ __('Créer un lien') }}
            </a>
        </div>
    @else
        {{-- Liste des liens (filtrée par Alpine.js) --}}
        <template x-for="link in filteredLinks" :key="link.id">
            <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 16px 20px; margin-bottom: 12px; transition: box-shadow .2s;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.06)'" onmouseout="this.style.boxShadow='none'">
                <div style="display: flex !important; justify-content: space-between !important; align-items: flex-start !important; flex-wrap: wrap !important; gap: 12px;">
                    {{-- Info lien --}}
                    <div style="flex: 1 !important; min-width: 200px;">
                        <a href="#" @click.prevent="copyToClipboard(link.short_url, 'short-' + link.id)"
                            :title="'{{ __('Cliquer pour copier') }}'"
                            style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 700; font-size: 1.1rem; color: var(--c-primary, #064E5A); text-decoration: none; word-break: break-all; cursor: pointer;">
                            <span x-show="copiedId !== 'short-' + link.id" x-text="link.short_url"></span>
                            <span x-show="copiedId === 'short-' + link.id" x-cloak style="color: #10B981;">{{ __('Copié !') }}</span>
                        </a>
                        <div @click="copyToClipboard(link.original_url, 'orig-' + link.id)"
                            :title="'{{ __('Cliquer pour copier') }}'"
                            style="font-size: 13px; color: var(--c-text-muted, #6E7687); margin-top: 4px; word-break: break-all; cursor: pointer;">
                            <span x-show="copiedId !== 'orig-' + link.id" x-text="truncateUrl(link.original_url, 60)"></span>
                            <span x-show="copiedId === 'orig-' + link.id" x-cloak style="color: #10B981;">{{ __('Copié !') }}</span>
                        </div>
                        <template x-if="link.title">
                            <div style="font-size: 13px; color: var(--c-dark, #1A1D23); margin-top: 2px; font-weight: 600;" x-text="link.title"></div>
                        </template>

                        {{-- Tags du lien --}}
                        <template x-if="link.tags && link.tags.length > 0">
                            <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-top: 6px;">
                                <template x-for="tag in link.tags" :key="tag">
                                    <span :style="getTagStyle(tag, false)"
                                          style="padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;"
                                          x-text="tag"></span>
                                </template>
                            </div>
                        </template>

                        <div style="display: flex !important; flex-wrap: wrap !important; gap: 8px; margin-top: 8px; font-size: 12px;">
                            <span style="color: var(--c-text-muted, #6E7687);" x-text="link.clicks_count + ' {{ __('clics') }}'"></span>
                            <span style="color: var(--c-text-muted, #6E7687);" x-text="link.created_at_human"></span>
                            <template x-if="link.expires_at">
                                <span style="background: #FFFBEB; color: #92400E; padding: 2px 8px; border-radius: 4px; font-weight: 600;" x-text="link.expires_at_formatted"></span>
                            </template>
                            <template x-if="link.has_password">
                                <span style="background: #FEF2F2; color: #DC2626; padding: 2px 8px; border-radius: 4px; font-weight: 600;">{{ __('protégé') }}</span>
                            </template>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div style="display: flex !important; flex-wrap: wrap !important; gap: 6px; align-items: center !important;">
                        <a href="javascript:void(0)" @click="copyToClipboard(link.short_url, 'action-' + link.id)"
                            :style="(copiedId === 'action-' + link.id ? 'background:#10B981;color:#fff;border-color:#10B981;' : 'background:transparent;color:var(--c-dark, #1A1D23);border-color:#D1D5DB;') + 'border: 1px solid #D1D5DB; padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; line-height: 1.2; text-decoration: none; display: inline-block;'"
                            :aria-label="'{{ __('Copier le lien') }}'">
                            <span x-text="copiedId === 'action-' + link.id ? '{{ __('Copié !') }}' : '{{ __('Copier') }}'"></span>
                        </a>
                        <a :href="link.qr_url" target="_blank"
                            style="border: 1px solid #D1D5DB; color: var(--c-dark, #1A1D23); padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; text-decoration: none; line-height: 1.2;"
                            aria-label="{{ __('Voir le QR code') }}">QR</a>
                        <a :href="link.stats_url"
                            style="border: 1px solid #D1D5DB; color: var(--c-dark, #1A1D23); padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; text-decoration: none; line-height: 1.2;"
                            aria-label="{{ __('Voir les statistiques') }}">{{ __('Stats') }}</a>
                        <a :href="link.edit_url"
                            style="background: var(--c-primary, #064E5A); color: #fff; padding: 5px 10px; border: none; border-radius: 6px; font-size: 11px; font-weight: 600; text-decoration: none; line-height: 1.2;"
                            aria-label="{{ __('Modifier ce lien') }}">{{ __('Modifier') }}</a>
                        <template x-if="link.can_extend">
                            <form :action="link.extend_url" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit"
                                    style="-webkit-appearance:none;background:#FFFBEB;color:#92400E;border:1px solid #FDE68A;padding:5px 10px;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;line-height:1.2;"
                                    aria-label="{{ __('Prolonger ce lien') }}">{{ __('Prolonger') }}</button>
                            </form>
                        </template>
                        <form :action="link.delete_url" method="POST" style="display: inline;" x-data>
                            @csrf
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="button"
                                @click="$dispatch('open-confirm-global', { message: @js(__('Supprimer ce lien ?')), callback: () => $el.closest('form').submit() })"
                                style="-webkit-appearance: none; background: transparent; color: #DC2626; border: 1px solid #FECACA; padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; line-height: 1.2;"
                                aria-label="{{ __('Supprimer ce lien') }}">{{ __('Supprimer') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </template>

        {{-- Aucun resultat --}}
        <template x-if="filteredLinks.length === 0 && (searchQuery.length > 0 || activeTags.length > 0)">
            <div style="text-align: center; padding: 40px 20px; color: var(--c-text-muted, #6E7687); font-size: 15px;">
                <p>{{ __('Aucun lien ne correspond à votre recherche.') }}</p>
                <button type="button" @click="searchQuery = ''; activeTags = []"
                    style="background: var(--c-primary, #064E5A); color: #fff; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; margin-top: 8px;">
                    {{ __('Réinitialiser les filtres') }}
                </button>
            </div>
        </template>

        {{-- Pagination (seulement quand pas de filtre actif) --}}
        @if($shortUrls->hasPages())
            <div x-show="!isFiltering" style="margin-top: 20px;">{{ $shortUrls->links() }}</div>
        @endif
    @endif
</div>

<script>
function shortUrlDashboard() {
    return {
        allLinks: @json($linksJson),

        searchQuery: '',
        activeTags: [],
        copiedId: null,

        get allTags() {
            var tagSet = new Set();
            this.allLinks.forEach(function(link) {
                if (link.tags && Array.isArray(link.tags)) {
                    link.tags.forEach(function(tag) { tagSet.add(tag); });
                }
            });
            return Array.from(tagSet).sort(function(a, b) { return a.localeCompare(b, 'fr'); });
        },

        get isFiltering() {
            return this.searchQuery.trim().length > 0 || this.activeTags.length > 0;
        },

        get filteredLinks() {
            var self = this;
            var results = this.allLinks;

            if (self.searchQuery.trim().length > 0) {
                var query = self.searchQuery.trim().toLowerCase();
                results = results.filter(function(link) {
                    var titleMatch = (link.title || '').toLowerCase().indexOf(query) !== -1;
                    var urlMatch = (link.original_url || '').toLowerCase().indexOf(query) !== -1;
                    var slugMatch = (link.slug || '').toLowerCase().indexOf(query) !== -1;
                    var tagMatch = false;
                    if (link.tags && Array.isArray(link.tags)) {
                        tagMatch = link.tags.some(function(tag) {
                            return tag.toLowerCase().indexOf(query) !== -1;
                        });
                    }
                    return titleMatch || urlMatch || slugMatch || tagMatch;
                });
            }

            if (self.activeTags.length > 0) {
                results = results.filter(function(link) {
                    if (!link.tags || !Array.isArray(link.tags)) return false;
                    return self.activeTags.some(function(activeTag) {
                        return link.tags.includes(activeTag);
                    });
                });
            }

            return results;
        },

        hashCode(str) {
            var hash = 0;
            for (var i = 0; i < str.length; i++) {
                hash = str.charCodeAt(i) + ((hash << 5) - hash);
                hash = hash & hash;
            }
            return Math.abs(hash);
        },

        getTagStyle(tag, isActive) {
            var hue = this.hashCode(tag) % 360;
            var bgLight = isActive ? 85 : 92;
            var textLight = isActive ? 28 : 35;
            return 'background-color: hsl(' + hue + ', 65%, ' + bgLight + '%); color: hsl(' + hue + ', 55%, ' + textLight + '%);';
        },

        toggleTag(tag) {
            var index = this.activeTags.indexOf(tag);
            if (index === -1) { this.activeTags.push(tag); }
            else { this.activeTags.splice(index, 1); }
        },

        clearTagFilters() { this.activeTags = []; },

        copyToClipboard(text, id) {
            var self = this;
            navigator.clipboard.writeText(text).then(function() {
                self.copiedId = id;
                setTimeout(function() { if (self.copiedId === id) self.copiedId = null; }, 1500);
            });
        },

        truncateUrl(url, maxLength) {
            if (!url) return '';
            return url.length <= maxLength ? url : url.substring(0, maxLength) + '...';
        }
    };
}
</script>

@endsection
