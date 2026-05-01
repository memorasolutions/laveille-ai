{{-- Composant toast réutilisable — notification temporaire bas de page
     Écoute l'événement Alpine 'toast' : $dispatch('toast', { message: '...', type: 'success' })
     Types: success (vert), error (rouge), info (bleu)
     Usage dans n'importe quelle page avec Alpine.js :
       $dispatch('toast', { message: 'Sauvegardé avec succès' })
--}}
<div x-data="{ show: false, message: '', type: 'success', timeout: null }"
     @toast.window="
        message = $event.detail.message || '';
        type = $event.detail.type || 'success';
        show = true;
        clearTimeout(timeout);
        timeout = setTimeout(() => show = false, 3000);
     "
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-4"
     x-cloak
     style="position: fixed; bottom: 24px; right: 24px; z-index: 10001; max-width: 360px;"
     role="status"
     aria-live="polite">
    <div style="padding: 12px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 10px; box-shadow: 0 8px 24px rgba(0,0,0,0.15);"
         :style="type === 'success' ? 'background: #065f46; color: #fff;' : type === 'error' ? 'background: #DC2626; color: #fff;' : 'background: #0B7285; color: #fff;'">
        <span x-show="type === 'success'" style="font-size: 18px;">✓</span>
        <span x-show="type === 'error'" style="font-size: 18px;">✕</span>
        <span x-show="type === 'info'" style="font-size: 18px;">ℹ</span>
        <span x-text="message"></span>
        <button @click="show = false" style="background: none; border: none; color: inherit; cursor: pointer; margin-left: auto; opacity: 0.7; font-size: 16px;">✕</button>
    </div>
</div>
