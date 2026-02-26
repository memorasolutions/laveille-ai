@extends('backoffice::layouts.admin', ['title' => 'Paramètres', 'subtitle' => 'Modifier'])

@section('content')

<div class="card" x-data="{
    selectedType: '{{ old('type', $setting->type ?? 'string') }}',
    selectedGroup: '{{ old('group', in_array($setting->group, ['general', 'mail', 'seo', 'branding']) ? $setting->group : '__custom') }}',
    customGroup: '{{ old('custom_group', !in_array($setting->group, ['general', 'mail', 'seo', 'branding']) ? $setting->group : '') }}'
}">
    <div class="card-header">
        <h6 class="mb-0">Modifier le paramètre : {{ $setting->key }}</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.settings.update', $setting) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row gy-3">
                {{-- Catégorie --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold text-primary-light text-sm mb-8">Catégorie</label>
                    <select name="group" x-model="selectedGroup"
                            class="form-select radius-8 @error('group') is-invalid @enderror">
                        <option value="general">Général</option>
                        <option value="mail">Courriel</option>
                        <option value="seo">SEO</option>
                        <option value="branding">Marque</option>
                        <option value="__custom">Autre...</option>
                    </select>
                    <small class="text-muted">Dans quel groupe ranger ce paramètre</small>
                    @error('group') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Groupe personnalisé --}}
                <div class="col-md-6" x-show="selectedGroup === '__custom'" x-cloak>
                    <label class="form-label fw-semibold text-primary-light text-sm mb-8">Nom du groupe</label>
                    <input type="text" name="custom_group" x-model="customGroup"
                           placeholder="ex: analytics, social"
                           class="form-control radius-8">
                </div>

                {{-- Identifiant unique --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold text-primary-light text-sm mb-8">Identifiant unique <span class="text-danger-main">*</span></label>
                    <input type="text" name="key" value="{{ old('key', $setting->key) }}" required
                           placeholder="ex: site_name, contact_email"
                           pattern="\S+"
                           title="Pas d'espaces autorisés"
                           class="form-control radius-8 @error('key') is-invalid @enderror">
                    <small class="text-muted">Nom technique du paramètre (sans espaces)</small>
                    @error('key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Type de valeur --}}
                <div class="col-12">
                    <label class="form-label fw-semibold text-primary-light text-sm mb-8">Type de valeur</label>
                    <div class="d-flex flex-wrap gap-2">
                        <label class="d-flex align-items-center gap-2 border radius-8 px-16 py-8 cursor-pointer"
                               :class="selectedType === 'string' ? 'border-primary-600 bg-primary-50 text-primary-600' : 'border-neutral-200 text-secondary-light'">
                            <input type="radio" name="type" value="string" x-model="selectedType" class="d-none">
                            <iconify-icon icon="solar:text-linear" class="text-lg"></iconify-icon>
                            Texte
                        </label>
                        <label class="d-flex align-items-center gap-2 border radius-8 px-16 py-8 cursor-pointer"
                               :class="selectedType === 'boolean' ? 'border-primary-600 bg-primary-50 text-primary-600' : 'border-neutral-200 text-secondary-light'">
                            <input type="radio" name="type" value="boolean" x-model="selectedType" class="d-none">
                            <iconify-icon icon="solar:check-circle-linear" class="text-lg"></iconify-icon>
                            Oui/Non
                        </label>
                        <label class="d-flex align-items-center gap-2 border radius-8 px-16 py-8 cursor-pointer"
                               :class="selectedType === 'integer' ? 'border-primary-600 bg-primary-50 text-primary-600' : 'border-neutral-200 text-secondary-light'">
                            <input type="radio" name="type" value="integer" x-model="selectedType" class="d-none">
                            <iconify-icon icon="solar:hashtag-linear" class="text-lg"></iconify-icon>
                            Nombre
                        </label>
                        <label class="d-flex align-items-center gap-2 border radius-8 px-16 py-8 cursor-pointer"
                               :class="selectedType === 'json' ? 'border-primary-600 bg-primary-50 text-primary-600' : 'border-neutral-200 text-secondary-light'">
                            <input type="radio" name="type" value="json" x-model="selectedType" class="d-none">
                            <iconify-icon icon="solar:code-linear" class="text-lg"></iconify-icon>
                            JSON
                        </label>
                    </div>
                    @error('type') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                {{-- Valeur - adapté au type --}}
                <div class="col-12">
                    <label class="form-label fw-semibold text-primary-light text-sm mb-8">Valeur</label>

                    {{-- Texte --}}
                    <div x-show="selectedType === 'string'" x-cloak>
                        <input type="text" name="value" value="{{ old('value', $setting->value) }}"
                               placeholder="Saisissez la valeur..."
                               class="form-control radius-8">
                    </div>

                    {{-- Booléen --}}
                    <div x-show="selectedType === 'boolean'" x-cloak>
                        <div class="form-check form-switch">
                            <input type="hidden" name="value" x-bind:value="selectedType === 'boolean' ? ($refs.boolToggle?.checked ? 'true' : 'false') : ''">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   x-ref="boolToggle" {{ old('value', $setting->value) === 'true' ? 'checked' : '' }}>
                            <label class="form-check-label" x-text="$refs.boolToggle?.checked ? 'Activé' : 'Désactivé'">Désactivé</label>
                        </div>
                    </div>

                    {{-- Nombre --}}
                    <div x-show="selectedType === 'integer'" x-cloak>
                        <input type="number" name="value" value="{{ old('value', $setting->value) }}"
                               placeholder="0"
                               class="form-control radius-8">
                    </div>

                    {{-- JSON --}}
                    <div x-show="selectedType === 'json'" x-cloak>
                        <textarea name="value" rows="4"
                                  placeholder='{"cle": "valeur"}'
                                  class="form-control radius-8 font-monospace" style="font-size: 13px;">{{ old('value', $setting->value) }}</textarea>
                    </div>

                    @error('value') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                {{-- Description --}}
                <div class="col-12">
                    <label class="form-label fw-semibold text-primary-light text-sm mb-8">Description</label>
                    <input type="text" name="description" value="{{ old('description', $setting->description) }}"
                           placeholder="Ex: Nom affiché du site web"
                           class="form-control radius-8">
                    <small class="text-muted">Aide-mémoire pour comprendre ce paramètre</small>
                    @error('description') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                {{-- Visible publiquement --}}
                <div class="col-12">
                    <div class="border radius-8 p-16 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fw-semibold text-sm">Visible publiquement</span>
                            <br>
                            <small class="text-muted">Rend ce paramètre accessible sans authentification</small>
                        </div>
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_public" value="0">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   name="is_public" value="1" {{ old('is_public', $setting->is_public) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-3 mt-24">
                <button type="submit" class="btn btn-primary-600">Enregistrer</button>
                <a href="{{ route('admin.settings.index') }}" class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-40 py-11 radius-8">Annuler</a>
            </div>
        </form>
    </div>
</div>

@endsection
