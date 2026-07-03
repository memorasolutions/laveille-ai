{{--
    Author: MEMORA solutions, https://memora.solutions

    Messagerie directe (DM) - démarrer une nouvelle conversation. La liste de
    contacts affichée provient EXCLUSIVEMENT de NewConversation::contacts()
    (dérivée des tables pédagogiques réelles) : aucun champ libre.
--}}
<div>
    <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); font-size: 1.25rem; margin-bottom: 12px;">
        Nouvelle conversation
    </h2>

    @if($this->contacts->isEmpty())
        <div style="padding: 24px; text-align: center; color: var(--sys-text-muted, #6B7280); border: 1px dashed #D1D5DB; border-radius: 8px;">
            Vous n'avez aucun contact autorisé pour l'instant. Un lien pédagogique commun (formateur/apprenant d'un même cours) est requis.
        </div>
    @else
        <p style="color: var(--sys-text-muted, #6B7280); margin-bottom: 16px;">
            Choisissez un formateur ou un apprenant de vos cours pour démarrer une conversation.
        </p>
        <ul class="list-unstyled" role="list" style="display: flex; flex-direction: column; gap: 8px;">
            @foreach($this->contacts as $contact)
                <li role="listitem">
                    <button type="button"
                            wire:click="startConversation({{ $contact->id }})"
                            wire:loading.attr="disabled"
                            class="w-100 text-start"
                            style="padding: 12px 16px; border: 1px solid #E5E7EB; border-radius: 8px; background-color: #fff; cursor: pointer;">
                        <strong style="color: var(--sys-text-default, #1A1D23);">{{ $contact->name }}</strong>
                    </button>
                </li>
            @endforeach
        </ul>
    @endif
</div>
