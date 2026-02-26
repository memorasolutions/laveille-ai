@props(['name' => 'content', 'value' => '', 'label' => null, 'required' => false])

<div wire:ignore>
<div id="tiptap-wrap" x-data="tiptapEditor({ content: '{{ addslashes(old($name, $value)) }}' })" x-init="$nextTick(() => { if(window.lucide) lucide.createIcons({attrs: {}, nodes: $el.querySelectorAll('[data-lucide]')}) })" @destroy.window="destroy()" :class="{'tiptap-fullscreen': isFullscreen}">
    @if($label)
        <label class="form-label fw-semibold">
            {{ $label }}
            @if($required)<span class="text-danger ms-1">*</span>@endif
        </label>
    @endif

    <div class="tiptap-toolbar d-flex flex-wrap align-items-center gap-1 p-2 border rounded-top bg-white">
        {{-- Groupe 1 : Historique --}}
        <button type="button" class="btn btn-sm btn-outline-secondary" @mousedown.prevent="cmd(() => editor.chain().focus().undo().run())" data-bs-toggle="tooltip" title="Annuler (Ctrl+Z)"><i data-lucide="undo-2" style="width:14px;height:14px;"></i></button>
        <button type="button" class="btn btn-sm btn-outline-secondary" @mousedown.prevent="cmd(() => editor.chain().focus().redo().run())" data-bs-toggle="tooltip" title="Rétablir (Ctrl+Y)"><i data-lucide="redo-2" style="width:14px;height:14px;"></i></button>
        <span class="vr mx-1 align-self-stretch opacity-25"></span>

        {{-- Groupe 2 : Mise en forme --}}
        <button type="button" class="btn btn-sm btn-outline-secondary" :class="{'is-active': isActive('bold')}" @mousedown.prevent="cmd(() => editor.chain().focus().toggleBold().run())" data-bs-toggle="tooltip" title="Gras (Ctrl+B)"><i data-lucide="bold" style="width:14px;height:14px;"></i></button>
        <button type="button" class="btn btn-sm btn-outline-secondary" :class="{'is-active': isActive('italic')}" @mousedown.prevent="cmd(() => editor.chain().focus().toggleItalic().run())" data-bs-toggle="tooltip" title="Italique (Ctrl+I)"><i data-lucide="italic" style="width:14px;height:14px;"></i></button>
        <button type="button" class="btn btn-sm btn-outline-secondary" :class="{'is-active': isActive('underline')}" @mousedown.prevent="cmd(() => editor.chain().focus().toggleUnderline().run())" data-bs-toggle="tooltip" title="Souligné (Ctrl+U)"><i data-lucide="underline" style="width:14px;height:14px;"></i></button>
        <button type="button" class="btn btn-sm btn-outline-secondary" :class="{'is-active': isActive('strike')}" @mousedown.prevent="cmd(() => editor.chain().focus().toggleStrike().run())" data-bs-toggle="tooltip" title="Barré"><i data-lucide="strikethrough" style="width:14px;height:14px;"></i></button>
        <span class="vr mx-1 align-self-stretch opacity-25"></span>

        {{-- Groupe 3 : Titres + Blockquote --}}
        <button type="button" class="btn btn-sm btn-outline-secondary" :class="{'is-active': isActive('heading', {level:1})}" @mousedown.prevent="cmd(() => editor.chain().focus().toggleHeading({level:1}).run())" data-bs-toggle="tooltip" title="Titre 1"><i data-lucide="heading-1" style="width:14px;height:14px;"></i></button>
        <button type="button" class="btn btn-sm btn-outline-secondary" :class="{'is-active': isActive('heading', {level:2})}" @mousedown.prevent="cmd(() => editor.chain().focus().toggleHeading({level:2}).run())" data-bs-toggle="tooltip" title="Titre 2"><i data-lucide="heading-2" style="width:14px;height:14px;"></i></button>
        <button type="button" class="btn btn-sm btn-outline-secondary" :class="{'is-active': isActive('heading', {level:3})}" @mousedown.prevent="cmd(() => editor.chain().focus().toggleHeading({level:3}).run())" data-bs-toggle="tooltip" title="Titre 3"><i data-lucide="heading-3" style="width:14px;height:14px;"></i></button>
        <button type="button" class="btn btn-sm btn-outline-secondary" :class="{'is-active': isActive('blockquote')}" @mousedown.prevent="cmd(() => editor.chain().focus().toggleBlockquote().run())" data-bs-toggle="tooltip" title="Citation"><i data-lucide="quote" style="width:14px;height:14px;"></i></button>
        <span class="vr mx-1 align-self-stretch opacity-25"></span>

        {{-- Groupe 4 : Listes --}}
        <button type="button" class="btn btn-sm btn-outline-secondary" :class="{'is-active': isActive('bulletList')}" @mousedown.prevent="cmd(() => editor.chain().focus().toggleBulletList().run())" data-bs-toggle="tooltip" title="Liste à puces"><i data-lucide="list" style="width:14px;height:14px;"></i></button>
        <button type="button" class="btn btn-sm btn-outline-secondary" :class="{'is-active': isActive('orderedList')}" @mousedown.prevent="cmd(() => editor.chain().focus().toggleOrderedList().run())" data-bs-toggle="tooltip" title="Liste numérotée"><i data-lucide="list-ordered" style="width:14px;height:14px;"></i></button>
        <button type="button" class="btn btn-sm btn-outline-secondary" :class="{'is-active': isActive('taskList')}" @mousedown.prevent="toggleTaskList()" data-bs-toggle="tooltip" title="Liste de tâches"><i data-lucide="list-checks" style="width:14px;height:14px;"></i></button>
        <span class="vr mx-1 align-self-stretch opacity-25"></span>

        {{-- Groupe 5 : Alignement --}}
        <button type="button" class="btn btn-sm btn-outline-secondary" :class="{'is-active': isActive({textAlign:'left'})}" @mousedown.prevent="cmd(() => editor.chain().focus().setTextAlign('left').run())" data-bs-toggle="tooltip" title="Gauche"><i data-lucide="align-left" style="width:14px;height:14px;"></i></button>
        <button type="button" class="btn btn-sm btn-outline-secondary" :class="{'is-active': isActive({textAlign:'center'})}" @mousedown.prevent="cmd(() => editor.chain().focus().setTextAlign('center').run())" data-bs-toggle="tooltip" title="Centrer"><i data-lucide="align-center" style="width:14px;height:14px;"></i></button>
        <button type="button" class="btn btn-sm btn-outline-secondary" :class="{'is-active': isActive({textAlign:'right'})}" @mousedown.prevent="cmd(() => editor.chain().focus().setTextAlign('right').run())" data-bs-toggle="tooltip" title="Droite"><i data-lucide="align-right" style="width:14px;height:14px;"></i></button>
        <button type="button" class="btn btn-sm btn-outline-secondary" :class="{'is-active': isActive({textAlign:'justify'})}" @mousedown.prevent="cmd(() => editor.chain().focus().setTextAlign('justify').run())" data-bs-toggle="tooltip" title="Justifier"><i data-lucide="align-justify" style="width:14px;height:14px;"></i></button>
        <span class="vr mx-1 align-self-stretch opacity-25"></span>

        {{-- Groupe 6 : Couleurs --}}
        <div class="d-inline-flex align-items-center position-relative" data-bs-toggle="tooltip" title="Couleur du texte">
            <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" @mousedown.prevent="$refs.colorInput.click()">
                <i data-lucide="palette" style="width:14px;height:14px;"></i>
                <span class="d-block rounded" :style="'width:12px;height:4px;background:'+currentColor"></span>
            </button>
            <input type="color" x-ref="colorInput" class="visually-hidden" :value="currentColor" @input="setColor($event.target.value)">
        </div>
        <div class="d-inline-flex align-items-center position-relative" data-bs-toggle="tooltip" title="Couleur de surlignage">
            <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" @mousedown.prevent="$refs.highlightInput.click()">
                <i data-lucide="highlighter" style="width:14px;height:14px;"></i>
                <span class="d-block rounded" :style="'width:12px;height:4px;background:'+currentHighlight"></span>
            </button>
            <input type="color" x-ref="highlightInput" :value="currentHighlight" class="visually-hidden" @input="setHighlightColor($event.target.value)">
        </div>
        <span class="vr mx-1 align-self-stretch opacity-25"></span>

        {{-- Groupe 7 : Police --}}
        <select class="form-select form-select-sm" style="width:auto;font-size:12px;padding:2px 24px 2px 6px;" @change="setFontFamily($event.target.value)" data-bs-toggle="tooltip" title="Police">
            <option value="">Par défaut</option>
            <option value="Inter">Inter</option>
            <option value="Arial">Arial</option>
            <option value="Georgia">Georgia</option>
            <option value="Times New Roman">Times New Roman</option>
            <option value="Courier New">Courier New</option>
            <option value="Verdana">Verdana</option>
        </select>
        <select class="form-select form-select-sm" style="width:auto;font-size:12px;padding:2px 24px 2px 6px;" @change="setFontSize($event.target.value)" data-bs-toggle="tooltip" title="Taille">
            <option value="">Taille</option>
            <option value="12px">12</option>
            <option value="14px">14</option>
            <option value="16px">16</option>
            <option value="18px">18</option>
            <option value="20px">20</option>
            <option value="24px">24</option>
            <option value="28px">28</option>
            <option value="32px">32</option>
        </select>
        <span class="vr mx-1 align-self-stretch opacity-25"></span>

        {{-- Groupe 8 : Insertion --}}
        <button type="button" class="btn btn-sm btn-outline-secondary" :class="{'is-active': isActive('link')}" @mousedown.prevent="setLink()" data-bs-toggle="tooltip" title="Lien"><i data-lucide="link" style="width:14px;height:14px;"></i></button>
        <button type="button" class="btn btn-sm btn-outline-secondary" @mousedown.prevent="addImage()" data-bs-toggle="tooltip" title="Image"><i data-lucide="image-plus" style="width:14px;height:14px;"></i></button>
        <button type="button" class="btn btn-sm btn-outline-secondary" @mousedown.prevent="insertTable()" data-bs-toggle="tooltip" title="Tableau"><i data-lucide="table" style="width:14px;height:14px;"></i></button>
        <button type="button" class="btn btn-sm btn-outline-secondary" @mousedown.prevent="addYoutube()" data-bs-toggle="tooltip" title="Vidéo YouTube"><i data-lucide="youtube" style="width:14px;height:14px;"></i></button>
        <button type="button" class="btn btn-sm btn-outline-secondary" @mousedown.prevent="cmd(() => editor.chain().focus().setHorizontalRule().run())" data-bs-toggle="tooltip" title="Ligne horizontale"><i data-lucide="minus" style="width:14px;height:14px;"></i></button>
        <span class="vr mx-1 align-self-stretch opacity-25"></span>

        {{-- Groupe 9 : Code + Exposant --}}
        <button type="button" class="btn btn-sm btn-outline-secondary" :class="{'is-active': isActive('code')}" @mousedown.prevent="cmd(() => editor.chain().focus().toggleCode().run())" data-bs-toggle="tooltip" title="Code inline"><i data-lucide="code" style="width:14px;height:14px;"></i></button>
        <button type="button" class="btn btn-sm btn-outline-secondary" :class="{'is-active': isActive('codeBlock')}" @mousedown.prevent="cmd(() => editor.chain().focus().toggleCodeBlock().run())" data-bs-toggle="tooltip" title="Bloc de code"><i data-lucide="square-code" style="width:14px;height:14px;"></i></button>
        <button type="button" class="btn btn-sm btn-outline-secondary" :class="{'is-active': isActive('superscript')}" @mousedown.prevent="cmd(() => editor.chain().focus().toggleSuperscript().run())" data-bs-toggle="tooltip" title="Exposant"><i data-lucide="superscript" style="width:14px;height:14px;"></i></button>
        <button type="button" class="btn btn-sm btn-outline-secondary" :class="{'is-active': isActive('subscript')}" @mousedown.prevent="cmd(() => editor.chain().focus().toggleSubscript().run())" data-bs-toggle="tooltip" title="Indice"><i data-lucide="subscript" style="width:14px;height:14px;"></i></button>
        <span class="vr mx-1 align-self-stretch opacity-25"></span>

        {{-- Groupe 10 : Outils --}}
        <button type="button" class="btn btn-sm btn-outline-secondary" @mousedown.prevent="clearFormatting()" data-bs-toggle="tooltip" title="Effacer la mise en forme"><i data-lucide="eraser" style="width:14px;height:14px;"></i></button>
        <button type="button" class="btn btn-sm btn-outline-secondary" :class="{'is-active': isFullscreen}" @mousedown.prevent="toggleFullscreen()" data-bs-toggle="tooltip" title="Plein écran"><i data-lucide="maximize-2" style="width:14px;height:14px;"></i></button>
    </div>

    <div x-ref="editorContent" class="tiptap-content border border-top-0" style="min-height:350px;outline:none;resize:vertical;overflow:auto;"></div>
    <div class="tiptap-statusbar d-flex align-items-center justify-content-end gap-3 px-3 py-1 border border-top-0 rounded-bottom bg-light">
        <small class="text-muted" x-text="wordCount + ' mots'"></small>
        <small class="text-muted" x-text="charCount + ' caractères'"></small>
    </div>
    <input type="hidden" x-ref="hiddenInput" name="{{ $name }}" :value="content">
