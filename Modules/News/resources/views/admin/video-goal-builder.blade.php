@extends('backoffice::layouts.admin', ['title' => 'Générateur d\'objectif vidéo', 'subtitle' => 'Sélectionne des actualités et génère un objectif de vidéo pour le Prompteur'])

@push('styles')
<style>
    .cb-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:18px; margin-bottom:18px; }
    .cb-section-title { font-weight:700; color:#0B7285; font-size:15px; margin-bottom:10px; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    .cb-counter { background:#0B7285; color:#fff; padding:2px 10px; border-radius:10px; font-size:12px; font-weight:700; }
    .cb-empty { text-align:center; color:#57606f; padding:24px; font-style:italic; }
    .cb-btn { background:#0B7285; color:#fff; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; font-weight:600; min-height:44px; min-width:44px; display:inline-flex; align-items:center; gap:6px; justify-content:center; }
    .cb-btn:hover:not(:disabled) { background:#075f6f; }
    .cb-btn:disabled { background:#6b7280; cursor:not-allowed; }
    .cb-btn-secondary { background:#fff; color:#0B7285; border:1.5px solid #0B7285; }
    .cb-btn-secondary:hover:not(:disabled) { background:#0B7285; color:#fff; }
    .cb-btn:focus-visible, .cb-btn-secondary:focus-visible, input:focus-visible, textarea:focus-visible, label.vgb-news-item:focus-within {
        outline: 3px solid #064E5A; outline-offset: 2px;
    }
    [x-cloak] { display:none !important; }

    /* Cartes d'actualités sélectionnables (calquées sur .cb-news-item du concentre-builder) */
    .vgb-news-item {
        display:flex; gap:12px; padding:12px; border:1px solid #e5e7eb; border-left:6px solid #94a3b8;
        border-radius:8px; background:#fff; margin-bottom:10px; align-items:flex-start; cursor:pointer;
        transition:background .15s, border-color .15s; min-height:44px;
    }
    .vgb-news-item:hover { background:#f9fafb; }
    .vgb-news-item.is-selected { border-color:#0B7285; border-left-color:#0B7285; background:#ecfeff; }
    .vgb-checkbox { width:24px; height:24px; min-width:24px; margin-top:2px; accent-color:#0B7285; cursor:pointer; }
    .vgb-thumb { width:64px; height:64px; min-width:64px; object-fit:cover; border-radius:6px; background:#e5e7eb; }
    .vgb-title { font-weight:600; color:#1f2937; font-size:14px; line-height:1.35; }
    .vgb-meta { font-size:12px; color:#57606f; margin-top:4px; }
    .vgb-summary { font-size:13px; color:#374151; margin-top:6px; line-height:1.45; }
    .vgb-pre {
        background:#0f172a; color:#e2e8f0; padding:14px; border-radius:8px; max-height:340px; overflow:auto;
        font-family:'SF Mono','Monaco','Consolas',monospace; font-size:13px; line-height:1.55; white-space:pre-wrap;
        word-break:break-word; width:100%; border:none; resize:vertical;
    }
    .vgb-error {
        color:#8f1d1d; background:#fef2f2; border:1px solid #fca5a5; border-radius:8px; padding:10px 14px;
        font-weight:600; font-size:13px; margin-top:10px;
    }
</style>
@endpush

@section('content')
<div
    x-data="videoGoalBuilder({
        defaultStart: @js($defaultStart),
        defaultEnd: @js($defaultEnd),
        newsEndpoint: @js(route('admin.news.video-goal.news')),
        generateEndpoint: @js(route('admin.news.video-goal.generate')),
    })"
    x-init="init()"
>
    {{-- En-tête --}}
    <div class="cb-card">
        <h1 style="font-size:20px; font-weight:800; color:#064E5A; margin-bottom:6px;">🎬 Générateur d'objectif vidéo</h1>
        <p style="color:#374151; font-size:14px; margin:0;">
            Sélectionnez des actualités pour générer automatiquement un objectif de vidéo à coller dans le Prompteur.
        </p>
    </div>

    {{-- Plage de dates libre --}}
    <div class="cb-card">
        <div class="cb-section-title">📅 Plage de dates</div>
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold" for="vgb-date-start">Du</label>
                <input type="date" id="vgb-date-start" class="form-control" x-model="dateStart" :max="dateEnd">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold" for="vgb-date-end">Au</label>
                <input type="date" id="vgb-date-end" class="form-control" x-model="dateEnd" :min="dateStart" :max="todayDate()">
            </div>
            <div class="col-md-4">
                <button type="button" class="cb-btn" @click="fetchNews()" :disabled="loading.news || !dateStart || !dateEnd">
                    <span x-show="!loading.news">📰 Charger les actualités</span>
                    <span x-show="loading.news" x-cloak>⏳ Chargement…</span>
                </button>
            </div>
        </div>
        <div class="vgb-error" role="alert" aria-live="polite" x-show="fetchError" x-cloak x-text="'⚠ ' + fetchError"></div>
    </div>

    {{-- Liste des actualités de la plage --}}
    <div class="cb-card">
        <div class="cb-section-title">
            📋 Actualités de la plage
            <span class="cb-counter" x-show="hasFetched" x-cloak x-text="newsItems.length + ' trouvée' + (newsItems.length > 1 ? 's' : '')"></span>
            <span class="cb-counter" style="background:#064E5A;" x-text="selectedIds.length + ' actualité' + (selectedIds.length > 1 ? 's' : '') + ' sélectionnée' + (selectedIds.length > 1 ? 's' : '')"></span>
        </div>

        <div style="max-height:520px; overflow-y:auto;">
            <template x-for="item in newsItems" :key="item.id">
                <label class="vgb-news-item" :class="{ 'is-selected': isSelected(item.id) }" :for="'vgb-news-' + item.id">
                    <input
                        type="checkbox"
                        class="vgb-checkbox"
                        :id="'vgb-news-' + item.id"
                        :checked="isSelected(item.id)"
                        @change="toggleSelect(item.id)"
                        :aria-label="'Sélectionner l\'actualité : ' + item.title"
                    >
                    <img :src="item.image_url" x-show="item.image_url" loading="lazy" class="vgb-thumb" alt="" onerror="this.style.display='none'">
                    <div style="flex:1; min-width:0;">
                        <div class="vgb-title" x-text="item.title"></div>
                        <div class="vgb-meta">
                            <span x-text="item.source_name || 'Source inconnue'"></span> · <span x-text="item.pub_date_short"></span>
                            <span x-show="item.category_tag" x-cloak> · <span x-text="item.category_tag"></span></span>
                        </div>
                        <div class="vgb-summary" x-text="item.summary" x-show="item.summary"></div>
                    </div>
                </label>
            </template>

            <div class="cb-empty" x-show="hasFetched && newsItems.length === 0 && !loading.news" x-cloak>
                Aucune actualité publiée dans cette plage de dates.
            </div>
            <div class="cb-empty" x-show="!hasFetched && !loading.news" x-cloak>
                Choisis une plage de dates puis clique « Charger les actualités ».
            </div>
        </div>
    </div>

    {{-- Génération de l'objectif --}}
    <div class="cb-card">
        <div class="cb-section-title">✨ Objectif de vidéo généré</div>
        <div class="mb-2 d-flex flex-wrap gap-2 align-items-center">
            <button type="button" class="cb-btn" @click="generateGoal()" :disabled="selectedIds.length === 0 || loading.generate">
                <span x-show="!loading.generate">⚡ Générer l'objectif</span>
                <span x-show="loading.generate" x-cloak>⏳ Génération en cours (peut prendre quelques secondes)…</span>
            </button>
            <button type="button" class="cb-btn cb-btn-secondary" @click="copyGoal()" :disabled="!generatedGoal" x-show="generatedGoal" x-cloak>📋 Copier</button>
            <a href="/outils/prompteur" target="_blank" rel="noopener noreferrer" class="cb-btn cb-btn-secondary">Ouvrir le Prompteur →</a>
        </div>

        <div class="vgb-error" role="alert" aria-live="polite" x-show="generateError" x-cloak x-text="'⚠ ' + generateError"></div>

        <div aria-live="polite">
            <label for="vgb-result" class="form-label small fw-bold" x-show="generatedGoal || loading.generate" x-cloak>Texte à coller dans le champ « Objectif de la vidéo »</label>
            <textarea id="vgb-result" class="vgb-pre" rows="6" readonly x-show="generatedGoal" x-cloak x-text="generatedGoal"></textarea>
            <div class="cb-empty" x-show="!generatedGoal && !loading.generate" x-cloak>
                L'objectif généré apparaîtra ici. Sélectionne au moins une actualité puis clique « Générer l'objectif ».
            </div>
        </div>
    </div>
</div>

<script>
function videoGoalBuilder(opts) {
    return {
        dateStart: opts.defaultStart,
        dateEnd: opts.defaultEnd,
        newsItems: [],
        selectedIds: [],
        hasFetched: false,
        fetchError: '',
        generateError: '',
        generatedGoal: '',
        loading: { news: false, generate: false },
        endpoints: { news: opts.newsEndpoint, generate: opts.generateEndpoint },

        init() {
            this.fetchNews();
        },

        todayDate() { return new Date().toISOString().split('T')[0]; },

        isSelected(id) { return this.selectedIds.includes(id); },

        toggleSelect(id) {
            if (this.isSelected(id)) {
                this.selectedIds = this.selectedIds.filter(i => i !== id);
            } else {
                this.selectedIds = [...this.selectedIds, id];
            }
        },

        async fetchNews() {
            if (!this.dateStart || !this.dateEnd) return;
            this.loading.news = true;
            this.fetchError = '';
            try {
                const res = await fetch(this.endpoints.news, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ date_start: this.dateStart, date_end: this.dateEnd }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.fetchError = data.error || data.message || ('Erreur HTTP ' + res.status);
                    this.newsItems = [];
                    this.hasFetched = true;
                    return;
                }
                this.newsItems = data.items || [];
                this.selectedIds = this.selectedIds.filter(id => this.newsItems.some(n => n.id === id));
                this.hasFetched = true;
            } catch (e) {
                this.fetchError = 'Erreur réseau : ' + e.message;
                this.newsItems = [];
                this.hasFetched = true;
            } finally {
                this.loading.news = false;
            }
        },

        async generateGoal() {
            if (this.selectedIds.length === 0) return;
            this.loading.generate = true;
            this.generateError = '';
            this.generatedGoal = '';
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
                    body: JSON.stringify({ article_ids: this.selectedIds }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.generateError = data.error || data.message || ('Erreur HTTP ' + res.status);
                    return;
                }
                this.generatedGoal = data.goal || '';
            } catch (e) {
                this.generateError = 'Erreur réseau : ' + e.message;
            } finally {
                this.loading.generate = false;
            }
        },

        // NOTE : window.copyToClipboard (Modules/FrontTheme/.../master.blade.php) n'est PAS chargé
        // dans le layout backoffice::layouts.admin (ce script n'y est jamais inclus). On reprend
        // donc le mécanisme de toast déjà utilisé par concentre-builder.blade.php (même layout) :
        // event window "notification-toast" écouté par backoffice::partials.toast-notifications.
        async copyGoal() {
            if (!this.generatedGoal) return;
            try {
                await navigator.clipboard.writeText(this.generatedGoal);
                window.dispatchEvent(new CustomEvent('notification-toast', {
                    detail: { type: 'success', message: "Objectif copié dans le presse-papiers !" }
                }));
            } catch (e) {
                window.dispatchEvent(new CustomEvent('notification-toast', {
                    detail: { type: 'critical', message: 'Copie impossible - copie le texte manuellement.' }
                }));
            }
        },
    };
}
</script>
@endsection
