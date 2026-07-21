@extends('backoffice::layouts.admin', ['title' => 'Concentré IA — builder de prompt', 'subtitle' => 'Génère le prompt Claude Code CLI hebdomadaire'])

@push('styles')
{{-- Composant partagé "sélecteur d'actualités" (recherche/filtres/tri/cluster/couleur),
     réutilisé aussi par admin/video-goal-builder.blade.php — voir public/assets/admin/. --}}
<link rel="stylesheet" href="{{ asset('assets/admin/news-article-picker.css') }}?v={{ config('version.semver') }}">
<style>
    .cb-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:18px; margin-bottom:18px; }
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
        videoGoalUrl: @js(route('admin.news.video-goal.index')),
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
        {{-- Colonne gauche : disponibles (composant partagé avec admin/video-goal-builder) --}}
        <div class="col-md-6">
            @include('news::admin.partials.news-article-picker')
        </div>

        {{-- Colonne droite : sélection ordonnée --}}
        <div class="col-md-6">
            <div class="cb-card">
                <div class="cb-section-title">
                    🎯 Ordre du concentré (glissé-déposé)
                    <span class="cb-counter" x-text="selectedIds.length + manualUrlsArray.length + ' total'"></span>
                </div>
                <div class="mb-2 d-flex gap-2 align-items-center flex-wrap" x-show="selectedIds.length >= 2" x-cloak>
                    <span style="font-size:12px; color:#6b7280; font-weight:600;">Re-trier rapidement :</span>
                    <button type="button" class="cb-btn cb-btn-secondary" style="font-size:12px; padding:5px 10px; min-height:32px;" @click="sortSelected('cluster')" title="Grouper par acteur (OpenAI ensemble, Google ensemble, etc.)">🏷 Acteur</button>
                    <button type="button" class="cb-btn cb-btn-secondary" style="font-size:12px; padding:5px 10px; min-height:32px;" @click="sortSelected('color')" title="Grouper par couleur attribuée">🎨 Couleur</button>
                    <button type="button" class="cb-btn cb-btn-secondary" style="font-size:12px; padding:5px 10px; min-height:32px;" @click="sortSelected('date')" title="Plus récent d'abord">📅 Date</button>
                    <span style="font-size:11px; color:#94a3b8; font-style:italic;">(tu peux ensuite ajuster au drag-drop)</span>
                </div>
                <div x-show="selectedIds.length > 0" x-cloak class="mb-2">
                    <button type="button" class="cb-btn cb-btn-secondary" @click="pushToVideoGoal()" style="font-size:12px; padding:6px 12px; min-height:36px;" title="Envoie la sélection actuelle vers le Générateur d'objectif vidéo">
                        🎬 Envoyer vers Objectif vidéo
                    </button>
                </div>
                <div id="cb-sortable" style="min-height:80px;">
                    <template x-for="(id, idx) in selectedIds" :key="'sel-' + id">
                        <div class="cb-news-item is-selected" :data-id="id" :style="'border-left-color:' + colorForItem(itemById(id))">
                            <span class="cb-handle" title="Glisser pour réordonner">⋮⋮</span>
                            <span class="cb-position-badge" x-text="idx + 1"></span>
                            <div class="cb-color-wrapper" x-data="{ open: false }" @click.outside="open = false">
                                <button type="button" class="cb-color-dot" :style="'background:' + colorForItem(itemById(id))" @click="open = !open" :title="'Couleur : ' + (manualColors[id] ? 'manuelle' : 'auto cluster')" aria-label="Choisir une couleur"></button>
                                <div class="cb-color-popover" x-show="open" x-cloak x-transition.opacity.duration.150ms>
                                    <template x-for="c in colorPalette" :key="'sp-' + id + '-' + c.value">
                                        <button type="button"
                                                :class="['cb-color-choice', c.value ? '' : 'cb-clear', (manualColors[id] || '') === c.value ? 'is-active' : '']"
                                                :style="c.value ? ('background:' + c.value) : ''"
                                                :title="c.label"
                                                @click="setColor(id, c.value); open = false">
                                            <span x-show="!c.value">×</span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div class="cb-title" x-text="itemById(id)?.title || '(introuvable)'"></div>
                                <div class="cb-meta">
                                    <span x-text="itemById(id)?.source_language === 'fr' ? '🇫🇷' : (itemById(id)?.source_language === 'en' ? '🇬🇧' : '')"></span>
                                    <span x-text="itemById(id)?.source_name"></span> · <span x-text="itemById(id)?.pub_date_short"></span>
                                    <span x-show="itemById(id)?.actor_cluster" x-cloak style="color:#0B7285; font-weight:600;"> · <span x-text="itemById(id)?.actor_cluster"></span></span>
                                </div>
                            </div>
                            <a :href="itemById(id)?.site_url" target="_blank" rel="noopener" title="Ouvrir dans un nouvel onglet" style="color:#0B7285; font-size:14px; padding:4px 8px; text-decoration:none;">🔗</a>
                            <button type="button" @click="removeItem(id)" title="Retirer de la sélection" style="background:none; border:none; color:#dc2626; font-size:18px; cursor:pointer; padding:2px 8px; line-height:1;">×</button>
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
            <span :style="'display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:14px;font-size:12px;font-weight:700;color:#fff;background:' + selectionZone.color + ';'" :title="selectionZone.tip">
                <span x-text="(selectedIds.length + manualUrlsArray.length) + ' / 20'"></span>
                <span style="opacity:0.85;" x-text="'· ' + selectionZone.label"></span>
            </span>
            <button class="cb-btn cb-btn-secondary" type="button" @click="copyToClipboard()" :disabled="!generatedPrompt" x-show="generatedPrompt" x-cloak>📋 Copier</button>
            <button class="cb-btn cb-btn-secondary" type="button" @click="downloadTxt()" :disabled="!generatedPrompt" x-show="generatedPrompt" x-cloak>💾 Télécharger .txt</button>
            <span x-show="copyOk" x-cloak x-transition style="color:#059669; font-size:13px; font-weight:600;">✓ Copié dans le presse-papier</span>
            <span x-show="generateError" x-cloak style="color:#dc2626; font-size:13px; font-weight:600;" x-text="generateError"></span>
        </div>
        <pre class="cb-pre" x-show="generatedPrompt" x-cloak><code x-text="generatedPrompt"></code></pre>
        <div class="cb-empty" x-show="!generatedPrompt" x-cloak>
            Le prompt apparaîtra ici après génération. <strong>Min. 3, max. 20</strong> actualités/URLs (sweet spot éditorial : 8-12).
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

