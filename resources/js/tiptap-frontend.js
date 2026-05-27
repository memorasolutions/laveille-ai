/**
 * TipTap editor for frontend discussions (Alpine.js CDN compatible).
 * Registers the tiptapEditor Alpine.data() component on window.Alpine.
 * Loaded via @vite('resources/js/tiptap-frontend.js') only on pages that need it.
 */
import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'
import Highlight from '@tiptap/extension-highlight'
import Underline from '@tiptap/extension-underline'
// Link inclus dans StarterKit v3.19+ — ne pas importer séparément (duplicate extension warning)

function registerTiptapEditor() {
    if (window._tiptapFrontendRegistered) return
    const Alpine = window.Alpine
    if (!Alpine || !Alpine.data) return
    window._tiptapFrontendRegistered = true

    Alpine.data('tiptapEditor', (config = {}) => ({
        editor: null,
        content: config.content || '',

        init() {
            if (this.editor) this.editor.destroy()
            const el = this.$refs.editorContent
            if (!el) return

            const editorInstance = new Editor({
                element: el,
                extensions: [
                    StarterKit.configure({ codeBlock: false, link: { openOnClick: false } }),
                    Placeholder.configure({ placeholder: config.placeholder || 'Écrivez votre message...' }),
                ],
                content: this.content,
                onUpdate: ({ editor }) => {
                    this.content = editor.getHTML()
                    if (this.$refs.hiddenInput) {
                        this.$refs.hiddenInput.value = this.content
                    }
                },
            })
            this.editor = editorInstance
            el._tiptapEditor = editorInstance
        },

        destroy() {
            if (this.editor) {
                const el = this.$refs.editorContent
                if (el) delete el._tiptapEditor
                this.editor.destroy()
                this.editor = null
            }
        },

        cmd(fn) {
            if (!this.editor) return
            try { fn() } catch (e) { console.warn('[TipTap]', e.message) }
        },

        isActive(type, attrs = {}) {
            return this.editor?.isActive(type, attrs) ?? false
        },

        setLink() {
            const url = prompt('URL du lien :')
            if (url) this.cmd(() => this.editor.chain().focus().setLink({ href: url }).run())
        },
    }))

    // === Composant dédié anonymiseur de texte (Sprint S129) ===
    // Tiptap avec Highlight + Underline + BubbleMenu vanilla + shim sourceText pour app.js source
    Alpine.data('tiptapAnonymiseur', (config = {}) => ({
        editor: null,
        characterCount: 0,
        isReady: false,
        _ghost: null,

        init() {
            // Shim ghost #sourceText pour anti-régression : app.js source utilise
            // document.getElementById('sourceText').innerText et .textContent.
            // On expose getter dynamique via le ghost div absolute -9999px.
            const ghost = document.createElement('div')
            ghost.id = 'sourceText'
            ghost.setAttribute('aria-hidden', 'true')
            ghost.style.cssText = 'position:absolute;left:-9999px;top:0;width:1px;height:1px;overflow:hidden;'
            document.body.appendChild(ghost)
            this._ghost = ghost

            const self = this
            Object.defineProperty(ghost, 'innerText', {
                configurable: true,
                get() { return self.editor ? self.editor.getText() : '' },
                set(value) {
                    const text = String(value || '')
                    if (self.editor) self.editor.commands.setContent(text)
                    else ghost.textContent = text
                }
            })
            Object.defineProperty(ghost, 'textContent', {
                configurable: true,
                get() { return self.editor ? self.editor.getText() : '' },
                set(value) {
                    const text = String(value || '')
                    if (self.editor) self.editor.commands.setContent(text)
                }
            })

            const el = this.$refs.editorContent
            if (!el) return

            const stripWordPaste = (html) => {
                if (!html) return html
                let cleaned = String(html)
                cleaned = cleaned.replace(/<!--\[if[^>]*?\]>[\s\S]*?<!\[endif\]-->/gi, '')
                cleaned = cleaned.replace(/<o:p[^>]*>[\s\S]*?<\/o:p>/gi, '')
                cleaned = cleaned.replace(/<\/?(meta|link|style|script|xml)[^>]*>/gi, '')
                cleaned = cleaned.replace(/\sclass="Mso[^"]*"/gi, '')
                cleaned = cleaned.replace(/\sstyle="[^"]*"/gi, '')
                cleaned = cleaned.replace(/<span[^>]*>(\s*)<\/span>/gi, '$1')
                return cleaned
            }

            this.editor = new Editor({
                element: el,
                extensions: [
                    StarterKit.configure({
                        codeBlock: false,
                        link: { openOnClick: false },
                        heading: { levels: [2, 3] }
                    }),
                    Placeholder.configure({
                        placeholder: config.placeholder || 'Collez ici le texte à anonymiser avant de l\'envoyer à une IA…'
                    }),
                    Highlight.configure({ multicolor: true }),
                    Underline
                ],
                editorProps: {
                    attributes: {
                        role: 'textbox',
                        'aria-multiline': 'true',
                        'aria-label': config.ariaLabel || 'Zone de texte à anonymiser',
                        spellcheck: 'true'
                    },
                    transformPastedHTML(html) { return stripWordPaste(html) }
                },
                content: config.content || '',
                onUpdate: ({ editor }) => {
                    self.characterCount = editor.getText().length
                    // Notifier app.js source via CustomEvent (anti-régression)
                    window.dispatchEvent(new CustomEvent('anonymiseur:text-change', { detail: { text: editor.getText() } }))
                    // Mettre à jour aussi #charCount du DOM si présent (compat JS source)
                    const cc = document.getElementById('charCount')
                    if (cc) cc.textContent = String(editor.getText().length)
                    // Trigger input event sur ghost pour les listeners app.js source
                    try { ghost.dispatchEvent(new Event('input', { bubbles: true })) } catch (e) { /* silent */ }
                },
                onSelectionUpdate: ({ editor }) => {
                    const bubble = self.$refs.bubbleMenu
                    if (!bubble) return
                    const sel = editor.state.selection
                    if (sel.empty || !window.getSelection().rangeCount) {
                        bubble.classList.remove('is-visible')
                        return
                    }
                    try {
                        const range = window.getSelection().getRangeAt(0)
                        const rect = range.getBoundingClientRect()
                        if (rect.width === 0 && rect.height === 0) {
                            bubble.classList.remove('is-visible')
                            return
                        }
                        const bubbleRect = bubble.getBoundingClientRect()
                        let left = rect.left + window.scrollX + (rect.width / 2) - (bubbleRect.width / 2)
                        let top = rect.top + window.scrollY - bubbleRect.height - 8
                        if (top < window.scrollY + 8) top = rect.bottom + window.scrollY + 8
                        if (left < window.scrollX + 8) left = window.scrollX + 8
                        const maxLeft = window.scrollX + window.innerWidth - bubbleRect.width - 8
                        if (left > maxLeft) left = maxLeft
                        bubble.style.left = `${left}px`
                        bubble.style.top = `${top}px`
                        bubble.classList.add('is-visible')
                    } catch (e) {
                        bubble.classList.remove('is-visible')
                    }
                }
            })

            el._tiptapEditor = this.editor
            this.isReady = true
            window.tiptapAnonymiseurEditor = this.editor
            window.dispatchEvent(new CustomEvent('tiptap-anonymiseur:ready', { detail: { editor: this.editor } }))
        },

        destroy() {
            if (this.editor) {
                const el = this.$refs.editorContent
                if (el) delete el._tiptapEditor
                this.editor.destroy()
                this.editor = null
            }
            if (this._ghost && this._ghost.parentNode) {
                this._ghost.parentNode.removeChild(this._ghost)
            }
        },

        toggleBold() { this.editor?.chain().focus().toggleBold().run() },
        toggleItalic() { this.editor?.chain().focus().toggleItalic().run() },
        toggleUnderline() { this.editor?.chain().focus().toggleUnderline().run() },
        toggleStrike() { this.editor?.chain().focus().toggleStrike().run() },
        toggleCode() { this.editor?.chain().focus().toggleCode().run() },
        toggleBulletList() { this.editor?.chain().focus().toggleBulletList().run() },
        toggleOrderedList() { this.editor?.chain().focus().toggleOrderedList().run() },
        toggleHighlight() { this.editor?.chain().focus().toggleHighlight().run() },
        clearMarks() { this.editor?.chain().focus().unsetAllMarks().run() },
        clearFormatting() { this.editor?.chain().focus().unsetAllMarks().clearNodes().run() },
        isActive(mark, attrs = {}) { return this.editor?.isActive(mark, attrs) || false },
        setContent(text) { this.editor?.commands.setContent(text || '') },
        getText() { return this.editor?.getText() || '' },
        getHTML() { return this.editor?.getHTML() || '' }
    }))
}

