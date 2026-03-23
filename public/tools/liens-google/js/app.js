/**
 * Application Principale 2025 - Transformateur Google Links
 * Architecture modulaire avec tous les composants réutilisables
 */

// Import des modules (sera chargé dynamiquement)
class GoogleTransformerApp {
  constructor() {
    this.detectionEngine = null;
    this.transformationEngine = null;
    this.stateManager = null;
    this.historyManager = null;
    this.currentDetection = null;
    this.elements = {};
    this.observers = [];
    
    this.init();
  }

  /**
   * Initialisation de l'application
   */
  async init() {
    try {
      // Initialiser le state manager (PWA)
      this.stateManager = new UIModules.StateManager();
      
      // Initialiser le moteur de détection
      this.detectionEngine = new UIModules.DetectionEngine();
      
      // Initialiser le moteur de transformation
      this.transformationEngine = new UIModules.TransformationEngine();
      
      // Initialiser l'historique
      this.historyManager = new UIModules.HistoryManager();
      
      // Observer les événements
      this.detectionEngine.addObserver((event, data) => {
        this.handleDetectionEvent(event, data);
      });
      
      this.transformationEngine.addObserver((event, data) => {
        this.handleTransformationEvent(event, data);
      });
      
      this.historyManager.addObserver((event, data) => {
        this.handleHistoryEvent(event, data);
      });
      
      // Observer les changements d'état
      this.stateManager.subscribe((newState, oldState, meta) => {
        this.handleStateChange(newState, oldState, meta);
      });
      
      // Initialiser l'interface
      this.initializeUI();
      
      // Bind des événements
      this.bindEvents();
      
      console.log('✅ Application PWA initialisée avec succès');
      
    } catch (error) {
      console.error('❌ Erreur lors de l\'initialisation:', error);
      this.showError('Erreur lors du chargement de l\'application');
    }
  }

  /**
   * Gère les événements du moteur de transformation
   * @param {string} event - Type d'événement
   * @param {*} data - Données de l'événement
   */
  handleTransformationEvent(event, data) {
    switch (event) {
      case 'transformation:applied':
        console.log('🔄 Transformation appliquée:', data.transform.name);
        this.showTransformationResult(data);
        
        // Ajouter à l'historique
        this.addToHistory(data);
        break;
        
      case 'transformation:error':
        console.error('❌ Erreur de transformation:', data.error);
        this.showError(data.error);
        break;
    }
  }

  /**
   * Gère les événements de l'historique
   * @param {string} event - Type d'événement
   * @param {*} data - Données de l'événement
   */
  handleHistoryEvent(event, data) {
    switch (event) {
      case 'item:added':
        console.log('📚 Ajouté à l\'historique:', data.title);
        break;
        
      case 'favorite:toggled':
        console.log('⭐ Favori:', data.favorite ? 'ajouté' : 'retiré');
        break;
    }
  }

  /**
   * Gère les changements d'état global
   * @param {Object} newState - Nouvel état
   * @param {Object} oldState - Ancien état
   * @param {Object} meta - Métadonnées du changement
   */
  handleStateChange(newState, oldState, meta) {
    // Sync l'interface avec l'état
    if (meta.type === 'SET_DETECTING') {
      if (newState.detection.isDetecting) {
        this.showStatus('detecting', 'Analyse du lien en cours...');
      }
    }
  }

  /**
   * Initialise les éléments de l'interface
   */
  initializeUI() {
    this.elements = {
      linkInput: document.getElementById('linkInput'),
      statusIndicator: document.getElementById('statusIndicator'),
      statusText: document.getElementById('statusText'),
      transformationsSection: document.getElementById('transformationsSection'),
      transformsGrid: document.getElementById('transformsGrid'),
      resultsSection: document.getElementById('resultsSection'),
      resultOutput: document.getElementById('resultOutput'),
      copyButton: document.getElementById('copyButton')
    };

    // Vérifier que tous les éléments existent
    const missingElements = Object.entries(this.elements)
      .filter(([key, element]) => !element)
      .map(([key]) => key);

    if (missingElements.length > 0) {
      throw new Error(`Éléments manquants: ${missingElements.join(', ')}`);
    }

    console.log('✅ Interface initialisée');
  }

