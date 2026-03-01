<div class="position-fixed bottom-0 end-0 p-4" style="z-index: 1055; max-width: 380px;"
     x-data="{
         toasts: [],
         addToast(data) {
             if (this.toasts.length >= 3) { this.toasts.shift(); }
             const toast = { id: Date.now() + Math.random(), type: data.type || 'info', title: data.title || '', message: data.message || '' };
             this.toasts.push(toast);
             setTimeout(() => { this.removeToast(toast.id); }, 5000);
         },
         removeToast(id) {
             this.toasts = this.toasts.filter(t => t.id !== id);
         },
         iconSvgFor(type) {
             if (type === 'warning') return '<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'20\' height=\'20\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><path d=\'m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z\'/><path d=\'M12 9v4\'/><path d=\'M12 17h.01\'/></svg>';
             if (type === 'critical') return '<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'20\' height=\'20\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><circle cx=\'12\' cy=\'12\' r=\'10\'/><path d=\'m15 9-6 6\'/><path d=\'m9 9 6 6\'/></svg>';
             return '<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'20\' height=\'20\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><circle cx=\'12\' cy=\'12\' r=\'10\'/><path d=\'M12 16v-4\'/><path d=\'M12 8h.01\'/></svg>';
         },
         borderFor(type) {
             if (type === 'warning') return 'border-warning';
             if (type === 'critical') return 'border-danger';
             return 'border-primary';
         },
         textFor(type) {
             if (type === 'warning') return 'text-warning';
             if (type === 'critical') return 'text-danger';
             return 'text-primary';
         }
     }"
     @notification-toast.window="addToast($event.detail[0] || $event.detail)">

    <template x-for="toast in toasts" :key="toast.id">
        <div class="mb-2"
             x-show="true"
             x-transition:enter="transition-all"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-all"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             role="alert"
             aria-live="assertive">
            <div class="card shadow-lg border-start border-3 p-2 rounded-2"
                 :class="borderFor(toast.type)">
                <div class="d-flex align-items-start gap-2">
                    <span x-html="iconSvgFor(toast.type)" class="flex-shrink-0 mt-1" :class="textFor(toast.type)"></span>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <h6 class="mb-1 fw-semibold text-sm" x-text="toast.title"></h6>
                            <button type="button" class="btn-close btn-sm" @click="removeToast(toast.id)" aria-label="Fermer"></button>
                        </div>
                        <p class="mb-0 text-muted small" x-text="toast.message"></p>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
