<!-- Admin inline moderation — visible uniquement pour les admins -->
@can('view_admin_panel')
<div style="display:inline-flex;gap:4px;margin-top:6px;flex-wrap:wrap;" x-data="{ done: false }">
    <template x-if="!done">
        <div style="display:flex;gap:4px;flex-wrap:wrap;">
            @if(empty($isApproved))
            <button @click="var tk=document.querySelector('meta[name=csrf-token]')?.content;fetch('{{ $approveUrl }}', {method:'POST',headers:{'X-CSRF-TOKEN':tk,'Accept':'application/json'}}).then(r=>{if(r.ok){done=true;$el.closest('[data-mod-item]')?.classList.add('border-success')}})"
                class="ct-btn ct-btn-primary ct-btn-xs">
                {{ __('Approuver') }}
            </button>
            @endif
            @if(isset($deleteUrl))
            <button onclick="var tk=document.querySelector('meta[name=csrf-token]')?.content;window.__confirmAction=()=>fetch('{{ $deleteUrl }}',{method:'POST',headers:{'X-CSRF-TOKEN':tk,'Accept':'application/json'}}).then(r=>{if(r.ok)location.reload()});window.dispatchEvent(new CustomEvent('confirm-action',{detail:{title:'{{ __("Supprimer") }}',message:'{{ __("Supprimer cette ressource ?") }}'}}))"
                class="ct-btn ct-btn-outline ct-btn-xs">
                {{ __('Supprimer') }}
            </button>
            @endif
            <button onclick="var tk=document.querySelector('meta[name=csrf-token]')?.content;window.__confirmAction=()=>fetch('{{ $rejectUrl }}',{method:'POST',headers:{'X-CSRF-TOKEN':tk,'Accept':'application/json'}}).then(r=>{if(r.ok)location.reload()});window.dispatchEvent(new CustomEvent('confirm-action',{detail:{title:'{{ __("Rejeter (-10 pts)") }}',message:'{{ __("Rejeter et retirer 10 points de réputation ?") }}'}}))"
                class="ct-btn ct-btn-outline-danger ct-btn-xs"
                title="{{ __('Rejeter et retirer 10 points de réputation') }}">
                {{ __('Rejeter (-10 pts)') }}
            </button>
            @isset($uploadScreenshotUrl)
            <button type="button"
                @click.stop="$refs['screendlg_{{ $resourceIdForScreenshot ?? 'x' }}'].showModal()"
                class="ct-btn ct-btn-outline-primary ct-btn-xs"
                title="{{ __('Uploader screenshot personnalisé') }}">
                📸 {{ __('Screenshot') }}
            </button>
            <dialog x-ref="screendlg_{{ $resourceIdForScreenshot ?? 'x' }}" style="border:0;padding:24px;border-radius:12px;max-width:500px;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                <form method="dialog" style="margin:0;text-align:right;"><button style="background:none;border:0;font-size:24px;color:#666;cursor:pointer;">&times;</button></form>
                <h5 style="margin-top:0;">📸 {{ __('Uploader un screenshot personnalisé') }}</h5>
                <form action="{{ $uploadScreenshotUrl }}" method="POST" enctype="multipart/form-data" style="margin-top:12px;">
                    @csrf
                    <input type="file" name="screenshot" accept="image/jpeg,image/png,image/webp" required style="display:block;margin-bottom:8px;">
                    <p style="font-size:12px;color:#6b7280;margin:0 0 12px;">{{ __('JPG/PNG/WebP, max 5 Mo. Redimensionné auto 1200×630.') }}</p>
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('Uploader') }}</button>
                </form>
            </dialog>
            @endisset
        </div>
    </template>
    <template x-if="done">
        <span style="font-size:11px;color:#16a34a;font-weight:600;">✓ {{ __('Traité') }}</span>
    </template>
</div>
@endcan
