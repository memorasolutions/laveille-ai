<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@php
    $definitions = \Modules\CustomFields\Models\CustomFieldDefinition::active()
        ->forModel($modelType ?? '')
        ->orderBy('sort_order')
        ->get();
    $existingValues = $fieldValues ?? [];
@endphp

@if($definitions->isNotEmpty())
<div class="card grid-margin">
    <div class="card-header">
        <h6 class="card-title mb-0">Champs personnalisés</h6>
    </div>
    <div class="card-body">
        @foreach($definitions as $def)
            @php $val = old("custom_fields.{$def->key}", $existingValues[$def->key] ?? $def->default_value); @endphp
            <div class="mb-3">
                <label for="cf_{{ $def->key }}" class="form-label">
                    {{ $def->name }}
                    @if($def->is_required) <span class="text-danger">*</span> @endif
                </label>

                @switch($def->type)
                    @case('textarea')
                        <textarea name="custom_fields[{{ $def->key }}]" id="cf_{{ $def->key }}" class="form-control" rows="3" placeholder="{{ $def->placeholder }}" {{ $def->is_required ? 'required' : '' }}>{{ $val }}</textarea>
                        @break
                    @case('select')
                        <select name="custom_fields[{{ $def->key }}]" id="cf_{{ $def->key }}" class="form-select" {{ $def->is_required ? 'required' : '' }}>
                            <option value="">Choisir...</option>
                            @foreach(($def->options ?? []) as $opt)
                                <option value="{{ $opt }}" {{ $val == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        @break
                    @case('radio')
                        @foreach(($def->options ?? []) as $opt)
                            <div class="form-check">
                                <input type="radio" name="custom_fields[{{ $def->key }}]" id="cf_{{ $def->key }}_{{ $loop->index }}" class="form-check-input" value="{{ $opt }}" {{ $val == $opt ? 'checked' : '' }}>
                                <label class="form-check-label" for="cf_{{ $def->key }}_{{ $loop->index }}">{{ $opt }}</label>
                            </div>
                        @endforeach
                        @break
                    @case('checkbox')
                        <div class="form-check form-switch">
                            <input type="hidden" name="custom_fields[{{ $def->key }}]" value="0">
                            <input type="checkbox" name="custom_fields[{{ $def->key }}]" id="cf_{{ $def->key }}" class="form-check-input" value="1" {{ $val ? 'checked' : '' }}>
                        </div>
                        @break
                    @case('repeater')
                        @php
                            $subFields = is_array($def->options) ? $def->options : [];
                            $repeaterVal = is_string($val) ? (json_decode($val, true) ?? []) : (is_array($val) ? $val : []);
                        @endphp
                        <div x-data="{
                            rows: {{ Js::from($repeaterVal) }},
                            subFields: {{ Js::from($subFields) }},
                            addRow() {
                                const newRow = {};
                                this.subFields.forEach(f => newRow[f.trim()] = '');
                                this.rows.push(newRow);
                            },
                            removeRow(index) { this.rows.splice(index, 1); }
                        }">
                            <template x-for="(row, index) in rows" :key="index">
                                <div class="card mb-2">
                                    <div class="card-body py-2 px-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small text-muted fw-medium" x-text="'{{ __('Ligne') }} ' + (index + 1)"></span>
                                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" @click="removeRow(index)">
                                                <i data-lucide="trash-2" class="icon-sm"></i>
                                            </button>
                                        </div>
                                        <div class="row g-2">
                                            <template x-for="field in subFields" :key="field">
                                                <div class="col-md-4">
                                                    <label class="form-label small" x-text="field.trim()"></label>
                                                    <input type="text" class="form-control form-control-sm"
                                                           x-model="rows[index][field.trim()]"
                                                           :placeholder="field.trim()">
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1" @click="addRow()">
                                <i data-lucide="plus" class="icon-sm"></i> {{ __('Ajouter une ligne') }}
                            </button>
                            <input type="hidden" name="custom_fields[{{ $def->key }}]" :value="JSON.stringify(rows)">
                        </div>
                        @break
                    @case('color')
                        <input type="color" name="custom_fields[{{ $def->key }}]" id="cf_{{ $def->key }}" class="form-control form-control-color" value="{{ $val ?? '#000000' }}">
                        @break
                    @default
                        <input type="{{ $def->type === 'number' ? 'number' : ($def->type === 'date' ? 'date' : ($def->type === 'email' ? 'email' : ($def->type === 'url' ? 'url' : 'text'))) }}" name="custom_fields[{{ $def->key }}]" id="cf_{{ $def->key }}" class="form-control" value="{{ $val }}" placeholder="{{ $def->placeholder }}" {{ $def->is_required ? 'required' : '' }}>
                @endswitch

                @if($def->description)
                    <div class="form-text">{{ $def->description }}</div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endif
