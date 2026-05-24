{{--
    AuthorEditor view — Top5-E S106 scaffolding.
    Stub minimal en attendant implementation EasyMDE complète S107.
--}}
<div class="author-editor-stub" style="padding: 24px; border: 2px dashed #C2410C; border-radius: 1rem; background: #FEF5ED;">
    <h3 style="font-family: 'Plus Jakarta Sans', sans-serif; color: #C2410C; margin: 0 0 8px 0;">
        ✍️ Éditeur Markdown
    </h3>
    <p style="font-family: 'DM Sans', sans-serif; color: #52586a; margin: 0 0 16px 0; font-size: 0.875rem;">
        Implementation complète en cours (S107) — EasyMDE + auto-save + slash commands + live preview.
        En attendant, utilise l'Article Builder (générateur prompts IA) dans l'onglet 🤖 Builders.
    </p>
    @if($post)
        <p style="color: #1A1D23; font-size: 0.875rem;">
            Brouillon courant : <strong>{{ $title ?: 'Sans titre' }}</strong> ({{ strlen($body_markdown) }} caractères)
        </p>
    @else
        <p style="color: #1A1D23; font-size: 0.875rem;">Aucun brouillon. <button type="button" wire:click="$set('title', 'Nouveau billet')" style="color: #0B7285; text-decoration: underline; background: none; border: none; cursor: pointer; font: inherit;">Créer un billet</button></p>
    @endif
</div>
