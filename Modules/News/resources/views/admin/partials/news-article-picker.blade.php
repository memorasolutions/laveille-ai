{{--
    @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
    @project laveille.ai

    ACTION: Partial partagé "colonne actualités disponibles" (recherche, filtre langue, filtre
            couleur, 3 modes de tri, regroupement par acteur, favicon/titre/meta/résumé/liens,
            pastille couleur, bouton +Ajouter). Utilisé par concentre-builder.blade.php ET
            video-goal-builder.blade.php.
    RAISON: Extraction DRY (v1.117.0) — copie fidèle du HTML de la section "📰 Actualités
            disponibles" de concentre-builder.blade.php. Ce partial est inclus DANS le scope
            x-data du parent (Blade @include partage le scope Alpine, pas de props nécessaires) ;
            il attend que le parent expose (directement ou via NewsArticlePicker(), voir
            public/assets/admin/news-article-picker.js) : newsItems, loading.news, fetchError,
            searchQuery, languageFilter, sortMode, colorFilter, colorPalette, filteredAvailable,
            groupedAvailable, availableItems, colorForItem(), setColor(), selectItem(),
            selectAllVisible(), manualColors.
--}}
<div class="cb-card">
    <div class="cb-section-title">
        📰 Actualités disponibles
        <span class="cb-counter" x-text="availableItems.length + ' / ' + newsItems.length"></span>
    </div>
    <div class="mb-2 d-flex gap-2 align-items-center flex-wrap">
        <input type="search" class="form-control form-control-sm" placeholder="🔍 Rechercher dans les titres / résumés…" x-model="searchQuery" style="flex:1; min-width:200px;">
        <select class="form-select form-select-sm" x-model="languageFilter" style="width:auto;">
            <option value="">🌐 Toutes langues</option>
            <option value="fr">🇫🇷 Français</option>
            <option value="en">🇬🇧 English</option>
        </select>
        <select class="form-select form-select-sm" x-model="sortMode" style="width:auto;" title="Mode de tri">
            <option value="cluster">🏷 Tri par acteur</option>
            <option value="color">🎨 Tri par couleur</option>
            <option value="date">📅 Tri par date</option>
        </select>
        <select class="form-select form-select-sm" x-model="colorFilter" style="width:auto;" title="Filtre par couleur">
            <option value="">🎨 Toutes couleurs</option>
            <template x-for="c in colorPalette.filter(x => x.value)" :key="c.value">
                <option :value="c.value" :style="'color:' + c.value + '; font-weight:700;'" x-text="'● ' + c.label"></option>
            </template>
        </select>
        <button class="cb-btn cb-btn-secondary" type="button" style="font-size:12px; padding:6px 12px;" @click="selectAllVisible()" :disabled="filteredAvailable.length === 0">Tout cocher</button>
    </div>
    <div style="max-height:600px; overflow-y:auto;">
        <template x-for="group in groupedAvailable" :key="'g-' + (group.cluster || 'none')">
            <div>
                <div class="cb-cluster-divider" x-show="group.cluster && sortMode === 'cluster'" x-cloak>
                    <span x-text="'🏷 ' + group.cluster + ' (' + group.items.length + ')'"></span>
                </div>
                <template x-for="item in group.items" :key="item.id">
                    <div class="cb-news-item" :style="'border-left-color:' + colorForItem(item)">
                        <img :src="item.favicon" loading="lazy" class="cb-fav" alt="" onerror="this.style.display='none'">
                        <div style="flex:1; min-width:0;">
                            <div class="cb-title" x-text="item.title" :title="item.title_original && item.title_original !== item.title ? 'Titre original : ' + item.title_original : ''"></div>
                            <div class="cb-meta">
                                <span x-text="item.source_language === 'fr' ? '🇫🇷' : (item.source_language === 'en' ? '🇬🇧' : '🌐')" :title="item.source_language === 'fr' ? 'Français' : (item.source_language === 'en' ? 'Anglais (titre FR si traduit)' : 'Langue inconnue')" style="margin-right:4px;"></span>
                                <span x-text="item.source_name || 'Source inconnue'"></span> · <span x-text="item.pub_date_short"></span>
                                <template x-if="item.already_used">
                                    <span class="cb-used-badge ms-1">🔁 déjà utilisée</span>
                                </template>
                            </div>
                            <div class="cb-summary" x-text="item.summary" x-show="item.summary"></div>
                            <div class="cb-actions mt-2">
                                <a :href="item.site_url" target="_blank" rel="noopener">🔗 Lire sur le site</a>
                                <a :href="item.source_url" target="_blank" rel="noopener" x-show="item.source_url">↗ Source</a>
                            </div>
                        </div>
                        <div class="cb-color-wrapper" x-data="{ open: false }" @click.outside="open = false">
                            <button type="button" class="cb-color-dot" :style="'background:' + colorForItem(item)" @click="open = !open" :title="'Couleur : ' + (manualColors[item.id] ? 'manuelle' : 'auto cluster')" aria-label="Choisir une couleur"></button>
                            <div class="cb-color-popover" x-show="open" x-cloak x-transition.opacity.duration.150ms>
                                <template x-for="c in colorPalette" :key="'p-' + item.id + '-' + c.value">
                                    <button type="button"
                                            :class="['cb-color-choice', c.value ? '' : 'cb-clear', (manualColors[item.id] || '') === c.value ? 'is-active' : '']"
                                            :style="c.value ? ('background:' + c.value) : ''"
                                            :title="c.label"
                                            @click="setColor(item.id, c.value); open = false">
                                        <span x-show="!c.value">×</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <button class="cb-btn" type="button" style="font-size:12px; padding:6px 10px; min-height:32px;" @click="selectItem(item.id)">+ Ajouter</button>
                    </div>
                </template>
            </div>
        </template>
        <div class="cb-empty" x-show="filteredAvailable.length === 0 && !loading.news" x-cloak>
            <template x-if="newsItems.length === 0">
                <span>Aucune actualité publiée cette semaine.</span>
            </template>
            <template x-if="newsItems.length > 0 && availableItems.length === 0">
                <span>Toutes les actualités sont déjà sélectionnées.</span>
            </template>
            <template x-if="availableItems.length > 0">
                <span>Aucun résultat pour cette recherche.</span>
            </template>
        </div>
    </div>
</div>
