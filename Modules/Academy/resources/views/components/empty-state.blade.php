{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    État vide réutilisable (DRY) pour l'éditeur de cours : pictogramme discret +
    message clair invitant à l'action suivante. N'enlève jamais de fonctionnalité :
    le formulaire d'ajout correspondant reste affiché à côté/en dessous.

    Props :
      $icon    emoji/pictogramme discret (décoratif, aria-hidden)
      $message message d'invitation (texte)
      $compact mise en page plus serrée pour les niveaux imbriqués (leçon/item)
--}}
@props([
    'icon' => '📭',
    'message' => '',
    'compact' => false,
])
<div role="note"
     style="display: flex; align-items: center; gap: 10px;
            border: 1px dashed #CBD5E1; border-radius: var(--sys-radius-md, 0.5rem);
            background: #F8FAFC; color: var(--sys-text-muted, #475569);
            padding: {{ $compact ? '10px 12px' : '16px 18px' }};
            margin-bottom: {{ $compact ? '10px' : '16px' }};
            font-size: {{ $compact ? '0.82rem' : '0.9rem' }};">
    <span aria-hidden="true" style="font-size: {{ $compact ? '1.1rem' : '1.4rem' }}; line-height: 1;">{{ $icon }}</span>
    <span>{{ $message }}</span>
</div>
