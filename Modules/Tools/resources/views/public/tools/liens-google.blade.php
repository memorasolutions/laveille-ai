<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@section('title', $tool->name . ' - ' . config('app.name'))
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $tool->name, 'breadcrumbItems' => [__('Outils'), $tool->name]])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-12">
                <div class="card shadow-sm" style="border-radius: var(--r-base);">
                    <div class="card-body p-4 p-md-5" x-data="googleLinksTool()">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h1 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); margin: 0;">{{ $tool->name }}</h1>
                                <p class="text-muted mb-0">{{ $tool->description }}</p>
                            </div>
                            <button class="btn btn-sm" @click="jQuery('#helpModal').modal('show')" style="background: var(--c-primary); color: #fff; border-radius: 50%; width: 32px; height: 32px; font-weight: 700; font-size: 1rem; padding: 0; line-height: 32px; flex-shrink: 0;" title="{{ __('Aide') }}">?</button>
                        </div>
                        <div class="mb-4"></div>

                        @include('fronttheme::partials.tabs', ['tabs' => [
                            ['id' => 'transform', 'label' => __('Transformateur de liens')],
                            ['id' => 'search', 'label' => __('Recherche avancée')],
                            ['id' => 'dorks', 'label' => __('Dorks éducatifs')],
                        ], 'model' => 'tab'])

                        {{-- Onglet 1 : Recherche avancée --}}
                        <div x-show="tab === 'search'" x-transition>
                            {{-- Recherche principale --}}
                            <div class="input-group mb-3">
                                <input type="text" class="form-control form-control-lg" x-model="query" @keydown.enter="generateSearch()" placeholder="{{ __('Entrez votre recherche...') }}" aria-label="{{ __('Recherche Google avancée') }}">
                                <span class="input-group-btn">
                                    <button class="btn btn-lg" @click="generateSearch()" style="background: var(--c-accent); color: #fff; border-radius: 0 var(--r-btn) var(--r-btn) 0;">{{ __('Générer') }}</button>
                                </span>
                            </div>

                            {{-- Presets --}}
                            <div class="mb-3">
                                <label class="form-label fw-medium" style="font-size: 0.85rem;">{{ __('Presets de recherche') }}</label>
                                <div class="d-flex flex-wrap gap-1">
                                    <template x-for="(p, i) in presetsSearch" :key="i">
                                        <button class="btn btn-sm btn-outline-secondary" @click="loadSearchPreset(p)" x-text="p.name" style="border-radius: var(--r-btn); font-size: 0.75rem;"></button>
                                    </template>
                                </div>
                            </div>

                            {{-- Opérateurs avancés --}}
                            <details class="mb-3">
                                <summary style="cursor: pointer; font-family: var(--f-heading); font-weight: 600; color: var(--c-dark);">{{ __('Opérateurs avancés') }}</summary>
                                <div class="mt-3 p-3 rounded" style="background: #f8f9fa;">
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label" style="font-size: 0.85rem;">site:</label>
                                            <input type="text" class="form-control form-control-sm" x-model="advSite" placeholder="{{ __('ex: .edu, gouv.qc.ca') }}" aria-label="site:">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label" style="font-size: 0.85rem;">filetype:</label>
                                            <select class="form-control form-control-sm" x-model="advFiletype" aria-label="filetype:">
                                                <option value="">{{ __('Tous les types') }}</option>
                                                <option value="pdf">PDF</option>
                                                <option value="doc">DOC</option>
                                                <option value="docx">DOCX</option>
                                                <option value="xls">XLS</option>
                                                <option value="xlsx">XLSX</option>
                                                <option value="ppt">PPT</option>
                                                <option value="pptx">PPTX</option>
                                                <option value="csv">CSV</option>
                                                <option value="txt">TXT</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label" style="font-size: 0.85rem;">intitle:</label>
                                            <input type="text" class="form-control form-control-sm" x-model="advIntitle" placeholder="{{ __('Mots dans le titre') }}" aria-label="intitle:">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label" style="font-size: 0.85rem;">inurl:</label>
                                            <input type="text" class="form-control form-control-sm" x-model="advInurl" placeholder="{{ __('Mots dans l\'URL') }}" aria-label="inurl:">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label" style="font-size: 0.85rem;">{{ __('Expression exacte') }} ("")</label>
                                            <input type="text" class="form-control form-control-sm" x-model="advExact" placeholder="{{ __('Ex: intelligence artificielle') }}" aria-label="{{ __('Expression exacte') }}">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label" style="font-size: 0.85rem;">{{ __('Exclure') }} (-)</label>
                                            <input type="text" class="form-control form-control-sm" x-model="advExclude" placeholder="{{ __('Mots à exclure, séparés par virgule') }}" aria-label="{{ __('Exclure des mots') }}">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label" style="font-size: 0.85rem;">{{ __('Après le') }} (after:)</label>
                                            <input type="date" class="form-control form-control-sm" x-model="advDateAfter" aria-label="{{ __('Date après') }}">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label" style="font-size: 0.85rem;">{{ __('Avant le') }} (before:)</label>
                                            <input type="date" class="form-control form-control-sm" x-model="advDateBefore" aria-label="{{ __('Date avant') }}">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label" style="font-size: 0.85rem;">related:</label>
                                            <input type="text" class="form-control form-control-sm" x-model="advRelated" placeholder="{{ __('ex: wikipedia.org') }}" aria-label="related:">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label" style="font-size: 0.85rem;">define:</label>
                                            <input type="text" class="form-control form-control-sm" x-model="advDefine" placeholder="{{ __('ex: intelligence artificielle') }}" aria-label="define:">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label" style="font-size: 0.85rem;">{{ __('Plage numérique') }} (..)</label>
                                            <div class="d-flex gap-1">
                                                <input type="number" class="form-control form-control-sm" x-model="advRangeMin" placeholder="100" aria-label="{{ __('Plage minimum') }}">
                                                <span class="align-self-center">..</span>
                                                <input type="number" class="form-control form-control-sm" x-model="advRangeMax" placeholder="500" aria-label="{{ __('Plage maximum') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label" style="font-size: 0.85rem;">AROUND(X)</label>
                                            <input type="number" class="form-control form-control-sm" x-model.number="advAround" min="0" max="50" placeholder="0" aria-label="AROUND">
                                        </div>
                                        <div class="col-md-4 mb-2 d-flex align-items-end">
                                            <button class="btn btn-sm btn-outline-secondary w-100" @click="clearAdvanced()" style="border-radius: var(--r-btn);">{{ __('Réinitialiser') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </details>

                            {{-- Previsualisation de la requete --}}
                            <div class="p-3 rounded mb-3" x-show="builtQuery" style="background: var(--c-primary-light); font-family: monospace; font-size: 0.9rem; word-break: break-all;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <span x-text="builtQuery"></span>
                                    <button class="btn btn-sm" @click="copyUrl(builtQuery)" style="background: var(--c-primary); color: #fff; font-size: 0.7rem; white-space: nowrap; margin-left: 8px;" x-text="copied === builtQuery ? '{{ __('Copié !') }}' : '{{ __('Copier') }}'"></button>
                                </div>
                            </div>

                            {{-- Grille des 12 services --}}
                            <template x-if="searchLinks.length > 0">
                                <div class="row">
                                    <template x-for="link in searchLinks" :key="link.label">
                                        <div class="col-sm-6 col-md-4 mb-3">
                                            <div class="p-3 rounded d-flex align-items-center justify-content-between" style="background: var(--c-primary-light);">
                                                <a :href="link.url" target="_blank" rel="noopener" style="color: var(--c-primary); text-decoration: none; font-size: 0.9rem;">
                                                    <span x-text="link.icon"></span> <span x-text="link.label"></span>
                                                </a>
                                                <div class="d-flex gap-1">
                                                    <a :href="link.url" target="_blank" rel="noopener" class="btn btn-sm" style="background: var(--c-accent); color: #fff; font-size: 0.7rem;">{{ __('Ouvrir') }}</a>
                                                    <button class="btn btn-sm" @click="copyUrl(link.url)" style="background: var(--c-primary); color: #fff; font-size: 0.7rem;" x-text="copied === link.url ? '{{ __('Copié !') }}' : '{{ __('Copier') }}'"></button>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        {{-- Onglet 2 : Transformateur --}}
                        <div x-show="tab === 'transform'" x-transition>
                            <div class="input-group mb-3">
                                <input type="text" class="form-control form-control-lg" x-model="googleUrl" @input="detectUrl()" @paste="setTimeout(detectUrl.bind($data), 100)" placeholder="{{ __('Collez un lien Google (Docs, Sheets, Slides, Drive, YouTube...)') }}" aria-label="{{ __('Lien Google à transformer') }}">
                                <span class="input-group-btn">
                                    <button class="btn btn-lg" @click="googleUrl = ''; detected = null; transforms = []; selectedTransform = null; showPdfOptions = false;" style="background: #DC2626; color: #fff; border-radius: 0 var(--r-btn) var(--r-btn) 0;" x-show="googleUrl">&#10005;</button>
                                </span>
                            </div>

                            <div class="alert alert-success" x-show="detected" x-transition>
                                <span x-text="detected?.icon" style="font-size: 1.3em;"></span>
                                <strong x-text="detected?.label"></strong> {{ __('détecté') }} —
                                <span x-text="transforms.length + ' {{ __('options disponibles') }}'"></span>
                            </div>
                            <div class="alert alert-warning" x-show="!detected && googleUrl.length > 10" x-transition>
                                {{ __('Type de lien non reconnu. Collez un lien Google Docs, Sheets, Slides, Forms, Drive ou YouTube.') }}
                            </div>

                            {{-- Grille de boutons cliquables --}}
                            <template x-if="transforms.length > 0">
                                <div>
                                    <template x-if="transforms.length > 0 && transforms[0].cat">
                                        <div class="mb-3">
                                            <template x-for="cat in ['Exporter', 'Partager', 'Données', 'Navigation']" :key="cat">
                                                <div class="mb-2" x-show="transforms.filter(function(t) { return t.cat === cat; }).length > 0">
                                                    <small class="text-muted d-block mb-1" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;" x-text="cat"></small>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <template x-for="(t, ti) in transforms.filter(function(t) { return t.cat === cat; })" :key="cat + '-' + ti">
                                                            <button class="btn btn-sm" @click="if (t.name.indexOf('PDF') !== -1 && detected && detected.type === 'google_sheets') { showPdfOptions = !showPdfOptions; selectedTransform = t; } else { showPdfOptions = false; selectedTransform = t; }"
                                                                    :style="selectedTransform && selectedTransform.name === t.name ? 'background: var(--c-primary); color: #fff;' : 'background: #f8f9fa; color: var(--c-dark);'"
                                                                    style="border-radius: var(--r-btn); font-size: 0.8rem; border: 1px solid #dee2e6;">
                                                                <span x-text="t.icon"></span> <span x-text="t.name"></span>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="transforms.length > 0 && !transforms[0].cat">
                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            <template x-for="(t, ti) in transforms" :key="t.name">
                                                <button class="btn btn-sm" @click="showPdfOptions = false; selectedTransform = t;"
                                                        :style="selectedTransform && selectedTransform.name === t.name ? 'background: var(--c-primary); color: #fff;' : 'background: #f8f9fa; color: var(--c-dark);'"
                                                        style="border-radius: var(--r-btn); font-size: 0.8rem; border: 1px solid #dee2e6;">
                                                    <span x-text="t.icon"></span> <span x-text="t.name"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- Panneau options PDF avancées --}}
                                    <div x-show="showPdfOptions" x-transition style="background: #f8f9fa; border-radius: var(--r-btn); padding: 15px; margin-bottom: 15px; border: 1px solid #dee2e6;">
                                        <h3 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); margin-bottom: 12px; font-size: 1rem;">{{ __('Options d\'export PDF') }}</h3>
                                        <div class="row">
                                            <div class="col-md-4 mb-2">
                                                <label style="font-size: 0.85rem; color: var(--c-dark);">{{ __('Format papier') }}</label>
                                                <select class="form-control form-control-sm" x-model="pdfSize">
                                                    <option value="0">Letter (8.5 x 11)</option>
                                                    <option value="1">Tabloid (11 x 17)</option>
                                                    <option value="2">Legal (8.5 x 14)</option>
                                                    <option value="6">A3 (297 x 420 mm)</option>
                                                    <option value="7">A4 (210 x 297 mm)</option>
                                                    <option value="8">A5 (148 x 210 mm)</option>
                                                    <option value="9">B4 (250 x 353 mm)</option>
                                                    <option value="10">B5 (176 x 250 mm)</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label style="font-size: 0.85rem; color: var(--c-dark);">{{ __('Orientation') }}</label><br>
                                                <div class="d-flex gap-1">
                                                    <button class="btn btn-sm" @click="pdfPortrait = true" :style="pdfPortrait ? 'background: var(--c-primary); color: #fff;' : ''" style="border-radius: var(--r-btn);">{{ __('Portrait') }}</button>
                                                    <button class="btn btn-sm" @click="pdfPortrait = false" :style="!pdfPortrait ? 'background: var(--c-primary); color: #fff;' : ''" style="border-radius: var(--r-btn);">{{ __('Paysage') }}</button>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label style="font-size: 0.85rem; color: var(--c-dark);">{{ __('Échelle') }}</label>
                                                <select class="form-control form-control-sm" x-model="pdfScale">
                                                    <option value="1">{{ __('Normal (100 %)') }}</option>
                                                    <option value="2">{{ __('Ajuster à la largeur') }}</option>
                                                    <option value="3">{{ __('Ajuster à la hauteur') }}</option>
                                                    <option value="4">{{ __('Ajuster à la page') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-2">
                                                <label style="font-size: 0.85rem; color: var(--c-dark);">{{ __('Numéros de page') }}</label>
                                                <select class="form-control form-control-sm" x-model="pdfPagenum">
                                                    <option value="UNDEFINED">{{ __('Aucun') }}</option>
                                                    <option value="CENTER">{{ __('Centre') }}</option>
                                                    <option value="LEFT">{{ __('Gauche') }}</option>
                                                    <option value="RIGHT">{{ __('Droite') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label style="font-size: 0.85rem; color: var(--c-dark);">{{ __('Marges') }}</label>
                                                <div class="d-flex gap-1 flex-wrap">
                                                    <button class="btn btn-sm" @click="pdfMargins = 'normal'" :style="pdfMargins === 'normal' ? 'background: var(--c-primary); color: #fff;' : ''" style="border-radius: var(--r-btn); font-size: 0.75rem;">{{ __('Normal') }}</button>
                                                    <button class="btn btn-sm" @click="pdfMargins = 'narrow'" :style="pdfMargins === 'narrow' ? 'background: var(--c-primary); color: #fff;' : ''" style="border-radius: var(--r-btn); font-size: 0.75rem;">{{ __('Étroit') }}</button>
                                                    <button class="btn btn-sm" @click="pdfMargins = 'wide'" :style="pdfMargins === 'wide' ? 'background: var(--c-primary); color: #fff;' : ''" style="border-radius: var(--r-btn); font-size: 0.75rem;">{{ __('Large') }}</button>
                                                    <button class="btn btn-sm" @click="pdfMargins = 'none'" :style="pdfMargins === 'none' ? 'background: var(--c-primary); color: #fff;' : ''" style="border-radius: var(--r-btn); font-size: 0.75rem;">{{ __('Sans') }}</button>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label style="font-size: 0.85rem; color: var(--c-dark);">{{ __('Ordre des pages') }}</label>
                                                <div class="d-flex gap-1">
                                                    <button class="btn btn-sm" @click="pdfPageorder = '1'" :style="pdfPageorder === '1' ? 'background: var(--c-primary); color: #fff;' : ''" style="border-radius: var(--r-btn); font-size: 0.75rem;">{{ __('Bas puis droite') }}</button>
                                                    <button class="btn btn-sm" @click="pdfPageorder = '2'" :style="pdfPageorder === '2' ? 'background: var(--c-primary); color: #fff;' : ''" style="border-radius: var(--r-btn); font-size: 0.75rem;">{{ __('Droite puis bas') }}</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-2">
                                                <label style="font-size: 0.85rem; color: var(--c-dark);">{{ __('Feuille spécifique (GID)') }}</label>
                                                <input type="text" class="form-control form-control-sm" x-model="pdfGid" placeholder="{{ __('ex: 0') }}">
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label style="font-size: 0.85rem; color: var(--c-dark);">{{ __('Plage de cellules') }}</label>
                                                <input type="text" class="form-control form-control-sm" x-model="pdfRange" placeholder="{{ __('ex: A1:D10') }}">
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label style="font-size: 0.85rem; color: var(--c-dark);">{{ __('Alignement') }}</label>
                                                <div class="d-flex gap-1">
                                                    <select class="form-control form-control-sm" x-model="pdfHalign" style="flex:1;">
                                                        <option value="LEFT">{{ __('Gauche') }}</option>
                                                        <option value="CENTER">{{ __('Centre') }}</option>
                                                        <option value="RIGHT">{{ __('Droite') }}</option>
                                                    </select>
                                                    <select class="form-control form-control-sm" x-model="pdfValign" style="flex:1;">
                                                        <option value="TOP">{{ __('Haut') }}</option>
                                                        <option value="MIDDLE">{{ __('Milieu') }}</option>
                                                        <option value="BOTTOM">{{ __('Bas') }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-wrap gap-3 mt-2 mb-3" style="font-size: 0.85rem;">
                                            <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer;"><input type="checkbox" x-model="pdfGridlines" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;"> {{ __('Quadrillage') }}</label>
                                            <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer;"><input type="checkbox" x-model="pdfFitw" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;"> {{ __('Ajuster largeur') }}</label>
                                            <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer;"><input type="checkbox" x-model="pdfPrinttitle" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;"> {{ __('Titre du document') }}</label>
                                            <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer;"><input type="checkbox" x-model="pdfSheetnames" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;"> {{ __('Noms des feuilles') }}</label>
                                            <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer;"><input type="checkbox" x-model="pdfFzr" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;"> {{ __('Lignes figées') }}</label>
                                            <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer;"><input type="checkbox" x-model="pdfFzc" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;"> {{ __('Colonnes figées') }}</label>
                                        </div>
                                        <div class="d-flex gap-2 align-items-center">
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-sm" @click="pdfAttachment = false" :style="!pdfAttachment ? 'background: var(--c-primary); color: #fff;' : ''" style="border-radius: var(--r-btn); font-size: 0.8rem;">{{ __('Ouvrir') }}</button>
                                                <button class="btn btn-sm" @click="pdfAttachment = true" :style="pdfAttachment ? 'background: var(--c-primary); color: #fff;' : ''" style="border-radius: var(--r-btn); font-size: 0.8rem;">{{ __('Télécharger') }}</button>
                                            </div>
                                            <button class="btn btn-sm flex-fill" @click="applyPdfOptions()" style="background: var(--c-accent); color: #fff; border-radius: var(--r-btn); font-family: var(--f-heading); font-weight: 700;">{{ __('Générer le lien PDF personnalisé') }}</button>
                                        </div>
                                    </div>

                                    {{-- Résultat unique --}}
                                    <template x-if="selectedTransform">
                                        <div class="p-4 rounded text-center" style="background: var(--c-primary-light); border: 2px solid var(--c-primary);">
                                            <div class="mb-2">
                                                <span x-text="selectedTransform.icon" style="font-size: 1.5em;"></span>
                                                <strong style="font-size: 1.1rem;" x-text="selectedTransform.name"></strong>
                                            </div>
                                            <p class="text-muted small mb-2" x-text="selectedTransform.desc"></p>
                                            <div class="p-2 rounded mb-3" style="background: #fff; border: 1px solid #dee2e6; word-break: break-all; font-size: 0.8rem; font-family: monospace; cursor: text;" @click="$event.target.select ? null : null; copyUrl(selectedTransform.url)" x-text="selectedTransform.url"></div>
                                            <div class="d-flex justify-content-center gap-2">
                                                <button class="btn" @click="copyUrl(selectedTransform.url)" style="background: var(--c-primary); color: #fff; border-radius: var(--r-btn); font-family: var(--f-heading); font-weight: 700;" x-text="copied === selectedTransform.url ? '{{ __('Copié !') }}' : '{{ __('Copier le lien') }}'"></button>
                                                <a :href="selectedTransform.url" target="_blank" rel="noopener" class="btn" style="background: var(--c-accent); color: #fff; border-radius: var(--r-btn); font-family: var(--f-heading); font-weight: 700;">{{ __('Ouvrir le lien') }}</a>
                                                <button type="button" class="btn" style="background:#1A1D23;color:#fff;border-radius:var(--r-btn);font-family:var(--f-heading);font-weight:700" @click="localStorage.setItem('pendingShortUrl', selectedTransform.url); window.location.href='/raccourcir'">✂️ {{ __('Raccourcir avec veille.la') }}</button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <template x-if="transformHistory.length > 0">
                                <div class="mt-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h3 style="color: var(--c-dark); margin: 0; font-size: 1rem; font-family: var(--f-heading); font-weight: 700;">{{ __('Historique récent') }}</h3>
                                        <button class="btn btn-sm btn-outline-danger" @click="transformHistory = []; localStorage.removeItem('glh')" style="font-size: 0.7rem;">{{ __('Effacer') }}</button>
                                    </div>
                                    <template x-for="(h, hi) in transformHistory" :key="h.timestamp">
                                        <div class="p-2 mb-1 rounded small d-flex justify-content-between align-items-center" style="background: #f8f9fa;">
                                            <div style="cursor: pointer; flex: 1;" @click="googleUrl = h.url; detectUrl();">
                                                <span x-text="h.icon"></span> <span x-text="h.type"></span> -
                                                <span class="text-muted" x-text="h.url.substring(0, 55) + '...'"></span>
                                            </div>
                                            <button class="btn btn-sm btn-outline-danger" @click.stop="transformHistory.splice(hi, 1); localStorage.setItem('glh', JSON.stringify(transformHistory))" style="font-size: 0.6rem; padding: 1px 5px;">✕</button>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        {{-- Onglet 3 : Dorks éducatifs --}}
                        <div x-show="tab === 'dorks'" x-transition>
                            <p class="text-muted mb-3">{{ __('Cliquez sur un dork pour pré-remplir la recherche avancée. Ces requêtes utilisent les opérateurs Google pour trouver du contenu éducatif spécifique.') }}</p>

                            <template x-for="(cat, ci) in dorkCategories" :key="ci">
                                <div class="mb-4">
                                    <h3 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); font-size: 1rem; margin-bottom: 0.5rem;" x-text="cat.name"></h3>
                                    <div class="d-flex flex-wrap gap-2">
                                        <template x-for="(d, di) in cat.dorks" :key="di">
                                            <button class="btn btn-sm" @click="loadDork(d)" style="border-radius: var(--r-btn); font-size: 0.8rem; text-align: left; background: #f8f9fa; border: 1px solid #dee2e6; color: var(--c-dark);">
                                                <strong x-text="d.name"></strong>
                                                <br><small style="color: #6c757d; font-family: monospace; font-size: 0.7rem;" x-text="d.query.substring(0, 50) + (d.query.length > 50 ? '...' : '')"></small>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Historique des recherches --}}
                        <template x-if="searchHistory.length > 0">
                            <div class="mt-4 pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h3 style="font-family: var(--f-heading); font-weight: 700; margin: 0; font-size: 1rem;">{{ __('Historique des recherches') }} (<span x-text="searchHistory.length"></span>)</h6>
                                    <button class="btn btn-sm btn-outline-danger" @click="searchHistory = []; localStorage.removeItem('gl_searches')" style="font-size: 0.7rem;">{{ __('Effacer') }}</button>
                                </div>
                                <template x-for="(h, i) in searchHistory" :key="i">
                                    <div class="p-2 mb-1 rounded small d-flex justify-content-between align-items-center" style="background: #f8f9fa; cursor: pointer;" @click="query = h.query; generateSearch();">
                                        <span style="font-family: monospace;" x-text="h.query.substring(0, 70) + (h.query.length > 70 ? '...' : '')"></span>
                                        <small class="text-muted" x-text="h.date"></small>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Modale aide --}}
