/**
 * StateManager 2025 - PWA State Management Réutilisable
 * Redux-like pattern sans dépendances, compatible offline
 */

class StateManager {
  constructor(initialState = {}, options = {}) {
    this.state = { ...this.getDefaultState(), ...initialState };
    this.subscribers = new Set();
    this.middleware = [];
    this.history = [];
    this.options = {
      persistKey: 'app_state',
      maxHistory: 50,
      enablePersistence: true,
      enableDevtools: false,
      ...options
    };

    this.init();
  }

  /**
   * État par défaut de l'application
   */
  getDefaultState() {
    return {
      // Interface state
      ui: {
        theme: 'light',
        language: 'fr',
        compactMode: false,
        showHistory: true,
        activeSection: null
      },

      // Detection state  
      detection: {
        currentUrl: '',
        detectedPattern: null,
        lastDetection: null,
        isDetecting: false
      },

      // Transformation state
      transformation: {
        selectedTransform: null,
        inputData: {},
        lastResult: null,
        isProcessing: false
      },

      // History state (selon devis max_history_items: 20)
      history: {
        links: [],
        transformations: [],
        maxItems: 20,
        favorites: []
      },

      // Cache state (PWA performance)
      cache: {
        patterns: new Map(),
        transformations: new Map(),
        lastUpdated: null,
        version: '1.0.0'
      },

      // Notification state
      notifications: {
        queue: [],
        current: null,
        settings: {
          duration: 5000,
          position: 'top-right',
          enableSound: false
        }
      },

      // Offline state (PWA requirement)
      offline: {
        isOnline: navigator.onLine,
        pendingActions: [],
        lastSync: null,
        syncEnabled: true
      }
    };
  }

  /**
   * Initialisation du state manager
   */
  init() {
    // Charger l'état persisté
    if (this.options.enablePersistence) {
      this.loadPersistedState();
    }

    // Observer le statut online/offline
    this.setupOfflineHandlers();

    // DevTools support
    if (this.options.enableDevtools && window.__STATE_DEVTOOLS__) {
      this.setupDevtools();
    }

    // Auto-save périodique
    this.setupAutoSave();

    console.log('✅ StateManager initialisé');
  }

  /**
   * Obtient l'état complet ou une partie
   * @param {string} path - Chemin vers la propriété (ex: 'ui.theme')
   * @returns {*} - État ou propriété
   */
  getState(path = null) {
    if (!path) return { ...this.state };

    return path.split('.').reduce((obj, key) => {
      return obj && obj[key] !== undefined ? obj[key] : undefined;
    }, this.state);
  }

  /**
   * Met à jour l'état avec validation et historique
   * @param {Object|Function} updates - Mises à jour ou fonction reducer
   * @param {Object} meta - Métadonnées de l'action
   */
  setState(updates, meta = {}) {
    const previousState = { ...this.state };
    
    try {
      // Support function updates (comme React)
      if (typeof updates === 'function') {
        updates = updates(this.state);
      }

      // Validation des mises à jour
      this.validateUpdates(updates);

      // Application des middlewares
      const processedUpdates = this.applyMiddleware(updates, previousState, meta);

      // Fusion immutable
      this.state = this.deepMerge(this.state, processedUpdates);

      // Historique des changements
      this.addToHistory({
        action: meta.type || 'UPDATE_STATE',
        previousState,
        newState: { ...this.state },
        updates: processedUpdates,
        timestamp: Date.now(),
        meta
      });

      // Notifications aux abonnés
      this.notifySubscribers(previousState, this.state, meta);

      // Persistance automatique
      if (this.options.enablePersistence) {
        this.persistState();
      }

    } catch (error) {
      console.error('❌ Erreur setState:', error);
      // Restore previous state on error
      this.state = previousState;
      throw error;
    }
  }

  /**
   * Actions spécialisées pour les fonctionnalités métier
   */
  
  // Detection actions
  setDetecting(isDetecting) {
    this.setState({
      detection: { isDetecting }
    }, { type: 'SET_DETECTING' });
  }

  setDetectedPattern(pattern, url) {
    this.setState({
      detection: {
        currentUrl: url,
        detectedPattern: pattern,
        lastDetection: { pattern, url, timestamp: Date.now() },
        isDetecting: false
      }
    }, { type: 'SET_DETECTED_PATTERN' });
  }

