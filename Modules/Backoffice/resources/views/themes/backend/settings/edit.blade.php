<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Paramètres', 'subtitle' => 'Modifier'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">{{ __('Paramètres') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Modifier') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
        <i data-lucide="settings" class="icon-md text-primary"></i>{{ __('Modifier le paramètre :') }} <code class="text-primary fs-5">{{ $setting->key }}</code>
    </h4>
    <a href="{{ route('admin.settings.index') }}" class="btn btn-sm btn-light d-inline-flex align-items-center gap-2">
        <i data-lucide="arrow-left"></i> {{ __('Retour') }}
    </a>
</div>

<div class="card" x-data="{
    selectedType: '{{ old('type', $setting->type ?? 'string') }}',
    selectedGroup: '{{ old('group', in_array($setting->group, ['general', 'mail', 'seo', 'branding']) ? $setting->group : '__custom') }}',
    customGroup: '{{ old('custom_group', !in_array($setting->group, ['general', 'mail', 'seo', 'branding']) ? $setting->group : '') }}'
}">
    <div class="card-body">
        <form action="{{ route('admin.settings.update', $setting) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-4">

                {{-- Catégorie --}}
                <div class="col-md-6">
                    <label class="form-label fw-medium">Catégorie</label>
                    <select name="group" x-model="selectedGroup"
                            class="form-select @error('group') is-invalid @enderror">
                        <option value="general">Général</option>
                        <option value="mail">Courriel</option>
                        <option value="seo">SEO</option>
                        <option value="branding">Marque</option>
                        <option value="__custom">Autre...</option>
                    </select>
                    <div class="form-text">Dans quel groupe ranger ce paramètre</div>
                    @error('group')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Groupe personnalisé --}}
                <div class="col-md-6" x-show="selectedGroup === '__custom'" x-cloak>
                    <label class="form-label fw-medium">Nom du groupe</label>
                    <input type="text" name="custom_group" x-model="customGroup"
                           placeholder="ex: analytics, social"
                           class="form-control">
                </div>

                {{-- Identifiant unique --}}
                <div class="col-md-6">
                    <label class="form-label fw-medium">
                        Identifiant unique <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="key" value="{{ old('key', $setting->key) }}" required
                           placeholder="ex: site_name, contact_email"
                           pattern="\S+"
                           title="Pas d'espaces autorisés"
                           class="form-control @error('key') is-invalid @enderror">
                    <div class="form-text">Nom technique du paramètre (sans espaces)</div>
                    @error('key')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Type de valeur --}}
                <div class="col-12">
                    <label class="form-label fw-medium">Type de valeur</label>
                    <div class="d-flex flex-wrap gap-2">
                        <label class="d-inline-flex align-items-center gap-2 border rounded px-3 py-2"
                               style="cursor:pointer;"
                               :class="selectedType === 'string' ? 'border-primary bg-primary bg-opacity-10 text-primary' : 'border text-body'">
                            <input type="radio" name="type" value="string" x-model="selectedType" class="d-none">
                            <i data-lucide="type" style="width:16px;height:16px;"></i>
                            Texte
                        </label>
                        <label class="d-inline-flex align-items-center gap-2 border rounded px-3 py-2"
                               style="cursor:pointer;"
                               :class="selectedType === 'boolean' ? 'border-primary bg-primary bg-opacity-10 text-primary' : 'border text-body'">
                            <input type="radio" name="type" value="boolean" x-model="selectedType" class="d-none">
                            <i data-lucide="toggle-left" style="width:16px;height:16px;"></i>
                            Oui/Non
                        </label>
                        <label class="d-inline-flex align-items-center gap-2 border rounded px-3 py-2"
                               style="cursor:pointer;"
                               :class="selectedType === 'integer' ? 'border-primary bg-primary bg-opacity-10 text-primary' : 'border text-body'">
                            <input type="radio" name="type" value="integer" x-model="selectedType" class="d-none">
                            <i data-lucide="hash" style="width:16px;height:16px;"></i>
                            Nombre
                        </label>
                        <label class="d-inline-flex align-items-center gap-2 border rounded px-3 py-2"
                               style="cursor:pointer;"
                               :class="selectedType === 'json' ? 'border-primary bg-primary bg-opacity-10 text-primary' : 'border text-body'">
                            <input type="radio" name="type" value="json" x-model="selectedType" class="d-none">
                            <i data-lucide="braces" style="width:16px;height:16px;"></i>
                            JSON
                        </label>
                    </div>
                    @error('type')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Valeur - adapté au type --}}
                <div class="col-12">
                    <label class="form-label fw-medium">Valeur</label>

                    {{-- Texte --}}
                    <div x-show="selectedType === 'string'" x-cloak>
                        <input type="text" name="value" value="{{ old('value', $setting->value) }}"
                               placeholder="Saisissez la valeur..."
                               class="form-control">
                    </div>

                    {{-- Booléen --}}
                    <div x-show="selectedType === 'boolean'" x-cloak>
                        <div class="form-check form-switch d-flex align-items-center gap-3">
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
                               class="form-control">
                    </div>

                    {{-- JSON --}}
                    <div x-show="selectedType === 'json'" x-cloak>
                        <textarea name="value" rows="4"
                                  placeholder='{"cle": "valeur"}'
                                  class="form-control font-monospace">{{ old('value', $setting->value) }}</textarea>
                    </div>

                    @error('value')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Description --}}
                <div class="col-12">
                    <label class="form-label fw-medium">Description</label>
                    <input type="text" name="description" value="{{ old('description', $setting->description) }}"
                           placeholder="Ex: Nom affiché du site web"
                           class="form-control @error('description') is-invalid @enderror">
                    <div class="form-text">Aide-mémoire pour comprendre ce paramètre</div>
                    @error('description')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Visible publiquement --}}
                <div class="col-12">
                    <div class="border rounded p-3 d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <span class="fw-medium">Visible publiquement</span>
                            <p class="text-muted small mb-0 mt-1">Rend ce paramètre accessible sans authentification</p>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input type="hidden" name="is_public" value="0">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   name="is_public" value="1" {{ old('is_public', $setting->is_public) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>

            </div>

            <div class="d-flex align-items-center gap-3 mt-4">
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <i data-lucide="save"></i> Enregistrer
                </button>
                <a href="{{ route('admin.settings.index') }}" class="btn btn-sm btn-light d-inline-flex align-items-center gap-2">
                    <i data-lucide="x"></i> Annuler
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
