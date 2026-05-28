{{-- x-core::accordion — composant disclosure/accordéon standard (W3C APG). S131 #16.
     Source de vérité unique : heading + button(aria-expanded/aria-controls) + panneau hidden.
     CSS + JS partagés émis une seule fois (@once) quel que soit le nombre d'instances. --}}
@props([
    'id',
    'icon' => null,
    'title',
    'subtitle' => null,
    'cta' => null,
    'open' => false,
])
<div class="ct-accordion">
  {{-- Wrapper neutre (pas de <h?>) : un disclosure isolé n'exige pas de heading et éviter
       un saut de niveau (h1→h3) selon le contexte de la page. Le bouton porte la sémantique. --}}
  <div class="ct-accordion__heading">
    <button type="button" class="ct-accordion__trigger" id="{{ $id }}-trigger" aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="{{ $id }}-panel">
      <span class="ct-accordion__lead">
        @if($icon)<span class="ct-accordion__icon" aria-hidden="true">{{ $icon }}</span>@endif
        <span class="ct-accordion__titles">
          <span class="ct-accordion__title">{{ $title }}</span>
          @if($subtitle)<span class="ct-accordion__subtitle">{{ $subtitle }}</span>@endif
        </span>
      </span>
      <span class="ct-accordion__cta">
        @if($cta)<span class="ct-accordion__cta-text">{{ $cta }}</span>@endif
        <span class="ct-accordion__chevron" aria-hidden="true">›</span>
      </span>
    </button>
  </div>
  <div id="{{ $id }}-panel" class="ct-accordion__panel" role="region" aria-labelledby="{{ $id }}-trigger" @unless($open) hidden @endunless>
    <div class="ct-accordion__content">{{ $slot }}</div>
  </div>
</div>

@once
@push('head')
<style>
.ct-accordion,
.app .ct-accordion {
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  background: var(--bg-secondary);
  margin: 0;
}
.ct-accordion__heading,
.app .ct-accordion__heading {
  margin: 0;
  font-size: inherit;
  font-weight: inherit;
}
.ct-accordion__trigger,
.app .ct-accordion__trigger {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: #E6F7F5;
  border: 0;
  cursor: pointer;
  color: var(--text-primary);
  text-align: left;
  font-weight: 600;
  font-size: 0.92rem;
  font-family: inherit;
}
.ct-accordion__trigger:hover,
.app .ct-accordion__trigger:hover {
  background: #d8f0ed;
}
.ct-accordion__trigger:focus-visible,
.app .ct-accordion__trigger:focus-visible {
  outline: 3px solid rgba(11, 114, 133, 0.5);
  outline-offset: -3px;
}
.ct-accordion__lead,
.app .ct-accordion__lead {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  min-width: 0;
}
.ct-accordion__icon,
.app .ct-accordion__icon {
  font-size: 1.1rem;
  flex-shrink: 0;
}
.ct-accordion__titles,
.app .ct-accordion__titles {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.ct-accordion__title,
.app .ct-accordion__title {
  font-weight: 700;
}
.ct-accordion__subtitle,
.app .ct-accordion__subtitle {
  font-weight: 400;
  font-size: 0.82rem;
  color: #334155; /* WCAG AAA sur fond pâle (#E6F7F5) — var(--text-muted) échouait l'AA à 4.3:1 */
}
.ct-accordion__cta,
.app .ct-accordion__cta {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #064E5C; /* teal AAA (8.43:1 sur #E6F7F5) — var(--primary) #0B7285 = 5.05:1 (AAA fail) */
  font-weight: 700;
  font-size: 0.85rem;
  white-space: nowrap;
  flex-shrink: 0;
}
.ct-accordion__chevron,
.app .ct-accordion__chevron {
  display: inline-block;
  transition: transform var(--transition);
  font-size: 1.1rem;
  line-height: 1;
}
.ct-accordion__trigger[aria-expanded="true"] .ct-accordion__chevron,
.app .ct-accordion__trigger[aria-expanded="true"] .ct-accordion__chevron {
  transform: rotate(90deg);
}
.ct-accordion__panel[hidden],
.app .ct-accordion__panel[hidden] {
  display: none !important;
}
.ct-accordion__panel,
.app .ct-accordion__panel {
  border-top: 1px solid var(--border);
}
.ct-accordion__content,
.app .ct-accordion__content {
  padding: 1rem;
}
@media (prefers-reduced-motion: reduce) {
  .ct-accordion__chevron,
  .app .ct-accordion__chevron {
    transition: none !important;
  }
}
</style>
@endpush
@push('scripts')
<script>
(function () {
  document.addEventListener('click', function (e) {
    var trigger = e.target.closest && e.target.closest('.ct-accordion__trigger');
    if (!trigger) return;
    var panel = document.getElementById(trigger.getAttribute('aria-controls'));
    if (!panel) return;
    var expanded = trigger.getAttribute('aria-expanded') === 'true';
    trigger.setAttribute('aria-expanded', String(!expanded));
    if (expanded) { panel.setAttribute('hidden', ''); } else { panel.removeAttribute('hidden'); }
  });
})();
</script>
@endpush
@endonce
