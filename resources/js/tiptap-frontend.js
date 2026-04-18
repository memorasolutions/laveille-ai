/**
 * TipTap editor for frontend discussions (Alpine.js CDN compatible).
 * Registers the tiptapEditor Alpine.data() component on window.Alpine.
 * Loaded via @vite('resources/js/tiptap-frontend.js') only on pages that need it.
 */
import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'
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
