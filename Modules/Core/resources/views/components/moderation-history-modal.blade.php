{{-- Modale historique de modération — chargée 1x dans le layout master
     Écoute l'événement Alpine 'open-moderation-history' --}}
<div x-data="{
    open: false,
    history: [],
    loading: false,
    async loadHistory(event) {
        this.loading = true;
        this.open = true;
        this.history = [];
        try {
            const response = await fetch('/moderation/' + event.detail.type + '/' + event.detail.id + '/history');
            if (response.ok) {
                this.history = await response.json();
            }
        } catch (e) {
            console.error('Erreur chargement historique', e);
        }
        this.loading = false;
    }
}" @open-moderation-history.window="loadHistory" x-show="open" x-cloak
   style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;">

    {{-- Overlay --}}
    <div @click="open = false" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);"></div>

    {{-- Contenu modale --}}
    <div @click.stop style="position:relative;background:#fff;border-radius:0.75rem;max-width:500px;width:100%;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,0.15);">

        {{-- Header --}}
        <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;">
            <h4 style="margin:0;font-family:var(--f-heading);font-weight:700;color:var(--c-dark);font-size:16px;">📜 {{ __('Historique de modération') }}</h4>
            <button @click="open = false" style="background:none;border:none;font-size:20px;cursor:pointer;color:#374151;">&times;</button>
        </div>

        {{-- Body --}}
        <div style="flex:1;overflow-y:auto;padding:16px 20px;">
            {{-- Loading --}}
            <div x-show="loading" style="text-align:center;padding:20px;">
                <div style="display:inline-block;width:24px;height:24px;border:3px solid #e5e7eb;border-top-color:var(--c-primary);border-radius:50%;animation:spin 0.6s linear infinite;"></div>
            </div>

            {{-- Timeline --}}
            <template x-for="(entry, index) in history" :key="index">
                <div style="padding:10px 0;border-bottom:1px solid #f3f4f6;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                        <strong style="font-size:13px;color:var(--c-dark);" x-text="entry.user"></strong>
                        <span style="font-size:12px;color:#374151;" x-text="entry.date"></span>
                    </div>
                    <div style="font-size:13px;color:#4b5563;" x-text="entry.action"></div>
                    <template x-if="entry.properties && Object.keys(entry.properties).length > 0">
                        <div style="margin-top:4px;padding:6px 8px;background:#f9fafb;border-radius:0.5rem;font-size:11px;color:#374151;font-family:monospace;" x-text="JSON.stringify(entry.properties)"></div>
                    </template>
                </div>
            </template>

            {{-- Vide --}}
            <div x-show="!loading && history.length === 0" style="text-align:center;padding:24px;color:#374151;font-size:14px;">
                {{ __('Aucun historique de modération.') }}
            </div>
        </div>

        {{-- Footer --}}
        <div style="padding:12px 20px;border-top:1px solid #e5e7eb;text-align:right;">
            <button @click="open = false" style="padding:8px 16px;border:1px solid #e5e7eb;border-radius:0.5rem;background:#fff;cursor:pointer;font-weight:600;color:#374151;">{{ __('Fermer') }}</button>
        </div>
    </div>
</div>