</div>
</div>

@push('styles')
<style>
.tiptap-toolbar .btn { font-size:13px; padding:3px 8px; line-height:1.5; }
.tiptap-toolbar .btn.is-active { background-color:rgba(var(--bs-primary-rgb),.12); color:var(--bs-primary); border-color:rgba(var(--bs-primary-rgb),.4); }
.tiptap-toolbar .btn:not(.is-active):hover { background-color:rgba(var(--bs-primary-rgb),.05); }
.tiptap-content .ProseMirror { outline:none; min-height:310px; padding:1rem; }
.tiptap-content .ProseMirror h1 { font-size:1.8em; font-weight:700; margin:.8em 0 .4em; }
.tiptap-content .ProseMirror h2 { font-size:1.4em; font-weight:600; margin:.7em 0 .3em; }
.tiptap-content .ProseMirror h3 { font-size:1.2em; font-weight:600; margin:.6em 0 .3em; }
.tiptap-content .ProseMirror p { margin-bottom:.75em; }
.tiptap-content .ProseMirror ul,.tiptap-content .ProseMirror ol { padding-left:1.5em; margin-bottom:.75em; }
.tiptap-content .ProseMirror table { border-collapse:collapse; width:100%; margin-bottom:1em; }
.tiptap-content .ProseMirror th,.tiptap-content .ProseMirror td { border:1px solid var(--bs-border-color, #dee2e6); padding:.4em .6em; }
.tiptap-content .ProseMirror th { background-color:rgba(0,0,0,.04); font-weight:600; }
.tiptap-content .ProseMirror pre { background:#f8f9fa; border-radius:6px; padding:.8em; overflow-x:auto; }
.tiptap-content .ProseMirror code { background:#f8f9fa; border-radius:3px; font-size:87.5%; padding:.15em .3em; }
.tiptap-content .ProseMirror mark { background:#fff3cd; border-radius:2px; padding:.1em .2em; }
.tiptap-content .ProseMirror a { color:var(--bs-primary); text-decoration:underline; }
.tiptap-content .ProseMirror p.is-editor-empty:first-child::before { color:#adb5bd; content:attr(data-placeholder); float:left; height:0; pointer-events:none; }
.tiptap-content .ProseMirror .is-empty::before { color:#adb5bd; content:attr(data-placeholder); float:left; height:0; pointer-events:none; }
.tiptap-content .ProseMirror ul[data-type="taskList"] { list-style:none; padding-left:0; }
.tiptap-content .ProseMirror ul[data-type="taskList"] li { display:flex; align-items:flex-start; gap:.5rem; margin-bottom:.4em; }
.tiptap-content .ProseMirror ul[data-type="taskList"] li label { flex-shrink:0; margin-top:.25em; }
.tiptap-content .ProseMirror ul[data-type="taskList"] li[data-checked="true"] > div > p { text-decoration:line-through; opacity:.6; }
.tiptap-content .ProseMirror hr { border:none; border-top:2px solid var(--bs-border-color, #dee2e6); margin:1.5em 0; }
.tiptap-content .ProseMirror div[data-youtube-video] { position:relative; padding-bottom:56.25%; height:0; overflow:hidden; border-radius:8px; margin:1em 0; }
.tiptap-content .ProseMirror div[data-youtube-video] iframe { position:absolute; top:0; left:0; width:100%; height:100%; border:0; }
.tiptap-content .ProseMirror sup { font-size:75%; vertical-align:super; }
.tiptap-content .ProseMirror sub { font-size:75%; vertical-align:sub; }
.tiptap-statusbar { font-size:12px; }
.tiptap-content .ProseMirror blockquote { border-left:4px solid var(--bs-primary, #0d6efd); padding:.5rem 1rem; margin:1em 0; background:rgba(var(--bs-primary-rgb),.04); border-radius:0 6px 6px 0; }
.tiptap-content .ProseMirror blockquote p:last-child { margin-bottom:0; }
.tiptap-fullscreen { position:fixed!important; top:0; left:0; right:0; bottom:0; z-index:9999; background:#fff; display:flex; flex-direction:column; padding:0; }
.tiptap-fullscreen .tiptap-toolbar { border-radius:0!important; flex-shrink:0; }
.tiptap-fullscreen .tiptap-content { flex:1; border-radius:0!important; overflow:auto; }
.tiptap-fullscreen .tiptap-content .ProseMirror { min-height:100%; }
.tiptap-fullscreen .tiptap-statusbar { border-radius:0!important; flex-shrink:0; }
.tiptap-toolbar .form-select { border-color:var(--bs-border-color); }
.tiptap-toolbar .form-select:focus { border-color:rgba(var(--bs-primary-rgb),.4); box-shadow:none; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#tiptap-wrap [data-bs-toggle="tooltip"]').forEach(function(el) {
        new bootstrap.Tooltip(el, { trigger: 'hover' });
    });
});
</script>
@endpush
