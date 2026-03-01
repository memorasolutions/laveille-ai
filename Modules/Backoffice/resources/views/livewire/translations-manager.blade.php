<div>
    {{-- Header --}}
    <div class="d-block d-md-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center gap-3 mb-3 mb-md-0">
            <h6 class="mb-0 d-flex align-items-center gap-2">
                <i data-lucide="languages"></i>
                Traductions
            </h6>
            <span class="badge bg-light border text-muted px-2 py-1 rounded-2">
                {{ $translatedCount }}/{{ $totalCount }} traduites
            </span>
        </div>
        {{-- Principe ADHD: 1 action primaire visible + actions secondaires dans dropdown --}}
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-primary d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addKeyModal" title="Ajouter une nouvelle clé">
                <i data-lucide="plus-circle"></i>
                Ajouter une clé
            </button>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i data-lucide="more-horizontal"></i>
                    Plus
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <button type="button"
                                class="dropdown-item d-flex align-items-center gap-2"
                                wire:click="autoTranslateAll"
                                wire:loading.attr="disabled"
                                wire:target="autoTranslateAll">
                            <i data-lucide="wand-2"></i>
                            <span wire:loading.remove wire:target="autoTranslateAll">Traduire tout (IA)</span>
                            <span wire:loading wire:target="autoTranslateAll">Traduction en cours...</span>
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addLocaleModal">
                            <i data-lucide="globe"></i>
                            Ajouter une langue
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2" wire:click="exportLocale">
                            <i data-lucide="download"></i>
                            Exporter
                        </button>
                    </li>
                    <li>
                        <label class="dropdown-item d-flex align-items-center gap-2 mb-0" role="button">
                            <i data-lucide="upload"></i>
                            Importer
                            <input type="file" class="d-none" wire:model="importFile" accept=".json" aria-label="Fichier JSON à importer">
                        </label>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card h-100 p-0 mb-3">
        <div class="card-body py-3 px-4">
            <div class="row g-3 align-items-center">
                <div class="col-md-3">
                    <label for="targetLocale" class="visually-hidden">Langue cible</label>
                    <select class="form-select form-select-sm" id="targetLocale" wire:model.live="targetLocale" aria-label="Langue cible">
                        @foreach($locales as $locale)
                            @if($locale !== 'fr')
                                <option value="{{ $locale }}">{{ strtoupper($locale) }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="translationSearch" class="visually-hidden">Rechercher</label>
                    <input type="text" class="form-control form-control-sm" id="translationSearch" wire:model.live.debounce.300ms="search" placeholder="Rechercher..." aria-label="Rechercher une clé ou traduction">
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="showUntranslatedOnly" wire:model.live="showUntranslatedOnly">
                        <label class="form-check-label text-sm text-muted" for="showUntranslatedOnly">
                            Non traduites uniquement
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Progress bar --}}
    @if($totalCount > 0)
        <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
                <small class="text-muted">Progression</small>
                <small class="text-muted">{{ $progressPercentage }}%</small>
            </div>
            <div class="progress" style="height: 6px;" role="progressbar" aria-valuenow="{{ $progressPercentage }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progression des traductions">
                <div class="progress-bar bg-success" style="width: {{ $progressPercentage }}%"></div>
            </div>
        </div>
    @endif

    {{-- Table --}}
    @if(count($translations) > 0)
        <div class="card h-100 p-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0" style="table-layout: fixed; width: 100%;">
                        <thead>
                            <tr>
                                <th class="py-2 px-3" style="width: 25%">Clé</th>
                                <th class="py-2 px-3" style="width: 32%">Source (FR)</th>
                                <th class="py-2 px-3" style="width: 32%">Traduction ({{ strtoupper($targetLocale) }})</th>
                                <th class="py-2 px-3 text-center" style="width: 11%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($translations as $key => $translation)
                                <tr wire:key="row-{{ md5($key) }}">
                                    <td class="py-2 px-3 align-middle" style="max-width: 0; overflow: hidden;">
                                        <code class="text-sm d-block" style="word-break: break-word; overflow-wrap: anywhere; white-space: normal;">{{ $key }}</code>
                                    </td>
                                    <td class="py-2 px-3 align-middle" style="max-width: 0; overflow: hidden;">
                                        <input type="text" class="form-control form-control-sm bg-light" value="{{ $translation['source'] }}" readonly aria-label="Valeur source FR pour {{ $key }}">
                                    </td>
                                    <td class="py-2 px-3 align-middle" style="max-width: 0; overflow: hidden;">
                                        <input type="text"
                                               class="form-control form-control-sm"
                                               value="{{ $translation['target'] }}"
                                               wire:blur="updateTranslation('{{ addslashes($key) }}', $event.target.value)"
                                               aria-label="Traduction {{ strtoupper($targetLocale) }} pour {{ $key }}">
                                    </td>
                                    <td class="py-2 px-3 align-middle text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            @if($translation['target'] === '')
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-primary d-flex justify-content-center align-items-center rounded-circle p-0"
                                                        style="width:32px;height:32px;"
                                                        wire:click="autoTranslate('{{ addslashes($key) }}')"
                                                        wire:loading.attr="disabled"
                                                        wire:target="autoTranslate('{{ addslashes($key) }}')"
                                                        title="Traduire automatiquement avec l'IA">
                                                    <i data-lucide="wand-2"
                                                       wire:loading.class="d-none"
                                                       wire:target="autoTranslate('{{ addslashes($key) }}')"></i>
                                                    <span class="spinner-border spinner-border-sm d-none"
                                                          wire:loading.class.remove="d-none"
                                                          wire:target="autoTranslate('{{ addslashes($key) }}')"></span>
                                                </button>
                                            @endif
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger d-flex justify-content-center align-items-center rounded-circle p-0"
                                                    style="width:32px;height:32px;"
                                                    wire:click="deleteKey('{{ addslashes($key) }}')"
                                                    wire:confirm="Supprimer cette clé de toutes les langues ?"
                                                    title="Supprimer la clé {{ $key }}">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pagination --}}
        @if($lastPage > 1)
            <div class="d-flex justify-content-between align-items-center mt-3">
                <span class="text-muted text-sm">
                    Page {{ $currentPage }} sur {{ $lastPage }} ({{ $totalFiltered }} résultats)
                </span>
                <div class="d-flex gap-2">
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary"
                            wire:click="previousPage"
                            @if($currentPage <= 1) disabled @endif
                            title="Page précédente">
                        <i data-lucide="chevron-left"></i>
                    </button>
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary"
                            wire:click="nextPage"
                            @if($currentPage >= $lastPage) disabled @endif
                            title="Page suivante">
                        <i data-lucide="chevron-right"></i>
                    </button>
                </div>
            </div>
        @endif
    @else
        {{-- Empty state --}}
        <div class="card h-100 p-0">
            <div class="card-body text-center py-5">
                <i data-lucide="languages" class="text-muted d-block mx-auto mb-2" style="width:40px;height:40px;"></i>
                <p class="text-muted mb-0">
                    @if($search || $showUntranslatedOnly)
                        Aucun résultat pour vos critères de recherche.
                    @else
                        Aucune traduction disponible.
                    @endif
                </p>
            </div>
        </div>
    @endif

    {{-- Modal : Ajouter une clé --}}
    <div class="modal fade" id="addKeyModal" tabindex="-1" aria-labelledby="addKeyModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-bottom py-3 px-4">
                    <h6 class="modal-title" id="addKeyModalLabel">Ajouter une clé de traduction</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body py-3 px-4">
                    <div class="mb-3">
                        <label for="newKey" class="form-label">Clé</label>
                        <input type="text" class="form-control" id="newKey" wire:model="newKey" placeholder="ex: Welcome message">
                    </div>
                    <div class="mb-3">
                        <label for="newSourceValue" class="form-label">Valeur FR</label>
                        <input type="text" class="form-control" id="newSourceValue" wire:model="newSourceValue" placeholder="ex: Bienvenue">
                    </div>
                    <div class="mb-0">
                        <label for="newTargetValue" class="form-label">Traduction ({{ strtoupper($targetLocale) }})</label>
                        <input type="text" class="form-control" id="newTargetValue" wire:model="newTargetValue" placeholder="ex: Welcome">
                    </div>
                </div>
                <div class="modal-footer border-top py-3 px-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" wire:click="addKey" data-bs-dismiss="modal">Ajouter</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal : Ajouter une langue --}}
    <div class="modal fade" id="addLocaleModal" tabindex="-1" aria-labelledby="addLocaleModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-bottom py-3 px-4">
                    <h6 class="modal-title" id="addLocaleModalLabel">Ajouter une langue</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body py-3 px-4">
                    <div class="mb-0">
                        <label for="newLocaleInput" class="form-label">Code locale (ISO 639-1)</label>
                        <input type="text" class="form-control" id="newLocaleInput" wire:model="newLocale" placeholder="ex: es, de, pt" maxlength="2">
                        <small class="text-muted">Code à 2 lettres uniquement.</small>
                    </div>
                </div>
                <div class="modal-footer border-top py-3 px-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" wire:click="addLocale" data-bs-dismiss="modal">Ajouter</button>
                </div>
            </div>
        </div>
    </div>
</div>
