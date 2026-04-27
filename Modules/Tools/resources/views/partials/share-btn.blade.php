<!-- Partiel de bouton de partage avec Alpine.js, Bootstrap inline, SVG Lucide et gestion clipboard/share API -->
@php $shareData = $tool->getShareData(); @endphp
<div style="position:relative; display:inline-block;" x-data="{
    feedback: null,
    busy: false,
    share() {
        if (this.busy) return;
        this.busy = true;

        const title = {{ \Illuminate\Support\Js::from($shareData['title']) }};
        const description = {{ \Illuminate\Support\Js::from($shareData['description']) }};
        const url = {{ \Illuminate\Support\Js::from($shareData['url']) }};
        const clipboardText = {{ \Illuminate\Support\Js::from($shareData['clipboard_text']) }};

        if (navigator.share && navigator.canShare && navigator.canShare({ title, text: description, url })) {
            navigator.share({ title, text: description, url })
                .then(() => {
                    this.feedback = 'shared';
                    this.reset();
                })
                .catch(err => {
                    if (err.name === 'AbortError') {
                        this.busy = false;
                    } else {
                        this.copyFallback(clipboardText);
                    }
                });
        } else {
            this.copyFallback(clipboardText);
        }
    },
    copyFallback(text) {
        navigator.clipboard.writeText(text)
            .then(() => {
                this.feedback = 'copied';
                this.reset();
            })
            .catch(() => {
                this.feedback = 'error';
                this.reset();
            });
    },
    reset() {
        setTimeout(() => {
            this.feedback = null;
            this.busy = false;
        }, 2500);
    }
}">
    <button
        type="button"
        class="ct-btn ct-btn-primary ct-btn-icon"
        style="border-radius:50%; width:32px; height:32px; padding:0; line-height:32px; flex-shrink:0;"
        @click="share()"
        :disabled="busy"
        aria-label="Partager {{ e($tool->name) }} sur les reseaux sociaux"
        title="Partager {{ e($tool->name) }}">
        <svg width="16" height="16" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;" aria-hidden="true" focusable="false">
            <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path>
            <polyline points="16 6 12 2 8 6"></polyline>
            <line x1="12" y1="2" x2="12" y2="15"></line>
        </svg>
    </button>

    <div
        x-show="feedback"
        x-cloak
        x-transition:enter.opacity.duration.300ms
        x-transition:leave.opacity.duration.300ms
        role="status"
        aria-live="polite"
        aria-atomic="true"
        x-bind:style="'position:fixed; bottom:20px; right:20px; z-index:9999; padding:12px 16px; border-radius:8px; color:#ffffff; font-size:0.9rem; font-weight:500; box-shadow:0 4px 12px rgba(0,0,0,0.2); background-color:' + (feedback === 'error' ? '#991b1b' : '#065f46')">
        <span x-text="feedback === 'shared' ? 'Partage' : feedback === 'copied' ? 'Copie dans le presse-papier' : 'Erreur de partage'"></span>
    </div>
</div>
