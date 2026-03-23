/**
 * Module Input - Web Component Réutilisable
 * Tendances 2025 : Web Components natifs, validation intégrée
 * Utilisé pour : URL principale, paramètres GID, dimensions, config
 */

class UIInput extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
        this._value = '';
        this._isValid = true;
    }

    static get observedAttributes() {
        return ['type', 'placeholder', 'value', 'size', 'variant', 'label', 'error', 'required', 'pattern'];
    }

    connectedCallback() {
        this.render();
        this.setupEventListeners();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (oldValue !== newValue) {
            this.render();
            if (name === 'value') {
                this._value = newValue;
                this.validate();
            }
        }
    }

    render() {
        const type = this.getAttribute('type') || 'text';
        const placeholder = this.getAttribute('placeholder') || '';
        const value = this.getAttribute('value') || '';
        const size = this.getAttribute('size') || 'medium';
        const variant = this.getAttribute('variant') || 'default';
        const label = this.getAttribute('label') || '';
        const error = this.getAttribute('error') || '';
        const required = this.hasAttribute('required');
        const pattern = this.getAttribute('pattern') || '';

        const sizes = {
            small: { height: '36px', padding: '8px 12px', fontSize: '14px' },
            medium: { height: '44px', padding: '10px 16px', fontSize: '16px' },
            large: { height: '52px', padding: '12px 20px', fontSize: '18px' }
        };

        const currentSize = sizes[size] || sizes.medium;

        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    display: block;
                    width: 100%;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
                }

                .input-wrapper {
                    position: relative;
                    width: 100%;
                }

                .label {
                    display: block;
                    margin-bottom: 6px;
                    font-size: 14px;
                    font-weight: 500;
                    color: #374151;
                }

                .label.required::after {
                    content: ' *';
                    color: #ef4444;
                }

                .input {
                    width: 100%;
                    height: ${currentSize.height};
                    padding: ${currentSize.padding};
                    font-size: ${currentSize.fontSize};
                    font-family: inherit;
                    background: #ffffff;
                    border: 2px solid #e5e7eb;
                    border-radius: 8px;
                    color: #374151;
                    transition: all 0.2s ease;
                    outline: none;
                    box-sizing: border-box;
                }

                .input:focus {
                    border-color: #0ea5e9;
                    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
                }

                .input::placeholder {
                    color: #9ca3af;
                }

                .input.error {
                    border-color: #ef4444;
                }

                .input.error:focus {
                    border-color: #ef4444;
                    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
                }

                .input.success {
                    border-color: #10b981;
                }

                .input.success:focus {
                    border-color: #10b981;
                    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
                }

                .input[type="url"] {
                    font-family: 'Courier New', monospace;
                }

                .error-message {
                    margin-top: 4px;
                    font-size: 12px;
                    color: #ef4444;
                    display: none;
                }

                .error-message.show {
                    display: block;
                }

                .icon-wrapper {
                    position: absolute;
                    right: 12px;
                    top: 50%;
                    transform: translateY(-50%);
                    pointer-events: none;
                }

                .icon {
                    font-size: 18px;
                }

                .icon.success { color: #10b981; }
                .icon.error { color: #ef4444; }
                .icon.loading { 
                    color: #f59e0b;
                    animation: spin 1s linear infinite;
                }

                @keyframes spin {
                    to { transform: rotate(360deg); }
                }

                /* Variant URL */
                :host([variant="url"]) .input {
                    background: #f9fafb;
                    font-family: 'SF Mono', Monaco, 'Courier New', monospace;
                }

                /* Variant Search */
                :host([variant="search"]) .input {
                    padding-left: 40px;
                }

                :host([variant="search"]) .search-icon {
                    position: absolute;
                    left: 12px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: #9ca3af;
                    font-size: 18px;
                }

                @media (max-width: 640px) {
                    .input {
                        font-size: 16px; /* Prevent zoom on iOS */
                    }
                }
            </style>
            <div class="input-wrapper">
                ${label ? `<label class="label ${required ? 'required' : ''}">${label}</label>` : ''}
                ${variant === 'search' ? '<span class="search-icon">🔍</span>' : ''}
                <input 
                    class="input ${error ? 'error' : ''}"
                    type="${type}"
                    placeholder="${placeholder}"
                    value="${value}"
                    ${required ? 'required' : ''}
                    ${pattern ? `pattern="${pattern}"` : ''}
                />
                <div class="icon-wrapper">
                    <span class="icon" style="display: none;"></span>
                </div>
                ${error ? `<div class="error-message show">${error}</div>` : '<div class="error-message"></div>'}
            </div>
        `;
    }

    setupEventListeners() {
        const input = this.shadowRoot.querySelector('.input');
        
        // Input event avec debouncing
        let timeout;
        input.addEventListener('input', (e) => {
            this._value = e.target.value;
            clearTimeout(timeout);
            
            // Dispatch immédiat pour feedback
            this.dispatchEvent(new CustomEvent('input', {
                detail: { value: this._value },
                bubbles: true
            }));
            
            // Validation avec debouncing
            timeout = setTimeout(() => {
                this.validate();
                this.dispatchEvent(new CustomEvent('change', {
                    detail: { value: this._value, isValid: this._isValid },
                    bubbles: true
                }));
            }, 300);
        });

        // Focus/Blur events
        input.addEventListener('focus', () => {
            this.dispatchEvent(new CustomEvent('focus', { bubbles: true }));
        });

        input.addEventListener('blur', () => {
            this.validate();
            this.dispatchEvent(new CustomEvent('blur', {
                detail: { value: this._value, isValid: this._isValid },
                bubbles: true
            }));
        });

        // Enter key
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.dispatchEvent(new CustomEvent('enter', {
                    detail: { value: this._value, isValid: this._isValid },
                    bubbles: true
                }));
            }
        });
    }

    validate() {
        const input = this.shadowRoot.querySelector('.input');
        const errorMessage = this.shadowRoot.querySelector('.error-message');
        const icon = this.shadowRoot.querySelector('.icon');
        const type = this.getAttribute('type');
        const required = this.hasAttribute('required');
        const pattern = this.getAttribute('pattern');

        let isValid = true;
        let error = '';

        // Validation required
        if (required && !this._value.trim()) {
            isValid = false;
            error = 'Ce champ est requis';
        }
        // Validation URL
        else if (type === 'url' && this._value) {
            try {
                new URL(this._value);
                if (!this._value.includes('google')) {
                    isValid = false;
                    error = 'Veuillez entrer un lien Google';
                }
            } catch {
                isValid = false;
                error = 'URL invalide';
            }
        }
        // Validation pattern
        else if (pattern && this._value) {
            const regex = new RegExp(pattern);
            if (!regex.test(this._value)) {
                isValid = false;
                error = 'Format invalide';
            }
        }

        // Update UI
        this._isValid = isValid;
        input.classList.toggle('error', !isValid && this._value);
        input.classList.toggle('success', isValid && this._value);
        
        errorMessage.textContent = error;
        errorMessage.classList.toggle('show', !!error);

        // Update icon
        icon.style.display = this._value ? 'block' : 'none';
        icon.className = 'icon';
        if (this._value) {
            icon.classList.add(isValid ? 'success' : 'error');
            icon.textContent = isValid ? '✓' : '✗';
        }

        return isValid;
    }

    // Public API
    get value() {
        return this._value;
    }

    set value(val) {
        this.setAttribute('value', val);
    }

    get isValid() {
        return this._isValid;
    }

    clear() {
        this.value = '';
        this.validate();
    }

    focus() {
        this.shadowRoot.querySelector('.input').focus();
    }

    showLoading() {
        const icon = this.shadowRoot.querySelector('.icon');
        icon.style.display = 'block';
        icon.className = 'icon loading';
        icon.textContent = '⏳';
    }

    hideLoading() {
        this.validate();
    }
}

// Enregistrer le composant
customElements.define('ui-input', UIInput);

// Export pour utilisation modulaire
if (typeof module !== 'undefined' && module.exports) {
    module.exports = UIInput;
}