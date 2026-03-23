/**
 * HistoryManager 2025 - Gestion historique réutilisable
 * Selon devis : section history + max_history_items: 20
 */

class HistoryManager {
  constructor(options = {}) {
    this.options = {
      maxItems: 20, // Selon devis
      persistKey: 'app_history',
      enablePersistence: true,
      enableFavorites: true,
      enableSearch: true,
      enableCategories: true,
      ...options
    };

    this.history = [];
    this.favorites = [];
    this.observers = [];
    this.searchIndex = new Map();

    this.init();
  }

  /**
   * Initialisation du gestionnaire d'historique
   */
  init() {
    // Charger l'historique persisté
    if (this.options.enablePersistence) {
      this.loadPersistedHistory();
    }

    // Créer l'index de recherche
    if (this.options.enableSearch) {
      this.buildSearchIndex();
    }

    console.log('✅ HistoryManager initialisé');
  }

  /**
   * Ajoute un élément à l'historique
   * @param {Object} item - Élément à ajouter
   * @returns {string} - ID de l'élément ajouté
   */
  addItem(item) {
    // Validation de l'élément
    const validatedItem = this.validateAndNormalizeItem(item);
    
    // Générer ID unique
    validatedItem.id = this.generateId();
    validatedItem.timestamp = Date.now();
    validatedItem.favorite = false;

    // Éviter les doublons (même URL dans les 5 derniers)
    const isDuplicate = this.history.slice(0, 5).some(
      existingItem => existingItem.url === validatedItem.url
    );

    if (!isDuplicate) {
      // Ajouter au début de l'historique
      this.history.unshift(validatedItem);
      
      // Maintenir la limite maximale (devis : 20 items)
      if (this.history.length > this.options.maxItems) {
        const removed = this.history.splice(this.options.maxItems);
        // Nettoyer les favoris supprimés
        removed.forEach(removedItem => {
          if (removedItem.favorite) {
            this.removeFromFavorites(removedItem.id);
          }
        });
      }

      // Mettre à jour l'index de recherche
      if (this.options.enableSearch) {
        this.addToSearchIndex(validatedItem);
      }

      // Persister les changements
      if (this.options.enablePersistence) {
        this.persistHistory();
      }

      // Notifier les observateurs
      this.notifyObservers('item:added', validatedItem);
    }

    return validatedItem.id;
  }

  /**
   * Valide et normalise un élément d'historique
   * @param {Object} item - Élément à valider
   * @returns {Object} - Élément validé
   */
  validateAndNormalizeItem(item) {
    if (!item || typeof item !== 'object') {
      throw new Error('L\'élément d\'historique doit être un objet');
    }

    if (!item.url || typeof item.url !== 'string') {
      throw new Error('L\'URL est requise');
    }

    // Normaliser l'URL
    const normalizedUrl = this.normalizeUrl(item.url);

    return {
      url: normalizedUrl,
      originalUrl: item.url,
      title: item.title || this.extractTitle(normalizedUrl),
      type: item.type || 'unknown',
      pattern: item.pattern || null,
      transformation: item.transformation || null,
      category: this.categorizeItem(item),
      metadata: item.metadata || {},
      tags: item.tags || [],
      description: item.description || '',
      ...item // Permet d'autres propriétés personnalisées
    };
  }

  /**
   * Extrait un titre depuis une URL
   * @param {string} url - URL à analyser
   * @returns {string} - Titre extrait
   */
  extractTitle(url) {
    try {
      const urlObj = new URL(url);
      
      // Patterns spécifiques Google
      if (urlObj.hostname.includes('docs.google.com')) {
        if (url.includes('/document/')) return 'Google Docs';
        if (url.includes('/spreadsheets/')) return 'Google Sheets';
        if (url.includes('/presentation/')) return 'Google Slides';
        if (url.includes('/forms/')) return 'Google Forms';
      }
      
      if (urlObj.hostname.includes('youtube.com') || urlObj.hostname.includes('youtu.be')) {
        return 'YouTube Video';
      }
      
      if (urlObj.hostname.includes('drive.google.com')) {
        return 'Google Drive';
      }

      // Titre générique depuis l'hostname
      return urlObj.hostname.replace('www.', '').split('.')[0];
      
    } catch {
      return 'Lien';
    }
  }

