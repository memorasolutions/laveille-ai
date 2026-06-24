{{--
    @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
    @project memora/laravel-saas-boilerplate

    F20 - BASE DE DONNÉES : champ de saisie réutilisable (DRY) d'une fiche, rendu selon le
    TYPE du champ du schéma. Utilisé par le formulaire d'AJOUT et le formulaire d'ÉDITION.
    Le nom du champ est `values[{id}]` (validation serveur par type dans DatabaseController).
    La valeur fournie est ré-affichée échappée par Blade ({{ }}). `required` est posé côté
    client par confort ; l'autorité reste la validation serveur.

    Variables : $field (DatabaseField), $value (string), $entryId (préfixe d'id unique).
--}}
@php
    $dbFieldId = 'dbf-'.$entryId.'-'.$field->id;
    $dbValue   = (string) ($value ?? '');
    $dbReq     = (bool) $field->required;
@endphp
<label for="{{ $dbFieldId }}" style="font-size: 0.78rem; font-weight: 600;">
    {{ $field->label }}@if($dbReq) <span aria-hidden="true" style="color: var(--sys-action-danger, #DC2626);">*</span><span class="visually-hidden">obligatoire</span>@endif
</label>
@if($field->type === 'textarea')
    <textarea id="{{ $dbFieldId }}" name="values[{{ $field->id }}]" rows="3" class="academy-db-field" style="resize: vertical;" @if($dbReq) required @endif>{{ $dbValue }}</textarea>
@elseif($field->type === 'number')
    <input id="{{ $dbFieldId }}" type="number" step="any" name="values[{{ $field->id }}]" value="{{ $dbValue }}" class="academy-db-field" @if($dbReq) required @endif>
@elseif($field->type === 'url')
    <input id="{{ $dbFieldId }}" type="url" name="values[{{ $field->id }}]" value="{{ $dbValue }}" placeholder="https://…" class="academy-db-field" @if($dbReq) required @endif>
@elseif($field->type === 'select')
    <select id="{{ $dbFieldId }}" name="values[{{ $field->id }}]" class="academy-db-field" @if($dbReq) required @endif>
        <option value="">{{ $dbReq ? 'Choisir…' : '(aucun)' }}</option>
        @foreach((array) ($field->options ?? []) as $opt)
            <option value="{{ $opt }}" @selected($dbValue === $opt)>{{ $opt }}</option>
        @endforeach
    </select>
@else
    <input id="{{ $dbFieldId }}" type="text" name="values[{{ $field->id }}]" value="{{ $dbValue }}" class="academy-db-field" maxlength="{{ \Modules\Academy\Services\DatabaseService::TEXT_MAX }}" @if($dbReq) required @endif>
@endif
