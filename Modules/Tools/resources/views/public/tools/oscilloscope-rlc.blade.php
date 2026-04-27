<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@php $shareData = $tool->getShareData(); @endphp
@section('meta_description', $shareData['meta_description'])
@section('og_type', $shareData['og_type'])
@section('og_image', $shareData['og_image'])
@section('share_text', $shareData['share_text'])
@section('title', $tool->name . ' - ' . config('app.name'))
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $tool->name, 'breadcrumbItems' => [__('Outils'), $tool->name]])
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('tools/oscilloscope-rlc/assets/css/style.css') }}">
<style>
.oscilloscope-wrapper { margin: 0 -15px; }
.oscilloscope-wrapper .app-container { min-height: 700px; }
@media (max-width: 768px) { .oscilloscope-wrapper .app-container { min-height: 500px; } }
</style>
@endpush
@section('content')
<section class="wpo-blog-single-section" style="padding-top: 0;">
    <div class="container-fluid" style="padding: 0;">
        <div class="oscilloscope-wrapper" data-theme="light">
    <div class="app-container">
        <!-- Menu popup flottant -->
        <button id="btn-menu" class="btn-menu-trigger" aria-label="Menu" aria-expanded="false" aria-controls="menu-popup">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <div id="menu-popup" class="menu-popup" role="menu" aria-label="Menu principal">
            <div class="menu-popup-content">
                <!-- Theme toggle -->
                <button class="menu-item" id="menu-theme" role="menuitem">
                    <span class="menu-icon" id="theme-icon">
                        <svg class="icon-sun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="5"></circle>
                            <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"></path>
                        </svg>
                        <svg class="icon-moon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                    </span>
                    <span class="menu-label" id="theme-label">Mode clair</span>
                </button>

                <div class="menu-divider"></div>

                <!-- Capture oscilloscope -->
                <button class="menu-item" id="menu-capture" role="menuitem">
                    <span class="menu-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                    </span>
                    <span class="menu-label">Capturer l'oscilloscope</span>
                </button>

                <div class="menu-divider"></div>

                <!-- Sauvegarder circuit -->
                <button class="menu-item" id="menu-save" role="menuitem">
                    <span class="menu-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                    </span>
                    <span class="menu-label">Sauvegarder le circuit</span>
                </button>

                <!-- Mes circuits -->
                <button class="menu-item" id="menu-circuits" role="menuitem">
                    <span class="menu-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </span>
                    <span class="menu-label">Mes circuits sauvegardés</span>
                    <span class="menu-badge" id="circuits-count">0</span>
                </button>

                <div class="menu-divider"></div>

                <!-- Export -->
                <button class="menu-item" id="menu-export" role="menuitem">
                    <span class="menu-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                    </span>
                    <span class="menu-label">Exporter mes circuits</span>
                </button>

                <!-- Import -->
                <button class="menu-item" id="menu-import" role="menuitem">
                    <span class="menu-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                    </span>
                    <span class="menu-label">Importer des circuits</span>
                </button>

                <!-- Notice sauvegarde locale -->
                <div class="menu-notice">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <span>Vos circuits sont sauvegardés localement dans votre navigateur. Exportez-les pour les conserver.</span>
                </div>
            </div>
        </div>

        <!-- Overlay pour fermer le menu -->
        <div id="menu-overlay" class="menu-overlay"></div>

        <!-- Main content -->
        <main class="app-main app-main-no-header">
            <!-- Sidebar gauche - Configuration -->
            <aside class="sidebar sidebar-left">
                <!-- Type de circuit -->
                <section class="card">
                    <h2 class="card-title">Type de circuit</h2>
                    <select id="circuit-type" class="select-full">
                        <option value="rl_series">RL série</option>
                        <option value="rc_series">RC série</option>
                        <option value="rlc_series">RLC série</option>
                        <option value="rl_parallel">RL parallèle</option>
                        <option value="rc_parallel">RC parallèle</option>
                        <option value="rlc_parallel" selected>RLC parallèle</option>
                    </select>
                </section>

                <!-- Facteur de puissance -->
                <section class="card card-power-factor">
                    <div class="result-row result-highlight">
                        <span class="result-label">Facteur de puissance</span>
                        <span class="result-value" id="result-pf">-</span>
                    </div>
                    <div class="result-row">
                        <span class="result-label">Angle de phase</span>
                        <span class="result-value" id="result-phase">-</span>
                    </div>
                </section>

                <!-- Résultats source -->
                <section class="card card-results">
                    <h2 class="card-title">Source</h2>
                    <div class="result-row">
                        <span class="result-label">Tension</span>
                        <span class="result-value" id="result-voltage">-</span>
                    </div>
                    <div class="result-row">
                        <span class="result-label">Courant</span>
                        <span class="result-value" id="result-current">-</span>
                    </div>
                    <div class="result-row">
                        <span class="result-label">Impédance</span>
                        <span class="result-value" id="result-impedance">-</span>
                    </div>
                    <div class="result-row">
                        <span class="result-label">Puissance</span>
                        <span class="result-value" id="result-power">-</span>
                    </div>
                </section>

                <!-- Paramètres source -->
                <section class="card">
                    <h2 class="card-title">Source AC</h2>
                    <div class="input-group">
                        <label for="voltage">Tension</label>
                        <div class="input-with-unit">
                            <input type="number" id="voltage" value="120" min="0" step="any">
                            <select id="voltage-unit" class="unit-select">
                                <option value="1e6">MV</option>
                                <option value="1e3">kV</option>
                                <option value="1" selected>V</option>
                                <option value="1e-3">mV</option>
                            </select>
                        </div>
                    </div>
                    <div class="input-group">
                        <label for="frequency">Fréquence</label>
                        <div class="input-with-unit">
                            <input type="number" id="frequency" value="60" min="0" step="any">
                            <select id="frequency-unit" class="unit-select">
                                <option value="1e9">GHz</option>
                                <option value="1e6">MHz</option>
                                <option value="1e3">kHz</option>
                                <option value="1" selected>Hz</option>
                            </select>
                        </div>
                    </div>
                </section>
            </aside>

            <!-- Zone centrale - Oscilloscope -->
            <section class="main-section">
                <!-- Oscilloscope -->
                <div class="oscilloscope-container">
                    <canvas id="oscilloscope" width="800" height="400"></canvas>
                    <div class="oscilloscope-legend" id="legend"></div>

                    <!-- Bouton réglages grille (WCAG 2.2 compliant) -->
                    <button id="btn-grid-settings"
                            class="btn-grid-settings"
                            aria-label="Réglages de la grille"
                            aria-expanded="false"
                            aria-controls="grid-settings-popover">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                    </button>

                    <!-- Bouton normaliser amplitudes (activé par défaut) -->
                    <button id="btn-normalize"
                            class="btn-normalize"
                            aria-label="Normaliser les amplitudes"
                            aria-pressed="true"
                            title="Normaliser les amplitudes (égalise la hauteur des courbes)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <line x1="6" y1="6" x2="6" y2="18"></line>
                            <line x1="18" y1="6" x2="18" y2="18"></line>
                            <line x1="3" y1="12" x2="9" y2="12"></line>
                            <line x1="15" y1="12" x2="21" y2="12"></line>
                        </svg>
                    </button>

                    <!-- Bouton plein écran -->
                    <button id="btn-fullscreen"
                            class="btn-fullscreen"
                            aria-label="Plein écran"
                            title="Plein écran">
                        <svg class="icon-expand" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path>
                        </svg>
                        <svg class="icon-compress" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="display:none">
                            <path d="M4 14h6v6m10-10h-6V4m0 6l7-7M3 21l7-7"></path>
                        </svg>
                    </button>

                    <!-- Popover réglages grille (WCAG 2.2) - Design 2026 avec onglets -->
                    <div id="grid-settings-popover"
                         class="grid-settings-popover"
                         role="dialog"
                         aria-label="Réglages de la grille">
                        <!-- Onglets segmented control -->
                        <div class="settings-tabs" role="tablist">
                            <button class="settings-tab active" role="tab" aria-selected="true" data-tab="grid">Lignes</button>
                            <button class="settings-tab" role="tab" aria-selected="false" data-tab="display">Affichage</button>
                        </div>

                        <!-- Contenu onglet Lignes -->
                        <div class="settings-panel active" data-panel="grid">
                            <!-- Axes -->
                            <div class="settings-section">
                                <div class="settings-section-header">
                                    <span>Axes</span>
                                    <span class="opacity-value" id="axis-opacity-value">70%</span>
                                </div>
                                <div class="settings-section-content">
                                    <div class="color-presets" data-target="axis" role="group" aria-label="Couleur des axes">
                                        <button class="color-preset" data-color="blue" style="--preset-color: #3498db" aria-label="Bleu"></button>
                                        <button class="color-preset" data-color="green" style="--preset-color: #27ae60" aria-label="Vert"></button>
                                        <button class="color-preset" data-color="amber" style="--preset-color: #f39c12" aria-label="Amber"></button>
                                        <button class="color-preset" data-color="black" style="--preset-color: #1e1e1e" aria-label="Noir"></button>
                                        <button class="color-preset" data-color="white" style="--preset-color: #ffffff; border: 1px solid #666" aria-label="Blanc"></button>
                                        <button class="color-preset" data-color="red" style="--preset-color: #e74c3c" aria-label="Rouge"></button>
                                    </div>
                                    <input type="range" id="axis-opacity" min="0" max="100" value="70" class="opacity-slider">
                                </div>
                            </div>
                            <!-- Divisions -->
                            <div class="settings-section">
                                <div class="settings-section-header">
                                    <span>Divisions</span>
                                    <span class="opacity-value" id="division-opacity-value">35%</span>
                                </div>
                                <div class="settings-section-content">
                                    <div class="color-presets" data-target="division" role="group" aria-label="Couleur des divisions">
                                        <button class="color-preset" data-color="blue" style="--preset-color: #3498db" aria-label="Bleu"></button>
                                        <button class="color-preset" data-color="green" style="--preset-color: #27ae60" aria-label="Vert"></button>
                                        <button class="color-preset" data-color="amber" style="--preset-color: #f39c12" aria-label="Amber"></button>
                                        <button class="color-preset" data-color="black" style="--preset-color: #1e1e1e" aria-label="Noir"></button>
                                        <button class="color-preset" data-color="white" style="--preset-color: #ffffff; border: 1px solid #666" aria-label="Blanc"></button>
                                        <button class="color-preset" data-color="red" style="--preset-color: #e74c3c" aria-label="Rouge"></button>
                                    </div>
                                    <input type="range" id="division-opacity" min="0" max="100" value="35" class="opacity-slider">
                                </div>
                            </div>
                            <!-- Sous-divisions -->
                            <div class="settings-section">
                                <div class="settings-section-header">
                                    <span>Sous-div.</span>
                                    <span class="opacity-value" id="subdivision-opacity-value">18%</span>
                                </div>
                                <div class="settings-section-content">
                                    <div class="color-presets" data-target="subdivision" role="group" aria-label="Couleur des sous-divisions">
                                        <button class="color-preset" data-color="blue" style="--preset-color: #3498db" aria-label="Bleu"></button>
                                        <button class="color-preset" data-color="green" style="--preset-color: #27ae60" aria-label="Vert"></button>
                                        <button class="color-preset" data-color="amber" style="--preset-color: #f39c12" aria-label="Amber"></button>
                                        <button class="color-preset" data-color="black" style="--preset-color: #1e1e1e" aria-label="Noir"></button>
                                        <button class="color-preset" data-color="white" style="--preset-color: #ffffff; border: 1px solid #666" aria-label="Blanc"></button>
                                        <button class="color-preset" data-color="red" style="--preset-color: #e74c3c" aria-label="Rouge"></button>
                                    </div>
                                    <input type="range" id="subdivision-opacity" min="0" max="100" value="18" class="opacity-slider">
                                </div>
                            </div>
                        </div>

                        <!-- Contenu onglet Affichage -->
                        <div class="settings-panel" data-panel="display">
                            <!-- Fond écran + texte (WCAG 2.2 compliant) -->
                            <div class="settings-section">
                                <div class="settings-section-header">
                                    <span>Fond et texte</span>
                                </div>
                                <div class="settings-section-content">
                                    <div class="color-presets color-presets-dual" data-target="background" role="group" aria-label="Couleur de fond et texte">
                                        <!-- Fonds sombres (lignes bleues) -->
                                        <button class="color-preset-dual" data-color="dark" data-theme="dark" data-bg="#0d1117" data-text="#ffffff" aria-label="Sombre avec texte blanc" title="Sombre / Blanc (17:1)"></button>
                                        <button class="color-preset-dual" data-color="navy" data-theme="dark" data-bg="#1a1a2e" data-text="#e0e0e0" aria-label="Marine avec texte gris clair" title="Marine / Gris (12:1)"></button>
                                        <button class="color-preset-dual" data-color="green-dark" data-theme="dark" data-bg="#0a1612" data-text="#90d4a8" aria-label="Vert sombre avec texte vert clair" title="Vert / Vert clair (8:1)"></button>
                                        <button class="color-preset-dual" data-color="black" data-theme="dark" data-bg="#000000" data-text="#ffffff" aria-label="Noir avec texte blanc" title="Noir / Blanc (21:1)"></button>
                                        <!-- Fonds clairs (lignes noires) -->
                                        <button class="color-preset-dual" data-color="light" data-theme="light" data-bg="#f5f5f5" data-text="#1a1a2e" aria-label="Clair avec texte sombre" title="Clair / Sombre (12:1)"></button>
                                        <button class="color-preset-dual" data-color="sepia" data-theme="light" data-bg="#f4ecd8" data-text="#2d2a26" aria-label="Sépia avec texte brun" title="Sépia / Brun (10:1)"></button>
                                        <button class="color-preset-dual" data-color="blue-light" data-theme="light" data-bg="#e3f2fd" data-text="#0d47a1" aria-label="Bleu clair avec texte bleu foncé" title="Bleu clair / Bleu (9:1)"></button>
                                        <button class="color-preset-dual" data-color="mint" data-theme="light" data-bg="#e8f5e9" data-text="#1b5e20" aria-label="Menthe avec texte vert foncé" title="Menthe / Vert (8:1)"></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bouton reset -->
                        <button id="btn-grid-reset" class="btn-settings-reset" aria-label="Réinitialiser aux valeurs par défaut">
                            Réinitialiser
                        </button>
                    </div>
                </div>

                <!-- Contrôles oscilloscope -->
                <div class="oscilloscope-controls">
                    <div class="control-group">
                        <label for="time-div">Temps/Div</label>
                        <select id="time-div">
                            <option value="0.000001">1 μs</option>
                            <option value="0.000002">2 μs</option>
                            <option value="0.000005">5 μs</option>
                            <option value="0.00001">10 μs</option>
                            <option value="0.00002">20 μs</option>
                            <option value="0.00005">50 μs</option>
                            <option value="0.0001">100 μs</option>
                            <option value="0.0002">200 μs</option>
                            <option value="0.0005">500 μs</option>
                            <option value="0.001">1 ms</option>
                            <option value="0.002" selected>2 ms</option>
                            <option value="0.005">5 ms</option>
                            <option value="0.01">10 ms</option>
                            <option value="0.02">20 ms</option>
                            <option value="0.05">50 ms</option>
                            <option value="0.1">100 ms</option>
                        </select>
                    </div>
                    <div class="control-group">
                        <label for="phase-offset">Phase</label>
                        <input type="range" id="phase-offset" min="-180" max="180" value="0">
                        <span id="phase-value">0°</span>
                    </div>
                </div>

                <!-- Canaux -->
                <div class="channels-grid">
                    <div class="channel channel-1">
                        <div class="channel-header">
                            <label class="channel-toggle">
                                <input type="checkbox" id="channel-1-enabled" checked>
                                <span class="channel-toggle-slider"></span>
                            </label>
                            <span class="channel-label">CH1</span>
                            <button type="button" class="channel-settings-btn" data-channel="1" title="Paramètres de la trace">
                                <span class="channel-color-indicator" style="background-color: #E69F00;"></span>
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="3"/><path d="M12 1v4m0 14v4M4.22 4.22l2.83 2.83m9.9 9.9l2.83 2.83M1 12h4m14 0h4M4.22 19.78l2.83-2.83m9.9-9.9l2.83-2.83"/></svg>
                            </button>
                            <div class="channel-settings-popover" data-channel="1">
                                <div class="popover-section">
                                    <div class="popover-section-label">Couleur</div>
                                    <div class="color-swatches">
                                        <button class="color-swatch" data-color="#E69F00" title="Orange" style="background-color: #E69F00;"></button>
                                        <button class="color-swatch" data-color="#56B4E9" title="Bleu ciel" style="background-color: #56B4E9;"></button>
                                        <button class="color-swatch" data-color="#009E73" title="Vert teal" style="background-color: #009E73;"></button>
                                        <button class="color-swatch" data-color="#CC79A7" title="Rose" style="background-color: #CC79A7;"></button>
                                        <button class="color-swatch" data-color="#F0E442" title="Jaune" style="background-color: #F0E442;"></button>
                                        <button class="color-swatch" data-color="#D55E00" title="Rouge-orange" style="background-color: #D55E00;"></button>
                                        <button class="color-swatch" data-color="#FFFFFF" title="Blanc" style="background-color: #FFFFFF; border: 1px solid #666;"></button>
                                        <button class="color-swatch" data-color="#0072B2" title="Bleu foncé" style="background-color: #0072B2;"></button>
                                    </div>
                                </div>
                                <div class="popover-section">
                                    <label class="popover-checkbox">
                                        <input type="checkbox" class="phase-marker-toggle" data-channel="1" checked>
                                        <span>Marqueur de phase</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <select id="channel-1-signal" class="channel-signal">
                            <option value="">---</option>
                        </select>
                        <select id="channel-1-scale" class="channel-scale">
                            <optgroup label="Voltage" class="scale-voltage">
                                <option value="0.001">1 mV</option>
                                <option value="0.002">2 mV</option>
                                <option value="0.005">5 mV</option>
                                <option value="0.01">10 mV</option>
                                <option value="0.02">20 mV</option>
                                <option value="0.05">50 mV</option>
                                <option value="0.1">100 mV</option>
                                <option value="0.2">200 mV</option>
                                <option value="0.5">500 mV</option>
                                <option value="1">1 V</option>
                                <option value="2">2 V</option>
                                <option value="5">5 V</option>
                                <option value="10">10 V</option>
                                <option value="20">20 V</option>
                                <option value="50" selected>50 V</option>
                                <option value="100">100 V</option>
                                <option value="200">200 V</option>
                            </optgroup>
                            <optgroup label="Courant" class="scale-current">
                                <option value="0.001">1 mA</option>
                                <option value="0.002">2 mA</option>
                                <option value="0.005">5 mA</option>
                                <option value="0.01">10 mA</option>
                                <option value="0.02">20 mA</option>
                                <option value="0.05">50 mA</option>
                                <option value="0.1">100 mA</option>
                                <option value="0.2">200 mA</option>
                                <option value="0.5">500 mA</option>
                                <option value="1">1 A</option>
                                <option value="2">2 A</option>
                                <option value="5">5 A</option>
                                <option value="10">10 A</option>
                                <option value="20">20 A</option>
                                <option value="50">50 A</option>
                                <option value="100">100 A</option>
                                <option value="200">200 A</option>
                            </optgroup>
                        </select>
                        <div class="channel-offset">
                            <div class="offset-slider-wrapper">
                                <input type="range" id="channel-1-offset" min="-5" max="5" step="0.5" value="0" class="offset-slider at-center" title="Double-clic pour recentrer">
                            </div>
                            <button type="button" class="offset-reset-btn" data-channel="1" title="Recentrer">&#x27F2;</button>
                        </div>
                    </div>
                    <div class="channel channel-2">
                        <div class="channel-header">
                            <label class="channel-toggle">
                                <input type="checkbox" id="channel-2-enabled" checked>
                                <span class="channel-toggle-slider"></span>
                            </label>
                            <span class="channel-label">CH2</span>
                            <button type="button" class="channel-settings-btn" data-channel="2" title="Paramètres de la trace">
                                <span class="channel-color-indicator" style="background-color: #56B4E9;"></span>
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="3"/><path d="M12 1v4m0 14v4M4.22 4.22l2.83 2.83m9.9 9.9l2.83 2.83M1 12h4m14 0h4M4.22 19.78l2.83-2.83m9.9-9.9l2.83-2.83"/></svg>
                            </button>
                            <div class="channel-settings-popover" data-channel="2">
                                <div class="popover-section">
                                    <div class="popover-section-label">Couleur</div>
                                    <div class="color-swatches">
                                        <button class="color-swatch" data-color="#E69F00" title="Orange" style="background-color: #E69F00;"></button>
                                        <button class="color-swatch" data-color="#56B4E9" title="Bleu ciel" style="background-color: #56B4E9;"></button>
                                        <button class="color-swatch" data-color="#009E73" title="Vert teal" style="background-color: #009E73;"></button>
                                        <button class="color-swatch" data-color="#CC79A7" title="Rose" style="background-color: #CC79A7;"></button>
                                        <button class="color-swatch" data-color="#F0E442" title="Jaune" style="background-color: #F0E442;"></button>
                                        <button class="color-swatch" data-color="#D55E00" title="Rouge-orange" style="background-color: #D55E00;"></button>
                                        <button class="color-swatch" data-color="#FFFFFF" title="Blanc" style="background-color: #FFFFFF; border: 1px solid #666;"></button>
                                        <button class="color-swatch" data-color="#0072B2" title="Bleu foncé" style="background-color: #0072B2;"></button>
                                    </div>
                                </div>
                                <div class="popover-section">
                                    <label class="popover-checkbox">
                                        <input type="checkbox" class="phase-marker-toggle" data-channel="2" checked>
                                        <span>Marqueur de phase</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <select id="channel-2-signal" class="channel-signal">
                            <option value="">---</option>
                        </select>
                        <select id="channel-2-scale" class="channel-scale">
                            <optgroup label="Voltage" class="scale-voltage">
                                <option value="0.001">1 mV</option>
                                <option value="0.002">2 mV</option>
                                <option value="0.005">5 mV</option>
                                <option value="0.01">10 mV</option>
                                <option value="0.02">20 mV</option>
                                <option value="0.05">50 mV</option>
                                <option value="0.1">100 mV</option>
                                <option value="0.2">200 mV</option>
                                <option value="0.5">500 mV</option>
                                <option value="1">1 V</option>
                                <option value="2">2 V</option>
                                <option value="5">5 V</option>
                                <option value="10">10 V</option>
                                <option value="20">20 V</option>
                                <option value="50" selected>50 V</option>
                                <option value="100">100 V</option>
                                <option value="200">200 V</option>
                            </optgroup>
                            <optgroup label="Courant" class="scale-current">
                                <option value="0.001">1 mA</option>
                                <option value="0.002">2 mA</option>
                                <option value="0.005">5 mA</option>
                                <option value="0.01">10 mA</option>
                                <option value="0.02">20 mA</option>
                                <option value="0.05">50 mA</option>
                                <option value="0.1">100 mA</option>
                                <option value="0.2">200 mA</option>
                                <option value="0.5">500 mA</option>
                                <option value="1">1 A</option>
                                <option value="2">2 A</option>
                                <option value="5">5 A</option>
                                <option value="10">10 A</option>
                                <option value="20">20 A</option>
                                <option value="50">50 A</option>
                                <option value="100">100 A</option>
                                <option value="200">200 A</option>
                            </optgroup>
                        </select>
                        <div class="channel-offset">
                            <div class="offset-slider-wrapper">
                                <input type="range" id="channel-2-offset" min="-5" max="5" step="0.5" value="0" class="offset-slider at-center" title="Double-clic pour recentrer">
                            </div>
                            <button type="button" class="offset-reset-btn" data-channel="2" title="Recentrer">&#x27F2;</button>
                        </div>
                    </div>
                    <div class="channel channel-3">
                        <div class="channel-header">
                            <label class="channel-toggle">
                                <input type="checkbox" id="channel-3-enabled" checked>
                                <span class="channel-toggle-slider"></span>
                            </label>
                            <span class="channel-label">CH3</span>
                            <button type="button" class="channel-settings-btn" data-channel="3" title="Paramètres de la trace">
                                <span class="channel-color-indicator" style="background-color: #009E73;"></span>
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="3"/><path d="M12 1v4m0 14v4M4.22 4.22l2.83 2.83m9.9 9.9l2.83 2.83M1 12h4m14 0h4M4.22 19.78l2.83-2.83m9.9-9.9l2.83-2.83"/></svg>
                            </button>
                            <div class="channel-settings-popover" data-channel="3">
                                <div class="popover-section">
                                    <div class="popover-section-label">Couleur</div>
                                    <div class="color-swatches">
                                        <button class="color-swatch" data-color="#E69F00" title="Orange" style="background-color: #E69F00;"></button>
                                        <button class="color-swatch" data-color="#56B4E9" title="Bleu ciel" style="background-color: #56B4E9;"></button>
                                        <button class="color-swatch" data-color="#009E73" title="Vert teal" style="background-color: #009E73;"></button>
                                        <button class="color-swatch" data-color="#CC79A7" title="Rose" style="background-color: #CC79A7;"></button>
                                        <button class="color-swatch" data-color="#F0E442" title="Jaune" style="background-color: #F0E442;"></button>
                                        <button class="color-swatch" data-color="#D55E00" title="Rouge-orange" style="background-color: #D55E00;"></button>
                                        <button class="color-swatch" data-color="#FFFFFF" title="Blanc" style="background-color: #FFFFFF; border: 1px solid #666;"></button>
                                        <button class="color-swatch" data-color="#0072B2" title="Bleu foncé" style="background-color: #0072B2;"></button>
                                    </div>
                                </div>
                                <div class="popover-section">
                                    <label class="popover-checkbox">
                                        <input type="checkbox" class="phase-marker-toggle" data-channel="3">
                                        <span>Marqueur de phase</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <select id="channel-3-signal" class="channel-signal">
                            <option value="">---</option>
                        </select>
                        <select id="channel-3-scale" class="channel-scale">
                            <optgroup label="Voltage" class="scale-voltage">
                                <option value="0.001">1 mV</option>
                                <option value="0.002">2 mV</option>
                                <option value="0.005">5 mV</option>
                                <option value="0.01">10 mV</option>
                                <option value="0.02">20 mV</option>
                                <option value="0.05">50 mV</option>
                                <option value="0.1">100 mV</option>
                                <option value="0.2">200 mV</option>
                                <option value="0.5">500 mV</option>
                                <option value="1">1 V</option>
                                <option value="2">2 V</option>
                                <option value="5">5 V</option>
                                <option value="10">10 V</option>
                                <option value="20">20 V</option>
                                <option value="50" selected>50 V</option>
                                <option value="100">100 V</option>
                                <option value="200">200 V</option>
                            </optgroup>
                            <optgroup label="Courant" class="scale-current">
                                <option value="0.001">1 mA</option>
                                <option value="0.002">2 mA</option>
                                <option value="0.005">5 mA</option>
                                <option value="0.01">10 mA</option>
                                <option value="0.02">20 mA</option>
                                <option value="0.05">50 mA</option>
                                <option value="0.1">100 mA</option>
                                <option value="0.2">200 mA</option>
                                <option value="0.5">500 mA</option>
                                <option value="1">1 A</option>
                                <option value="2">2 A</option>
                                <option value="5">5 A</option>
                                <option value="10">10 A</option>
                                <option value="20">20 A</option>
                                <option value="50">50 A</option>
                                <option value="100">100 A</option>
                                <option value="200">200 A</option>
                            </optgroup>
                        </select>
                        <div class="channel-offset">
                            <div class="offset-slider-wrapper">
                                <input type="range" id="channel-3-offset" min="-5" max="5" step="0.5" value="0" class="offset-slider at-center" title="Double-clic pour recentrer">
                            </div>
                            <button type="button" class="offset-reset-btn" data-channel="3" title="Recentrer">&#x27F2;</button>
                        </div>
                    </div>
                    <div class="channel channel-4">
                        <div class="channel-header">
                            <label class="channel-toggle">
                                <input type="checkbox" id="channel-4-enabled" checked>
                                <span class="channel-toggle-slider"></span>
                            </label>
                            <span class="channel-label">CH4</span>
                            <button type="button" class="channel-settings-btn" data-channel="4" title="Paramètres de la trace">
                                <span class="channel-color-indicator" style="background-color: #CC79A7;"></span>
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="3"/><path d="M12 1v4m0 14v4M4.22 4.22l2.83 2.83m9.9 9.9l2.83 2.83M1 12h4m14 0h4M4.22 19.78l2.83-2.83m9.9-9.9l2.83-2.83"/></svg>
                            </button>
                            <div class="channel-settings-popover" data-channel="4">
                                <div class="popover-section">
                                    <div class="popover-section-label">Couleur</div>
                                    <div class="color-swatches">
                                        <button class="color-swatch" data-color="#E69F00" title="Orange" style="background-color: #E69F00;"></button>
                                        <button class="color-swatch" data-color="#56B4E9" title="Bleu ciel" style="background-color: #56B4E9;"></button>
                                        <button class="color-swatch" data-color="#009E73" title="Vert teal" style="background-color: #009E73;"></button>
                                        <button class="color-swatch" data-color="#CC79A7" title="Rose" style="background-color: #CC79A7;"></button>
                                        <button class="color-swatch" data-color="#F0E442" title="Jaune" style="background-color: #F0E442;"></button>
                                        <button class="color-swatch" data-color="#D55E00" title="Rouge-orange" style="background-color: #D55E00;"></button>
                                        <button class="color-swatch" data-color="#FFFFFF" title="Blanc" style="background-color: #FFFFFF; border: 1px solid #666;"></button>
                                        <button class="color-swatch" data-color="#0072B2" title="Bleu foncé" style="background-color: #0072B2;"></button>
                                    </div>
                                </div>
                                <div class="popover-section">
                                    <label class="popover-checkbox">
                                        <input type="checkbox" class="phase-marker-toggle" data-channel="4">
                                        <span>Marqueur de phase</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <select id="channel-4-signal" class="channel-signal">
                            <option value="">---</option>
                        </select>
                        <select id="channel-4-scale" class="channel-scale">
                            <optgroup label="Voltage" class="scale-voltage">
                                <option value="0.001">1 mV</option>
                                <option value="0.002">2 mV</option>
                                <option value="0.005">5 mV</option>
                                <option value="0.01">10 mV</option>
                                <option value="0.02">20 mV</option>
                                <option value="0.05">50 mV</option>
                                <option value="0.1">100 mV</option>
                                <option value="0.2">200 mV</option>
                                <option value="0.5">500 mV</option>
                                <option value="1">1 V</option>
                                <option value="2">2 V</option>
                                <option value="5">5 V</option>
                                <option value="10">10 V</option>
                                <option value="20">20 V</option>
                                <option value="50" selected>50 V</option>
                                <option value="100">100 V</option>
                                <option value="200">200 V</option>
                            </optgroup>
                            <optgroup label="Courant" class="scale-current">
                                <option value="0.001">1 mA</option>
                                <option value="0.002">2 mA</option>
                                <option value="0.005">5 mA</option>
                                <option value="0.01">10 mA</option>
                                <option value="0.02">20 mA</option>
                                <option value="0.05">50 mA</option>
                                <option value="0.1">100 mA</option>
                                <option value="0.2">200 mA</option>
                                <option value="0.5">500 mA</option>
                                <option value="1">1 A</option>
                                <option value="2">2 A</option>
                                <option value="5">5 A</option>
                                <option value="10">10 A</option>
                                <option value="20">20 A</option>
                                <option value="50">50 A</option>
                                <option value="100">100 A</option>
                                <option value="200">200 A</option>
                            </optgroup>
                        </select>
                        <div class="channel-offset">
                            <div class="offset-slider-wrapper">
                                <input type="range" id="channel-4-offset" min="-5" max="5" step="0.5" value="0" class="offset-slider at-center" title="Double-clic pour recentrer">
                            </div>
                            <button type="button" class="offset-reset-btn" data-channel="4" title="Recentrer">&#x27F2;</button>
                        </div>
                    </div>
                </div>

            </section>

            <!-- Sidebar droite - Résultats -->
            <aside class="sidebar sidebar-right">
                <!-- Composants (résultats - collapsible) -->
                <section class="card card-components-group">
                    <h2 class="card-title card-toggle" data-collapsed="false">
                        <span>Composants</span>
                        <span class="toggle-icon"></span>
                    </h2>
                    <div class="card-content">
                        <!-- Résistance -->
                        <div class="component-section" id="card-resistor">
                            <h3 class="component-title">Résistance</h3>
                            <div class="result-row">
                                <span class="result-label">V<sub>R</sub></span>
                                <span class="result-value" id="result-vr">-</span>
                            </div>
                            <div class="result-row">
                                <span class="result-label">I<sub>R</sub></span>
                                <span class="result-value" id="result-ir">-</span>
                            </div>
                            <div class="result-row">
                                <span class="result-label">P<sub>R</sub></span>
                                <span class="result-value" id="result-pr">-</span>
                            </div>
                        </div>

                        <!-- Inductance -->
                        <div class="component-section" id="card-inductor">
                            <h3 class="component-title">Inductance</h3>
                            <div class="result-row">
                                <span class="result-label">V<sub>L</sub></span>
                                <span class="result-value" id="result-vl">-</span>
                            </div>
                            <div class="result-row">
                                <span class="result-label">I<sub>L</sub></span>
                                <span class="result-value" id="result-il">-</span>
                            </div>
                            <div class="result-row">
                                <span class="result-label">X<sub>L</sub></span>
                                <span class="result-value" id="result-xl">-</span>
                            </div>
                        </div>

                        <!-- Capacitance -->
                        <div class="component-section" id="card-capacitor">
                            <h3 class="component-title">Capacitance</h3>
                            <div class="result-row">
                                <span class="result-label">V<sub>C</sub></span>
                                <span class="result-value" id="result-vc">-</span>
                            </div>
                            <div class="result-row">
                                <span class="result-label">I<sub>C</sub></span>
                                <span class="result-value" id="result-ic">-</span>
                            </div>
                            <div class="result-row">
                                <span class="result-label">X<sub>C</sub></span>
                                <span class="result-value" id="result-xc">-</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Composants (entrées) -->
                <section class="card">
                    <h2 class="card-title">Composants</h2>
                    <div class="input-group" id="group-resistance">
                        <label for="resistance">Résistance</label>
                        <div class="input-with-unit">
                            <input type="number" id="resistance" value="100" min="0" step="any">
                            <select id="resistance-unit" class="unit-select">
                                <option value="1e6">MΩ</option>
                                <option value="1e3">kΩ</option>
                                <option value="1" selected>Ω</option>
                                <option value="1e-3">mΩ</option>
                            </select>
                        </div>
                    </div>
                    <div class="input-group" id="group-inductance">
                        <label for="inductance">Inductance</label>
                        <div class="input-with-unit">
                            <input type="number" id="inductance" value="100" min="0" step="any">
                            <select id="inductance-unit" class="unit-select">
                                <option value="1">H</option>
                                <option value="1e-3" selected>mH</option>
                                <option value="1e-6">μH</option>
                                <option value="1e-9">nH</option>
                            </select>
                        </div>
                    </div>
                    <div class="input-group" id="group-capacitance">
                        <label for="capacitance">Capacitance</label>
                        <div class="input-with-unit">
                            <input type="number" id="capacitance" value="100" min="0" step="any">
                            <select id="capacitance-unit" class="unit-select">
                                <option value="1">F</option>
                                <option value="1e-3">mF</option>
                                <option value="1e-6" selected>μF</option>
                                <option value="1e-9">nF</option>
                                <option value="1e-12">pF</option>
                            </select>
                        </div>
                    </div>
                </section>
            </aside>
        </main>
    </div>

    <!-- Modal sauvegarde -->
    <dialog id="modal-save" class="modal">
        <div class="modal-content">
            <h2>Sauvegarder le circuit</h2>
            <div class="input-group">
                <label for="circuit-name">Nom du circuit</label>
                <input type="text" id="circuit-name" placeholder="Mon circuit RLC">
            </div>
            <div class="modal-actions">
                <button id="btn-save-confirm" class="ct-btn ct-btn-primary">Sauvegarder</button>
                <button id="btn-save-cancel" class="ct-btn ct-btn-outline">Annuler</button>
            </div>
        </div>
    </dialog>

    <!-- Modal circuits sauvegardés -->
    <dialog id="modal-circuits" class="modal modal-circuits">
        <div class="modal-content">
            <h2>Mes circuits sauvegardés</h2>
            <div id="circuits-list" class="circuits-list">
                <!-- Rempli dynamiquement -->
            </div>
            <div class="modal-actions">
                <button id="btn-circuits-close" class="ct-btn ct-btn-outline">Fermer</button>
            </div>
        </div>
    </dialog>

    <!-- Toast notifications -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Scripts -->
        </div>
    </div>
</section>
@endsection
@push('scripts')
<script src="{{ asset('tools/oscilloscope-rlc/assets/js/storage.js') }}"></script>
<script src="{{ asset('tools/oscilloscope-rlc/assets/js/circuit.js') }}"></script>
<script src="{{ asset('tools/oscilloscope-rlc/assets/js/oscilloscope.js') }}"></script>
<script src="{{ asset('tools/oscilloscope-rlc/assets/js/scrubber.js') }}"></script>
<script src="{{ asset('tools/oscilloscope-rlc/assets/js/app.js') }}"></script>
@endpush
