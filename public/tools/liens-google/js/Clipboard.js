/**
 * Gestionnaire de presse-papier moderne
 * Avec fallback pour navigateurs anciens
 */

class ClipboardManager {
    constructor() {
        this.isSupported = this.checkSupport();
        this.lastCopied = '';
    }

    checkSupport() {
        return {
            modern: typeof navigator.clipboard !== 'undefined' && 
                    typeof navigator.clipboard.writeText === 'function',
            legacy: document.queryCommandSupported && 
                    document.queryCommandSupported('copy')
        };
    }

    async copy(text, options = {}) {
        const { showNotification = false, notificationMessage = 'Copié !' } = options;
        
        try {
            // Essayer l'API moderne
            if (this.isSupported.modern) {
                await navigator.clipboard.writeText(text);
            } else {
                // Fallback sur l'ancienne méthode
                await this.legacyCopy(text);
            }
            
            this.lastCopied = text;
            
            if (showNotification) {
                this.showNotification(notificationMessage);
            }
            
            return true;
        } catch (error) {
            console.error('Erreur lors de la copie:', error);
            
            if (showNotification) {
                this.showNotification('Erreur lors de la copie', 'error');
            }
            
            return false;
        }
    }

    async legacyCopy(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.top = '-9999px';
        textarea.style.left = '-9999px';
        textarea.setAttribute('readonly', '');
        
        document.body.appendChild(textarea);
        
        // Support iOS
        if (navigator.userAgent.match(/ipad|iphone/i)) {
            const range = document.createRange();
            range.selectNodeContents(textarea);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
            textarea.setSelectionRange(0, 999999);
        } else {
            textarea.select();
        }
        
        const success = document.execCommand('copy');
        document.body.removeChild(textarea);
        
        if (!success) {
            throw new Error('Commande copy échouée');
        }
    }

    async copyHTML(html, plainText) {
        if (!this.isSupported.modern || !navigator.clipboard.write) {
            // Fallback sur copie texte simple
            return this.copy(plainText);
        }

        try {
            const blob = new Blob([html], { type: 'text/html' });
            const textBlob = new Blob([plainText], { type: 'text/plain' });
            
            const data = [
                new ClipboardItem({
                    'text/html': blob,
                    'text/plain': textBlob
                })
            ];
            
            await navigator.clipboard.write(data);
            this.lastCopied = plainText;
            return true;
        } catch (error) {
            console.warn('Fallback sur copie texte:', error);
            return this.copy(plainText);
        }
    }

    async read() {
        if (!this.isSupported.modern) {
            throw new Error('Lecture du presse-papier non supportée');
        }
        
        try {
            const text = await navigator.clipboard.readText();
            return text;
        } catch (error) {
            console.error('Erreur lecture presse-papier:', error);
            throw error;
        }
    }

    showNotification(message, type = 'success') {
        // Créer une notification temporaire
        const notif = document.createElement('div');
        notif.className = `clipboard-notification ${type}`;
        notif.textContent = message;
        notif.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 20px;
            background: ${type === 'success' ? '#10b981' : '#ef4444'};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 9999;
            animation: slideIn 0.3s ease;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 14px;
            font-weight: 500;
        `;
        
        // Ajouter l'animation CSS
        if (!document.querySelector('#clipboard-animations')) {
            const style = document.createElement('style');
            style.id = 'clipboard-animations';
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateY(100%); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateY(0); opacity: 1; }
                    to { transform: translateY(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }
        
        document.body.appendChild(notif);
        
        // Auto-suppression
        setTimeout(() => {
            notif.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notif.remove(), 300);
        }, 2000);
    }

    attachToButton(button, getText) {
        button.addEventListener('click', async (e) => {
            e.preventDefault();
            
            const text = typeof getText === 'function' ? getText() : getText;
            if (!text) return;
            
            const success = await this.copy(text);
            
            if (success) {
                // Feedback visuel sur le bouton
                const originalText = button.textContent;
                button.textContent = '✓ Copié !';
                button.disabled = true;
                
                setTimeout(() => {
                    button.textContent = originalText;
                    button.disabled = false;
                }, 2000);
            }
        });
    }

    copyWithFeedback(text, element) {
        return new Promise(async (resolve) => {
            const originalContent = element.innerHTML;
            const originalClass = element.className;
            
            const success = await this.copy(text);
            
            if (success) {
                element.innerHTML = '✓ Copié !';
                element.className += ' copy-success';
                
                setTimeout(() => {
                    element.innerHTML = originalContent;
                    element.className = originalClass;
                }, 2000);
            }
            
            resolve(success);
        });
    }

    copyMultiple(items, separator = '\n') {
        const text = items.join(separator);
        return this.copy(text);
    }

    copyJSON(data, formatted = true) {
        const text = formatted 
            ? JSON.stringify(data, null, 2)
            : JSON.stringify(data);
        return this.copy(text);
    }

    copyCode(code, language = 'javascript') {
        const formatted = `\`\`\`${language}\n${code}\n\`\`\``;
        return this.copy(formatted);
    }

    getLastCopied() {
        return this.lastCopied;
    }

    clear() {
        this.lastCopied = '';
    }
}

// Instance singleton
const clipboardManager = new ClipboardManager();

// Export pour utilisation
if (typeof module !== 'undefined' && module.exports) {
    module.exports = clipboardManager;
}