  /**
   * Bind les événements de l'interface
   */
  bindEvents() {
    // Input de lien avec debouncing
    let inputTimeout;
    this.elements.linkInput.addEventListener('input', (e) => {
      clearTimeout(inputTimeout);
      inputTimeout = setTimeout(() => {
        this.handleLinkInput(e.target.value);
      }, 300);
    });

    // Bouton de copie
    this.elements.copyButton.addEventListener('button:click', (e) => {
      this.handleCopyResult();
    });

    // Reset au focus de l'input
    this.elements.linkInput.addEventListener('focus', () => {
      if (!this.elements.linkInput.value.trim()) {
        this.resetInterface();
      }
    });

    console.log('✅ Événements bindés');
  }

  /**
   * Gère la saisie dans le champ de lien
   * @param {string} url - URL saisie
   */
  handleLinkInput(url) {
    if (!url.trim()) {
      this.resetInterface();
      return;
    }

    // Mettre à jour l'état
    this.stateManager.setDetecting(true);
    
    // Détection avec délai pour l'UX
    setTimeout(() => {
      const detection = this.detectionEngine.detect(url.trim());
      
      if (detection) {
        this.currentDetection = detection;
        
        // Mettre à jour l'état
        this.stateManager.setDetectedPattern(detection.pattern, url.trim());
        
        this.showDetectionSuccess(detection);
        this.displayTransformations(detection);
      } else {
        this.stateManager.setDetecting(false);
        this.showStatus('error', 'Type de lien non reconnu. Vérifiez que c\'est un lien Google valide.');
      }
    }, 100);
  }

  /**
   * Gère les événements du moteur de détection
   * @param {string} event - Type d'événement
   * @param {*} data - Données de l'événement
   */
  handleDetectionEvent(event, data) {
    switch (event) {
      case 'detection:success':
        console.log('🔍 Détection réussie:', data.pattern.name);
        break;
        
      case 'detection:failed':
        console.log('❌ Détection échouée pour:', data.url);
        break;
        
      case 'detection:error':
        console.error('⚠️ Erreur de détection:', data.error);
        break;
    }
  }

  /**
   * Affiche le statut de détection
   * @param {string} type - Type de statut (detecting, success, error)
   * @param {string} message - Message à afficher
   */
  showStatus(type, message) {
    const indicator = this.elements.statusIndicator;
    const text = this.elements.statusText;
    
    indicator.className = `status-indicator show ${type}`;
    text.textContent = message;
  }

  /**
   * Affiche le succès de la détection
   * @param {Object} detection - Résultat de la détection
   */
  showDetectionSuccess(detection) {
    const pattern = detection.pattern;
    const message = `${pattern.icon} ${pattern.name} détecté - ${this.getTransformationCount(pattern.id)} transformations disponibles`;
    
    this.showStatus('success', message);
  }

