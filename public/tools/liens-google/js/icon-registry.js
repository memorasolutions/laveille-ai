/**
 * Module IconRegistry - Registre centralisé des icônes
 * Réutilisable pour : UI, notifications, boutons, types de fichiers
 * Keep it simple - Une seule source de vérité pour les icônes
 */

class IconRegistry {
    constructor() {
        // Utiliser IconProvider si disponible, sinon utiliser les entités HTML
        this.provider = window.IconProvider || null;
        
        // Icônes par catégorie avec entités HTML
        this.icons = {
            // Types de fichiers Google
            fileTypes: {
                docs: '&#128196;',     // 📄
                sheets: '&#128202;',   // 📊
                slides: '&#127919;',   // 🎯
                forms: '&#128221;',    // 📝
                drive: '&#128190;',    // 💾
                pdf: '&#128209;',      // 📑
                image: '&#127912;',    // 🖼️
                video: '&#127916;',    // 🎬
                folder: '&#128193;',   // 📁
                generic: '&#128206;'   // 📎
            },

            // Actions
            actions: {
                copy: '&#128203;',     // 📋
                paste: '&#128204;',    // 📌
                download: '&#8595;',   // ⬇
                upload: '&#8593;',     // ⬆
                share: '&#128279;',    // 🔗
                edit: '&#9998;',       // ✎
                delete: '&#128465;',   // 🗑
                view: '&#128065;',     // 👁
                preview: '&#128064;',  // 👀
                export: '&#128228;',   // 📤
                import: '&#128229;',   // 📥
                print: '&#128424;',    // 🖨
                save: '&#128190;',     // 💾
                refresh: '&#128260;',  // 🔄
                settings: '&#9881;',   // ⚙
                search: '&#128269;',   // 🔍
                filter: '&#128317;',   // 🔽
                sort: '&#8597;',       // ↕
                add: '&#10133;',       // ➕
                remove: '&#10134;',    // ➖
                check: '&#9989;',      // ✅
                cancel: '&#10060;',    // ❌
                info: '&#8505;',       // ℹ
                help: '&#10067;',      // ❓
                warning: '&#9888;',    // ⚠
                error: '&#10071;',     // ❗
                success: '&#9989;',    // ✅
                apply: '&#10004;'      // ✔
            },

            // États
            states: {
                loading: '&#9203;',    // ⏳
                ready: '&#128994;',    // 🟢
                busy: '&#128308;',     // 🔴
                idle: '&#9898;',       // ⚪
                active: '&#128309;',   // 🔵
                inactive: '&#9899;',   // ⚫
                online: '&#128994;',   // 🟢
                offline: '&#128308;',  // 🔴
                synced: '&#9729;',     // ☁
                notSynced: '&#127787;' // 🌫
            },

            // UI Elements
            ui: {
                menu: '☰',
                close: '✖️',
                minimize: '➖',
                maximize: '➕',
                expand: '▼',
                collapse: '▲',
                next: '▶️',
                previous: '◀️',
                play: '▶️',
                pause: '⏸️',
                stop: '⏹️',
                gridOn: '⊞',
                gridOff: '⊡',
                listView: '☰',
                gridView: '⊞',
                thumbnailView: '🎞️'
            },

            // Formats d'export
            formats: {
                pdf: '📄',
                word: '📝',
                excel: '📊',
                powerpoint: '🎞️',
                csv: '📈',
                txt: '📃',
                html: '🌐',
                epub: '📚',
                zip: '🗜️',
                json: '{ }',
                xml: '< >'
            },

            // Orientations
            orientation: {
                portrait: '📱',
                landscape: '💻',
                auto: '🔄'
            },

            // Marges
            margins: {
                normal: '⬜',
                narrow: '▫️',
                none: '◻️',
                wide: '⬛',
                custom: '📐'
            },

            // Notifications
            notifications: {
                info: 'ℹ️',
                success: '✅',
                warning: '⚠️',
                error: '❌',
                loading: '⏳',
                question: '❓'
            },

            // Services Google
            google: {
                docs: '📄',
                sheets: '📊',
                slides: '🎯',
                forms: '📝',
                drive: '💾',
                meet: '📹',
                calendar: '📅',
                maps: '🗺️',
                photos: '📷',
                youtube: '🎬',
                gmail: '📧',
                translate: '🌐'
            }
        };

        // Alias pour compatibilité
        this.aliases = {
            'document': 'docs',
            'spreadsheet': 'sheets',
            'presentation': 'slides',
            'form': 'forms',
            'check': 'success',
            'cross': 'error',
            'tick': 'check'
        };
    }

