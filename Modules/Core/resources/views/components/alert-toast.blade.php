@props(['position' => 'top-right', 'maxStack' => 3])

@once
<style>
.ct-toast-container { position: fixed; z-index: 100000; display: flex; flex-direction: column; gap: 12px; pointer-events: none; }
.ct-toast-container.top-right { top: 20px; right: 20px; }
.ct-toast-container.top-left { top: 20px; left: 20px; }
.ct-toast-container.bottom-right { bottom: 20px; right: 20px; }
.ct-toast-container.bottom-left { bottom: 20px; left: 20px; }
.ct-toast-item { display: flex; align-items: flex-start; gap: 12px; padding: 14px 16px; border-radius: 10px; color: #fff; max-width: 380px; min-width: 260px; box-shadow: 0 8px 24px rgba(0,0,0,0.18); pointer-events: auto; font-size: 0.92rem; line-height: 1.45; }
.ct-toast-item.success { background-color: #065F46; }
.ct-toast-item.error { background-color: #991B1B; }
.ct-toast-item.warning { background-color: #92400E; }
.ct-toast-item.info { background-color: #1E3A8A; }
.ct-toast-icon { font-size: 1.15rem; line-height: 1; flex-shrink: 0; margin-top: 1px; }
.ct-toast-message { flex: 1; word-break: break-word; }
.ct-toast-close { background: none; border: none; color: #fff; cursor: pointer; font-size: 1.25rem; line-height: 1; padding: 0 4px; opacity: 0.85; }
.ct-toast-close:hover, .ct-toast-close:focus-visible { opacity: 1; outline: 2px solid #fff; outline-offset: 2px; }
</style>
@endonce

<div x-data="{
    toasts: [],
    addToast(toast) {
        if (this.toasts.length >= {{ (int) $maxStack }}) this.toasts.shift();
        this.toasts.push(toast);
        if (toast.duration > 0) {
            setTimeout(() => this.removeToast(toast.id), toast.duration);
        }
    },
    removeToast(id) {
        this.toasts = this.toasts.filter(t => t.id !== id);
    }
}"
x-init="window.addEventListener('toast-show', (e) => {
    const detail = e.detail || {};
    addToast({
        id: Date.now() + Math.random(),
        variant: detail.variant || 'success',
        message: detail.message || '',
        duration: typeof detail.duration === 'number' ? detail.duration : 4000
    });
});">
    <template x-teleport="body">
        <div class="ct-toast-container {{ $position }}" x-cloak>
            <template x-for="toast in toasts" :key="toast.id">
                <div x-transition.opacity
                     :class="'ct-toast-item ' + toast.variant"
                     :role="(toast.variant === 'error' || toast.variant === 'warning') ? 'alert' : 'status'"
                     :aria-live="(toast.variant === 'error' || toast.variant === 'warning') ? 'assertive' : 'polite'">
                    <span class="ct-toast-icon" aria-hidden="true">
                        <template x-if="toast.variant === 'success'"><span>✓</span></template>
                        <template x-if="toast.variant === 'error'"><span>✕</span></template>
                        <template x-if="toast.variant === 'warning'"><span>⚠</span></template>
                        <template x-if="toast.variant === 'info'"><span>ℹ</span></template>
                    </span>
                    <span class="ct-toast-message" x-text="toast.message"></span>
                    <button type="button" class="ct-toast-close" @click="removeToast(toast.id)" aria-label="Fermer">&times;</button>
                </div>
            </template>
        </div>
    </template>
</div>