  /**
   * Affiche les transformations disponibles
   * @param {Object} detection - Résultat de la détection
   */
  displayTransformations(detection) {
    const transforms = this.transformationEngine.getTransformationsForPattern(detection.pattern.id);
    const grid = this.elements.transformsGrid;
    
    // Vider la grille
    grid.innerHTML = '';
    
    if (transforms.length === 0) {
      grid.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: var(--color-text-secondary);">
          Aucune transformation disponible pour ce type de lien.
        </div>
      `;
    } else {
      transforms.forEach(transform => {
        const card = this.createTransformationCard(transform, detection);
        grid.appendChild(card);
      });
    }
    
    // Afficher la section
    this.elements.transformationsSection.classList.add('show');
    
    // Scroll vers les transformations
    setTimeout(() => {
      this.elements.transformationsSection.scrollIntoView({ 
        behavior: 'smooth', 
        block: 'start' 
      });
    }, 100);
  }

  /**
   * Crée une carte de transformation
   * @param {Object} transform - Configuration de transformation
   * @param {Object} detection - Résultat de la détection
   * @returns {HTMLElement} - Élément DOM de la carte
   */
  createTransformationCard(transform, detection) {
    const card = document.createElement('div');
    card.className = 'transform-card';
    card.setAttribute('tabindex', '0');
    card.setAttribute('role', 'button');
    card.setAttribute('aria-label', `Appliquer la transformation: ${transform.name}`);
    
    card.innerHTML = `
      <div class="transform-title">
        <span style="margin-right: 0.5rem;">${transform.icon}</span>
        ${transform.name}
      </div>
      <div class="transform-description">
        ${transform.description}
      </div>
      ${transform.category ? `<div style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--color-primary-600); font-weight: 500;">${transform.category}</div>` : ''}
    `;

    // Événement de clic
    const applyTransformation = () => {
      this.applyTransformation(transform, detection);
    };

    card.addEventListener('click', applyTransformation);
    card.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        applyTransformation();
      }
    });

    return card;
  }

  /**
   * Applique une transformation
   * @param {Object} transform - Configuration de transformation
   * @param {Object} detection - Résultat de la détection
   */
  applyTransformation(transform, detection) {
    // Si la transformation nécessite des inputs, afficher modal/formulaire
    if (transform.requiresInput) {
      this.showInputModal(transform, detection);
      return;
    }

    // Données de base pour la transformation
    const data = {
      FILE_ID: detection.fileId
    };

    // Appliquer la transformation
    const result = this.transformationEngine.applyTransformation(
      detection.pattern.id,
      transform.id,
      data
    );

    if (result.success) {
      this.showTransformationResult(result);
    } else {
      this.showError(result.error);
    }
  }

  /**
   * Affiche une modal pour les inputs requis
   * @param {Object} transform - Configuration de transformation
   * @param {Object} detection - Résultat de la détection
   */
  showInputModal(transform, detection) {
    // TODO: Implémenter modal avec inputs
    // Pour l'instant, utiliser prompt (temporaire)
    const data = { FILE_ID: detection.fileId };
    
    if (transform.inputFields) {
      transform.inputFields.forEach(field => {
        const value = prompt(
          `${field.label}:`,
          field.defaultValue || field.placeholder || ''
        );
        
        if (value !== null) {
          data[field.name] = value;
        }
      });
    }

    // Appliquer avec les données saisies
    const result = this.transformationEngine.applyTransformation(
      detection.pattern.id,
      transform.id,
      data
    );

    if (result.success) {
      this.showTransformationResult(result);
    } else {
      this.showError(result.error);
    }
  }

  /**
   * Affiche le résultat d'une transformation
   * @param {Object} result - Résultat de transformation
   */
  showTransformationResult(result) {
    this.elements.resultOutput.value = result.url;
    this.elements.resultsSection.classList.add('show');
    
    // Scroll vers le résultat
    setTimeout(() => {
      this.elements.resultsSection.scrollIntoView({ 
        behavior: 'smooth', 
        block: 'start' 
      });
    }, 100);
    
    console.log('✅ Transformation appliquée:', result.transform.name, '→', result.url);
  }

  /**
   * Ajoute une transformation à l'historique
   * @param {Object} transformationResult - Résultat de transformation
   */
  addToHistory(transformationResult) {
    if (!this.historyManager || !this.currentDetection) return;

    const historyItem = {
      url: transformationResult.url,
      originalUrl: this.currentDetection.originalUrl,
      title: `${this.currentDetection.pattern.name} - ${transformationResult.transform.name}`,
      type: this.currentDetection.pattern.id,
      pattern: this.currentDetection.pattern.name,
      transformation: transformationResult.transform.name,
      category: transformationResult.transform.category || 'Autres',
      description: transformationResult.transform.description,
      metadata: {
        fileId: this.currentDetection.fileId,
        confidence: this.currentDetection.confidence,
        transformData: transformationResult.data
      }
    };

    this.historyManager.addItem(historyItem);
    
    // Mettre à jour l'état
    this.stateManager.addToLinkHistory(
      transformationResult.url,
      this.currentDetection.pattern,
      transformationResult.transform
    );
  }

  /**
   * Gère la copie du résultat
   */
  async handleCopyResult() {
    const result = this.elements.resultOutput.value;
    
    if (!result.trim()) {
      return;
    }

    try {
      // Méthode moderne avec Clipboard API
      if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(result);
      } else {
        // Fallback pour navigateurs anciens
        this.fallbackCopy(result);
      }
      
      // Feedback visuel
      this.showCopySuccess();
      
    } catch (error) {
      console.warn('Erreur lors de la copie:', error);
      this.fallbackCopy(result);
    }
  }

  /**
   * Méthode de copie fallback
   * @param {string} text - Texte à copier
   */
  fallbackCopy(text) {
    try {
      const textarea = document.createElement('textarea');
      textarea.value = text;
      textarea.style.position = 'fixed';
      textarea.style.left = '-999999px';
      textarea.style.top = '-999999px';
      document.body.appendChild(textarea);
      textarea.focus();
      textarea.select();
      document.execCommand('copy');
      textarea.remove();
      this.showCopySuccess();
    } catch (error) {
      console.error('Erreur lors de la copie fallback:', error);
      this.showError('Impossible de copier le lien');
    }
  }

  /**
   * Affiche le succès de la copie
   */
  showCopySuccess() {
    const button = this.elements.copyButton;
    const originalText = button.textContent;
    
    button.textContent = '✅ Copié !';
    button.setAttribute('variant', 'success');
    
    setTimeout(() => {
      button.textContent = originalText;
      button.setAttribute('variant', 'primary');
    }, 2000);
  }

  /**
   * Obtient le nombre de transformations pour un type
   * @param {string} patternId - ID du pattern
   * @returns {number} - Nombre de transformations
   */
  getTransformationCount(patternId) {
    const transforms = this.transformationEngine.getTransformationsForPattern(patternId);
    return transforms.length;
  }

  /**
   * Affiche une erreur
   * @param {string} message - Message d'erreur
   */
  showError(message) {
    this.showStatus('error', message);
    
    // Auto-hide après 5 secondes
    setTimeout(() => {
      if (this.elements.statusIndicator.classList.contains('error')) {
        this.elements.statusIndicator.classList.remove('show');
      }
    }, 5000);
  }

  /**
   * Remet l'interface à zéro
   */
  resetInterface() {
    this.currentDetection = null;
    
    this.elements.statusIndicator.classList.remove('show');
    this.elements.transformationsSection.classList.remove('show');
    this.elements.resultsSection.classList.remove('show');
    this.elements.transformsGrid.innerHTML = '';
    this.elements.resultOutput.value = '';
  }

  /**
   * Obtient les statistiques de l'application
   * @returns {Object} - Statistiques
   */
  getStats() {
    const detectionStats = this.detectionEngine ? this.detectionEngine.getStats() : null;
    
    return {
      detection: detectionStats,
      transformations: {
        totalTypes: this.transformations.size,
        totalTransforms: Array.from(this.transformations.values())
          .reduce((sum, transforms) => sum + transforms.length, 0)
      },
      ui: {
        currentDetection: this.currentDetection ? this.currentDetection.pattern.name : null,
        elementsInitialized: Object.keys(this.elements).length
      }
    };
  }
}

// Initialisation automatique quand le DOM est prêt
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    window.GoogleTransformerApp = new GoogleTransformerApp();
  });
} else {
  window.GoogleTransformerApp = new GoogleTransformerApp();
}