// Register on Alpine — handle both cases:
// 1. If Alpine is not yet loaded, listen for alpine:init
// 2. If Alpine is already loaded (CDN defer loaded before this module), register directly
if (window.Alpine && window.Alpine.data) {
    registerTiptapEditor()
} else {
    document.addEventListener('alpine:init', registerTiptapEditor)
}

// Also handle late registration — Alpine CDN may load AFTER this module in some scenarios
document.addEventListener('DOMContentLoaded', () => {
    if (window.Alpine && window.Alpine.data && !window._tiptapFrontendRegistered) {
        registerTiptapEditor()
    }
})

// === Init vanilla Tiptap pour anonymiseur (S129) ===
// Bypass Alpine race condition : init direct sur [data-tiptap-anonymiseur="1"] dès DOMContentLoaded.
// Plus prévisible que x-data Alpine (qui peut scanner avant que le composant ne soit registered).
async function initAnonymiseurTiptap() {
    const host = document.querySelector('[data-tiptap-anonymiseur="1"]')
    if (!host || host._tiptapInited) return
    host._tiptapInited = true

    // Ghost sourceText shim pour anti-régression app.js source
    let ghost = document.getElementById('sourceText')
    if (!ghost) {
        ghost = document.createElement('div')
        ghost.id = 'sourceText'
        ghost.setAttribute('aria-hidden', 'true')
        ghost.style.cssText = 'position:absolute;left:-9999px;top:0;width:1px;height:1px;overflow:hidden;'
        document.body.appendChild(ghost)
    }

    const stripWordPaste = (html) => {
        if (!html) return html
        let cleaned = String(html)
        cleaned = cleaned.replace(/<!--\[if[^>]*?\]>[\s\S]*?<!\[endif\]-->/gi, '')
        cleaned = cleaned.replace(/<o:p[^>]*>[\s\S]*?<\/o:p>/gi, '')
        cleaned = cleaned.replace(/<\/?(meta|link|style|script|xml)[^>]*>/gi, '')
        cleaned = cleaned.replace(/\sclass="Mso[^"]*"/gi, '')
        cleaned = cleaned.replace(/\sstyle="[^"]*"/gi, '')
        cleaned = cleaned.replace(/<span[^>]*>(\s*)<\/span>/gi, '$1')
        return cleaned
    }

    const editor = new Editor({
        element: host,
        extensions: [
            StarterKit.configure({ codeBlock: false, link: { openOnClick: false }, heading: { levels: [2, 3] } }),
            Placeholder.configure({ placeholder: 'Collez ici le texte à anonymiser avant de l\'envoyer à une IA…' }),
            Highlight.configure({ multicolor: true }),
            Underline
        ],
        editorProps: {
            attributes: {
                role: 'textbox', 'aria-multiline': 'true',
                'aria-label': 'Zone de texte à anonymiser', spellcheck: 'true'
            },
            transformPastedHTML(html) { return stripWordPaste(html) }
        },
        onUpdate: ({ editor }) => {
            const cc = document.getElementById('charCount')
            if (cc) cc.textContent = String(editor.getText().length)
            window.dispatchEvent(new CustomEvent('anonymiseur:text-change', { detail: { text: editor.getText() } }))
            try { ghost.dispatchEvent(new Event('input', { bubbles: true })) } catch (e) {}
        },
        onSelectionUpdate: ({ editor }) => {
            const bubble = document.getElementById('tiptap-bubble-menu')
            if (!bubble) return
            const sel = editor.state.selection
            if (sel.empty || !window.getSelection().rangeCount) {
                bubble.classList.remove('is-visible')
                return
            }
            try {
                const range = window.getSelection().getRangeAt(0)
                const rect = range.getBoundingClientRect()
                if (rect.width === 0 && rect.height === 0) { bubble.classList.remove('is-visible'); return }
                const br = bubble.getBoundingClientRect()
                let left = rect.left + window.scrollX + (rect.width / 2) - (br.width / 2)
                let top = rect.top + window.scrollY - br.height - 8
                if (top < window.scrollY + 8) top = rect.bottom + window.scrollY + 8
                if (left < window.scrollX + 8) left = window.scrollX + 8
                const maxLeft = window.scrollX + window.innerWidth - br.width - 8
                if (left > maxLeft) left = maxLeft
                bubble.style.left = `${left}px`
                bubble.style.top = `${top}px`
                bubble.classList.add('is-visible')
                bubble.querySelectorAll('button[data-mark]').forEach(btn => {
                    btn.classList.toggle('is-active', editor.isActive(btn.getAttribute('data-mark')))
                })
            } catch (e) { bubble.classList.remove('is-visible') }
        }
    })

    // Shim getters #sourceText
    Object.defineProperty(ghost, 'innerText', {
        configurable: true,
        get() { return editor.getText() },
        set(v) { editor.commands.setContent(String(v || '')) }
    })
    Object.defineProperty(ghost, 'textContent', {
        configurable: true,
        get() { return editor.getText() },
        set(v) { editor.commands.setContent(String(v || '')) }
    })

    host._tiptapEditor = editor
    window.tiptapAnonymiseurEditor = editor

    // Wire bubble menu
    const bubble = document.getElementById('tiptap-bubble-menu')
    if (bubble) {
        bubble.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-mark], button[data-action]')
            if (!btn) return
            e.preventDefault()
            const mark = btn.getAttribute('data-mark')
            const action = btn.getAttribute('data-action')
            if (mark === 'bold') editor.chain().focus().toggleBold().run()
            else if (mark === 'italic') editor.chain().focus().toggleItalic().run()
            else if (mark === 'underline') editor.chain().focus().toggleUnderline().run()
            else if (mark === 'strike') editor.chain().focus().toggleStrike().run()
            else if (mark === 'code') editor.chain().focus().toggleCode().run()
            else if (mark === 'highlight') editor.chain().focus().toggleHighlight().run()
            else if (action === 'clearmark') editor.chain().focus().unsetAllMarks().run()
        })
    }

    window.dispatchEvent(new CustomEvent('tiptap-anonymiseur:ready', { detail: { editor } }))
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAnonymiseurTiptap)
} else {
    initAnonymiseurTiptap()
}
