@extends('backoffice::layouts.admin', ['title' => 'Concentré IA — builder de prompt', 'subtitle' => 'Génère le prompt Claude Code CLI hebdomadaire'])

@push('styles')
<style>
    .cb-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:18px; margin-bottom:18px; }
    .cb-news-item { display:flex; gap:12px; padding:10px 12px; border:1px solid #e5e7eb; border-radius:8px; background:#fff; margin-bottom:8px; transition:background .15s; align-items:flex-start; }
    .cb-news-item:hover { background:#f9fafb; }
    .cb-news-item.is-selected { border-color:#0B7285; background:#ecfeff; }
    .cb-news-item .cb-fav { width:24px; height:24px; flex-shrink:0; border-radius:4px; }
    .cb-news-item .cb-handle { cursor:grab; color:#94a3b8; padding:0 4px; user-select:none; font-size:18px; }
    .cb-news-item .cb-handle:active { cursor:grabbing; }
    .cb-news-item .cb-title { font-weight:600; color:#1f2937; font-size:14px; line-height:1.35; }
    .cb-news-item .cb-meta { font-size:12px; color:#6b7280; margin-top:4px; }
    .cb-news-item .cb-summary { font-size:13px; color:#374151; margin-top:6px; line-height:1.45; }
    .cb-news-item .cb-actions { display:flex; gap:6px; align-items:center; }
    .cb-news-item .cb-actions a { color:#0B7285; text-decoration:none; font-size:12px; padding:4px 8px; border:1px solid #0B7285; border-radius:4px; min-height:32px; display:inline-flex; align-items:center; }
    .cb-news-item .cb-actions a:hover { background:#0B7285; color:#fff; }
    .cb-used-badge { background:#fef3c7; color:#92400e; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:700; }
    .cb-position-badge { background:#0B7285; color:#fff; font-weight:700; min-width:28px; height:28px; border-radius:14px; display:inline-flex; align-items:center; justify-content:center; font-size:13px; flex-shrink:0; }
    .cb-pre { background:#0f172a; color:#e2e8f0; padding:14px; border-radius:8px; max-height:420px; overflow:auto; font-family:'SF Mono','Monaco','Consolas',monospace; font-size:12px; line-height:1.55; white-space:pre-wrap; word-break:break-word; }
    .cb-pre mark { background:#fde68a; color:#0f172a; padding:0 2px; }
    .cb-btn { background:#0B7285; color:#fff; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; font-weight:600; min-height:44px; display:inline-flex; align-items:center; gap:6px; }
    .cb-btn:hover { background:#075f6f; }
    .cb-btn:disabled { background:#94a3b8; cursor:not-allowed; }
    .cb-btn-secondary { background:#fff; color:#0B7285; border:1.5px solid #0B7285; }
    .cb-btn-secondary:hover { background:#0B7285; color:#fff; }
    .cb-section-title { font-weight:700; color:#0B7285; font-size:15px; margin-bottom:10px; display:flex; align-items:center; gap:8px; }
    .cb-counter { background:#0B7285; color:#fff; padding:2px 10px; border-radius:10px; font-size:12px; font-weight:700; }
    .cb-empty { text-align:center; color:#94a3b8; padding:24px; font-style:italic; }
    [x-cloak] { display:none !important; }
</style>
@endpush

@section('content')
<div
    x-data="concentreBuilder({
        defaultStart: @js($defaultStart),
        defaultEnd: @js($defaultEnd),
        history: @js($history->map(fn($r) => [
            'id' => $r->id,
            'week_start' => $r->week_start->toDateString(),
            'week_end' => $r->week_end->toDateString(),
            'count' => is_array($r->selected_news_ids) ? count($r->selected_news_ids) : 0,
            'created_at' => $r->created_at->isoFormat('D MMM YYYY HH:mm'),
        ])->values()),
        newsEndpoint: @js(route('admin.concentre.news')),
        generateEndpoint: @js(route('admin.concentre.generate')),
        runEndpoint: @js(route('admin.concentre.runs.show', ['id' => 0])),
    })"
    x-init="init()"
>
    {{-- Bandeau brouillon restauré --}}
    <div x-show="draftRestored" x-cloak x-transition style="background:#ecfeff; border:1px solid #0B7285; border-radius:8px; padding:10px 14px; margin-bottom:14px; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;" role="status">
        <span style="font-size:14px; color:#064E5A;">
            ✓ <strong>Brouillon restauré</strong> automatiquement
            <span x-show="draftRestoredAt" x-cloak> (sauvegardé le <span x-text="draftRestoredAt"></span>)</span>.
            Tu peux continuer où tu en étais.
        </span>
        <button type="button" @click="clearDraft()" style="background:#fff; border:1px solid #dc2626; color:#dc2626; padding:5px 12px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; min-height:32px;">🗑 Effacer le brouillon</button>
    </div>

    {{-- Indicateur sauvegarde flash --}}
    <div x-show="savedFlash" x-cloak x-transition.opacity.duration.300ms style="position:fixed; bottom:20px; right:20px; background:#064E5A; color:#fff; padding:8px 14px; border-radius:20px; font-size:12px; font-weight:600; box-shadow:0 4px 12px rgba(0,0,0,0.15); z-index:9999;" role="status" aria-live="polite">
        💾 Sauvegardé
    </div>

    {{-- Bandeau période --}}
    <div class="cb-card">
        <div class="cb-section-title">📅 Période du concentré</div>
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Semaine du (lundi)</label>
                <input type="date" class="form-control" x-model="weekStart" @change="onWeekStartChange()" :max="todayDate()">
                <div class="form-text" x-show="weekStartError" x-cloak x-text="weekStartError" style="color:#dc2626;"></div>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Au (dimanche, auto)</label>
                <input type="date" class="form-control" x-model="weekEnd" readonly style="background:#f3f4f6;">
            </div>
            <div class="col-md-4 text-end">
                <button class="cb-btn cb-btn-secondary" type="button" @click="shiftWeek(-1)">← Semaine précédente</button>
                <button class="cb-btn cb-btn-secondary" type="button" @click="shiftWeek(1)" :disabled="!canShiftForward()">Semaine suivante →</button>
            </div>
        </div>
        <div class="mt-2 small text-muted">
            <span x-show="!loading.news && newsItems.length > 0">
                <strong x-text="newsItems.length"></strong> actualité<span x-show="newsItems.length > 1">s</span> publiée<span x-show="newsItems.length > 1">s</span> dans cette plage.
            </span>
            <span x-show="loading.news" x-cloak>⏳ Chargement des actualités…</span>
            <span x-show="!loading.news && newsItems.length === 0 && !fetchError" x-cloak style="color:#dc2626;">Aucune actualité publiée dans cette plage.</span>
            <span x-show="fetchError" x-cloak style="color:#dc2626; font-weight:600;" x-text="'⚠ ' + fetchError"></span>
        </div>
    </div>

    <div class="row">
        {{-- Colonne gauche : disponibles --}}
        <div class="col-md-6">
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
                    <button class="cb-btn cb-btn-secondary" type="button" style="font-size:12px; padding:6px 12px;" @click="selectAllVisible()" :disabled="filteredAvailable.length === 0">Tout cocher</button>
                </div>
                <div style="max-height:600px; overflow-y:auto;">
                    <template x-for="item in filteredAvailable" :key="item.id">
                        <div class="cb-news-item">
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
                            <button class="cb-btn" type="button" style="font-size:12px; padding:6px 10px; min-height:32px;" @click="selectItem(item.id)">+ Ajouter</button>
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
        </div>

        {{-- Colonne droite : sélection ordonnée --}}
        <div class="col-md-6">
            <div class="cb-card">
                <div class="cb-section-title">
                    🎯 Ordre du concentré (glissé-déposé)
                    <span class="cb-counter" x-text="selectedIds.length + manualUrlsArray.length + ' total'"></span>
                </div>
                <div id="cb-sortable" style="min-height:80px;">
                    <template x-for="(id, idx) in selectedIds" :key="'sel-' + id">
                        <div class="cb-news-item is-selected" :data-id="id">
                            <span class="cb-handle" title="Glisser pour réordonner">⋮⋮</span>
                            <span class="cb-position-badge" x-text="idx + 1"></span>
                            <div style="flex:1; min-width:0;">
                                <div class="cb-title" x-text="itemById(id)?.title || '(introuvable)'"></div>
                                <div class="cb-meta">
                                    <span x-text="itemById(id)?.source_name"></span> · <span x-text="itemById(id)?.pub_date_short"></span>
                                </div>
                                <div class="cb-actions mt-2">
                                    <a :href="itemById(id)?.site_url" target="_blank" rel="noopener">🔗 Lire</a>
                                </div>
                            </div>
                            <button class="cb-btn cb-btn-secondary" type="button" style="font-size:12px; padding:6px 10px; min-height:32px;" @click="removeItem(id)">× Retirer</button>
                        </div>
                    </template>
                </div>
                <div class="cb-empty" x-show="selectedIds.length === 0" x-cloak>
                    Aucune actualité sélectionnée. Clique « + Ajouter » dans la colonne de gauche.
                </div>

                <hr class="my-3">
                <div class="cb-section-title" style="font-size:13px;">➕ URLs additionnelles (1 par ligne)</div>
                <textarea class="form-control" rows="4" x-model="manualUrls" placeholder="https://exemple.com/article-supplementaire&#10;https://exemple.com/autre-source"></textarea>
                <div class="form-text">
                    <span x-text="manualUrlsArray.length"></span> URL<span x-show="manualUrlsArray.length !== 1">s</span> valide<span x-show="manualUrlsArray.length !== 1">s</span> détectée<span x-show="manualUrlsArray.length !== 1">s</span>.
                </div>
            </div>
        </div>
    </div>

    {{-- Prévisualisation prompt --}}
    <div class="cb-card">
        <div class="cb-section-title">
            ✨ Prompt Claude Code CLI généré
            <span class="cb-counter" x-text="'~' + estimatedTokens + ' tokens'" x-show="generatedPrompt"></span>
        </div>
        <div class="mb-2 d-flex flex-wrap gap-2 align-items-center">
            <button class="cb-btn" type="button" @click="generate()" :disabled="!canGenerate() || loading.generate">
                <span x-show="!loading.generate">⚡ Générer le prompt</span>
                <span x-show="loading.generate" x-cloak>⏳ Génération…</span>
            </button>
            <button class="cb-btn cb-btn-secondary" type="button" @click="copyToClipboard()" :disabled="!generatedPrompt" x-show="generatedPrompt" x-cloak>📋 Copier</button>
            <button class="cb-btn cb-btn-secondary" type="button" @click="downloadTxt()" :disabled="!generatedPrompt" x-show="generatedPrompt" x-cloak>💾 Télécharger .txt</button>
            <span x-show="copyOk" x-cloak x-transition style="color:#059669; font-size:13px; font-weight:600;">✓ Copié dans le presse-papier</span>
            <span x-show="generateError" x-cloak style="color:#dc2626; font-size:13px; font-weight:600;" x-text="generateError"></span>
        </div>
        <pre class="cb-pre" x-show="generatedPrompt" x-cloak><code x-text="generatedPrompt"></code></pre>
        <div class="cb-empty" x-show="!generatedPrompt" x-cloak>
            Le prompt apparaîtra ici après génération. Min. 3 et max. 12 actualités/URLs.
        </div>
    </div>

    {{-- Historique --}}
    <div class="cb-card" x-show="history.length > 0" x-cloak>
        <div class="cb-section-title">🕓 Historique (10 derniers concentrés générés)</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>Semaine</th>
                        <th>Actualités</th>
                        <th>Généré le</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="run in history" :key="run.id">
                        <tr>
                            <td><span x-text="run.week_start"></span> → <span x-text="run.week_end"></span></td>
                            <td><span x-text="run.count"></span></td>
                            <td><span x-text="run.created_at"></span></td>
                            <td>
                                <button class="cb-btn cb-btn-secondary" type="button" style="font-size:12px; padding:4px 10px; min-height:32px;" @click="reuseRun(run.id)">↺ Réutiliser</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- SortableJS pour drag-drop ordre --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js" defer></script>

<script>
function concentreBuilder(opts) {
    return {
        weekStart: opts.defaultStart,
        weekEnd: opts.defaultEnd,
        weekStartError: '',
        fetchError: '',
        draftRestored: false,
        draftRestoredAt: '',
        savedFlash: false,
        newsItems: [],
        selectedIds: [],
        manualUrls: '',
        generatedPrompt: '',
        generateError: '',
        copyOk: false,
        estimatedTokens: 0,
        history: opts.history || [],
        loading: { news: false, generate: false },
        endpoints: { news: opts.newsEndpoint, generate: opts.generateEndpoint, run: opts.runEndpoint },
        sortable: null,

        init() {
            this.restoreLocal();
            this.fetchNews();
            this.$nextTick(() => this.initSortable());
            this.$watch('selectedIds', () => this.saveLocal());
            this.$watch('manualUrls', () => this.saveLocal());
            this.$watch('weekStart', () => this.saveLocal());
            window.addEventListener('beforeunload', () => this.saveLocal());
        },

        clearDraft() {
            try { localStorage.removeItem('cb_state_v1'); } catch (e) {}
            this.draftRestored = false;
            this.selectedIds = [];
            this.manualUrls = '';
        },

        todayDate() { return new Date().toISOString().split('T')[0]; },
        canShiftForward() { return new Date(this.weekEnd) < new Date(this.todayDate()); },

        onWeekStartChange() {
            const d = new Date(this.weekStart + 'T00:00:00');
            if (d.getUTCDay() !== 1) {
                // Snap au lundi précédent
                const day = d.getUTCDay();
                const diff = day === 0 ? -6 : 1 - day;
                d.setUTCDate(d.getUTCDate() + diff);
                this.weekStart = d.toISOString().split('T')[0];
                this.weekStartError = 'Snappé au lundi le plus proche.';
                setTimeout(() => { this.weekStartError = ''; }, 2500);
            }
            const end = new Date(this.weekStart + 'T00:00:00');
            end.setUTCDate(end.getUTCDate() + 6);
            this.weekEnd = end.toISOString().split('T')[0];
            this.selectedIds = [];
            this.fetchNews();
        },

        shiftWeek(direction) {
            const d = new Date(this.weekStart + 'T00:00:00');
            d.setUTCDate(d.getUTCDate() + (7 * direction));
            this.weekStart = d.toISOString().split('T')[0];
            this.onWeekStartChange();
        },

        async fetchNews() {
            this.loading.news = true;
            this.fetchError = '';
            try {
                const url = this.endpoints.news + '?week_start=' + this.weekStart + '&week_end=' + this.weekEnd;
                const res = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                if (!res.ok) {
                    let detail = '';
                    try { const e = await res.json(); detail = e.error || e.message || ''; } catch (_) {}
                    this.fetchError = 'HTTP ' + res.status + (detail ? ' — ' + detail : '');
                    this.newsItems = [];
                    return;
                }
                const data = await res.json();
                this.newsItems = data.items || [];
            } catch (e) {
                this.fetchError = 'Erreur réseau : ' + e.message;
                this.newsItems = [];
            } finally {
                this.loading.news = false;
            }
        },

        get availableItems() {
            return this.newsItems.filter(n => !this.selectedIds.includes(n.id));
        },

        get filteredAvailable() {
            const q = this.searchQuery?.toLowerCase().trim() || '';
            const lang = this.languageFilter || '';
            return this.availableItems.filter(n => {
                if (lang && n.source_language !== lang) return false;
                if (!q) return true;
                return (n.title || '').toLowerCase().includes(q)
                    || (n.title_original || '').toLowerCase().includes(q)
                    || (n.summary || '').toLowerCase().includes(q);
            });
        },

        searchQuery: '',
        languageFilter: '',

        itemById(id) { return this.newsItems.find(n => n.id === id); },

        selectItem(id) {
            if (!this.selectedIds.includes(id)) {
                this.selectedIds = [...this.selectedIds, id];
                this.$nextTick(() => this.initSortable());
            }
        },

        removeItem(id) {
            this.selectedIds = this.selectedIds.filter(i => i !== id);
        },

        selectAllVisible() {
            const toAdd = this.filteredAvailable.map(n => n.id).filter(id => !this.selectedIds.includes(id));
            this.selectedIds = [...this.selectedIds, ...toAdd];
            this.$nextTick(() => this.initSortable());
        },

        initSortable() {
            const el = document.getElementById('cb-sortable');
            if (!el || typeof Sortable === 'undefined') return;
            if (this.sortable) this.sortable.destroy();
            this.sortable = Sortable.create(el, {
                handle: '.cb-handle',
                animation: 150,
                onEnd: (evt) => {
                    const newOrder = Array.from(el.querySelectorAll('[data-id]')).map(d => parseInt(d.dataset.id, 10));
                    this.selectedIds = newOrder;
                },
            });
        },

        get manualUrlsArray() {
            return (this.manualUrls || '').split(/\r?\n/)
                .map(u => u.trim())
                .filter(u => /^https?:\/\//i.test(u));
        },

        canGenerate() {
            const total = this.selectedIds.length + this.manualUrlsArray.length;
            return total >= 3 && total <= 12;
        },

        async generate() {
            this.loading.generate = true;
            this.generateError = '';
            this.generatedPrompt = '';
            try {
                const res = await fetch(this.endpoints.generate, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        week_start: this.weekStart,
                        week_end: this.weekEnd,
                        ordered_news_ids: this.selectedIds,
                        manual_urls: this.manualUrlsArray,
                    }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.generateError = data.error || data.message || 'Erreur HTTP ' + res.status;
                    return;
                }
                this.generatedPrompt = data.prompt;
                this.estimatedTokens = data.token_estimate;
                // Re-fetch history (page recharge ou append)
                this.history = [{
                    id: data.run_id,
                    week_start: this.weekStart,
                    week_end: this.weekEnd,
                    count: this.selectedIds.length + this.manualUrlsArray.length,
                    created_at: new Date().toLocaleString('fr-CA'),
                }, ...this.history].slice(0, 10);
            } catch (e) {
                this.generateError = 'Erreur réseau : ' + e.message;
            } finally {
                this.loading.generate = false;
            }
        },

        async copyToClipboard() {
            try {
                await navigator.clipboard.writeText(this.generatedPrompt);
                this.copyOk = true;
                setTimeout(() => { this.copyOk = false; }, 2500);
            } catch (e) {
                this.generateError = 'Impossible de copier (clipboard API indisponible).';
            }
        },

        downloadTxt() {
            const blob = new Blob([this.generatedPrompt], { type: 'text/plain;charset=utf-8' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'concentre-prompt-' + this.weekStart + '.txt';
            a.click();
            URL.revokeObjectURL(a.href);
        },

        async reuseRun(id) {
            try {
                const url = this.endpoints.run.replace('/0', '/' + id);
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                this.weekStart = data.week_start;
                this.weekEnd = data.week_end;
                this.selectedIds = data.selected_news_ids || [];
                this.manualUrls = data.manual_urls || '';
                this.generatedPrompt = data.generated_prompt || '';
                await this.fetchNews();
                this.$nextTick(() => this.initSortable());
            } catch (e) { console.error(e); }
        },

        saveLocal() {
            try {
                localStorage.setItem('cb_state_v1', JSON.stringify({
                    weekStart: this.weekStart,
                    selectedIds: this.selectedIds,
                    manualUrls: this.manualUrls,
                    savedAt: new Date().toISOString(),
                }));
                this.savedFlash = true;
                clearTimeout(this._flashTimer);
                this._flashTimer = setTimeout(() => { this.savedFlash = false; }, 1500);
            } catch (e) {}
        },

        restoreLocal() {
            try {
                const raw = localStorage.getItem('cb_state_v1');
                if (!raw) return;
                const s = JSON.parse(raw);
                let hadAny = false;
                if (s.weekStart && new Date(s.weekStart) >= new Date('2024-01-01')) {
                    this.weekStart = s.weekStart;
                    const end = new Date(this.weekStart + 'T00:00:00');
                    end.setUTCDate(end.getUTCDate() + 6);
                    this.weekEnd = end.toISOString().split('T')[0];
                    hadAny = true;
                }
                if (Array.isArray(s.selectedIds) && s.selectedIds.length > 0) {
                    this.selectedIds = s.selectedIds;
                    hadAny = true;
                }
                if (typeof s.manualUrls === 'string' && s.manualUrls.trim()) {
                    this.manualUrls = s.manualUrls;
                    hadAny = true;
                }
                if (hadAny) {
                    this.draftRestored = true;
                    this.draftRestoredAt = s.savedAt ? new Date(s.savedAt).toLocaleString('fr-CA') : '';
                }
            } catch (e) {}
        },
    };
}
</script>
@endsection
