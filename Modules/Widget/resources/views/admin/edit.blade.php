<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', 'Modifier : ' . $widget->title)

@section('content')
<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.widgets.index') }}">Widgets</a></li>
        <li class="breadcrumb-item active" aria-current="page">Modifier</li>
    </ol>
</nav>
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
    <h4 class="mb-3 mb-md-0">Modifier : {{ $widget->title }}</h4>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ route('admin.widgets.update', $widget) }}" method="POST" x-data="widgetForm('{{ old('type', $widget->type) }}', {{ Js::from($widget->settings ?? []) }})">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-lg-8 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="zone" class="form-label">Zone <span class="text-danger">*</span></label>
                            <select name="zone" id="zone" class="form-select" required>
                                @foreach(\Modules\Widget\Models\Widget::ZONE_LABELS as $key => $label)
                                    <option value="{{ $key }}" {{ old('zone', $widget->zone) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="type" id="type" class="form-select" required x-model="widgetType">
                                @foreach(\Modules\Widget\Models\Widget::TYPE_LABELS as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $widget->title) }}" required>
                    </div>

                    <div class="mb-3" x-show="['html','custom_text'].includes(widgetType)" x-cloak>
                        <label for="content" class="form-label">Contenu</label>
                        <textarea name="content" id="content" class="form-control" rows="5">{{ old('content', $widget->content) }}</textarea>
                    </div>

                    <div x-show="widgetType === 'recent_posts'" x-cloak>
                        <div class="mb-3">
                            <label class="form-label">Nombre d'articles</label>
                            <input type="number" name="settings[post_count]" class="form-control" :value="settings.post_count || 5" min="1" max="20">
                        </div>
                    </div>

                    <div x-show="widgetType === 'cta_button'" x-cloak>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Texte</label>
                                <input type="text" name="settings[button_text]" class="form-control" :value="settings.button_text || 'Découvrir'">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">URL</label>
                                <input type="url" name="settings[button_url]" class="form-control" :value="settings.button_url || ''">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Style</label>
                                <select name="settings[button_style]" class="form-select" x-model="settings.button_style">
                                    <option value="primary">Primaire</option>
                                    <option value="secondary">Secondaire</option>
                                    <option value="success">Succès</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div x-show="widgetType === 'social_links'" x-cloak>
                        <template x-for="(link, i) in socialLinks" :key="i">
                            <div class="row align-items-end mb-2">
                                <div class="col-md-4">
                                    <label class="form-label" x-show="i === 0">Nom</label>
                                    <input type="text" :name="'settings[social_links]['+i+'][name]'" class="form-control form-control-sm" x-model="link.name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" x-show="i === 0">URL</label>
                                    <input type="url" :name="'settings[social_links]['+i+'][url]'" class="form-control form-control-sm" x-model="link.url">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-sm btn-outline-danger w-100" @click="socialLinks.splice(i, 1)" x-show="socialLinks.length > 1">
                                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-1" @click="socialLinks.push({name:'',url:''})">
                            <i data-lucide="plus" style="width:14px;height:14px;"></i> Ajouter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1" {{ old('is_active', $widget->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Actif</label>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                        <a href="{{ route('admin.widgets.index') }}" class="btn btn-outline-secondary">Annuler</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('widgetForm', (initialType, initialSettings) => ({
        widgetType: initialType || '',
        settings: initialSettings || {},
        socialLinks: (initialSettings && initialSettings.social_links) ? initialSettings.social_links : [{name: '', url: ''}]
    }));
});
</script>
@endpush
