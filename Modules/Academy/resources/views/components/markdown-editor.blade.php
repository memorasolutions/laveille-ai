{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    Mini-éditeur markdown réutilisable (DRY) pour le champ « document » d'un item.
    Aide de saisie PURE : la <textarea> reste la source de vérité (liée à Livewire
    ou en champ name=… du formulaire). La barre d'outils insère de la syntaxe
    markdown via selectionStart/End ; l'aperçu est rendu CÔTÉ SERVEUR (méthode
    Livewire previewRichText → même helper sûr que le lecteur), jamais en JS.

    Paramètres :
      $uid        identifiant unique du bloc (ex. lesson id ou item id)
      $textareaId id du <textarea> ciblé (doit déjà exister dans la page)
--}}
@props([
    'uid' => null,
    'textareaId' => null,
])
@php
    $uid ??= uniqid();
    $textareaId ??= 'rich-text-'.$uid;
@endphp
<div
    x-data="academyMarkdownEditor('{{ $textareaId }}')"
    class="academy-md-editor"
    style="border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); overflow: hidden;">

    {{-- Barre d'outils --}}
    <div role="toolbar" aria-label="Mise en forme du document"
         class="d-flex flex-wrap align-items-center gap-1"
         style="background: #F8FAFC; border-bottom: 1px solid #E5E7EB; padding: 6px 8px;">
        <button type="button" @click="wrap('**','**','texte en gras')" aria-label="Gras" title="Gras"
                style="min-width: 28px; min-height: 28px; border: 1px solid #E5E7EB; background: #FFFFFF; border-radius: 6px; font-weight: 800; cursor: pointer; line-height: 1;">B</button>
        <button type="button" @click="wrap('*','*','texte en italique')" aria-label="Italique" title="Italique"
                style="min-width: 28px; min-height: 28px; border: 1px solid #E5E7EB; background: #FFFFFF; border-radius: 6px; font-style: italic; font-weight: 700; cursor: pointer; line-height: 1;">I</button>
        <button type="button" @click="prefixLine('## ')" aria-label="Titre" title="Titre"
                style="min-width: 28px; min-height: 28px; border: 1px solid #E5E7EB; background: #FFFFFF; border-radius: 6px; font-weight: 700; cursor: pointer; line-height: 1;">H</button>
        <button type="button" @click="prefixLine('- ')" aria-label="Liste à puces" title="Liste à puces"
                style="min-width: 28px; min-height: 28px; border: 1px solid #E5E7EB; background: #FFFFFF; border-radius: 6px; font-weight: 700; cursor: pointer; line-height: 1;">•</button>
        <button type="button" @click="insertLink()" aria-label="Lien" title="Lien"
                style="min-width: 28px; min-height: 28px; border: 1px solid #E5E7EB; background: #FFFFFF; border-radius: 6px; font-weight: 700; cursor: pointer; line-height: 1;">🔗</button>

        <span style="flex: 1 1 auto;"></span>

        <button type="button" @click="togglePreview()"
                :aria-pressed="showPreview ? 'true' : 'false'"
                aria-label="Aperçu du document" title="Aperçu"
                style="min-height: 28px; padding: 0 10px; border: 1px solid #E5E7EB; background: #FFFFFF; border-radius: 6px; font-size: 0.78rem; font-weight: 700; cursor: pointer;"
                :style="showPreview ? 'background: var(--sys-action-primary, #064E5A); color: #FFFFFF; border-color: var(--sys-action-primary, #064E5A);' : ''">
            <span x-show="!showPreview">👁️ Aperçu</span>
            <span x-show="showPreview" x-cloak>✎ Modifier</span>
        </button>
    </div>

    {{-- Le slot reçoit le <textarea> (sa liaison reste celle de l'appelant). --}}
    <div x-show="!showPreview">
        {{ $slot }}
    </div>

    {{-- Panneau d'aperçu (HTML rendu côté serveur, sûr). --}}
    <div x-show="showPreview" x-cloak role="region" aria-label="Aperçu du document"
         style="padding: 12px 14px; min-height: 60px; background: #FFFFFF;">
        <div x-show="loading" style="font-size: 0.8rem; color: var(--sys-text-muted, #6B7280);">Préparation de l'aperçu…</div>
        <div class="academy-richtext" x-show="!loading" x-html="previewHtml"></div>
        <p x-show="!loading && previewHtml.trim() === ''" style="font-size: 0.8rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
            Rien à prévisualiser pour l'instant.
        </p>
    </div>

    {{-- Micro-aide markdown --}}
    <p style="font-size: 0.7rem; color: var(--sys-text-muted, #6B7280); margin: 0; padding: 6px 10px; background: #F8FAFC; border-top: 1px solid #F1F5F9;">
        Markdown supporté : <strong>**gras**</strong>, <em>*italique*</em>, ## titres, - listes, [lien](url).
    </p>
</div>
