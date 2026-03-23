/**
 * Storage.js - Gestion de la persistance avec IndexedDB
 * 100% client-side - pas de dépendance serveur
 * Compatible iframe
 */

const Storage = {
    // Configuration IndexedDB
    dbName: 'OscilloscopeRLC',
    dbVersion: 1,
    db: null,

    // ========== INITIALISATION INDEXEDDB ==========

    /**
     * Initialiser la base de données IndexedDB
     */
    async init() {
        if (this.db) return this.db;

        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion);

            request.onerror = () => {
                console.error('Erreur ouverture IndexedDB:', request.error);
                reject(request.error);
            };

            request.onsuccess = () => {
                this.db = request.result;
                console.log('IndexedDB initialisé');

                // Migrer les données localStorage existantes
                this.migrateFromLocalStorage();

                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Store circuits
                if (!db.objectStoreNames.contains('circuits')) {
                    const circuitsStore = db.createObjectStore('circuits', { keyPath: 'id', autoIncrement: true });
                    circuitsStore.createIndex('name', 'name', { unique: false });
                    circuitsStore.createIndex('circuit_type', 'circuit_type', { unique: false });
                    circuitsStore.createIndex('created_at', 'created_at', { unique: false });
                }

                // Store settings
                if (!db.objectStoreNames.contains('settings')) {
                    db.createObjectStore('settings', { keyPath: 'key' });
                }

                // Store presets (circuits par défaut)
                if (!db.objectStoreNames.contains('presets')) {
                    const presetsStore = db.createObjectStore('presets', { keyPath: 'id', autoIncrement: true });
                    presetsStore.createIndex('category', 'category', { unique: false });
                }

                console.log('IndexedDB schema créé');
            };
        });
    },

    /**
     * Migrer les données existantes de localStorage vers IndexedDB
     */
    async migrateFromLocalStorage() {
        try {
            // Vérifier si migration déjà faite
            const migrated = localStorage.getItem('oscilloscope_migrated_to_idb');
            if (migrated) return;

            // Migrer le thème
            const theme = localStorage.getItem('oscilloscope_theme');
            if (theme) {
                await this.setSetting('theme', JSON.parse(theme));
            }

            // Migrer la config grille
            const gridConfig = localStorage.getItem('oscilloscope_gridConfig');
            if (gridConfig) {
                await this.setSetting('gridConfig', JSON.parse(gridConfig));
            }

            // Migrer la config oscilloscope
            const oscConfig = localStorage.getItem('oscilloscope_oscilloscopeConfig');
            if (oscConfig) {
                await this.setSetting('oscilloscopeConfig', JSON.parse(oscConfig));
            }

            // Migrer la dernière config
            const lastConfig = localStorage.getItem('oscilloscope_lastConfig');
            if (lastConfig) {
                await this.setSetting('lastConfig', JSON.parse(lastConfig));
            }

            // Marquer comme migré
            localStorage.setItem('oscilloscope_migrated_to_idb', 'true');
            console.log('Migration localStorage -> IndexedDB terminée');
        } catch (e) {
            console.warn('Erreur migration localStorage:', e);
        }
    },

    /**
     * S'assurer que la DB est prête
     */
    async ensureDB() {
        if (!this.db) {
            await this.init();
        }
        return this.db;
    },

    // ========== CIRCUITS ==========

    /**
     * Sauvegarder un circuit
     */
    async saveCircuit(circuitData) {
        await this.ensureDB();

        const circuit = {
            ...circuitData,
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString()
        };

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['circuits'], 'readwrite');
            const store = transaction.objectStore('circuits');
            const request = store.add(circuit);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    },

    /**
     * Mettre à jour un circuit
     */
    async updateCircuit(id, circuitData) {
        await this.ensureDB();

        const circuit = {
            ...circuitData,
            id: parseInt(id),
            updated_at: new Date().toISOString()
        };

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['circuits'], 'readwrite');
            const store = transaction.objectStore('circuits');
            const request = store.put(circuit);

            request.onsuccess = () => resolve(true);
            request.onerror = () => reject(request.error);
        });
    },

    /**
     * Récupérer tous les circuits
     */
    async getCircuits() {
        await this.ensureDB();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['circuits'], 'readonly');
            const store = transaction.objectStore('circuits');
            const request = store.getAll();

            request.onsuccess = () => {
                // Trier par date de création (plus récent d'abord)
                const circuits = request.result.sort((a, b) =>
                    new Date(b.created_at) - new Date(a.created_at)
                );
                resolve(circuits);
            };
            request.onerror = () => reject(request.error);
        });
    },

    /**
     * Récupérer un circuit par ID
     */
    async getCircuit(id) {
        await this.ensureDB();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['circuits'], 'readonly');
            const store = transaction.objectStore('circuits');
            const request = store.get(parseInt(id));

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    },

    /**
     * Supprimer un circuit
     */
    async deleteCircuit(id) {
        await this.ensureDB();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['circuits'], 'readwrite');
            const store = transaction.objectStore('circuits');
            const request = store.delete(parseInt(id));

            request.onsuccess = () => resolve(true);
            request.onerror = () => reject(request.error);
        });
    },

    /**
     * Renommer un circuit
     */
    async renameCircuit(id, newName) {
        const circuit = await this.getCircuit(id);
        if (circuit) {
            circuit.name = newName;
            return this.updateCircuit(id, circuit);
        }
        return false;
    },

    // ========== EXPORT / IMPORT ==========

    /**
     * Exporter tous les circuits en JSON
     */
    async exportCircuits() {
        const circuits = await this.getCircuits();

        const exportData = {
            version: 1,
            exportDate: new Date().toISOString(),
            application: 'Oscilloscope RLC',
            circuits: circuits.map(c => {
                // Retirer l'id pour l'export (sera régénéré à l'import)
                const { id, ...circuitData } = c;
                return circuitData;
            })
        };

        return JSON.stringify(exportData, null, 2);
    },

    /**
     * Importer des circuits depuis JSON
     * @returns {number} Nombre de circuits importés
     */
    async importCircuits(jsonString) {
        try {
            const data = JSON.parse(jsonString);

            // Valider le format
            if (!data.circuits || !Array.isArray(data.circuits)) {
                throw new Error('Format de fichier invalide');
            }

            let imported = 0;
            const existingCircuits = await this.getCircuits();
            const existingNames = new Set(existingCircuits.map(c => c.name));

            for (const circuit of data.circuits) {
                // Vérifier les champs requis
                if (!circuit.name || !circuit.circuit_type) {
                    continue;
                }

                // Gérer les doublons de nom
                let name = circuit.name;
                let suffix = 1;
                while (existingNames.has(name)) {
                    name = `${circuit.name} (${suffix})`;
                    suffix++;
                }
                existingNames.add(name);

                // Sauvegarder le circuit
                await this.saveCircuit({
                    ...circuit,
                    name,
                    imported_at: new Date().toISOString()
                });
                imported++;
            }

            return imported;
        } catch (e) {
            console.error('Erreur import:', e);
            throw e;
        }
    },

    /**
     * Télécharger les circuits en fichier JSON
     */
    async downloadCircuits() {
        const json = await this.exportCircuits();
        const blob = new Blob([json], { type: 'application/json' });
        const url = URL.createObjectURL(blob);

        const link = document.createElement('a');
        link.href = url;
        link.download = `oscilloscope-circuits-${new Date().toISOString().slice(0,10)}.json`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        URL.revokeObjectURL(url);
    },

    /**
     * Ouvrir le sélecteur de fichier pour import
     * @returns {Promise<number>} Nombre de circuits importés
     */
    openImportDialog() {
        return new Promise((resolve, reject) => {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.json';

            input.onchange = async (e) => {
                const file = e.target.files[0];
                if (!file) {
                    resolve(0);
                    return;
                }

                const reader = new FileReader();
                reader.onload = async (e) => {
                    try {
                        const count = await this.importCircuits(e.target.result);
                        resolve(count);
                    } catch (err) {
                        reject(err);
                    }
                };
                reader.onerror = () => reject(new Error('Erreur lecture fichier'));
                reader.readAsText(file);
            };

            input.click();
        });
    },

    // ========== PRESETS ==========

    /**
     * Récupérer les presets par défaut
     */
    getDefaultPresets() {
        return [
            // Résidentiel
            { category: 'residential', name: 'Prise murale 120V/60Hz', circuit_type: 'rlc_parallel', voltage: 120, frequency: 60, resistance: 100, inductance: 0.1, capacitance: 0.0001 },
            { category: 'residential', name: 'Prise murale 230V/50Hz', circuit_type: 'rlc_parallel', voltage: 230, frequency: 50, resistance: 100, inductance: 0.1, capacitance: 0.0001 },
            // Filtres
            { category: 'filter', name: 'Filtre passe-bas RC (1kHz)', circuit_type: 'rc_series', voltage: 5, frequency: 1000, resistance: 1000, inductance: 0, capacitance: 0.000000159 },
            { category: 'filter', name: 'Filtre passe-haut RC (1kHz)', circuit_type: 'rc_series', voltage: 5, frequency: 1000, resistance: 1000, inductance: 0, capacitance: 0.000000159 },
            // Résonance
            { category: 'resonance', name: 'Circuit résonant série', circuit_type: 'rlc_series', voltage: 10, frequency: 1000, resistance: 10, inductance: 0.01, capacitance: 0.00000253 },
            { category: 'resonance', name: 'Circuit résonant parallèle', circuit_type: 'rlc_parallel', voltage: 10, frequency: 1000, resistance: 1000, inductance: 0.01, capacitance: 0.00000253 },
            // Audio
            { category: 'audio', name: 'Crossover audio 3kHz', circuit_type: 'rlc_series', voltage: 1, frequency: 3000, resistance: 8, inductance: 0.0003, capacitance: 0.0000088 },
            // Industriel
            { category: 'industrial', name: 'Moteur AC industriel', circuit_type: 'rl_series', voltage: 380, frequency: 50, resistance: 5, inductance: 0.05, capacitance: 0 }
        ];
    },

    /**
     * Récupérer tous les presets
     */
    async getPresets() {
        await this.ensureDB();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['presets'], 'readonly');
            const store = transaction.objectStore('presets');
            const request = store.getAll();

            request.onsuccess = () => {
                let presets = request.result;
                // Si pas de presets, retourner les défauts
                if (presets.length === 0) {
                    presets = this.getDefaultPresets();
                }
                resolve(presets);
            };
            request.onerror = () => reject(request.error);
        });
    },

    // ========== SETTINGS ==========

    /**
     * Sauvegarder un paramètre
     */
    async setSetting(key, value) {
        await this.ensureDB();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['settings'], 'readwrite');
            const store = transaction.objectStore('settings');
            const request = store.put({ key, value, updated_at: new Date().toISOString() });

            request.onsuccess = () => resolve(true);
            request.onerror = () => reject(request.error);
        });
    },

    /**
     * Récupérer un paramètre
     */
    async getSetting(key, defaultValue = null) {
        await this.ensureDB();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['settings'], 'readonly');
            const store = transaction.objectStore('settings');
            const request = store.get(key);

            request.onsuccess = () => {
                resolve(request.result ? request.result.value : defaultValue);
            };
            request.onerror = () => reject(request.error);
        });
    },

    /**
     * Récupérer tous les paramètres
     */
    async getAllSettings() {
        await this.ensureDB();

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['settings'], 'readonly');
            const store = transaction.objectStore('settings');
            const request = store.getAll();

            request.onsuccess = () => {
                const settings = {};
                request.result.forEach(item => {
                    settings[item.key] = item.value;
                });
                resolve(settings);
            };
            request.onerror = () => reject(request.error);
        });
    },

    // ========== RACCOURCIS SYNCHRONES (avec cache) ==========
    // Pour compatibilité avec le code existant qui attend des valeurs synchrones

    _cache: {
        theme: null,
        gridConfig: null,
        oscilloscopeConfig: null,
        lastConfig: null
    },

    /**
     * Charger le cache au démarrage
     */
    async loadCache() {
        await this.ensureDB();

        this._cache.theme = await this.getSetting('theme', 'light');
        this._cache.gridConfig = await this.getSetting('gridConfig', this.getGridConfigDefaults());
        this._cache.oscilloscopeConfig = await this.getSetting('oscilloscopeConfig', this.getOscilloscopeConfigDefaults());
        this._cache.lastConfig = await this.getSetting('lastConfig', null);
        this._cache.oscilloscopeBg = await this.getSetting('oscilloscopeBg', 'light');
        this._cache.oscilloscopeText = await this.getSetting('oscilloscopeText', '#1a1a2e');
        this._cache.oscilloscopeTheme = await this.getSetting('oscilloscopeTheme', 'light');
    },

    /**
     * Thème
     */
    setTheme(theme) {
        this._cache.theme = theme;
        this.setSetting('theme', theme);
    },

    getTheme() {
        return this._cache.theme || 'light';
    },

    /**
     * Dernière configuration
     */
    setLastConfig(config) {
        this._cache.lastConfig = config;
        this.setSetting('lastConfig', config);
    },

    getLastConfig() {
        return this._cache.lastConfig;
    },

    /**
     * Config oscilloscope
     */
    setOscilloscopeConfig(config) {
        this._cache.oscilloscopeConfig = config;
        this.setSetting('oscilloscopeConfig', config);
    },

    getOscilloscopeConfig() {
        return this._cache.oscilloscopeConfig || this.getOscilloscopeConfigDefaults();
    },

    getOscilloscopeConfigDefaults() {
        return {
            timePerDiv: 0.002,
            phaseOffset: 0,
            channels: {
                1: { enabled: true, signal: 'V_Source', scale: 50 },
                2: { enabled: true, signal: 'I_Source', scale: 1 },
                3: { enabled: true, signal: '', scale: 50 },
                4: { enabled: true, signal: '', scale: 50 }
            }
        };
    },

    /**
     * Config grille
     */
    setGridConfig(config) {
        this._cache.gridConfig = config;
        this.setSetting('gridConfig', config);
    },

    getGridConfig() {
        return this._cache.gridConfig || this.getGridConfigDefaults();
    },

    getGridConfigDefaults() {
        return {
            dark: {
                divisionColor: 'blue',
                divisionOpacity: 25,
                subDivisionColor: 'blue',
                subDivisionOpacity: 15,
                axisColor: 'blue',
                axisOpacity: 80
            },
            light: {
                divisionColor: 'black',
                divisionOpacity: 25,
                subDivisionColor: 'black',
                subDivisionOpacity: 15,
                axisColor: 'black',
                axisOpacity: 80
            }
        };
    },

    resetGridConfig() {
        const defaults = this.getGridConfigDefaults();
        this.setGridConfig(defaults);
        return defaults;
    },

    /**
     * Couleur de fond de l'oscilloscope
     */
    setOscilloscopeBg(color) {
        this._cache.oscilloscopeBg = color;
        this.setSetting('oscilloscopeBg', color);
    },

    getOscilloscopeBg() {
        return this._cache.oscilloscopeBg || 'dark';
    },

    /**
     * Couleur de texte de l'oscilloscope (WCAG 2.2)
     */
    setOscilloscopeText(color) {
        this._cache.oscilloscopeText = color;
        this.setSetting('oscilloscopeText', color);
    },

    getOscilloscopeText() {
        return this._cache.oscilloscopeText || '#ffffff';
    },

    /**
     * Thème de l'oscilloscope (dark/light) pour les lignes de grille
     */
    setOscilloscopeTheme(theme) {
        this._cache.oscilloscopeTheme = theme;
        this.setSetting('oscilloscopeTheme', theme);
    },

    getOscilloscopeTheme() {
        return this._cache.oscilloscopeTheme || 'dark';
    }
};

// Export global
window.Storage = Storage;
