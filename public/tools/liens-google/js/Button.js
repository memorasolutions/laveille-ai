/**
 * Module Button - Web Component Réutilisable
 * Tendances 2025 : Web Components natifs, accessibilité ARIA
 * Utilisé pour : transformations, copie, actions, navigation
 */

class UIButton extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
        this._loading = false;
        this._disabled = false;
    }

    static get observedAttributes() {
        return ['variant', 'size', 'icon', 'loading', 'disabled', 'full-width', 'type'];
    }

    connectedCallback() {
        this.render();
        this.setupEventListeners();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (oldValue !== newValue) {
            this.render();
        }
    }

    render() {
        const variant = this.getAttribute('variant') || 'default';
        const size = this.getAttribute('size') || 'medium';
        const icon = this.getAttribute('icon') || '';
        const loading = this.hasAttribute('loading');
        const disabled = this.hasAttribute('disabled');
        const fullWidth = this.hasAttribute('full-width');
        const type = this.getAttribute('type') || 'button';

        const variants = {
            default: {
                bg: '#f3f4f6',
                hover: '#e5e7eb',
                text: '#374151',
                border: '#d1d5db'
            },
            primary: {
                bg: '#0ea5e9',
                hover: '#0284c7',
                text: '#ffffff',
                border: '#0ea5e9'
            },
            success: {
                bg: '#10b981',
                hover: '#059669',
                text: '#ffffff',
                border: '#10b981'
            },
            danger: {
                bg: '#ef4444',
                hover: '#dc2626',
                text: '#ffffff',
                border: '#ef4444'
            },
            ghost: {
                bg: 'transparent',
                hover: '#f3f4f6',
                text: '#374151',
                border: 'transparent'
            },
            outline: {
                bg: 'transparent',
                hover: '#f9fafb',
                text: '#0ea5e9',
                border: '#0ea5e9'
            }
        };

        const sizes = {
            small: {
                height: '32px',
                padding: '6px 12px',
                fontSize: '13px',
                iconSize: '14px'
            },
            medium: {
                height: '40px',
                padding: '8px 16px',
                fontSize: '14px',
                iconSize: '16px'
            },
            large: {
                height: '48px',
                padding: '10px 20px',
                fontSize: '16px',
                iconSize: '18px'
            }
        };

        const currentVariant = variants[variant] || variants.default;
        const currentSize = sizes[size] || sizes.medium;

        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    display: inline-block;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
                    ${fullWidth ? 'width: 100%;' : ''}
                }

                .button {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    height: ${currentSize.height};
                    padding: ${currentSize.padding};
                    font-size: ${currentSize.fontSize};
                    font-weight: 500;
                    line-height: 1;
                    color: ${currentVariant.text};
                    background: ${currentVariant.bg};
                    border: 1px solid ${currentVariant.border};
                    border-radius: 8px;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    outline: none;
                    user-select: none;
                    white-space: nowrap;
                    ${fullWidth ? 'width: 100%;' : ''}
                }

                .button:hover:not(:disabled) {
                    background: ${currentVariant.hover};
                    transform: translateY(-1px);
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                }

                .button:active:not(:disabled) {
                    transform: translateY(0);
                    box-shadow: none;
                }

                .button:focus-visible {
                    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.2);
                }

                .button:disabled {
                    opacity: 0.5;
                    cursor: not-allowed;
                }

                .button.loading {
                    cursor: wait;
                    pointer-events: none;
                }

                .icon {
                    font-size: ${currentSize.iconSize};
                    flex-shrink: 0;
                }

                .loading-icon {
                    animation: spin 1s linear infinite;
                }

                @keyframes spin {
                    to { transform: rotate(360deg); }
                }

                .content {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                }

                /* Ripple Effect */
                .button {
                    position: relative;
                    overflow: hidden;
                }

                .ripple {
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.5);
                    transform: scale(0);
                    animation: ripple 0.6s ease-out;
                    pointer-events: none;
                }

                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }

                /* Icon-only button */
                :host([icon-only]) .button {
                    padding: 8px;
                    width: ${currentSize.height};
                    height: ${currentSize.height};
                }

                /* Success animation */
                .button.success-animation {
                    animation: success-pulse 0.5s ease;
                }

                @keyframes success-pulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.05); }
                }

                @media (max-width: 640px) {
                    .button {
                        font-size: 16px; /* Prevent zoom on iOS */
                    }
                }
            </style>
            <button 
                class="button ${loading ? 'loading' : ''}" 
                type="${type}"
                ${disabled || loading ? 'disabled' : ''}
                aria-busy="${loading}"
                aria-disabled="${disabled}"
            >
                <div class="content">
                    ${loading ? '<span class="icon loading-icon">⏳</span>' : ''}
                    ${!loading && icon ? `<span class="icon">${icon}</span>` : ''}
                    <slot></slot>
                </div>
            </button>
        `;
    }

    setupEventListeners() {
        const button = this.shadowRoot.querySelector('.button');
        
        // Click avec ripple effect
        button.addEventListener('click', (e) => {
            if (!this._disabled && !this._loading) {
                // Ripple effect
                const ripple = document.createElement('span');
                ripple.className = 'ripple';
                
                const rect = button.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                
                button.appendChild(ripple);
                
                setTimeout(() => ripple.remove(), 600);
                
                // Dispatch custom event
                this.dispatchEvent(new CustomEvent('click', {
                    bubbles: true,
                    composed: true
                }));
            }
        });

        // Keyboard support
        button.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                button.click();
            }
        });
    }

    // Public API
    set loading(value) {
        if (value) {
            this.setAttribute('loading', '');
        } else {
            this.removeAttribute('loading');
        }
    }

    get loading() {
        return this.hasAttribute('loading');
    }

    set disabled(value) {
        if (value) {
            this.setAttribute('disabled', '');
        } else {
            this.removeAttribute('disabled');
        }
    }

    get disabled() {
        return this.hasAttribute('disabled');
    }

    showSuccess() {
        const button = this.shadowRoot.querySelector('.button');
        button.classList.add('success-animation');
        setTimeout(() => {
            button.classList.remove('success-animation');
        }, 500);
    }

    click() {
        this.shadowRoot.querySelector('.button').click();
    }
}

// Enregistrer le composant
customElements.define('ui-button', UIButton);

// Export pour utilisation modulaire
if (typeof module !== 'undefined' && module.exports) {
    module.exports = UIButton;
}