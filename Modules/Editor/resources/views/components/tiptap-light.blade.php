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
    <div class="tt-toolbar">
        <button type="button" @mousedown.prevent="cmd(() => editor.chain().focus().toggleBold().run())"
            :class="isActive('bold') && 'active'" class="tt-btn" aria-label="{{ __('Gras') }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6V4zm0 8h9a4 4 0 014 4 4 4 0 01-4 4H6v-8z"/></svg>
        </button>
        <button type="button" @mousedown.prevent="cmd(() => editor.chain().focus().toggleItalic().run())"
            :class="isActive('italic') && 'active'" class="tt-btn" aria-label="{{ __('Italique') }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg>
        </button>
        <span class="tt-sep"></span>
        <button type="button" @mousedown.prevent="cmd(() => editor.chain().focus().toggleHeading({level:2}).run())"
            :class="isActive('heading', {level:2}) && 'active'" class="tt-btn tt-btn-text" aria-label="{{ __('Titre H2') }}">H2</button>
        <button type="button" @mousedown.prevent="cmd(() => editor.chain().focus().toggleHeading({level:3}).run())"
            :class="isActive('heading', {level:3}) && 'active'" class="tt-btn tt-btn-text" aria-label="{{ __('Titre H3') }}">H3</button>
        <span class="tt-sep"></span>
        <button type="button" @mousedown.prevent="cmd(() => editor.chain().focus().toggleBulletList().run())"
            :class="isActive('bulletList') && 'active'" class="tt-btn" aria-label="{{ __('Liste a puces') }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="4" cy="6" r="1.5" fill="currentColor" stroke="none"/><circle cx="4" cy="12" r="1.5" fill="currentColor" stroke="none"/><circle cx="4" cy="18" r="1.5" fill="currentColor" stroke="none"/><line x1="9" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="9" y1="18" x2="21" y2="18"/></svg>
        </button>
        <button type="button" @mousedown.prevent="cmd(() => editor.chain().focus().toggleOrderedList().run())"
            :class="isActive('orderedList') && 'active'" class="tt-btn" aria-label="{{ __('Liste numerotee') }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><text x="2" y="8" fill="currentColor" stroke="none" font-size="8" font-weight="700">1</text><text x="2" y="14" fill="currentColor" stroke="none" font-size="8" font-weight="700">2</text><text x="2" y="20" fill="currentColor" stroke="none" font-size="8" font-weight="700">3</text></svg>
        </button>
        <span class="tt-sep"></span>
        <button type="button" @mousedown.prevent="cmd(() => editor.chain().focus().toggleBlockquote().run())"
            :class="isActive('blockquote') && 'active'" class="tt-btn" aria-label="{{ __('Citation') }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311C9.591 11.69 11 13.166 11 15a3 3 0 01-3 3c-1.305 0-2.497-.637-3.417-1.679zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311C19.591 11.69 21 13.166 21 15a3 3 0 01-3 3c-1.305 0-2.497-.637-3.417-1.679z"/></svg>
        </button>
        <button type="button" @mousedown.prevent="cmd(() => editor.chain().focus().toggleCode().run())"
            :class="isActive('code') && 'active'" class="tt-btn" aria-label="{{ __('Code en ligne') }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        </button>
        <button type="button" @mousedown.prevent="setLink()"
            :class="isActive('link') && 'active'" class="tt-btn" aria-label="{{ __('Inserer un lien') }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
        </button>
    </div>

    {{-- Zone d'édition --}}
    <div x-ref="editorContent" style="min-height:120px;padding:12px 14px;outline:none;font-size:14px;line-height:1.6;color:#1f2937;"></div>

    {{-- Hidden input pour le formulaire --}}
    <input type="hidden" x-ref="hiddenInput" name="{{ $name }}" :value="content">
</div>

<style>
    .tt-toolbar { display:flex; flex-wrap:wrap; gap:2px; padding:6px 10px; background:#f8fafc; border-bottom:1px solid #e5e7eb; align-items:center; }
    .tt-btn { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border:none; border-radius:6px; background:transparent; color:#64748b; cursor:pointer; transition:all 0.15s ease; }
    .tt-btn:hover { background:rgba(11,114,133,0.08); color:#0B7285; }
    .tt-btn.active { background:#0B7285; color:#fff; }
    .tt-btn-text { font-size:12px; font-weight:700; font-family:'DM Sans',sans-serif; width:auto; padding:0 8px; }
    .tt-sep { width:1px; height:20px; background:#e2e8f0; margin:0 4px; flex-shrink:0; }
    .tiptap { outline: none; }
    .tiptap p { margin: 0 0 0.5em; }
    .tiptap h2 { font-size: 1.3em; font-weight: 700; margin: 0.8em 0 0.4em; }
    .tiptap h3 { font-size: 1.1em; font-weight: 700; margin: 0.6em 0 0.3em; }
    .tiptap ul, .tiptap ol { padding-left: 1.5em; margin: 0.4em 0; }
    .tiptap blockquote { border-left: 3px solid #0B7285; padding-left: 12px; margin: 0.5em 0; color: #6b7280; font-style: italic; }
    .tiptap code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.9em; font-family: monospace; }
    .tiptap a { color: #0B7285; text-decoration: underline; }
    .tiptap p.is-editor-empty:first-child::before { content: '{{ $placeholder }}'; color: #6b7280; pointer-events: none; float: left; height: 0; }
</style>
