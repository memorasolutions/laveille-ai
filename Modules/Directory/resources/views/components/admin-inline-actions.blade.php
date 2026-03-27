<!-- Admin inline moderation — visible uniquement pour les admins -->
@can('view_admin_panel')
<div style="display:inline-flex;gap:4px;margin-top:6px;flex-wrap:wrap;" x-data="{ done: false }">
    <template x-if="!done">
        <div style="display:flex;gap:4px;flex-wrap:wrap;">
            <button @click="fetch('{{ $approveUrl }}', {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}}).then(r=>{if(r.ok){done=true;$el.closest('[data-mod-item]')?.classList.add('border-success')}})"
                style="font-size:11px;background:#16a34a;color:#fff;border:none;padding:3px 10px;border-radius:4px;cursor:pointer;font-weight:600;">
                {{ __('Approuver') }}
            </button>
            @if(isset($deleteUrl))
            <button @click="if(confirm('{{ __('Supprimer cette ressource (sans pénalité) ?') }}')){fetch('{{ $deleteUrl }}', {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}}).then(r=>{if(r.ok){$el.closest('[data-mod-item]')?.remove()}})}"
                style="font-size:11px;background:#6b7280;color:#fff;border:none;padding:3px 10px;border-radius:4px;cursor:pointer;font-weight:600;">
                {{ __('Supprimer') }}
            </button>
            @endif
            <button @click="if(confirm('{{ __('Rejeter et pénaliser l\'auteur (-10 pts) ?') }}')){fetch('{{ $rejectUrl }}', {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}}).then(r=>{if(r.ok){done=true;$el.closest('[data-mod-item]')?.remove()}})}"
                style="font-size:11px;background:#ef4444;color:#fff;border:none;padding:3px 10px;border-radius:4px;cursor:pointer;font-weight:600;"
                title="{{ __('Rejete et retire 10 points de réputation à l\'auteur') }}">
                {{ __('Rejeter (-10 pts)') }}
            </button>
        </div>
    </template>
    <template x-if="done">
        <span style="font-size:11px;color:#16a34a;font-weight:600;">✓ {{ __('Traité') }}</span>
    </template>
</div>
@endcan
