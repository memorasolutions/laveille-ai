<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Shortcodes'), 'subtitle' => __('Nouveau')])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.shortcodes.index') }}">{{ __('Shortcodes') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Ajouter') }}</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header border-bottom py-3 px-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <h5 class="mb-0 fw-semibold">{{ __('Nouveau shortcode') }}</h5>
            <a href="{{ route('admin.shortcodes.index') }}" class="btn btn-sm btn-light d-inline-flex align-items-center gap-2">
                <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
                {{ __('Retour') }}
            </a>
        </div>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.shortcodes.store') }}" x-data="{
            tag: '{{ old('tag') }}',
            tpl: {{ json_encode(old('html_template', '')) }},
            params: '{{ old('parameters') }}',
            hasContent: {{ old('has_content', true) ? 'true' : 'false' }},
            get parsedParams() { try { return JSON.parse(this.params) || []; } catch { return []; } },
            get usage() {
                if (!this.tag) return '';
                let p = Array.isArray(this.parsedParams) ? this.parsedParams.map(k => k + '=&quot;...&quot;').join(' ') : '';
                let s = '[' + this.tag + (p ? ' ' + p : '') + ']';
                return this.hasContent ? s + 'contenu[/' + this.tag + ']' : s;
            }
        }">
            @csrf

            <div class="row g-4">

                <div class="col-12 col-md-6">
                    <label for="tag" class="form-label fw-medium">
                        Tag <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="tag" id="tag" x-model="tag"
                        class="form-control font-monospace @error('tag') is-invalid @enderror"
                        value="{{ old('tag') }}" required pattern="[a-z][a-z0-9_]*"
                        placeholder="ex: button" aria-required="true" autocomplete="off">
                    @error('tag')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">
                        {{ __('Lettres minuscules, chiffres et underscores. Ex :') }} <code>my_button</code>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <label for="name" class="form-label fw-medium">
                        {{ __('Nom') }} <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="name" id="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="description" class="form-label fw-medium">{{ __('Description') }}</label>
                    <textarea name="description" id="description" rows="2"
                        class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="html_template" class="form-label fw-medium">
                        {{ __('Template HTML') }} <span class="text-danger">*</span>
                    </label>
                    <textarea name="html_template" id="html_template" rows="4" required x-model="tpl"
                        class="form-control font-monospace @error('html_template') is-invalid @enderror"
                        placeholder='ex: <a href="@{{ $url }}">@{{ $content }}</a>' aria-required="true">{{ old('html_template') }}</textarea>
                    @error('html_template')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="parameters" class="form-label fw-medium">{{ __('Paramètres JSON') }}</label>
                    <textarea name="parameters" id="parameters" rows="2" x-model="params"
                        class="form-control font-monospace @error('parameters') is-invalid @enderror"
                        placeholder='["url", "color"]'>{{ old('parameters') }}</textarea>
                    @error('parameters')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">{{ __('Liste JSON des noms de paramètres acceptés par ce shortcode.') }}</div>
                </div>

                <div class="col-12">
                    <div class="border rounded p-3 d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="fw-medium small">{{ __('Contient du contenu') }}</div>
                            <div class="text-muted" style="font-size:0.8rem;">{{ __('Le shortcode accepte du contenu entre les balises ouvrante et fermante') }}</div>
                        </div>
                        <input type="checkbox" name="has_content" value="1" x-model="hasContent"
                            class="form-check-input"
                            {{ old('has_content', true) ? 'checked' : '' }}>
                    </div>
                </div>

            </div>

            {{-- Preview live --}}
            <div class="card bg-body-tertiary mt-4" x-show="tag" x-cloak>
                <div class="card-header d-flex align-items-center gap-2 py-2">
                    <i data-lucide="eye" style="width:16px;height:16px;" class="text-primary"></i>
                    <span class="fw-semibold small">{{ __('Aperçu') }}</span>
                </div>
                <div class="card-body py-3">
                    <div class="mb-3">
                        <div class="fw-medium small mb-1">{{ __('Utilisation') }}</div>
                        <div class="bg-white rounded p-2 position-relative border">
                            <code class="small" x-text="usage"></code>
                            <button type="button" @click="navigator.clipboard.writeText(usage)"
                                    class="btn btn-sm btn-outline-secondary position-absolute top-0 end-0 m-1" style="padding:2px 6px"
                                    title="{{ __('Copier') }}" aria-label="{{ __('Copier la syntaxe') }}">
                                <i data-lucide="copy" style="width:12px;height:12px;"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <div class="fw-medium small mb-1">{{ __('Template HTML') }}</div>
                        <pre class="bg-white rounded p-2 mb-0 border small"><code x-text="tpl"></code></pre>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3 mt-4">
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <i data-lucide="save" style="width:16px;height:16px;"></i>
                    {{ __('Créer le shortcode') }}
                </button>
                <a href="{{ route('admin.shortcodes.index') }}" class="btn btn-light">{{ __('Annuler') }}</a>
            </div>
        </form>
    </div>
</div>

@endsection
