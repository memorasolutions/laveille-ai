/**
 * Module Notification - Web Component Réutilisable
 * Tendances 2025 : Web Components natifs, zero dépendances
 * Utilisé pour : détection, succès copie, erreurs, feedback
 */

class UINotification extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
        this.timeout = null;
    }

    static get observedAttributes() {
        return ['type', 'message', 'duration', 'position'];
    }

    connectedCallback() {
        this.render();
        this.autoHide();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (oldValue !== newValue) {
            this.render();
            if (name === 'message' && newValue) {
                this.show();
            }
        }
    }

    render() {
        const type = this.getAttribute('type') || 'info';
        const message = this.getAttribute('message') || '';
        const position = this.getAttribute('position') || 'top-center';
        
        const icons = {
            info: '💡',
            success: '✅',
            warning: '⚠️',
            error: '❌',
            loading: '⏳'
        };

        const colors = {
            info: { bg: '#e0f2fe', border: '#0284c7', text: '#075985' },
            success: { bg: '#d1fae5', border: '#10b981', text: '#047857' },
            warning: { bg: '#fef3c7', border: '#f59e0b', text: '#d97706' },
            error: { bg: '#fecaca', border: '#ef4444', text: '#dc2626' },
            loading: { bg: '#fef3c7', border: '#f59e0b', text: '#d97706' }
        };

        const color = colors[type] || colors.info;

        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    position: fixed;
                    z-index: 10000;
                    pointer-events: none;
                    transition: all 0.3s ease;
                    opacity: 0;
                    transform: translateY(-20px);
                }

                :host([position="top-center"]) {
                    top: 20px;
                    left: 50%;
                    transform: translateX(-50%) translateY(-20px);
                }

                :host([position="top-right"]) {
                    top: 20px;
                    right: 20px;
                }

                :host([position="bottom-center"]) {
                    bottom: 20px;
                    left: 50%;
                    transform: translateX(-50%) translateY(20px);
                }

                :host([position="bottom-right"]) {
                    bottom: 20px;
                    right: 20px;
                }

                :host(.show) {
                    opacity: 1;
                    transform: translateX(var(--translate-x, 0)) translateY(0);
                }

                :host([position="top-center"].show) {
                    transform: translateX(-50%) translateY(0);
                }

                :host([position="bottom-center"].show) {
                    transform: translateX(-50%) translateY(0);
                }

                .notification {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 12px 20px;
                    background: ${color.bg};
                    border: 1px solid ${color.border};
                    border-radius: 8px;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                    pointer-events: auto;
                    min-width: 250px;
                    max-width: 400px;
                }

                .icon {
                    font-size: 20px;
                    flex-shrink: 0;
                }

                .message {
                    flex: 1;
                    color: ${color.text};
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
                    font-size: 14px;
                    font-weight: 500;
                    line-height: 1.4;
                }

                @keyframes spin {
                    to { transform: rotate(360deg); }
                }

                .icon.loading {
                    animation: spin 1s linear infinite;
                }

                @media (max-width: 640px) {
                    :host([position*="center"]) {
                        left: 10px;
                        right: 10px;
                        transform: none !important;
                    }

                    :host([position*="center"].show) {
                        transform: none !important;
                    }

                    .notification {
                        min-width: auto;
                        max-width: none;
                    }
                }
            </style>
            <div class="notification">
                <span class="icon ${type === 'loading' ? 'loading' : ''}">${icons[type]}</span>
                <span class="message">${message}</span>
            </div>
        `;
    }

    show() {
        requestAnimationFrame(() => {
            this.classList.add('show');
        });
        this.autoHide();
    }

    hide() {
        this.classList.remove('show');
        setTimeout(() => {
            if (this.parentNode) {
                this.parentNode.removeChild(this);
            }
        }, 300);
    }

    autoHide() {
        const duration = parseInt(this.getAttribute('duration') || '3000');
        const type = this.getAttribute('type');
        
        // Pas d'auto-hide pour loading
        if (type === 'loading') return;
        
        if (this.timeout) {
            clearTimeout(this.timeout);
        }
        
        this.timeout = setTimeout(() => {
            this.hide();
        }, duration);
    }
}

// Enregistrer le composant
customElements.define('ui-notification', UINotification);

// API Helper pour créer des notifications facilement
window.Notification = {
    show(message, type = 'info', options = {}) {
        const notification = document.createElement('ui-notification');
        notification.setAttribute('message', message);
        notification.setAttribute('type', type);
        notification.setAttribute('position', options.position || 'top-center');
        notification.setAttribute('duration', options.duration || '3000');
        
        document.body.appendChild(notification);
        return notification;
    },

    info(message, options = {}) {
        return this.show(message, 'info', options);
    },

    success(message, options = {}) {
        return this.show(message, 'success', options);
    },

    warning(message, options = {}) {
        return this.show(message, 'warning', options);
    },

    error(message, options = {}) {
        return this.show(message, 'error', options);
    },

    loading(message = 'Chargement...', options = {}) {
        return this.show(message, 'loading', { ...options, duration: 0 });
    }
};

// Export pour utilisation modulaire
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { UINotification, Notification: window.Notification };
}