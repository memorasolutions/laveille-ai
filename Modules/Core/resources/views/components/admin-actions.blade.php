{{-- Composant modération frontend inline
     Usage : @include('core::components.admin-actions', ['item' => $review, 'type' => 'reviews'])
     Requiert : Modules\Core\Traits\HasModerationStatus sur le modèle --}}
@can('moderate_' . $type)
<div x-data="{
    open: false,
    showDelete: false,
    showBan: false,
    banDays: 7,
    rejectReason: '',
    showReject: false
}" @click.away="open = false; showDelete = false; showBan = false; showReject = false" style="display:inline-block;position:relative;">

    {{-- Badge statut --}}
    @if(isset($item->status))
        <span class="label label-{{ $item->status === 'approved' ? 'success' : ($item->status === 'rejected' ? 'warning' : 'info') }}" style="margin-right:6px;">
            {{ $item->status === 'approved' ? __('Approuvé') : ($item->status === 'rejected' ? __('Rejeté') : __('En attente')) }}
        </span>
    @endif

    {{-- Bouton principal --}}
    <button @click="open = !open" type="button"
        style="background:none;border:1px solid var(--c-primary);color:var(--c-primary);padding:3px 10px;border-radius:0.5rem;font-size:12px;font-weight:600;cursor:pointer;">
        🛡️ {{ __('Modérer') }}
    </button>

    {{-- Dropdown --}}
    <div x-show="open" x-cloak
        style="position:absolute;right:0;top:100%;z-index:1000;margin-top:4px;min-width:220px;background:#fff;border:1px solid #e5e7eb;border-radius:0.5rem;box-shadow:0 8px 24px rgba(0,0,0,0.12);padding:6px 0;">

        {{-- Approuver --}}
        @if(($item->status ?? 'pending') !== 'approved')
        <form method="POST" action="{{ route('moderation.approve', [$type, $item->id]) }}" style="margin:0;">
            @csrf
            <button type="submit" style="display:block;width:100%;text-align:left;padding:8px 14px;border:none;background:none;font-size:13px;cursor:pointer;color:#059669;" onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='none'">
                ✅ {{ __('Approuver') }}
            </button>
        </form>
        @else
        <div style="padding:8px 14px;font-size:13px;color:#9ca3af;">✅ {{ __('Déjà approuvé') }}</div>
        @endif

        {{-- Rejeter --}}
        @if(($item->status ?? 'pending') !== 'rejected')
        <button @click="showReject = !showReject" type="button"
            style="display:block;width:100%;text-align:left;padding:8px 14px;border:none;background:none;font-size:13px;cursor:pointer;color:#D97706;" onmouseover="this.style.background='#fffbeb'" onmouseout="this.style.background='none'">
            ❌ {{ __('Rejeter') }}
        </button>
        <div x-show="showReject" x-cloak style="padding:6px 14px;background:#fefce8;border-top:1px solid #fde68a;">
            <form method="POST" action="{{ route('moderation.reject', [$type, $item->id]) }}" style="margin:0;">
                @csrf
                <input type="text" name="reason" x-model="rejectReason" placeholder="{{ __('Raison (optionnel)...') }}"
                    style="width:100%;padding:6px 8px;border:1px solid #e5e7eb;border-radius:0.5rem;font-size:12px;margin-bottom:6px;">
                <button type="submit" class="btn btn-xs" style="background:#D97706;color:#fff;border:none;border-radius:0.5rem;">{{ __('Confirmer') }}</button>
            </form>
        </div>
        @endif

        <div style="border-top:1px solid #f3f4f6;margin:4px 0;"></div>

        {{-- Modifier (redirige vers l'admin si route existe) --}}
        @if($type === 'resources' && Route::has('admin.directory.resources.edit'))
        <a href="{{ route('admin.directory.resources.edit', $item->id) }}"
            style="display:block;width:100%;text-align:left;padding:8px 14px;border:none;background:none;font-size:13px;cursor:pointer;color:#2563eb;text-decoration:none;" onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background='none'">
            ✏️ {{ __('Modifier') }}
        </a>
        @endif

        {{-- Épingler --}}
        <form method="POST" action="{{ route('moderation.pin', [$type, $item->id]) }}" style="margin:0;">
            @csrf
            <button type="submit" style="display:block;width:100%;text-align:left;padding:8px 14px;border:none;background:none;font-size:13px;cursor:pointer;color:var(--c-primary);" onmouseover="this.style.background='var(--c-primary-light)'" onmouseout="this.style.background='none'">
                📌 {{ ($item->is_pinned ?? false) ? __('Désépingler') : __('Épingler') }}
            </button>
        </form>

        {{-- Historique --}}
        @can('view_moderation_history')
        <button @click="$dispatch('open-moderation-history', { type: '{{ $type }}', id: {{ $item->id }} })"
            type="button"
            style="display:block;width:100%;text-align:left;padding:8px 14px;border:none;background:none;font-size:13px;cursor:pointer;color:#6b7280;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='none'">
            📜 {{ __('Historique') }}
        </button>
        @endcan

        <div style="border-top:1px solid #f3f4f6;margin:4px 0;"></div>

        {{-- Supprimer --}}
        <button @click="showDelete = !showDelete" type="button"
            style="display:block;width:100%;text-align:left;padding:8px 14px;border:none;background:none;font-size:13px;cursor:pointer;color:var(--c-danger);" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='none'">
            🗑️ {{ __('Supprimer') }}
        </button>
        <div x-show="showDelete" x-cloak style="padding:8px 14px;background:#fef2f2;border-top:1px solid #fecaca;">
            <p style="font-size:12px;margin:0 0 6px;color:#991b1b;">{{ __('Confirmer la suppression ?') }}</p>
            <form method="POST" action="{{ route('moderation.destroy', [$type, $item->id]) }}" style="display:inline;">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-xs" style="background:var(--c-danger);color:#fff;border:none;border-radius:0.5rem;">{{ __('Oui') }}</button>
            </form>
            <button @click="showDelete = false" class="btn btn-xs btn-default" style="border-radius:0.5rem;margin-left:4px;">{{ __('Annuler') }}</button>
        </div>

        {{-- Bannir utilisateur --}}
        @can('ban_users')
        @if($item->user_id ?? false)
        <button @click="showBan = !showBan" type="button"
            style="display:block;width:100%;text-align:left;padding:8px 14px;border:none;background:none;font-size:13px;cursor:pointer;color:#1A1D23;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'">
            ⛔ {{ __('Bannir utilisateur') }}
        </button>
        <div x-show="showBan" x-cloak style="padding:8px 14px;background:#f3f4f6;border-top:1px solid #e5e7eb;">
            <form method="POST" action="{{ route('moderation.ban', $item->user_id) }}" style="margin:0;">
                @csrf
                <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px;">{{ __('Durée (jours)') }}</label>
                <input type="number" name="duration" x-model="banDays" min="1" max="365"
                    style="width:60px;padding:4px 6px;border:1px solid #e5e7eb;border-radius:0.5rem;font-size:12px;margin-bottom:6px;">
                <button type="submit" class="btn btn-xs" style="background:#1A1D23;color:#fff;border:none;border-radius:0.5rem;">{{ __('Confirmer') }}</button>
            </form>
        </div>
        @endif
        @endcan
    </div>
</div>
@endcan
