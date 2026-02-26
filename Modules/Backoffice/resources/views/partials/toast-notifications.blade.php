<div class="position-fixed bottom-0 end-0 p-24" style="z-index: 1055; max-width: 380px;"
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
         iconFor(type) {
             if (type === 'warning') return 'solar:danger-triangle-outline';
             if (type === 'critical') return 'solar:close-circle-outline';
             return 'solar:info-circle-outline';
         },
         borderFor(type) {
             if (type === 'warning') return 'border-warning-main';
             if (type === 'critical') return 'border-danger-main';
             return 'border-primary-600';
         },
         textFor(type) {
             if (type === 'warning') return 'text-warning-main';
             if (type === 'critical') return 'text-danger-main';
             return 'text-primary-600';
         }
     }"
     @notification-toast.window="addToast($event.detail[0] || $event.detail)">

    <template x-for="toast in toasts" :key="toast.id">
        <div class="mb-8"
             x-show="true"
             x-transition:enter="transition-all"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-all"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             role="alert"
             aria-live="assertive">
            <div class="card shadow-lg border-start border-3 p-12 radius-8"
                 :class="borderFor(toast.type)">
                <div class="d-flex align-items-start gap-12">
                    <iconify-icon :icon="iconFor(toast.type)"
                                  class="text-2xl flex-shrink-0 mt-2"
                                  :class="textFor(toast.type)"></iconify-icon>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <h6 class="mb-4 fw-semibold text-sm" x-text="toast.title"></h6>
                            <button type="button" class="btn-close btn-sm" @click="removeToast(toast.id)" aria-label="Fermer"></button>
                        </div>
                        <p class="mb-0 text-secondary-light text-xs" x-text="toast.message"></p>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
