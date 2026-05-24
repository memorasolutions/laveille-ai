@props(['author'])

@php
    $feedUrl = url('/@'.$author->slug.'/feed.xml');
    $authorName = $author->display_name ?? $author->slug;
@endphp

<div x-data="{
        authorSlug: '{{ $author->slug }}',
        isFollowed: false,
        showFeedModal: false,
        feedUrl: '{{ $feedUrl }}',
        toastMsg: '',
        init() {
            try {
                const followed = JSON.parse(localStorage.getItem('lv-followed-authors') || '[]');
                this.isFollowed = Array.isArray(followed) && followed.includes(this.authorSlug);
            } catch (e) { this.isFollowed = false; }
        },
        toggleFollow() {
            let followed = [];
            try { followed = JSON.parse(localStorage.getItem('lv-followed-authors') || '[]'); } catch (e) {}
            if (!Array.isArray(followed)) followed = [];
            if (this.isFollowed) {
                followed = followed.filter(s => s !== this.authorSlug);
                this.isFollowed = false;
                this.toast('Tu ne suis plus cet auteur');
            } else {
                followed.push(this.authorSlug);
                this.isFollowed = true;
                this.showFeedModal = true;
            }
            try { localStorage.setItem('lv-followed-authors', JSON.stringify(followed)); } catch (e) {}
        },
        copyFeedUrl() {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(this.feedUrl).then(() => this.toast('Lien RSS copié !'));
            } else {
                this.toast('Copie automatique indisponible — sélectionne le lien manuellement.');
            }
        },
        toast(msg) {
            this.toastMsg = msg;
            const t = document.createElement('div');
            t.setAttribute('role', 'status');
            t.setAttribute('aria-live', 'polite');
            t.textContent = msg;
            t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#064E5A;color:white;padding:12px 20px;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,0.2);z-index:9999;font-size:14px;';
            document.body.appendChild(t);
            setTimeout(() => { if (t.parentNode) document.body.removeChild(t); }, 2500);
        }
     }"
     @keydown.escape.window="showFeedModal = false"
     class="lv-follow-wrapper"
     style="display:inline-block; position:relative;">
    <button
        type="button"
        @click="toggleFollow()"
        :aria-pressed="isFollowed"
        :aria-label="isFollowed ? 'Ne plus suivre {{ $authorName }}' : 'Suivre {{ $authorName }}'"
        :title="isFollowed ? 'Cliquer pour ne plus suivre' : 'S\'abonner aux articles'"
        :style="isFollowed
            ? 'min-height:44px; min-width:44px; padding:8px 16px; display:inline-flex; align-items:center; gap:6px; background:var(--c-accent,#9A2A06); color:white; border:2px solid var(--c-accent,#9A2A06); border-radius:999px; cursor:pointer; font-weight:600; font-size:14px;'
            : 'min-height:44px; min-width:44px; padding:8px 16px; display:inline-flex; align-items:center; gap:6px; background:transparent; color:var(--c-primary,#064E5A); border:2px solid var(--c-primary,#064E5A); border-radius:999px; cursor:pointer; font-weight:600; font-size:14px;'"
        onfocus="this.style.outline='3px solid #9A2A06'; this.style.outlineOffset='2px';"
        onblur="this.style.outline='none';"
        class="lv-follow-btn"
    >
        <span x-text="isFollowed ? '✓ Suivi' : '🔔 Suivre'"></span>
    </button>

    <div x-show="showFeedModal"
         x-cloak
         @click="showFeedModal = false"
         x-transition.opacity.duration.200ms
         role="dialog"
         aria-modal="true"
         aria-labelledby="lv-follow-modal-title"
         style="position:fixed; inset:0; z-index:9998; display:flex; align-items:center; justify-content:center; padding:1rem; background:rgba(0,0,0,0.5); backdrop-filter:blur(4px);"
         class="lv-follow-modal">
        <div @click.stop
             style="background:var(--c-cream,#F8FAFB); max-width:480px; width:100%; padding:1.5rem; border-radius:12px; box-shadow:0 20px 60px rgba(0,0,0,0.25);">
            <h2 id="lv-follow-modal-title" style="margin:0 0 12px; font-size:20px; font-weight:700; color:var(--c-primary,#064E5A);">
                🎉 Tu suis désormais {{ $authorName }}
            </h2>
            <p style="margin:0 0 16px; color:#3F4554; line-height:1.5;">
                Pour recevoir les nouveaux articles dans ton lecteur RSS préféré, copie ce lien :
            </p>
            <div style="display:flex; gap:8px; align-items:stretch;">
                <input
                    type="text"
                    :value="feedUrl"
                    readonly
                    aria-label="URL du flux RSS"
                    onclick="this.select();"
                    style="flex:1; padding:10px 12px; border:2px solid rgba(6,78,90,0.2); border-radius:6px; background:white; color:#064E5A; font-size:13px; font-family:monospace;"
                />
                <button type="button"
                        @click="copyFeedUrl()"
                        aria-label="Copier l'URL du flux RSS"
                        style="min-height:44px; padding:8px 16px; background:var(--c-accent,#9A2A06); color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:14px;"
                        onfocus="this.style.outline='3px solid #064E5A'; this.style.outlineOffset='2px';"
                        onblur="this.style.outline='none';">
                    📋 Copier
                </button>
            </div>
            <p style="margin:14px 0 0; font-size:12px; color:#5A6270;">
                💡 Astuce : utilise un lecteur RSS comme Feedly, Inoreader ou NetNewsWire.
            </p>
            <button type="button"
                    @click="showFeedModal = false"
                    style="margin-top:16px; min-height:44px; padding:8px 16px; background:transparent; border:2px solid var(--c-primary,#064E5A); color:var(--c-primary,#064E5A); border-radius:6px; cursor:pointer; font-weight:600;">
                Fermer
            </button>
        </div>
    </div>
</div>