  // History actions (selon devis)
  addToLinkHistory(url, pattern, transformation = null) {
    const historyItem = {
      id: Date.now().toString(),
      url,
      pattern: pattern?.name || 'Inconnu',
      transformation: transformation?.name || null,
      timestamp: Date.now(),
      favorite: false
    };

    this.setState(state => ({
      history: {
        ...state.history,
        links: [
          historyItem,
          ...state.history.links.slice(0, state.history.maxItems - 1)
        ]
      }
    }), { type: 'ADD_TO_HISTORY' });
  }

  toggleFavorite(itemId) {
    this.setState(state => ({
      history: {
        ...state.history,
        links: state.history.links.map(item =>
          item.id === itemId ? { ...item, favorite: !item.favorite } : item
        )
      }
    }), { type: 'TOGGLE_FAVORITE' });
  }

  // Notification actions
  addNotification(notification) {
    const id = Date.now().toString();
    const notif = {
      id,
      type: 'info',
      duration: this.state.notifications.settings.duration,
      timestamp: Date.now(),
      ...notification
    };

    this.setState(state => ({
      notifications: {
        ...state.notifications,
        queue: [...state.notifications.queue, notif]
      }
    }), { type: 'ADD_NOTIFICATION' });

    // Auto-remove après duration
    if (notif.duration > 0) {
      setTimeout(() => this.removeNotification(id), notif.duration);
    }
  }

  removeNotification(id) {
    this.setState(state => ({
      notifications: {
        ...state.notifications,
        queue: state.notifications.queue.filter(n => n.id !== id)
      }
    }), { type: 'REMOVE_NOTIFICATION' });
  }

  // Cache actions (PWA performance)
  setCacheData(key, data, expiry = 3600000) { // 1h par défaut
    const cacheItem = {
      data,
      timestamp: Date.now(),
      expiry: Date.now() + expiry
    };

    this.setState(state => ({
      cache: {
        ...state.cache,
        [key]: cacheItem,
        lastUpdated: Date.now()
      }
    }), { type: 'SET_CACHE' });
  }

  getCacheData(key) {
    const item = this.state.cache[key];
    if (!item) return null;
    
    // Vérifier expiration
    if (Date.now() > item.expiry) {
      this.removeCacheData(key);
      return null;
    }
    
    return item.data;
  }

  removeCacheData(key) {
    this.setState(state => {
      const newCache = { ...state.cache };
      delete newCache[key];
      return { cache: newCache };
    }, { type: 'REMOVE_CACHE' });
  }

  // Offline actions (PWA)
  addPendingAction(action) {
    this.setState(state => ({
      offline: {
        ...state.offline,
        pendingActions: [...state.offline.pendingActions, {
          ...action,
          id: Date.now().toString(),
          timestamp: Date.now()
        }]
      }
    }), { type: 'ADD_PENDING_ACTION' });
  }

  removePendingAction(actionId) {
    this.setState(state => ({
      offline: {
        ...state.offline,
        pendingActions: state.offline.pendingActions.filter(a => a.id !== actionId)
      }
    }), { type: 'REMOVE_PENDING_ACTION' });
  }

  /**
   * Abonnements aux changements d'état
   * @param {Function} callback - Fonction appelée à chaque changement
   * @param {string} path - Chemin spécifique à observer (optionnel)
   * @returns {Function} - Fonction de désabonnement
   */
  subscribe(callback, path = null) {
    const subscription = { callback, path };
    this.subscribers.add(subscription);
    
    return () => this.subscribers.delete(subscription);
  }

  /**
   * Notifie tous les abonnés des changements
   */
  notifySubscribers(previousState, newState, meta) {
    this.subscribers.forEach(subscription => {
      try {
        if (subscription.path) {
          // Observer seulement un chemin spécifique
          const oldValue = this.getStateValue(previousState, subscription.path);
          const newValue = this.getStateValue(newState, subscription.path);
          
          if (oldValue !== newValue) {
            subscription.callback(newValue, oldValue, meta);
          }
        } else {
          // Observer tout l'état
          subscription.callback(newState, previousState, meta);
        }
      } catch (error) {
        console.error('❌ Erreur dans subscriber:', error);
      }
    });
  }

  /**
   * Middlewares pour le traitement des actions
   */
  use(middleware) {
    this.middleware.push(middleware);
  }