{{-- Mixin partagé "sélecteur d'actualités" (recherche/filtres/tri/cluster/couleur/sélection),
     réutilisé aussi par admin/video-goal-builder.blade.php — voir public/assets/admin/. Chargé
     AVANT le script inline ci-dessous : window.NewsArticlePicker doit exister avant init(). --}}
<script src="{{ asset('assets/admin/news-article-picker.js') }}?v={{ config('version.semver') }}" defer></script>

<script>
function concentreBuilder(opts) {
    const state = {
        weekStart: opts.defaultStart,
        weekEnd: opts.defaultEnd,
        weekStartError: '',
        draftRestored: false,
        draftRestoredAt: '',
        savedFlash: false,
        manualUrls: '',
        generatedPrompt: '',
        generateError: '',
        copyOk: false,
        estimatedTokens: 0,
        history: opts.history || [],
        loading: { news: false, generate: false },
        endpoints: { news: opts.newsEndpoint, generate: opts.generateEndpoint, run: opts.runEndpoint, videoGoalUrl: opts.videoGoalUrl },
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

        // fetchNews(), availableItems/filteredAvailable/groupedAvailable (getters), état
        // recherche/filtres/tri/couleurs et colorForItem()/setColor() proviennent maintenant du
        // mixin partagé NewsArticlePicker() fusionné dans init() (voir plus haut) — DRY avec
        // admin/video-goal-builder.blade.php, voir public/assets/admin/news-article-picker.js.

        sortSelected(mode) {
            const ids = [...this.selectedIds];
            ids.sort((idA, idB) => {
                const a = this.itemById(idA);
                const b = this.itemById(idB);
                if (!a || !b) return 0;
                if (mode === 'cluster') {
                    const ca = a.actor_cluster || '￿';
                    const cb = b.actor_cluster || '￿';
                    if (ca !== cb) return ca.localeCompare(cb);
                    return (b.pub_date || '').localeCompare(a.pub_date || '');
                }
                if (mode === 'color') {
                    const ca = this.colorForItem(a);
                    const cb = this.colorForItem(b);
                    if (ca !== cb) return ca.localeCompare(cb);
                    return (b.pub_date || '').localeCompare(a.pub_date || '');
                }
                if (mode === 'date') {
                    return (b.pub_date || '').localeCompare(a.pub_date || '');
                }
                return 0;
            });
            this.selectedIds = ids;
            this.$nextTick(() => this.initSortable());
        },

        // itemById(), selectItem(), removeItem(), selectAllVisible() proviennent du mixin
        // partagé NewsArticlePicker() (voir commentaire plus haut) — elles ré-invoquent déjà
        // this.initSortable() après ajout (guard optionnelle, typeof this.initSortable ===
        // 'function', puisque video-goal-builder n'a pas de drag-drop SortableJS).

        initSortable() {
            const el = document.getElementById('cb-sortable');
            if (!el || typeof Sortable === 'undefined') return;
            if (this.sortable) this.sortable.destroy();
            this.sortable = Sortable.create(el, {
                handle: '.cb-handle',
                animation: 150,
                ghostClass: 'cb-drag-ghost',
                chosenClass: 'cb-drag-chosen',
                forceFallback: true,
                fallbackTolerance: 5,
                onEnd: (evt) => {
                    const oldIndex = evt.oldIndex;
                    const newIndex = evt.newIndex;
                    if (oldIndex === undefined || newIndex === undefined || oldIndex === newIndex) return;
                    // Splice array directement (évite race conditions Alpine/DOM)
                    const arr = [...this.selectedIds];
                    const [moved] = arr.splice(oldIndex, 1);
                    arr.splice(newIndex, 0, moved);
                    this.selectedIds = arr;
                    // Force Alpine à re-render dans le bon ordre (Sortable a déjà bougé le DOM)
                    this.$nextTick(() => this.initSortable());
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
            return total >= 3 && total <= 20;
        },

        // Zone qualité éditoriale selon nombre d'items
        get selectionZone() {
            const t = this.selectedIds.length + this.manualUrlsArray.length;
            if (t === 0) return { label: 'Vide', color: '#94a3b8', tip: 'Sélectionne 3 à 20 actualités.' };
            if (t < 3) return { label: 'Trop court', color: '#dc2626', tip: 'Min. 3 pour générer le prompt.' };
            if (t <= 7) return { label: 'Concentré court', color: '#eab308', tip: 'Article ~1000-1500 mots.' };
            if (t <= 12) return { label: 'Sweet spot', color: '#22c55e', tip: 'Article ~1800-2500 mots (recommandé).' };
            if (t <= 20) return { label: 'Concentré long', color: '#f97316', tip: 'Article ~3000-4000 mots, plus dense.' };
            return { label: 'Au-dessus du max', color: '#dc2626', tip: 'Max. 20 actualités.' };
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
                window.dispatchEvent(new CustomEvent('notification-toast', {
                    detail: { type: 'success', message: 'Prompt copié dans le presse-papiers !' }
                }));
                this.copyOk = true;
                setTimeout(() => { this.copyOk = false; }, 2500);
            } catch (e) {
                this.generateError = 'Impossible de copier (clipboard API indisponible).';
            }
        },

        // Envoi de la sélection vers le Générateur d'objectif vidéo : mécanisme 100% client-side
        // (sessionStorage), aucun appel serveur — cohérent avec la philosophie déjà établie entre
        // ces deux outils (voir Modules/News/routes/web.php).
        pushToVideoGoal() {
            if (this.selectedIds.length === 0) return;
            const items = this.selectedIds.map(id => this.itemById(id)).filter(Boolean);
            sessionStorage.setItem('lv_vgb_import', JSON.stringify({ items, selectedIds: this.selectedIds }));
            window.location.href = this.endpoints.videoGoalUrl;
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
                    manualColors: this.manualColors,
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
                if (s.manualColors && typeof s.manualColors === 'object') {
                    this.manualColors = s.manualColors;
                }
                if (hadAny) {
                    this.draftRestored = true;
                    this.draftRestoredAt = s.savedAt ? new Date(s.savedAt).toLocaleString('fr-CA') : '';
                }
            } catch (e) {}
        },
    };

    // Fusionne le mixin partagé (état + getters + méthodes du sélecteur d'actualités) sur l'objet
    // AVANT qu'Alpine ne le rende réactif (Alpine.reactive() enveloppe la valeur RETOURNÉE par
    // cette factory x-data, pas l'objet manipulé après coup). Object.defineProperties (PAS
    // Object.assign ni spread) préserve les `get xxx() {...}` comme de vrais getters — un spread
    // les figerait en valeurs statiques et casserait la réactivité.
    // NOTE IMPORTANTE : fusionner APRÈS coup dans init() (sur `this`, déjà réactif à ce stade) ne
    // fonctionne PAS de façon fiable avec l'Alpine embarqué par Livewire dans ce projet — vérifié
    // en local (Playwright) : les propriétés ajoutées via defineProperties sur `this` à l'intérieur
    // d'init() n'étaient PAS visibles par le reste du template (erreurs "x is not defined"). D'où
    // la fusion ICI, avant le `return`, sur l'objet encore brut.
    Object.defineProperties(state, Object.getOwnPropertyDescriptors(NewsArticlePicker({
        fetchStrategy: (ctx) => ({
            method: 'GET',
            url: ctx.endpoints.news + '?week_start=' + ctx.weekStart + '&week_end=' + ctx.weekEnd,
        }),
    })));

    return state;
}
</script>
@endsection
