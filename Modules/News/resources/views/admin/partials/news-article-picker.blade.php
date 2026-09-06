{{--
    @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
    @project laveille.ai

    ACTION: Partial partagé "colonne actualités disponibles" (recherche, filtre langue, filtre
            compagnie d'IA, filtre couleur, 3 modes de tri, regroupement par acteur,
            favicon/titre/meta/résumé/liens, pastille couleur, bouton +Ajouter). Utilisé par
            concentre-builder.blade.php ET video-goal-builder.blade.php.
    RAISON: Extraction DRY (v1.117.0) - copie fidèle du HTML de la section "📰 Actualités
            disponibles" de concentre-builder.blade.php. Ce partial est inclus DANS le scope
            x-data du parent (Blade @include partage le scope Alpine, pas de props nécessaires) ;
            il attend que le parent expose (directement ou via NewsArticlePicker(), voir
            public/assets/admin/news-article-picker.js) : newsItems, loading.news, fetchError,
            searchQuery, languageFilter, companyFilter, sortMode, colorFilter, colorPalette,
            filteredAvailable, groupedAvailable, availableItems, availableCompanies,
            colorForItem(), setColor(), selectItem(), selectAllVisible(), manualColors.
            Filtre compagnie ajouté 2026-08-29 (demande du fondateur) : voir
            OfficialCompanySourcesSeeder pour la donnée qui l'alimente.

    Ticket #2210 (2026-09-05) : le mixin window.NewsArticlePicker (voir le fichier ci-dessus) doit
    exister AVANT que Livewire ne démarre Alpine et n'évalue le x-data des 3 pages hôtes
    (composition-builder, concentre-builder, video-goal-builder) - sinon la fabrique x-data entière
    lève un ReferenceError et Alpine échoue en cascade sur chaque directive du composant (~34
    erreurs constatées). Même classe de défaut et même correctif que l'historique 1.65.302
    (2026-06-21, éditeur Markdown Académie) : un <script defer> posé en @push('scripts')/inline se
    charge APRÈS que Livewire ait déjà démarré Alpine. Solution : @assets, injecté tôt par
    Livewire (dédupliqué) - voir aussi ShortUrl/index.blade.php et Tools/crosswords/index.blade.php
    pour le même pattern hors composant Livewire. Ce partial étant inclus par les 3 pages, PORTER
    la directive ICI (au lieu de la dupliquer 3 fois) est le point DRY naturel.
--}}
@assets
<script src="{{ asset('assets/admin/news-article-picker.js') }}?v={{ config('version.semver') }}" defer></script>
@endassets
<div class="cb-card">
    <div class="cb-section-title">
        📰 Actualités disponibles
        <span class="cb-counter" x-text="availableItems.length + ' / ' + newsItems.length"></span>
    </div>
    {{--
        Avis de liste (2026-08-23). Deux situations doivent se DIRE plutôt que se deviner :
        une journée sans collecte (on montre alors le dernier jour disponible) et une traduction
        de titres indisponible (les titres anglais restent tels quels). Sans cet avis, l'écran
        est muet et la même question revient un mois plus tard. Rendu seulement si le contrôleur
        hôte fournit ces champs : `avisListe` vaut la chaîne vide partout ailleurs.
    --}}
    <div class="cb-list-notice" role="status" aria-live="polite" x-show="avisListe" x-cloak x-text="'ℹ️ ' + avisListe"></div>
    <div class="mb-2 d-flex gap-2 align-items-center flex-wrap">
        <input type="search" class="form-control form-control-sm" placeholder="🔍 Rechercher dans les titres / résumés…" x-model="searchQuery" style="flex:1; min-width:200px;">
        <select class="form-select form-select-sm" x-model="languageFilter" style="width:auto;">
            <option value="">🌐 Toutes langues</option>
            <option value="fr">🇫🇷 Français</option>
            <option value="en">🇬🇧 English</option>
        </select>
        {{-- Filtre par compagnie d'IA (2026-08-29) - masqué de lui-même quand aucun article du
             lot n'a de compagnie renseignée (concentre-builder, objectif vidéo : ce champ n'existe
             pas dans leur charge utile), donc rien à configurer par page hôte. --}}
        <select class="form-select form-select-sm" x-model="companyFilter" style="width:auto;" x-show="availableCompanies.length > 0" x-cloak title="Filtre par compagnie d'IA">
            <option value="">🏢 Toutes compagnies</option>
            <template x-for="c in availableCompanies" :key="c">
                <option :value="c" x-text="c"></option>
            </template>
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
                                {{-- Ticket #2208 : cette fiche vient d'un AUTRE jour que celui affiché. Elle est là
                                     parce qu'un travail éditorial y a déjà été commencé (résumé composé ou fiche
                                     relue) - la même définition que la purge nocturne refuse de supprimer. Sans ce
                                     badge, l'admin ne comprendrait pas sa présence dans la liste du jour.
                                     Réutilise la classe de badge existante : aucun style neuf, donc aucun nouveau
                                     risque de contraste. --}}
                                <template x-if="item.hors_jour">
                                    <span class="cb-used-badge ms-1" title="Un travail éditorial a été commencé sur cette fiche un autre jour : elle reste accessible pour être terminée.">📌 commencée un autre jour</span>
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
                {{-- Texte d'état vide paramétrable : le libellé hebdomadaire par défaut convient au
                     Concentré et à l'Objectif vidéo, mais l'écran de composition n'a AUCUN filtre
                     de semaine (200 dernières collectées, toutes dates) - il passe le sien. --}}
                <span>{{ $emptyStateText ?? 'Aucune actualité publiée cette semaine.' }}</span>
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