    /**
     * Obtenir une icône par chemin
     * @param {string} path - Chemin de l'icône (ex: 'actions.copy')
     * @param {string} fallback - Icône par défaut si non trouvée
     * @returns {string} - Icône emoji
     */
    get(path, fallback = '📌') {
        const parts = path.split('.');
        let current = this.icons;
        
        for (const part of parts) {
            // Vérifier les alias
            const key = this.aliases[part] || part;
            
            if (current && typeof current === 'object' && key in current) {
                current = current[key];
            } else {
                return fallback;
            }
        }
        
        return current || fallback;
    }

    /**
     * Obtenir toutes les icônes d'une catégorie
     * @param {string} category - Nom de la catégorie
     * @returns {Object} - Objet avec toutes les icônes
     */
    getCategory(category) {
        return this.icons[category] || {};
    }

    /**
     * Ajouter une icône personnalisée
     * @param {string} path - Chemin où ajouter l'icône
     * @param {string} icon - Icône à ajouter
     */
    add(path, icon) {
        const parts = path.split('.');
        const key = parts.pop();
        let current = this.icons;
        
        for (const part of parts) {
            if (!(part in current)) {
                current[part] = {};
            }
            current = current[part];
        }
        
        current[key] = icon;
    }

    /**
     * Ajouter un alias
     * @param {string} alias - Nom de l'alias
     * @param {string} target - Cible de l'alias
     */
    addAlias(alias, target) {
        this.aliases[alias] = target;
    }

    /**
     * Obtenir l'icône appropriée pour un type MIME
     * @param {string} mimeType - Type MIME
     * @returns {string} - Icône
     */
    getForMimeType(mimeType) {
        const typeMap = {
            'application/pdf': this.icons.formats.pdf,
            'application/msword': this.icons.formats.word,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document': this.icons.formats.word,
            'application/vnd.ms-excel': this.icons.formats.excel,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': this.icons.formats.excel,
            'application/vnd.ms-powerpoint': this.icons.formats.powerpoint,
            'application/vnd.openxmlformats-officedocument.presentationml.presentation': this.icons.formats.powerpoint,
            'text/csv': this.icons.formats.csv,
            'text/plain': this.icons.formats.txt,
            'text/html': this.icons.formats.html,
            'application/json': this.icons.formats.json,
            'application/xml': this.icons.formats.xml,
            'application/zip': this.icons.formats.zip,
            'image/': this.icons.fileTypes.image,
            'video/': this.icons.fileTypes.video
        };

        for (const [key, icon] of Object.entries(typeMap)) {
            if (mimeType.startsWith(key)) {
                return icon;
            }
        }

        return this.icons.fileTypes.generic;
    }

    /**
     * Obtenir l'icône pour une extension de fichier
     * @param {string} extension - Extension (avec ou sans point)
     * @returns {string} - Icône
     */
    getForExtension(extension) {
        const ext = extension.toLowerCase().replace('.', '');
        
        const extMap = {
            'pdf': this.icons.formats.pdf,
            'doc': this.icons.formats.word,
            'docx': this.icons.formats.word,
            'xls': this.icons.formats.excel,
            'xlsx': this.icons.formats.excel,
            'ppt': this.icons.formats.powerpoint,
            'pptx': this.icons.formats.powerpoint,
            'csv': this.icons.formats.csv,
            'txt': this.icons.formats.txt,
            'html': this.icons.formats.html,
            'epub': this.icons.formats.epub,
            'json': this.icons.formats.json,
            'xml': this.icons.formats.xml,
            'zip': this.icons.formats.zip,
            'jpg': this.icons.fileTypes.image,
            'jpeg': this.icons.fileTypes.image,
            'png': this.icons.fileTypes.image,
            'gif': this.icons.fileTypes.image,
            'mp4': this.icons.fileTypes.video,
            'avi': this.icons.fileTypes.video,
            'mov': this.icons.fileTypes.video
        };

        return extMap[ext] || this.icons.fileTypes.generic;
    }

    /**
     * Créer un élément HTML avec icône
     * @param {string} iconPath - Chemin de l'icône
     * @param {string} text - Texte à afficher
     * @param {string} tag - Tag HTML (span par défaut)
     * @returns {HTMLElement}
     */
    createElement(iconPath, text = '', tag = 'span') {
        const element = document.createElement(tag);
        const icon = this.get(iconPath);
        
        if (text) {
            element.innerHTML = `<span class="icon">${icon}</span> <span class="text">${text}</span>`;
        } else {
            element.innerHTML = icon;
        }
        
        element.className = 'icon-element';
        return element;
    }

    /**
     * Obtenir toutes les icônes disponibles (pour documentation)
     * @returns {Object}
     */
    getAll() {
        return this.icons;
    }
}

// Export singleton
const iconRegistry = new IconRegistry();

if (typeof module !== 'undefined' && module.exports) {
    module.exports = iconRegistry;
}