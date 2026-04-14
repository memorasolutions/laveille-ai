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
                class="ct-btn ct-btn-ghost ct-btn-xs">
                {{ __('Supprimer') }}
            </button>
            @endif
            <button onclick="var tk=document.querySelector('meta[name=csrf-token]')?.content;window.__confirmAction=()=>fetch('{{ $rejectUrl }}',{method:'POST',headers:{'X-CSRF-TOKEN':tk,'Accept':'application/json'}}).then(r=>{if(r.ok)location.reload()});window.dispatchEvent(new CustomEvent('confirm-action',{detail:{title:'{{ __("Rejeter (-10 pts)") }}',message:'{{ __("Rejeter et retirer 10 points de réputation ?") }}'}}))"
                class="ct-btn ct-btn-outline-danger ct-btn-xs"
                title="{{ __('Rejeter et retirer 10 points de réputation') }}">
                {{ __('Rejeter (-10 pts)') }}
            </button>
        </div>
    </template>
    <template x-if="done">
        <span style="font-size:11px;color:#16a34a;font-weight:600;">✓ {{ __('Traité') }}</span>
    </template>
</div>
@endcan