<div class="modal fade" id="helpModal" tabindex="-1" role="dialog" aria-labelledby="helpModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: var(--r-base);">
            <div class="modal-header" style="background: var(--c-primary); border-radius: var(--r-base) var(--r-base) 0 0;">
                <h4 class="modal-title" id="helpModalLabel" style="color: #fff; font-family: var(--f-heading); font-weight: 700;">{{ __('Comment utiliser cet outil') }}</h4>
                <button type="button" onclick="jQuery('#helpModal').modal('hide')" aria-label="{{ __('Fermer') }}" style="background: none; border: none; color: #fff !important; opacity: 1; font-size: 1.5rem; font-weight: 700; cursor: pointer; float: right;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <h4 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); border-bottom: 2px solid var(--c-primary); padding-bottom: 0.5rem;">{{ __('Transformateur de liens') }}</h4>
                <p>{{ __('Collez n\'importe quel lien Google et obtenez des transformations instantanées. Types pris en charge :') }}</p>
                <ul>
                    <li><strong>Google Sheets</strong> — {{ __('16 options : export Excel, CSV, PDF (A4, lettre, avec/sans grille), ODS, TSV, HTML, copie, aperçu, publication web, flux CSV public, vue mobile, navigation par feuille ou cellule.') }}</li>
                    <li><strong>Google Docs</strong> — {{ __('9 options : export PDF, Word, texte, HTML, EPUB, copie, édition, intégration iframe.') }}</li>
                    <li><strong>Google Slides</strong> — {{ __('7 options : présentation plein écran, export PDF/PowerPoint, copie, aperçu, intégration slideshow.') }}</li>
                    <li><strong>YouTube</strong> — {{ __('9 options : intégration avec autoplay/boucle, miniatures HD/HQ/MQ/SD, lien court, horodatages.') }}</li>
                    <li><strong>{{ __('Aussi') }}</strong> — Google Forms, Drive, Drawings, Colab, Maps.</li>
                </ul>
                <p class="text-muted" style="font-size: 0.85rem;">{{ __('Cliquez sur un bouton pour générer un seul lien, puis copiez-le ou ouvrez-le directement.') }}</p>

                <h4 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); border-bottom: 2px solid var(--c-primary); padding-bottom: 0.5rem; margin-top: 1.5rem;">{{ __('Recherche avancée') }}</h4>
                <p>{{ __('Construisez des recherches Google puissantes avec les opérateurs avancés :') }}</p>
                <ul>
                    <li><code>site:</code> — {{ __('rechercher uniquement sur un site précis') }}</li>
                    <li><code>filetype:</code> — {{ __('trouver des fichiers spécifiques (PDF, DOCX, CSV...)') }}</li>
                    <li><code>intitle:</code> — {{ __('les mots doivent apparaître dans le titre de la page') }}</li>
                    <li><code>inurl:</code> — {{ __('les mots doivent apparaître dans l\'URL') }}</li>
                    <li><code>""</code> — {{ __('correspondance exacte (expression entre guillemets)') }}</li>
                    <li><code>-</code> — {{ __('exclure un mot des résultats') }}</li>
                    <li><code>after: / before:</code> — {{ __('filtrer par date') }}</li>
                    <li><code>related:</code> — {{ __('trouver des sites similaires') }}</li>
                    <li><code>define:</code> — {{ __('obtenir la définition d\'un mot') }}</li>
                    <li><code>AROUND(X)</code> — {{ __('deux mots doivent être proches l\'un de l\'autre (X mots max)') }}</li>
                </ul>
                <p class="text-muted" style="font-size: 0.85rem;">{{ __('Les préréglages remplissent automatiquement les champs pour des recherches courantes. 12 services Google sont générés simultanément.') }}</p>

                <h4 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); border-bottom: 2px solid var(--c-primary); padding-bottom: 0.5rem; margin-top: 1.5rem;">{{ __('Dorks éducatifs') }}</h4>
                <p>{{ __('Des requêtes de recherche pré-construites, organisées par catégorie, pour trouver rapidement du contenu éducatif et de recherche. Cliquez sur un dork pour le lancer automatiquement dans la recherche avancée.') }}</p>
                <p>{{ __('Catégories disponibles : recherche académique, données et statistiques, ressources pédagogiques, intelligence artificielle, SEO et marketing, cybersécurité éducative.') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="jQuery('#helpModal').modal('hide')" style="background: var(--c-primary); color: #fff; border-radius: var(--r-btn);">{{ __('Compris !') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('googleLinksTool', function() {
        return {
            tab: 'transform',
            query: '',
            copied: '',
            searchLinks: [],

            // Opérateurs avancés
            advSite: '', advFiletype: '', advIntitle: '', advInurl: '',
            advExclude: '', advExact: '', advDateAfter: '', advDateBefore: '',
            advAround: 0, advAroundTerm: '',
            advRelated: '', advDefine: '', advRangeMin: '', advRangeMax: '',

            // Transformateur
            googleUrl: '', detected: null, transforms: [], selectedTransform: null,

            // Options PDF avancées
            showPdfOptions: false,
            pdfSize: '0', pdfPortrait: true, pdfGridlines: false, pdfFitw: true,
            pdfPagenum: 'UNDEFINED', pdfAttachment: true, pdfGid: '', pdfRange: '',
            pdfScale: '1', pdfPageorder: '1', pdfPrinttitle: true, pdfSheetnames: true,
            pdfFzr: true, pdfFzc: true, pdfHalign: 'LEFT', pdfValign: 'TOP', pdfMargins: 'normal',
            transformHistory: JSON.parse(localStorage.getItem('glh') || '[]'),
            searchHistory: JSON.parse(localStorage.getItem('gl_searches') || '[]'),

            // Presets
            presetsSearch: [
                { name: 'PDFs académiques', site: '.edu', filetype: 'pdf', intitle: '', exclude: '' },
                { name: 'Données gouvernementales QC/CA', site: '.gc.ca OR .gouv.qc.ca', filetype: 'csv', intitle: '', exclude: '' },
                { name: 'Présentations', site: '', filetype: 'ppt', intitle: '', exclude: '' },
                { name: 'Articles scientifiques', site: '', filetype: 'pdf', intitle: 'abstract', exclude: 'wikipedia' },
                { name: 'Données ouvertes', site: 'donneesquebec.ca OR ouvert.canada.ca', filetype: 'csv', intitle: '', exclude: '' },
                { name: 'Ressources éducatives', site: '.edu OR .ac.uk', filetype: '', intitle: 'cours OR syllabus', exclude: '' },
                { name: 'Opportunités guest post', site: '', filetype: '', intitle: '"write for us" OR "guest post"', exclude: '' },
                { name: 'Rapports récents', site: '', filetype: 'pdf', intitle: 'rapport', exclude: '' }
            ],

            // Dorks éducatifs
            dorkCategories: [
                {
                    name: 'Recherche académique',
                    dorks: [
                        { name: 'Thèses et mémoires', query: 'filetype:pdf intitle:"memoire" OR intitle:"these" site:.edu' },
                        { name: 'Articles avec résumé', query: 'filetype:pdf intext:"abstract" intext:"conclusion" intext:"references"' },
                        { name: 'Revues de littérature', query: 'filetype:pdf intitle:"literature review" OR intitle:"revue de litterature"' },
                        { name: 'Études de cas', query: 'filetype:pdf intitle:"case study" OR intitle:"etude de cas"' }
                    ]
                },
                {
                    name: 'Données et statistiques',
                    dorks: [
                        { name: 'Jeux de données CSV', query: 'filetype:csv inurl:data OR inurl:dataset' },
                        { name: 'Données gouvernementales QC', query: 'site:gouv.qc.ca filetype:pdf OR filetype:csv "statistiques"' },
                        { name: 'Données ouvertes Canada', query: 'site:ouvert.canada.ca OR site:open.canada.ca filetype:csv' },
                        { name: 'Rapports annuels', query: 'filetype:pdf intitle:"rapport annuel" after:2024-01-01' }
                    ]
                },
                {
                    name: 'Ressources pédagogiques',
                    dorks: [
                        { name: 'Plans de cours', query: 'filetype:pdf intitle:"plan de cours" OR intitle:"syllabus"' },
                        { name: 'Exercices et examens', query: 'filetype:pdf intitle:"exercices" OR intitle:"examen" site:.edu' },
                        { name: 'Guides pédagogiques', query: 'filetype:pdf intitle:"guide pedagogique" OR intitle:"guide de l\'enseignant"' },
                        { name: 'Présentations de cours', query: 'filetype:ppt OR filetype:pptx intitle:"cours" OR intitle:"chapitre"' }
                    ]
                },
                {
                    name: 'Intelligence artificielle',
                    dorks: [
                        { name: 'Articles IA récents', query: 'filetype:pdf "artificial intelligence" OR "machine learning" after:2025-01-01' },
                        { name: 'Tutoriels IA', query: 'intitle:"tutorial" OR intitle:"tutoriel" "deep learning" OR "neural network" filetype:pdf' },
                        { name: 'Éthique de l\'IA', query: 'filetype:pdf intitle:"AI ethics" OR intitle:"ethique" "intelligence artificielle"' },
                        { name: 'IA en éducation', query: 'filetype:pdf "AI in education" OR "IA en education" after:2024-01-01' }
                    ]
                },
                {
                    name: 'SEO et marketing',
                    dorks: [
                        { name: 'Pages indexées d\'un site', query: 'site:example.com' },
                        { name: 'Pages similaires', query: 'related:example.com' },
                        { name: 'Opportunités guest post', query: 'intitle:"write for us" OR intitle:"guest post" OR intitle:"contribuer"' },
                        { name: 'Infographies', query: 'intitle:infographic OR intitle:infographie filetype:pdf' },
                        { name: 'Mentions sans lien', query: '"votre marque" -site:votre-site.com -link:votre-site.com' }
                    ]
                },
                {
                    name: 'Cybersécurité éducative',
                    dorks: [
                        { name: 'Fichiers exposés', query: 'intitle:"index of" filetype:pdf OR filetype:xls' },
                        { name: 'Pages de connexion', query: 'intitle:"login" inurl:admin' },
                        { name: 'Répertoires ouverts', query: 'intitle:"index of" "parent directory"' },
                        { name: 'Documents confidentiels', query: 'intitle:"confidentiel" OR intitle:"ne pas distribuer" filetype:pdf' },
                        { name: 'Fichiers de config exposés', query: 'filetype:env OR filetype:yml "password" OR "secret"' }
                    ]
                }
            ],

            get builtQuery() {
                var parts = [];
                if (this.advExact) parts.push('"' + this.advExact + '"');
                if (this.query) parts.push(this.query);
                if (this.advSite) parts.push('site:' + this.advSite);
                if (this.advFiletype) parts.push('filetype:' + this.advFiletype);
                if (this.advIntitle) parts.push('intitle:' + this.advIntitle);
                if (this.advInurl) parts.push('inurl:' + this.advInurl);
                if (this.advRelated) parts.push('related:' + this.advRelated);
                if (this.advDefine) parts.push('define:' + this.advDefine);
                if (this.advExclude) {
                    var excludes = this.advExclude.split(',');
                    for (var i = 0; i < excludes.length; i++) {
                        var word = excludes[i].trim();
                        if (word) parts.push('-' + word);
                    }
                }
                if (this.advDateAfter) parts.push('after:' + this.advDateAfter);
                if (this.advDateBefore) parts.push('before:' + this.advDateBefore);
                if (this.advRangeMin && this.advRangeMax) parts.push(this.advRangeMin + '..' + this.advRangeMax);
                if (this.advAround > 0 && this.advAroundTerm) {
                    parts.push('AROUND(' + this.advAround + ')');
                    parts.push(this.advAroundTerm);
                }
                return parts.join(' ');
            },

            generateSearch: function() {
                var built = this.builtQuery;
                if (!built.trim()) return;
                var q = encodeURIComponent(built);
                this.searchLinks = [
                    { label: 'Google', url: 'https://www.google.com/search?q=' + q, icon: '🔍' },
                    { label: 'Images', url: 'https://www.google.com/search?tbm=isch&q=' + q, icon: '🖼' },
                    { label: 'Maps', url: 'https://www.google.com/maps/search/' + q, icon: '🗺' },
                    { label: 'Traduction', url: 'https://translate.google.com/?sl=auto&tl=fr&text=' + q, icon: '🌐' },
                    { label: 'Actualités', url: 'https://news.google.com/search?q=' + q, icon: '📰' },
                    { label: 'YouTube', url: 'https://www.youtube.com/results?search_query=' + q, icon: '🎬' },
                    { label: 'Scholar', url: 'https://scholar.google.com/scholar?q=' + q, icon: '📚' },
                    { label: 'Books', url: 'https://books.google.com/books?q=' + q, icon: '📖' },
                    { label: 'Patents', url: 'https://patents.google.com/?q=' + q, icon: '📜' },
                    { label: 'Trends', url: 'https://trends.google.com/trends/explore?q=' + encodeURIComponent(this.query || built), icon: '📈' },
                    { label: 'Finance', url: 'https://www.google.com/finance?q=' + q, icon: '💹' },
                    { label: 'Shopping', url: 'https://www.google.com/search?tbm=shop&q=' + q, icon: '🛒' }
                ];
                this.addSearchToHistory(built);
            },

            loadSearchPreset: function(preset) {
                this.advSite = preset.site || '';
                this.advFiletype = preset.filetype || '';
                this.advIntitle = preset.intitle || '';
                this.advExclude = preset.exclude || '';
            },

            clearAdvanced: function() {
                this.advSite = ''; this.advFiletype = ''; this.advIntitle = ''; this.advInurl = '';
                this.advExclude = ''; this.advExact = ''; this.advDateAfter = ''; this.advDateBefore = '';
                this.advAround = 0; this.advAroundTerm = '';
                this.advRelated = ''; this.advDefine = ''; this.advRangeMin = ''; this.advRangeMax = '';
            },

            loadDork: function(dork) {
                this.tab = 'search';
                this.query = dork.query;
                this.clearAdvanced();
                this.generateSearch();
            },

            addSearchToHistory: function(queryText) {
                var entry = { query: queryText, date: new Date().toLocaleString('fr-CA') };
                this.searchHistory = [entry].concat(this.searchHistory.slice(0, 9));
                localStorage.setItem('gl_searches', JSON.stringify(this.searchHistory));
            },

            // Transformateur
            detectUrl: function() {
                this.detected = null;
                this.transforms = [];
                this.selectedTransform = null;
                if (!this.googleUrl || this.googleUrl.length < 10) return;

                var patterns = [
                    { type: 'google_docs', regex: /docs\.google\.com\/document\/d\/([a-zA-Z0-9-_]+)/, icon: '📄', label: 'Google Docs' },
                    { type: 'google_sheets', regex: /docs\.google\.com\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/, icon: '📊', label: 'Google Sheets' },
                    { type: 'google_slides', regex: /docs\.google\.com\/presentation\/d\/([a-zA-Z0-9-_]+)/, icon: '🎯', label: 'Google Slides' },
                    { type: 'google_forms', regex: /docs\.google\.com\/forms\/d\/([a-zA-Z0-9-_]+)/, icon: '📝', label: 'Google Forms' },
                    { type: 'google_draw', regex: /docs\.google\.com\/drawings\/d\/([a-zA-Z0-9-_]+)/, icon: '🎨', label: 'Google Drawings' },
                    { type: 'google_drive', regex: /drive\.google\.com\/(?:file\/d\/|open\?id=)([a-zA-Z0-9-_]+)/, icon: '💾', label: 'Google Drive' },
                    { type: 'youtube', regex: /(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9-_]{11})/, icon: '🎬', label: 'YouTube' },
                    { type: 'google_colab', regex: /colab\.research\.google\.com\/drive\/([a-zA-Z0-9-_]+)/, icon: '🐍', label: 'Google Colab' },
                    { type: 'google_maps', regex: /google\.\w+\/maps\/place\/([^\/?]+)/, icon: '📍', label: 'Google Maps' }
                ];

                for (var i = 0; i < patterns.length; i++) {
                    var p = patterns[i];
                    var match = this.googleUrl.match(p.regex);
                    if (match) {
                        this.detected = { type: p.type, icon: p.icon, label: p.label, id: match[1] };
                        this.buildTransforms(p.type, match[1]);
                        if (this.transforms.length > 0) this.selectedTransform = this.transforms[0];
                        this.addTransformHistory(p.icon, p.label);
                        break;
                    }
                }
            },

            buildTransforms: function(type, id) {
                var base_doc = 'https://docs.google.com/document/d/' + id;
                var base_sheet = 'https://docs.google.com/spreadsheets/d/' + id;
                var base_slide = 'https://docs.google.com/presentation/d/' + id;
                var base_form = 'https://docs.google.com/forms/d/' + id;
                var base_draw = 'https://docs.google.com/drawings/d/' + id;

                var map = {
                    google_docs: [
                        { icon: '👁', name: 'Aperçu', desc: 'Visualiser sans menus', url: base_doc + '/preview' },
                        { icon: '📄', name: 'Export PDF', desc: 'Télécharger en PDF', url: base_doc + '/export?format=pdf' },
                        { icon: '📝', name: 'Export Word', desc: 'Télécharger en DOCX', url: base_doc + '/export?format=docx' },
                        { icon: '📃', name: 'Export texte brut', desc: 'Télécharger en TXT', url: base_doc + '/export?format=txt' },
                        { icon: '🌐', name: 'Export HTML', desc: 'Télécharger en HTML', url: base_doc + '/export?format=html' },
                        { icon: '📚', name: 'Export EPUB', desc: 'Télécharger en EPUB', url: base_doc + '/export?format=epub' },
                        { icon: '📋', name: 'Créer une copie', desc: 'Dupliquer dans votre Drive', url: base_doc + '/copy' },
                        { icon: '✏', name: 'Mode édition', desc: 'Ouvrir en édition', url: base_doc + '/edit' },
                        { icon: '🖼', name: 'Intégration iframe', desc: 'Embed dans un site web', url: base_doc + '/pub?embedded=true' }
                    ],
                    google_sheets: [
                        // Exporter
                        { icon: '📊', name: 'Excel (XLSX)', desc: 'Télécharger au format Microsoft Excel', url: base_sheet + '/export?format=xlsx', cat: 'Exporter' },
                        { icon: '📝', name: 'CSV', desc: 'Valeurs séparées par virgules', url: base_sheet + '/export?format=csv', cat: 'Exporter' },
                        { icon: '📝', name: 'CSV feuille active', desc: 'Exporter la feuille active en CSV', url: base_sheet + '/export?format=csv&gid=0', cat: 'Exporter' },
                        { icon: '📄', name: 'Export PDF', desc: 'PDF personnalisable (format, orientation, marges...)', url: base_sheet + '/export?format=pdf', cat: 'Exporter' },
                        { icon: '📚', name: 'ODS', desc: 'Format OpenDocument', url: base_sheet + '/export?format=ods', cat: 'Exporter' },
                        { icon: '🔣', name: 'TSV', desc: 'Valeurs séparées par tabulations', url: base_sheet + '/export?format=tsv', cat: 'Exporter' },
                        { icon: '🌐', name: 'HTML (zip)', desc: 'Page web compressée', url: base_sheet + '/export?format=zip', cat: 'Exporter' },
                        // Partager
                        { icon: '👁', name: 'Aperçu', desc: 'Visualiser sans édition', url: base_sheet + '/preview', cat: 'Partager' },
                        { icon: '📋', name: 'Créer une copie', desc: 'Dupliquer dans votre Drive', url: base_sheet + '/copy', cat: 'Partager' },
                        { icon: '📃', name: 'Utiliser comme modèle', desc: 'Ouvrir comme modèle à copier', url: base_sheet + '/template/preview', cat: 'Partager' },
                        { icon: '🖥', name: 'Publication web', desc: 'Publier comme page web', url: base_sheet + '/pub?output=html', cat: 'Partager' },
                        { icon: '📌', name: 'Intégration iframe', desc: 'Code embed pour un site', url: base_sheet + '/pubhtml?widget=true', cat: 'Partager' },
                        // Données
                        { icon: '📊', name: 'Flux CSV public', desc: 'Flux de données pour applis', url: base_sheet + '/gviz/tq?tqx=out:csv', cat: 'Données' },
                        { icon: '📱', name: 'Vue mobile', desc: 'Version mobile', url: base_sheet + '/mobilebasic', cat: 'Données' },
                        { icon: '➡', name: 'Feuille 1', desc: 'Aller à la première feuille', url: base_sheet + '/edit#gid=0', cat: 'Navigation' },
                        { icon: '📍', name: 'Cellule A1', desc: 'Aller à la cellule A1', url: base_sheet + '/edit#gid=0&range=A1', cat: 'Navigation' }
                    ],
                    google_slides: [
                        { icon: '🎯', name: 'Mode présentation', desc: 'Lancer le diaporama plein écran', url: base_slide + '/present' },
                        { icon: '📄', name: 'Export PDF', desc: 'Télécharger en PDF', url: base_slide + '/export/pdf' },
                        { icon: '🎞', name: 'Export PowerPoint', desc: 'Télécharger en PPTX', url: base_slide + '/export/pptx' },
                        { icon: '📋', name: 'Créer une copie', desc: 'Dupliquer dans votre Drive', url: base_slide + '/copy' },
                        { icon: '👁', name: 'Aperçu', desc: 'Visualiser sans édition', url: base_slide + '/preview' },
                        { icon: '🖼', name: 'Intégration slideshow', desc: 'Embed dans un site web', url: base_slide + '/embed' },
                        { icon: '1️⃣', name: 'Diapositive 1', desc: 'Commencer à la première diapositive', url: base_slide + '/present#slide=id.p1' }
                    ],
                    google_forms: [
                        { icon: '👁', name: 'Aperçu', desc: 'Visualiser le formulaire', url: base_form + '/viewform' },
                        { icon: '📝', name: 'Lien pré-rempli', desc: 'Formulaire pré-rempli', url: base_form + '/viewform?usp=pp_url' },
                        { icon: '📋', name: 'Créer une copie', desc: 'Dupliquer ce formulaire', url: base_form + '/copy' }
                    ],
                    google_draw: [
                        { icon: '📄', name: 'Export PDF', desc: 'Télécharger en PDF', url: base_draw + '/export/pdf' },
                        { icon: '🖼', name: 'Export PNG', desc: 'Télécharger en PNG', url: base_draw + '/export/png' },
                        { icon: '🎨', name: 'Export SVG', desc: 'Télécharger en SVG', url: base_draw + '/export/svg' },
                        { icon: '📋', name: 'Créer une copie', desc: 'Dupliquer dans votre Drive', url: base_draw + '/copy' },
                        { icon: '👁', name: 'Aperçu', desc: 'Visualiser sans édition', url: base_draw + '/preview' }
                    ],
                    google_drive: [
                        { icon: '⬇', name: 'Téléchargement direct', desc: 'Lien de téléchargement', url: 'https://drive.google.com/uc?export=download&id=' + id },
                        { icon: '👁', name: 'Aperçu', desc: 'Visualiser le fichier', url: 'https://drive.google.com/file/d/' + id + '/preview' },
                        { icon: '📋', name: 'Créer une copie', desc: 'Dupliquer dans votre Drive', url: 'https://drive.google.com/file/d/' + id + '/copy' }
                    ],
                    youtube: [
                        { icon: '🎬', name: 'Intégration embed', desc: 'URL d\'intégration iframe', url: 'https://www.youtube.com/embed/' + id },
                        { icon: '🔁', name: 'Embed autoplay + boucle', desc: 'Intégration avec lecture auto et boucle', url: 'https://www.youtube.com/embed/' + id + '?autoplay=1&loop=1&playlist=' + id },
                        { icon: '🖼', name: 'Miniature HD', desc: 'Image haute qualité (1280x720)', url: 'https://img.youtube.com/vi/' + id + '/maxresdefault.jpg' },
                        { icon: '🖼', name: 'Miniature HQ', desc: 'Image haute qualité (480x360)', url: 'https://img.youtube.com/vi/' + id + '/hqdefault.jpg' },
                        { icon: '🖼', name: 'Miniature MQ', desc: 'Image moyenne qualité (320x180)', url: 'https://img.youtube.com/vi/' + id + '/mqdefault.jpg' },
                        { icon: '🖼', name: 'Miniature SD', desc: 'Image standard (120x90)', url: 'https://img.youtube.com/vi/' + id + '/sddefault.jpg' },
                        { icon: '🔗', name: 'Lien court', desc: 'URL raccourcie youtu.be', url: 'https://youtu.be/' + id },
                        { icon: '⏱', name: 'Début à 0:30', desc: 'Lien avec timestamp 30 secondes', url: 'https://www.youtube.com/watch?v=' + id + '&t=30s' },
                        { icon: '⏱', name: 'Début à 1:00', desc: 'Lien avec timestamp 1 minute', url: 'https://www.youtube.com/watch?v=' + id + '&t=60s' }
                    ],
                    google_colab: [
                        { icon: '👁', name: 'Aperçu', desc: 'Visualiser le notebook', url: 'https://colab.research.google.com/drive/' + id },
                        { icon: '📋', name: 'Créer une copie', desc: 'Copier dans votre Drive', url: 'https://colab.research.google.com/drive/' + id + '#scrollTo=copy' },
                        { icon: '🐍', name: 'Ouvrir dans Colab', desc: 'Ouvrir directement dans Colab', url: 'https://colab.research.google.com/drive/' + id }
                    ],
                    google_maps: [
                        { icon: '🖼', name: 'Intégration iframe', desc: 'Embed Google Maps dans un site', url: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1000!2d0!3d0!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s' + id + '!2s!5e0' },
                        { icon: '🧭', name: 'Itinéraire', desc: 'Obtenir un itinéraire vers ce lieu', url: 'https://www.google.com/maps/dir//' + decodeURIComponent(id) },
                        { icon: '🛰', name: 'Vue satellite', desc: 'Voir en mode satellite', url: 'https://www.google.com/maps/place/' + id + '/@0,0,500m/data=!3m1!1e3' }
                    ]
                };
                this.transforms = map[type] || [];
            },

            buildPdfUrl: function(baseUrl) {
                var url = baseUrl;
                url += '&size=' + this.pdfSize;
                url += '&portrait=' + this.pdfPortrait;
                url += '&gridlines=' + this.pdfGridlines;
                url += '&fitw=' + this.pdfFitw;
                url += '&pagenum=' + this.pdfPagenum;
                url += '&attachment=' + this.pdfAttachment;
                url += '&printtitle=' + this.pdfPrinttitle;
                url += '&sheetnames=' + this.pdfSheetnames;
                url += '&fzr=' + this.pdfFzr;
                url += '&fzc=' + this.pdfFzc;
                url += '&horizontal_alignment=' + this.pdfHalign;
                url += '&vertical_alignment=' + this.pdfValign;
                url += '&pageorder=' + this.pdfPageorder;
                url += '&scale=' + this.pdfScale;
                if (this.pdfGid) url += '&gid=' + this.pdfGid;
                if (this.pdfRange) url += '&range=' + encodeURIComponent(this.pdfRange);
                var margins = { 'normal': '0.75', 'narrow': '0.25', 'wide': '1.0', 'none': '0' };
                var m = margins[this.pdfMargins] || '0.75';
                url += '&top_margin=' + m + '&bottom_margin=' + m + '&left_margin=' + m + '&right_margin=' + m;
                return url;
            },

            applyPdfOptions: function() {
                if (!this.detected || !this.detected.id) return;
                var baseUrl = 'https://docs.google.com/spreadsheets/d/' + this.detected.id + '/export?format=pdf';
                var url = this.buildPdfUrl(baseUrl);
                this.selectedTransform = { icon: '📄', name: 'Export PDF personnalisé', desc: 'PDF avec vos options', url: url };
            },

            copyUrl: function(url) {
                var self = this;
                navigator.clipboard.writeText(url).then(function() {
                    self.copied = url;
                    setTimeout(function() { self.copied = ''; }, 2000);
                });
            },

            addTransformHistory: function(icon, label) {
                var entry = { url: this.googleUrl, type: label, icon: icon, timestamp: Date.now() };
                this.transformHistory = [entry].concat(this.transformHistory.slice(0, 4));
                localStorage.setItem('glh', JSON.stringify(this.transformHistory));
            }
        };
    });
});
</script>
@endpush
