// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'
import { Table } from '@tiptap/extension-table'
import { TableRow } from '@tiptap/extension-table-row'
import { TableCell } from '@tiptap/extension-table-cell'
import { TableHeader } from '@tiptap/extension-table-header'
import TextAlign from '@tiptap/extension-text-align'
import { Color } from '@tiptap/extension-color'
import { TextStyle as BaseTextStyle } from '@tiptap/extension-text-style'

const TextStyle = BaseTextStyle.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            fontSize: {
                default: null,
                parseHTML: el => el.style.fontSize || null,
                renderHTML: attrs => attrs.fontSize ? { style: `font-size: ${attrs.fontSize}` } : {},
            },
        }
    },
})
import Underline from '@tiptap/extension-underline'
import Link from '@tiptap/extension-link'
import Highlight from '@tiptap/extension-highlight'
import CodeBlockLowlight from '@tiptap/extension-code-block-lowlight'
import { createLowlight, common } from 'lowlight'
import Placeholder from '@tiptap/extension-placeholder'
import CharacterCount from '@tiptap/extension-character-count'
import Typography from '@tiptap/extension-typography'
import TaskList from '@tiptap/extension-task-list'
import TaskItem from '@tiptap/extension-task-item'
import Youtube from '@tiptap/extension-youtube'
import Superscript from '@tiptap/extension-superscript'
import Subscript from '@tiptap/extension-subscript'
import FontFamily from '@tiptap/extension-font-family'

const lowlight = createLowlight(common)