  /**
   * Catégorise un élément selon le type
   * @param {Object} item - Élément à catégoriser
   * @returns {string} - Catégorie
   */
  categorizeItem(item) {
    if (item.category) return item.category;

    const url = item.url.toLowerCase();
    
    if (url.includes('docs.google.com')) {
      if (url.includes('/document/')) return 'Documents';
      if (url.includes('/spreadsheets/')) return 'Tableurs';
      if (url.includes('/presentation/')) return 'Présentations';
      if (url.includes('/forms/')) return 'Formulaires';
    }
    
    if (url.includes('youtube.com') || url.includes('youtu.be')) {
      return 'Vidéos';
    }
    
    if (url.includes('drive.google.com')) {
      return 'Stockage';
    }

    return 'Autres';
  }

  /**
   * Normalise une URL pour éviter les doublons
   * @param {string} url - URL à normaliser
   * @returns {string} - URL normalisée
   */
  normalizeUrl(url) {
    try {
      const urlObj = new URL(url);
      
      // Supprimer les paramètres de tracking
      const trackingParams = ['utm_source', 'utm_medium', 'utm_campaign', 'fbclid', 'gclid', 'usp'];
      trackingParams.forEach(param => {
        urlObj.searchParams.delete(param);
      });

      // Normaliser les liens Google
      if (urlObj.hostname.includes('docs.google.com')) {
        // Supprimer /edit, /view à la fin
        urlObj.pathname = urlObj.pathname.replace(/\/(edit|view)$/, '');
      }

      return urlObj.toString();
    } catch {
      return url;
    }
  }

