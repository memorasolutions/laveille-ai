<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', 'Modifier : ' . $form->title)

@section('content')
<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.formbuilder.forms.index') }}">Formulaires</a></li>
        <li class="breadcrumb-item active" aria-current="page">Modifier</li>
    </ol>
</nav>
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
    <h4 class="mb-3 mb-md-0">Modifier : {{ $form->title }}</h4>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ route('admin.formbuilder.forms.update', $form) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-md-4 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Paramètres</h6>

                    <div class="mb-3">
                        <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $form->title) }}" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug</label>
                        <input type="text" class="form-control bg-light" id="slug" name="slug" value="{{ old('slug', $form->slug) }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $form->description) }}</textarea>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="is_published" name="is_published" value="1" {{ old('is_published', $form->is_published) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_published">Publié</label>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                        <a href="{{ route('admin.formbuilder.forms.index') }}" class="btn btn-outline-secondary">Annuler</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8 grid-margin stretch-card" x-data="{
            fields: {{ Js::from(old('fields', $form->fields->map(fn($f) => [
                'id' => $f->id,
                'label' => $f->label,
                'name' => $f->name,
                'type' => $f->type,
                'is_required' => $f->is_required ? 1 : 0,
                'sort_order' => $f->sort_order,
                'options' => is_array($f->options) ? implode(',', $f->options) : '',
            ])->toArray())) }},
            addField() {
                this.fields.push({
                    id: null, label: '', name: 'champ_' + Date.now(),
                    type: 'text', is_required: 0, sort_order: this.fields.length + 1, options: ''
                });
            },
            removeField(i) { window.dispatchEvent(new CustomEvent('confirm-action', { detail: { title: @json(__('Confirmer')), message: @json(__('Supprimer ce champ ?')), action: () => this.fields.splice(i, 1) } })); }
        }">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title mb-0">Champs du formulaire</h6>
                        <button type="button" class="btn btn-sm btn-success" @click="addField()">
                            <i data-lucide="plus" style="width: 16px; height: 16px;"></i> Ajouter
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:60px">Ordre</th>
                                    <th>Label</th>
                                    <th>Nom</th>
                                    <th>Type</th>
                                    <th style="width:70px">Requis</th>
                                    <th style="width:50px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(field, i) in fields" :key="i">
                                    <tr>
                                        <td>
                                            <input type="hidden" :name="'fields['+i+'][id]'" x-model="field.id">
                                            <input type="number" class="form-control form-control-sm" :name="'fields['+i+'][sort_order]'" x-model="field.sort_order" min="0">
                                        </td>
                                        <td><input type="text" class="form-control form-control-sm" :name="'fields['+i+'][label]'" x-model="field.label" required></td>
                                        <td><input type="text" class="form-control form-control-sm" :name="'fields['+i+'][name]'" x-model="field.name" required></td>
                                        <td>
                                            <select class="form-select form-select-sm" :name="'fields['+i+'][type]'" x-model="field.type">
                                                <option value="text">Texte</option>
                                                <option value="email">Email</option>
                                                <option value="textarea">Zone de texte</option>
                                                <option value="number">Nombre</option>
                                                <option value="date">Date</option>
                                                <option value="select">Liste</option>
                                                <option value="checkbox">Case à cocher</option>
                                                <option value="radio">Boutons radio</option>
                                                <option value="file">Fichier</option>
                                            </select>
                                            <template x-if="['select','radio'].includes(field.type)">
                                                <input type="text" class="form-control form-control-sm mt-1" :name="'fields['+i+'][options]'" x-model="field.options" placeholder="opt1,opt2,opt3">
                                            </template>
                                        </td>
                                        <td class="text-center">
                                            <input type="hidden" :name="'fields['+i+'][is_required]'" value="0">
                                            <input type="checkbox" class="form-check-input" :name="'fields['+i+'][is_required]'" value="1" :checked="field.is_required == 1">
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-danger p-1" @click="removeField(i)">
                                                <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <p x-show="fields.length === 0" class="text-center text-muted py-3">Aucun champ. Cliquez sur « Ajouter ».</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