export function tiptapEditorComponent(config = {}) {
    return {
        editor: null,
        content: config.content || '',
        wordCount: 0,
        charCount: 0,
        isFullscreen: false,
        currentColor: '#000000',
        currentHighlight: '#fff3cd',
        init() {
            if (this.editor) this.editor.destroy()
            const el = this.$refs.editorContent
            if (!el) return
            // Prevent double init on same DOM element
            if (el._tiptapEditor) {
                el._tiptapEditor.destroy()
                delete el._tiptapEditor
            }
            const editorInstance = new Editor({
                element: el,
                extensions: [
                    StarterKit.configure({ codeBlock: false, link: false, underline: false }),
                    Link.configure({ openOnClick: false }),
                    Underline,
                    Image,
                    Table.configure({ resizable: true }),
                    TableRow,
                    TableCell,
                    TableHeader,
                    TextAlign.configure({ types: ['heading', 'paragraph'] }),
                    TextStyle,
                    Color,
                    Highlight.configure({ multicolor: true }),
                    CodeBlockLowlight.configure({ lowlight }),
                    Placeholder.configure({ placeholder: 'Commencez à écrire votre contenu...' }),
                    CharacterCount,
                    Typography,
                    TaskList,
                    TaskItem.configure({ nested: true }),
                    Youtube.configure({ controls: false, nocookie: true }),
                    Superscript,
                    Subscript,
                    FontFamily,
                ],
                content: this.content,
                onUpdate: ({ editor }) => {
                    this.content = editor.getHTML()
                    this.wordCount = editor.storage.characterCount.words()
                    this.charCount = editor.storage.characterCount.characters()
                    if (this.$refs.hiddenInput) {
                        this.$refs.hiddenInput.value = this.content
                    }
                },
            })
            this.editor = editorInstance
            // Store RAW (non-proxied) editor on DOM element.
            // Alpine wraps this.editor in a Proxy; ProseMirror's applyInner
            // compares tr.doc === state.doc by reference, which fails through proxies.
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
        // Universal command executor - synchronous to preserve ProseMirror selection state
        // NOTE: @mousedown.prevent on buttons prevents editor blur, so no need for rAF
        exec(fn) {
            if (!this.editor) return
            try { fn(this.editor) } catch (e) { console.warn('[TipTap]', e.message) }
        },
        cmd(callback) {
            if (!this.editor) return
            try { callback() } catch (e) { console.warn('[TipTap]', e.message) }
        },
        isActive(type, attrs = {}) {
            return this.editor?.isActive(type, attrs) ?? false
        },
        setLink() {
            const url = prompt('URL du lien :')
            if (url) this.cmd(() => this.editor.chain().focus().setLink({ href: url }).run())
        },
        // Media picker state
        mediaPickerOpen: false,
        mediaItems: [],
        mediaLoading: false,
        mediaUploading: false,
        mediaSearch: '',
        mediaPage: 1,
        mediaLastPage: 1,

        addImage() {
            this.mediaPickerOpen = true
            this.mediaSearch = ''
            this.mediaUrlInput = ''
            this.mediaPage = 1
            this.fetchMedia()
        },

        async fetchMedia() {
            this.mediaLoading = true
            try {
                const params = new URLSearchParams({ page: this.mediaPage })
                if (this.mediaSearch) params.set('search', this.mediaSearch)
                const res = await fetch(`/admin/media-api?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                })
                const data = await res.json()
                this.mediaItems = data.items || []
                this.mediaLastPage = data.meta?.last_page || 1
            } catch (e) {
                console.warn('[MediaPicker] fetch error:', e)
                this.mediaItems = []
            }
            this.mediaLoading = false
        },

        async uploadMedia(event) {
            const file = event.target.files?.[0]
            if (!file) return
            this.mediaUploading = true
            try {
                const form = new FormData()
                form.append('file', file)
                const token = document.querySelector('meta[name="csrf-token"]')?.content
                const res = await fetch('/admin/media-api', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                    body: form,
                })
                if (res.ok) {
                    await this.fetchMedia()
                }
            } catch (e) {
                console.warn('[MediaPicker] upload error:', e)
            }
            this.mediaUploading = false
            event.target.value = ''
        },

        selectMedia(item) {
            this.insertMediaImage(item.url, item.alt_text || '')
        },

        insertMediaImage(url, alt = '') {
            if (url && this.editor) {
                // Use RAW (non-proxied) editor to avoid Alpine Proxy breaking
                // ProseMirror's reference equality check (tr.doc === state.doc)
                const rawEditor = this.$refs.editorContent?._tiptapEditor
                if (rawEditor && !rawEditor.isDestroyed) {
                    rawEditor.commands.setImage({ src: url, alt })
                }
            }
            this.mediaPickerOpen = false
        },

        mediaUrlInput: '',
        insertImageByUrl() {
            if (this.mediaUrlInput.trim()) {
                this.insertMediaImage(this.mediaUrlInput.trim())
                this.mediaUrlInput = ''
            }
        },
        insertTable() {
            this.cmd(() => this.editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run())
        },
        addYoutube() {
            const url = prompt('URL YouTube :')
            if (url) this.cmd(() => this.editor.commands.setYoutubeVideo({ src: url }))
        },
        toggleTaskList() {
            this.cmd(() => this.editor.chain().focus().toggleTaskList().run())
        },
        characterCount() {
            return this.editor?.storage.characterCount ?? { characters: () => 0, words: () => 0 }
        },
        setColor(color) {
            this.currentColor = color
            this.cmd(() => this.editor.chain().focus().setColor(color).run())
        },
        setHighlightColor(color) {
            this.currentHighlight = color
            this.cmd(() => this.editor.chain().focus().toggleHighlight({ color }).run())
        },
        clearFormatting() {
            this.cmd(() => this.editor.chain().focus().unsetAllMarks().clearNodes().run())
        },
        toggleFullscreen() {
            this.isFullscreen = !this.isFullscreen
        },
        setFontFamily(family) {
            this.cmd(() => {
                if (family) {
                    this.editor.chain().focus().setFontFamily(family).run()
                } else {
                    this.editor.chain().focus().unsetFontFamily().run()
                }
            })
        },
        setFontSize(size) {
            this.cmd(() => {
                if (size) {
                    this.editor.chain().focus().setMark('textStyle', { fontSize: size }).run()
                } else {
                    this.editor.chain().focus().unsetMark('textStyle').run()
                }
            })
        },
    }
}

if (!window._tiptapRegistered) {
    window._tiptapRegistered = true
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('tiptapEditor', tiptapEditorComponent)
    })
}