  /**
   * Génère un ID unique pour un élément
   * @returns {string} - ID unique
   */
  generateId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
  }

  /**
   * Obtient tous les éléments d'historique
   * @param {Object} filters - Filtres de recherche
   * @returns {Array} - Liste des éléments
   */
  getItems(filters = {}) {
    let items = [...this.history];

    // Filtrer par catégorie
    if (filters.category) {
      items = items.filter(item => item.category === filters.category);
    }

    // Filtrer par type
    if (filters.type) {
      items = items.filter(item => item.type === filters.type);
    }

    // Filtrer par favoris
    if (filters.favorites) {
      items = items.filter(item => item.favorite);
    }

    // Recherche textuelle
    if (filters.search) {
      items = this.searchItems(filters.search);
    }

    // Trier
    if (filters.sortBy) {
      items = this.sortItems(items, filters.sortBy, filters.sortOrder);
    }

    // Limiter le nombre de résultats
    if (filters.limit) {
      items = items.slice(0, filters.limit);
    }

    return items;
  }

  /**
   * Recherche d'éléments par texte
   * @param {string} query - Requête de recherche
   * @returns {Array} - Éléments trouvés
   */
  searchItems(query) {
    if (!query || !this.options.enableSearch) return this.history;

    const searchTerms = query.toLowerCase().split(/\s+/);
    
    return this.history.filter(item => {
      const searchableText = [
        item.title,
        item.url,
        item.description,
        item.category,
        item.type,
        ...(item.tags || [])
      ].join(' ').toLowerCase();

      return searchTerms.every(term => searchableText.includes(term));
    });
  }

  /**
   * Trie les éléments selon un critère
   * @param {Array} items - Éléments à trier
   * @param {string} sortBy - Critère de tri
   * @param {string} sortOrder - Ordre (asc/desc)
   * @returns {Array} - Éléments triés
   */
  sortItems(items, sortBy, sortOrder = 'desc') {
    return items.sort((a, b) => {
      let comparison = 0;

      switch (sortBy) {
        case 'timestamp':
          comparison = a.timestamp - b.timestamp;
          break;
        case 'title':
          comparison = a.title.localeCompare(b.title);
          break;
        case 'category':
          comparison = a.category.localeCompare(b.category);
          break;
        case 'favorite':
          comparison = (a.favorite ? 1 : 0) - (b.favorite ? 1 : 0);
          break;
        default:
          comparison = a.timestamp - b.timestamp;
      }

      return sortOrder === 'asc' ? comparison : -comparison;
    });
  }

  /**
   * Obtient un élément par son ID
   * @param {string} id - ID de l'élément
   * @returns {Object|null} - Élément trouvé
   */
  getItem(id) {
    return this.history.find(item => item.id === id) || null;
  }

  /**
   * Met à jour un élément d'historique
   * @param {string} id - ID de l'élément
   * @param {Object} updates - Mises à jour
   * @returns {boolean} - Succès de la mise à jour
   */
  updateItem(id, updates) {
    const itemIndex = this.history.findIndex(item => item.id === id);
    
    if (itemIndex === -1) {
      return false;
    }

    // Appliquer les mises à jour
    this.history[itemIndex] = { 
      ...this.history[itemIndex], 
      ...updates,
      updatedAt: Date.now()
    };

    // Mettre à jour l'index de recherche
    if (this.options.enableSearch) {
      this.updateSearchIndex(this.history[itemIndex]);
    }

    // Persister
    if (this.options.enablePersistence) {
      this.persistHistory();
    }

    // Notifier
    this.notifyObservers('item:updated', this.history[itemIndex]);

    return true;
  }

  /**
   * Supprime un élément d'historique
   * @param {string} id - ID de l'élément
   * @returns {boolean} - Succès de la suppression
   */
  removeItem(id) {
    const itemIndex = this.history.findIndex(item => item.id === id);
    
    if (itemIndex === -1) {
      return false;
    }

    const removedItem = this.history.splice(itemIndex, 1)[0];

    // Retirer des favoris si nécessaire
    if (removedItem.favorite) {
      this.removeFromFavorites(id);
    }

    // Mettre à jour l'index de recherche
    if (this.options.enableSearch) {
      this.removeFromSearchIndex(id);
    }

    // Persister
    if (this.options.enablePersistence) {
      this.persistHistory();
    }

    // Notifier
    this.notifyObservers('item:removed', removedItem);

    return true;
  }

  /**
   * Toggle le statut favori d'un élément
   * @param {string} id - ID de l'élément
   * @returns {boolean} - Nouveau statut favori
   */
  toggleFavorite(id) {
    const item = this.getItem(id);
    if (!item) return false;

    const newFavoriteStatus = !item.favorite;
    
    this.updateItem(id, { favorite: newFavoriteStatus });

    if (newFavoriteStatus) {
      this.addToFavorites(item);
    } else {
      this.removeFromFavorites(id);
    }

    this.notifyObservers('favorite:toggled', { id, favorite: newFavoriteStatus });

    return newFavoriteStatus;
  }

  /**
   * Gestion des favoris
   */
  addToFavorites(item) {
    if (!this.favorites.find(fav => fav.id === item.id)) {
      this.favorites.push({ ...item });
      this.notifyObservers('favorite:added', item);
    }
  }

  removeFromFavorites(id) {
    const index = this.favorites.findIndex(fav => fav.id === id);
    if (index > -1) {
      const removed = this.favorites.splice(index, 1)[0];
      this.notifyObservers('favorite:removed', removed);
    }
  }

  getFavorites() {
    return [...this.favorites];
  }

  /**
   * Obtient les statistiques d'historique
   * @returns {Object} - Statistiques
   */
  getStats() {
    const categories = {};
    const types = {};

    this.history.forEach(item => {
      categories[item.category] = (categories[item.category] || 0) + 1;
      types[item.type] = (types[item.type] || 0) + 1;
    });

    return {
      totalItems: this.history.length,
      favorites: this.favorites.length,
      categories,
      types,
      oldestItem: this.history.length > 0 ? this.history[this.history.length - 1].timestamp : null,
      newestItem: this.history.length > 0 ? this.history[0].timestamp : null
    };
  }

  /**
   * Obtient les catégories disponibles
   * @returns {Array} - Liste des catégories avec compteurs
   */
  getCategories() {
    const categories = {};
    
    this.history.forEach(item => {
      if (!categories[item.category]) {
        categories[item.category] = { count: 0, items: [] };
      }
      categories[item.category].count++;
      categories[item.category].items.push(item.id);
    });

    return Object.entries(categories).map(([name, data]) => ({
      name,
      count: data.count,
      items: data.items
    }));
  }

  /**
   * Vide l'historique (avec confirmation)
   * @param {boolean} keepFavorites - Conserver les favoris
   * @returns {number} - Nombre d'éléments supprimés
   */
  clearHistory(keepFavorites = true) {
    const countBefore = this.history.length;

    if (keepFavorites) {
      this.history = this.history.filter(item => item.favorite);
    } else {
      this.history = [];
      this.favorites = [];
    }

    // Reconstruire l'index de recherche
    if (this.options.enableSearch) {
      this.buildSearchIndex();
    }

    // Persister
    if (this.options.enablePersistence) {
      this.persistHistory();
    }

    const removedCount = countBefore - this.history.length;
    this.notifyObservers('history:cleared', { removedCount, keepFavorites });

    return removedCount;
  }

  /**
   * Index de recherche
   */
  buildSearchIndex() {
    this.searchIndex.clear();
    this.history.forEach(item => {
      this.addToSearchIndex(item);
    });
  }

  addToSearchIndex(item) {
    // Construire l'index de mots-clés
    const keywords = [
      item.title,
      item.description,
      item.category,
      item.type,
      ...(item.tags || [])
    ].join(' ').toLowerCase().split(/\s+/);

    keywords.forEach(keyword => {
      if (keyword.length > 2) {
        if (!this.searchIndex.has(keyword)) {
          this.searchIndex.set(keyword, new Set());
        }
        this.searchIndex.get(keyword).add(item.id);
      }
    });
  }

  updateSearchIndex(item) {
    // Simplement reconstruire - optimisation possible
    this.buildSearchIndex();
  }

  removeFromSearchIndex(itemId) {
    this.searchIndex.forEach((itemIds, keyword) => {
      itemIds.delete(itemId);
      if (itemIds.size === 0) {
        this.searchIndex.delete(keyword);
      }
    });
  }

  /**
   * Persistance
   */
  persistHistory() {
    if (!this.options.enablePersistence) return;

    try {
      const data = {
        history: this.history,
        favorites: this.favorites,
        version: '1.0.0',
        timestamp: Date.now()
      };

      localStorage.setItem(this.options.persistKey, JSON.stringify(data));
    } catch (error) {
      console.warn('⚠️ Erreur persistance historique:', error);
    }
  }

  loadPersistedHistory() {
    try {
      const data = localStorage.getItem(this.options.persistKey);
      if (data) {
        const parsed = JSON.parse(data);
        this.history = parsed.history || [];
        this.favorites = parsed.favorites || [];
      }
    } catch (error) {
      console.warn('⚠️ Erreur chargement historique persisté:', error);
    }
  }

  /**
   * Export/Import
   */
  exportHistory() {
    return JSON.stringify({
      history: this.history,
      favorites: this.favorites,
      exportDate: new Date().toISOString(),
      version: '1.0.0'
    }, null, 2);
  }

  importHistory(dataJson) {
    try {
      const data = JSON.parse(dataJson);
      
      if (data.history) {
        this.history = data.history;
      }
      
      if (data.favorites) {
        this.favorites = data.favorites;
      }

      // Reconstruire l'index
      if (this.options.enableSearch) {
        this.buildSearchIndex();
      }

      // Persister
      if (this.options.enablePersistence) {
        this.persistHistory();
      }

      this.notifyObservers('history:imported', data);
      
    } catch (error) {
      console.error('❌ Erreur import historique:', error);
      throw error;
    }
  }

  /**
   * Observateurs
   */
  addObserver(callback) {
    this.observers.push(callback);
  }

  removeObserver(callback) {
    const index = this.observers.indexOf(callback);
    if (index > -1) {
      this.observers.splice(index, 1);
    }
  }

  notifyObservers(event, data) {
    this.observers.forEach(callback => {
      try {
        callback(event, data);
      } catch (error) {
        console.warn('⚠️ Erreur dans observer HistoryManager:', error);
      }
    });
  }
}

// Export pour usage modulaire
if (typeof module !== 'undefined' && module.exports) {
  module.exports = HistoryManager;
}

// Namespace global
if (typeof window !== 'undefined') {
  window.UIModules = window.UIModules || {};
  window.UIModules.HistoryManager = HistoryManager;
}