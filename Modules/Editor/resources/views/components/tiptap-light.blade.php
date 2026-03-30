{{-- Éditeur TipTap light pour discussions/commentaires communautaires --}}
{{-- Usage: @include('editor::components.tiptap-light', ['name' => 'body', 'value' => '', 'placeholder' => '...']) --}}
@props(['name' => 'body', 'value' => '', 'placeholder' => 'Écrivez votre message...'])

@once
@push('head')
    @vite('resources/js/tiptap-frontend.js')
@endpush
@endonce

<div x-data="tiptapEditor({ content: {{ json_encode($value ?: '') }} })" style="border:1px solid #d1d5db;border-radius:10px;overflow:hidden;">

    {{-- Toolbar light --}}
    <div style="display:flex;flex-wrap:wrap;gap:3px;padding:6px 8px;background:#f8fafc;border-bottom:1px solid #e5e7eb;">
        {{-- Gras --}}
        <button type="button" @mousedown.prevent="cmd(() => editor.chain().focus().toggleBold().run())"
            :style="isActive('bold') ? 'background:#0B7285;color:#fff' : 'background:#fff;color:#374151'"
            style="border:1px solid #d1d5db;padding:4px 8px;border-radius:6px;font-size:13px;font-weight:700;cursor:pointer;line-height:1;"
            aria-label="{{ __('Gras') }}">B</button>

        {{-- Italique --}}
        <button type="button" @mousedown.prevent="cmd(() => editor.chain().focus().toggleItalic().run())"
            :style="isActive('italic') ? 'background:#0B7285;color:#fff' : 'background:#fff;color:#374151'"
            style="border:1px solid #d1d5db;padding:4px 8px;border-radius:6px;font-size:13px;font-style:italic;cursor:pointer;line-height:1;"
            aria-label="{{ __('Italique') }}">I</button>

        <span style="width:1px;background:#e5e7eb;margin:0 2px;align-self:stretch;"></span>

        {{-- H2 --}}
        <button type="button" @mousedown.prevent="cmd(() => editor.chain().focus().toggleHeading({level:2}).run())"
            :style="isActive('heading', {level:2}) ? 'background:#0B7285;color:#fff' : 'background:#fff;color:#374151'"
            style="border:1px solid #d1d5db;padding:4px 8px;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;line-height:1;"
            aria-label="{{ __('Titre H2') }}">H2</button>

        {{-- H3 --}}
        <button type="button" @mousedown.prevent="cmd(() => editor.chain().focus().toggleHeading({level:3}).run())"
            :style="isActive('heading', {level:3}) ? 'background:#0B7285;color:#fff' : 'background:#fff;color:#374151'"
            style="border:1px solid #d1d5db;padding:4px 8px;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;line-height:1;"
            aria-label="{{ __('Titre H3') }}">H3</button>

        <span style="width:1px;background:#e5e7eb;margin:0 2px;align-self:stretch;"></span>

        {{-- Liste à puces --}}
        <button type="button" @mousedown.prevent="cmd(() => editor.chain().focus().toggleBulletList().run())"
            :style="isActive('bulletList') ? 'background:#0B7285;color:#fff' : 'background:#fff;color:#374151'"
            style="border:1px solid #d1d5db;padding:4px 8px;border-radius:6px;font-size:13px;cursor:pointer;line-height:1;"
            aria-label="{{ __('Liste à puces') }}">• —</button>

        {{-- Liste numérotée --}}
        <button type="button" @mousedown.prevent="cmd(() => editor.chain().focus().toggleOrderedList().run())"
            :style="isActive('orderedList') ? 'background:#0B7285;color:#fff' : 'background:#fff;color:#374151'"
            style="border:1px solid #d1d5db;padding:4px 8px;border-radius:6px;font-size:13px;cursor:pointer;line-height:1;"
            aria-label="{{ __('Liste numérotée') }}">1.</button>

        <span style="width:1px;background:#e5e7eb;margin:0 2px;align-self:stretch;"></span>

        {{-- Citation --}}
        <button type="button" @mousedown.prevent="cmd(() => editor.chain().focus().toggleBlockquote().run())"
            :style="isActive('blockquote') ? 'background:#0B7285;color:#fff' : 'background:#fff;color:#374151'"
            style="border:1px solid #d1d5db;padding:4px 8px;border-radius:6px;font-size:15px;cursor:pointer;line-height:1;"
            aria-label="{{ __('Citation') }}">&ldquo;</button>

        {{-- Code inline --}}
        <button type="button" @mousedown.prevent="cmd(() => editor.chain().focus().toggleCode().run())"
            :style="isActive('code') ? 'background:#0B7285;color:#fff' : 'background:#fff;color:#374151'"
            style="border:1px solid #d1d5db;padding:4px 8px;border-radius:6px;font-size:12px;font-family:monospace;cursor:pointer;line-height:1;"
            aria-label="{{ __('Code en ligne') }}">&lt;/&gt;</button>

        {{-- Lien --}}
        <button type="button" @mousedown.prevent="setLink()"
            :style="isActive('link') ? 'background:#0B7285;color:#fff' : 'background:#fff;color:#374151'"
            style="border:1px solid #d1d5db;padding:4px 8px;border-radius:6px;font-size:13px;cursor:pointer;line-height:1;"
            aria-label="{{ __('Insérer un lien') }}">🔗</button>
    </div>

    {{-- Zone d'édition --}}
    <div x-ref="editorContent" style="min-height:120px;padding:12px 14px;outline:none;font-size:14px;line-height:1.6;color:#1f2937;"></div>

    {{-- Hidden input pour le formulaire --}}
    <input type="hidden" x-ref="hiddenInput" name="{{ $name }}" :value="content">
</div>

<style>
    .tiptap { outline: none; }
    .tiptap p { margin: 0 0 0.5em; }
    .tiptap h2 { font-size: 1.3em; font-weight: 700; margin: 0.8em 0 0.4em; }
    .tiptap h3 { font-size: 1.1em; font-weight: 700; margin: 0.6em 0 0.3em; }
    .tiptap ul, .tiptap ol { padding-left: 1.5em; margin: 0.4em 0; }
    .tiptap blockquote { border-left: 3px solid #0B7285; padding-left: 12px; margin: 0.5em 0; color: #6b7280; font-style: italic; }
    .tiptap code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.9em; font-family: monospace; }
    .tiptap a { color: #0B7285; text-decoration: underline; }
    .tiptap p.is-editor-empty:first-child::before { content: '{{ $placeholder }}'; color: #9ca3af; pointer-events: none; float: left; height: 0; }
</style>
