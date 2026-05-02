@extends('fronttheme::layouts.master')

@section('title', __('Mes mots croisés sauvegardés') . ' - laveille.ai')
@section('meta_description', __('Liste de mes grilles de mots croisés sauvegardées sur laveille.ai.'))

@section('content')
<style>
.cw-user-page { max-width: 1100px; margin: 0 auto; padding: 2rem 1rem; }
.cw-user-page h1 { color: #053d4a; font-weight: 800; }
.cw-user-page .lead { color: #1A1D23; }
.cw-grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
.cw-card { background: #fff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 1.25rem; transition: box-shadow .15s, border-color .15s; }
.cw-card:hover { border-color: #053d4a; box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.cw-card h2 { font-size: 1.1rem; font-weight: 700; color: #053d4a; margin: 0 0 .5rem; word-break: break-word; }
.cw-card .meta { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; margin-bottom: 1rem; font-size: .85rem; color: #475569; }
.cw-card .meta .badge-pill { background: #e0f2f1; color: #053d4a; padding: 2px 10px; border-radius: 999px; font-weight: 600; font-size: .75rem; }
.cw-card .meta .badge-public { background: #d1fae5; color: #065f46; padding: 2px 10px; border-radius: 999px; font-weight: 600; font-size: .75rem; display: inline-flex; align-items: center; gap: 4px; }
.cw-card .actions { display: flex; flex-wrap: wrap; gap: .5rem; }
.cw-card .actions a, .cw-card .actions button { min-height: 44px; }
.cw-empty { text-align: center; padding: 3rem 1rem; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px; color: #1A1D23; }
.cw-empty svg { color: #053d4a; margin-bottom: 1rem; }
.cw-empty h2 { color: #053d4a; font-weight: 700; }
</style>

<section class="page-section">
  <div class="cw-user-page" x-data="userCrosswords()">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
      <div>
        <h1>{{ __('Mes mots croisés sauvegardés') }}</h1>
        <p class="lead mb-0">{{ trans_choice('{0}Aucune grille pour l\'instant.|{1}1 grille sauvegardée.|[2,*]:count grilles sauvegardées.', $presets->total(), ['count' => $presets->total()]) }}</p>
      </div>
      <a href="{{ url('/outils/mots-croises') }}" class="ct-btn ct-btn-primary d-inline-flex align-items-center gap-2" style="min-height:44px">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <span>{{ __('Créer une nouvelle grille') }}</span>
      </a>
    </div>

    @if($presets->total() === 0)
      <div class="cw-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/></svg>
        <h2>{{ __('Aucune grille sauvegardée') }}</h2>
        <p>{{ __('Créez votre première grille de mots croisés et cliquez sur "Sauvegarder dans mon compte" pour la retrouver ici.') }}</p>
      </div>
    @else
      <div class="cw-grid">
        @foreach($presets as $preset)
          <article class="cw-card">
            <h2>{{ $preset->name }}</h2>
            <div class="meta">
              <span class="badge-pill">{{ $preset->pairs_count }} {{ trans_choice('mot|mots', $preset->pairs_count) }}</span>
              @if($preset->is_public)
                <span class="badge-public" title="{{ __('Accessible via lien partageable') }}">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                  {{ __('Publique') }}
                </span>
              @endif
              <span title="{{ $preset->updated_at }}">{{ $preset->updated_at?->diffForHumans() }}</span>
            </div>
            <div class="actions">
              @if($preset->is_public)
                <a href="{{ url('/jeumc/'.$preset->public_id) }}" class="ct-btn ct-btn-primary d-inline-flex align-items-center gap-2" target="_blank" rel="noopener" :aria-label="'{{ __('Jouer la grille') }} {{ $preset->name }}'">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="6 4 20 12 6 20 6 4"/></svg>
                  <span>{{ __('Jouer') }}</span>
                </a>
              @endif
              <a href="{{ route('user.crosswords.edit', $preset->public_id) }}" class="ct-btn ct-btn-outline d-inline-flex align-items-center gap-2">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                <span>{{ __('Modifier') }}</span>
              </a>
              <button type="button" class="ct-btn ct-btn-outline d-inline-flex align-items-center gap-2" style="color:#7f1d1d;border-color:#fecaca" @click="confirmDelete('{{ $preset->public_id }}', @js($preset->name))" :aria-label="'{{ __('Supprimer') }} {{ $preset->name }}'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                <span>{{ __('Supprimer') }}</span>
              </button>
            </div>
          </article>
        @endforeach
      </div>

      @if($presets->hasPages())
        <div class="mt-4 d-flex justify-content-center">
          {{ $presets->links() }}
        </div>
      @endif
    @endif
  </div>
</section>

<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('userCrosswords', () => ({
    confirmDelete(publicId, name) {
      const message = @json(__('Supprimer définitivement la grille')) + ' « ' + name + ' » ?\n' + @json(__('Cette action est irréversible.'));
      window.dispatchEvent(new CustomEvent('open-confirm-global', {
        detail: { message: message, callback: async () => {
          try {
            const csrf = document.querySelector('meta[name=csrf-token]').getAttribute('content');
            const res = await fetch('/api/crossword-presets/' + publicId, {
              method: 'DELETE',
              headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'}
            });
            if (res.ok) {
              window.dispatchEvent(new CustomEvent('toast-show', { detail: { message: @json(__('Grille supprimée.')), variant: 'success', duration: 3000 }}));
              setTimeout(() => window.location.reload(), 800);
            } else {
              window.dispatchEvent(new CustomEvent('toast-show', { detail: { message: @json(__('Erreur lors de la suppression. Réessayez.')), variant: 'danger', duration: 5000 }}));
            }
          } catch (e) {
            console.error(e);
            window.dispatchEvent(new CustomEvent('toast-show', { detail: { message: @json(__('Erreur réseau.')), variant: 'danger', duration: 5000 }}));
          }
        }}
      }));
    }
  }));
});
</script>
@endsection
