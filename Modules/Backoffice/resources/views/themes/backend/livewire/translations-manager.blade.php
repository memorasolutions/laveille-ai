<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    {{-- Header --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <h6 class="fw-semibold text-dark d-flex align-items-center gap-2 mb-0">
                <i data-lucide="languages" class="icon-sm text-primary"></i>
                {{ __('Traductions') }}
            </h6>
            <span class="badge bg-light text-muted border">
                {{ $translatedCount }}/{{ $totalCount }} {{ __('traduites') }}
            </span>
        </div>
        {{-- Toolbar: actions primaire + secondaires --}}
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button"
                    class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2"
                    data-bs-toggle="modal" data-bs-target="#addKeyModal"
                    title="{{ __('Ajouter une nouvelle clé') }}">
                <i data-lucide="plus" class="icon-sm"></i>
                {{ __('Ajouter une clé') }}
            </button>

            {{-- Dropdown "Plus" via Alpine.js --}}
            <div class="position-relative" x-data="{ open: false }" @click.outside="open = false">
                <button @click="open = !open"
                        class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2">
                    <i data-lucide="more-vertical" class="icon-sm"></i>
                    {{ __('Plus') }}
                    <i data-lucide="chevron-down" class="icon-sm"></i>
                </button>
                <div x-show="open" x-cloak
                     class="position-absolute end-0 bg-white border rounded shadow mt-1 py-1"
                     style="z-index:50; min-width:180px;">
                    <button type="button"
                            class="dropdown-item d-flex align-items-center gap-2"
                            wire:click="autoTranslateAll"
                            wire:loading.attr="disabled"
                            wire:target="autoTranslateAll"
                            @click="open = false">
                        <i data-lucide="wand-2" class="icon-sm text-primary"></i>
                        <span wire:loading.remove wire:target="autoTranslateAll">{{ __('Traduire tout (IA)') }}</span>
                        <span wire:loading wire:target="autoTranslateAll">{{ __('Traduction en cours...') }}</span>
                    </button>
                    <hr class="dropdown-divider">
                    <button type="button"
                            class="dropdown-item d-flex align-items-center gap-2"
                            data-bs-toggle="modal" data-bs-target="#addLocaleModal"
                            @click="open = false">
                        <i data-lucide="globe" class="icon-sm text-info"></i>
                        {{ __('Ajouter une langue') }}
                    </button>
                    <button type="button"
                            class="dropdown-item d-flex align-items-center gap-2"
                            wire:click="exportLocale"
                            @click="open = false">
                        <i data-lucide="download" class="icon-sm text-success"></i>
                        {{ __('Exporter') }}
                    </button>
                    <label class="dropdown-item d-flex align-items-center gap-2 mb-0" role="button">
                        <i data-lucide="upload" class="icon-sm text-warning"></i>
                        {{ __('Importer') }}
                        <input type="file" class="d-none" wire:model="importFile" accept=".json" aria-label="{{ __('Fichier JSON à importer') }}">
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div style="width:150px;">
                    <label for="targetLocale" class="visually-hidden">{{ __('Langue cible') }}</label>
                    <select class="form-select form-select-sm"
                            id="targetLocale" wire:model.live="targetLocale" aria-label="{{ __('Langue cible') }}">
                        @foreach($locales as $locale)
                            @if($locale !== 'fr')
                                <option value="{{ $locale }}">{{ strtoupper($locale) }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="flex-grow-1">
                    <label for="translationSearch" class="visually-hidden">{{ __('Rechercher') }}</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">
                            <i data-lucide="search" class="icon-sm text-muted"></i>
                        </span>
                        <input type="text"
                               class="form-control"
                               id="translationSearch"
                               wire:model.live.debounce.300ms="search"
                               placeholder="{{ __('Rechercher une clé ou traduction...') }}"
                               aria-label="{{ __('Rechercher une clé ou traduction') }}">
                    </div>
                </div>
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox"
                           id="showUntranslatedOnly" wire:model.live="showUntranslatedOnly">
                    <label class="form-check-label text-muted" for="showUntranslatedOnly">
                        {{ __('Non traduites uniquement') }}
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- Progress bar --}}
    @if($totalCount > 0)
        <div class="mb-4">
            <div class="d-flex justify-content-between mb-1">
                <small class="text-muted">{{ __('Progression') }}</small>
                <small class="fw-medium text-dark">{{ $progressPercentage }}%</small>
            </div>
            <div class="progress" style="height:6px;"
                 role="progressbar" aria-valuenow="{{ $progressPercentage }}" aria-valuemin="0" aria-valuemax="100"
                 aria-label="{{ __('Progression des traductions') }}">
                <div class="progress-bar bg-success"
                     style="width: {{ $progressPercentage }}%"></div>
            </div>
        </div>
    @endif

    {{-- Table --}}
    @if(count($translations) > 0)
        <div class="card border overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="table-layout: fixed;">
                    <thead class="table-light">
                        <tr>
                            <th class="fw-medium py-3 px-3" style="width: 25%">{{ __('Clé') }}</th>
                            <th class="fw-medium py-3 px-3" style="width: 32%">{{ __('Source (FR)') }}</th>
                            <th class="fw-medium py-3 px-3" style="width: 32%">{{ __('Traduction') }} ({{ strtoupper($targetLocale) }})</th>
                            <th class="fw-medium py-3 px-3 text-center" style="width: 11%">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($translations as $key => $translation)
                            <tr wire:key="row-{{ md5($key) }}">
                                <td class="py-2 px-3 align-middle" style="max-width: 0; overflow: hidden;">
                                    <code class="small text-muted bg-light px-2 py-1 rounded d-block"
                                          style="word-break: break-word; overflow-wrap: anywhere; white-space: normal;">{{ $key }}</code>
                                </td>
                                <td class="py-2 px-3 align-middle" style="max-width: 0; overflow: hidden;">
                                    <input type="text"
                                           class="form-control form-control-sm bg-light text-muted"
                                           value="{{ $translation['source'] }}"
                                           readonly
                                           aria-label="{{ __('Valeur source FR pour') }} {{ $key }}">
                                </td>
                                <td class="py-2 px-3 align-middle" style="max-width: 0; overflow: hidden;">
                                    <input type="text"
                                           class="form-control form-control-sm"
                                           value="{{ $translation['target'] }}"
                                           wire:blur="updateTranslation('{{ addslashes($key) }}', $event.target.value)"
                                           aria-label="{{ __('Traduction') }} {{ strtoupper($targetLocale) }} {{ __('pour') }} {{ $key }}">
                                </td>
                                <td class="py-2 px-3 align-middle">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        @if($translation['target'] === '')
                                            <button type="button"
                                                    class="btn btn-sm btn-primary bg-opacity-10 text-primary border-0 rounded-circle d-flex align-items-center justify-content-center p-0"
                                                    style="width:32px;height:32px;"
                                                    wire:click="autoTranslate('{{ addslashes($key) }}')"
                                                    wire:loading.attr="disabled"
                                                    wire:target="autoTranslate('{{ addslashes($key) }}')"
                                                    title="{{ __("Traduire automatiquement avec l'IA") }}">
                                                <i data-lucide="wand-2" class="icon-sm"
                                                   wire:loading.class="d-none"
                                                   wire:target="autoTranslate('{{ addslashes($key) }}')"></i>
                                                <span class="spinner-border spinner-border-sm d-none"
                                                      wire:loading.class.remove="d-none"
                                                      wire:target="autoTranslate('{{ addslashes($key) }}')"></span>
                                            </button>
                                        @endif
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center p-0 border-0"
                                                style="width:24px;height:24px;"
                                                wire:click="deleteKey('{{ addslashes($key) }}')"
                                                wire:confirm="{{ __('Supprimer cette clé de toutes les langues ?') }}"
                                                title="{{ __('Supprimer la clé') }} {{ $key }}">
                                            <i data-lucide="trash-2" class="icon-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        @if($lastPage > 1)
            <div class="d-flex align-items-center justify-content-between mt-3">
                <span class="text-muted small">
                    {{ __('Page') }} {{ $currentPage }} {{ __('sur') }} {{ $lastPage }} ({{ $totalFiltered }} {{ __('résultats') }})
                </span>
                <div class="d-flex gap-2">
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center p-0"
                            style="width:32px;height:32px;"
                            wire:click="previousPage"
                            @if($currentPage <= 1) disabled @endif
                            title="{{ __('Page précédente') }}">
                        <i data-lucide="chevron-left" class="icon-sm"></i>
                    </button>
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center p-0"
                            style="width:32px;height:32px;"
                            wire:click="nextPage"
                            @if($currentPage >= $lastPage) disabled @endif
                            title="{{ __('Page suivante') }}">
                        <i data-lucide="chevron-right" class="icon-sm"></i>
                    </button>
                </div>
            </div>
        @endif
    @else
        {{-- Empty state --}}
        <div class="card border">
            <div class="card-body py-5 text-center">
                <i data-lucide="languages" class="text-muted mb-3 d-block" style="width:48px;height:48px;margin:0 auto;"></i>
                <p class="text-muted mb-0">
                    @if($search || $showUntranslatedOnly)
                        {{ __('Aucun résultat pour vos critères de recherche.') }}
                    @else
                        {{ __('Aucune traduction disponible.') }}
                    @endif
                </p>
            </div>
        </div>
    @endif

    {{-- Modal : Ajouter une clé --}}
    <div class="modal fade" id="addKeyModal" tabindex="-1" aria-labelledby="addKeyModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content border shadow">
                <div class="modal-header border-bottom py-3 px-4">
                    <h6 class="modal-title fw-semibold text-dark" id="addKeyModalLabel">{{ __('Ajouter une clé de traduction') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Fermer') }}"></button>
                </div>
                <div class="modal-body py-4 px-4">
                    <div class="mb-3">
                        <label for="newKey" class="form-label fw-medium text-dark">{{ __('Clé') }}</label>
                        <input type="text" class="form-control"
                               id="newKey" wire:model="newKey" placeholder="{{ __('ex: Message de bienvenue') }}">
                    </div>
                    <div class="mb-3">
                        <label for="newSourceValue" class="form-label fw-medium text-dark">{{ __('Valeur FR') }}</label>
                        <input type="text" class="form-control"
                               id="newSourceValue" wire:model="newSourceValue" placeholder="{{ __('ex: Bienvenue') }}">
                    </div>
                    <div>
                        <label for="newTargetValue" class="form-label fw-medium text-dark">{{ __('Traduction') }} ({{ strtoupper($targetLocale) }})</label>
                        <input type="text" class="form-control"
                               id="newTargetValue" wire:model="newTargetValue" placeholder="{{ __('ex: Bienvenue') }}">
                    </div>
                </div>
                <div class="modal-footer border-top py-3 px-4 d-flex justify-content-end gap-2">
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary"
                            data-bs-dismiss="modal">{{ __('Annuler') }}</button>
                    <button type="button"
                            class="btn btn-sm btn-primary"
                            wire:click="addKey" data-bs-dismiss="modal">{{ __('Ajouter') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal : Ajouter une langue --}}
    <div class="modal fade" id="addLocaleModal" tabindex="-1" aria-labelledby="addLocaleModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content border shadow">
                <div class="modal-header border-bottom py-3 px-4">
                    <h6 class="modal-title fw-semibold text-dark" id="addLocaleModalLabel">{{ __('Ajouter une langue') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Fermer') }}"></button>
                </div>
                <div class="modal-body py-4 px-4">
                    <div>
                        <label for="newLocaleInput" class="form-label fw-medium text-dark">{{ __('Code locale (ISO 639-1)') }}</label>
                        <input type="text"
                               class="form-control"
                               id="newLocaleInput" wire:model="newLocale" placeholder="{{ __('ex: es, de, pt') }}" maxlength="2">
                        <small class="text-muted mt-1 d-block">{{ __('Code à 2 lettres uniquement.') }}</small>
                    </div>
                </div>
                <div class="modal-footer border-top py-3 px-4 d-flex justify-content-end gap-2">
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary"
                            data-bs-dismiss="modal">{{ __('Annuler') }}</button>
                    <button type="button"
                            class="btn btn-sm btn-primary"
                            wire:click="addLocale" data-bs-dismiss="modal">{{ __('Ajouter') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>