  applyMiddleware(updates, previousState, meta) {
    return this.middleware.reduce((processedUpdates, middleware) => {
      return middleware(processedUpdates, previousState, this.state, meta);
    }, updates);
  }

  /**
   * Validation des mises à jour
   */
  validateUpdates(updates) {
    if (typeof updates !== 'object' || updates === null) {
      throw new Error('Updates must be an object');
    }
  }

  /**
   * Fusion immutable profonde
   */
  deepMerge(target, source) {
    const result = { ...target };
    
    Object.keys(source).forEach(key => {
      if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
        result[key] = this.deepMerge(target[key] || {}, source[key]);
      } else {
        result[key] = source[key];
      }
    });
    
    return result;
  }

  /**
   * Persistance locale (PWA requirement)
   */
  persistState() {
    try {
      const stateToPersist = {
        ...this.state,
        cache: {} // Exclure le cache de la persistance
      };
      
      localStorage.setItem(this.options.persistKey, JSON.stringify(stateToPersist));
    } catch (error) {
      console.warn('⚠️ Erreur persistance état:', error);
    }
  }

  loadPersistedState() {
    try {
      const persistedState = localStorage.getItem(this.options.persistKey);
      if (persistedState) {
        const parsed = JSON.parse(persistedState);
        this.state = this.deepMerge(this.state, parsed);
      }
    } catch (error) {
      console.warn('⚠️ Erreur chargement état persisté:', error);
    }
  }

  /**
   * Gestion offline (PWA)
   */
  setupOfflineHandlers() {
    window.addEventListener('online', () => {
      this.setState({
        offline: { isOnline: true, lastSync: Date.now() }
      }, { type: 'ONLINE' });

      // Traiter les actions en attente
      this.processPendingActions();
    });

    window.addEventListener('offline', () => {
      this.setState({
        offline: { isOnline: false }
      }, { type: 'OFFLINE' });
    });
  }

  processPendingActions() {
    const pendingActions = this.getState('offline.pendingActions');
    
    pendingActions.forEach(action => {
      // Traiter l'action différée
      console.log('🔄 Traitement action différée:', action);
      this.removePendingAction(action.id);
    });
  }

  /**
   * Auto-save périodique
   */
  setupAutoSave() {
    if (this.options.enablePersistence) {
      setInterval(() => {
        this.persistState();
      }, 30000); // 30 secondes
    }
  }

  /**
   * DevTools support
   */
  setupDevtools() {
    window.__STATE_DEVTOOLS__.init(this);
  }

  /**
   * Historique des actions
   */
  addToHistory(entry) {
    this.history.push(entry);
    
    if (this.history.length > this.options.maxHistory) {
      this.history.shift();
    }
  }

  getHistory() {
    return [...this.history];
  }

  /**
   * Helper pour obtenir une valeur d'état par chemin
   */
  getStateValue(state, path) {
    return path.split('.').reduce((obj, key) => obj && obj[key], state);
  }

  /**
   * Reset complet de l'état
   */
  resetState() {
    this.state = this.getDefaultState();
    this.history = [];
    
    if (this.options.enablePersistence) {
      localStorage.removeItem(this.options.persistKey);
    }
    
    this.notifySubscribers({}, this.state, { type: 'RESET_STATE' });
  }

  /**
   * Export/Import pour sauvegarde
   */
  exportState() {
    return JSON.stringify(this.state, null, 2);
  }

  importState(stateJson) {
    try {
      const importedState = JSON.parse(stateJson);
      this.setState(importedState, { type: 'IMPORT_STATE' });
    } catch (error) {
      console.error('❌ Erreur import état:', error);
      throw error;
    }
  }

  /**
   * Statistiques et debugging
   */
  getStats() {
    return {
      stateSize: JSON.stringify(this.state).length,
      subscribers: this.subscribers.size,
      historyLength: this.history.length,
      cacheItems: Object.keys(this.state.cache).length,
      pendingActions: this.state.offline.pendingActions.length,
      lastPersist: this.state.cache.lastUpdated
    };
  }
}

// Export pour usage modulaire
if (typeof module !== 'undefined' && module.exports) {
  module.exports = StateManager;
}

// Namespace global
if (typeof window !== 'undefined') {
  window.UIModules = window.UIModules || {};
  window.UIModules.StateManager = StateManager;
}