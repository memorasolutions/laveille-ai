{{-- Composant réutilisable — bouton "Suggérer une modification"
     Usage : @include('fronttheme::partials.suggest-edit', ['model' => $term, 'route' => route('dictionary.suggestions.store', $term->slug)])
     $model : objet Eloquent utilisant le trait HasSuggestions (suggestableFields())
     $route : URL du POST pour soumettre la suggestion
     Le composant se désactive silencieusement si le module Directory n'est pas actif.
--}}
@if(class_exists(\Modules\Directory\Models\ToolSuggestion::class))
@php
    $fields = method_exists($model, 'suggestableFields') ? $model->suggestableFields() : [];
    if (empty($fields)) { $fields = ['other' => 'Autre']; }
@endphp

@if(session('success'))
    <div style="background: #D1FAE5; color: #065F46; padding: 10px 16px; border-radius: var(--r-base); font-size: 13px; margin-bottom: 12px;">
        ✓ {{ session('success') }}
    </div>
@endif

<div x-data="{ showSuggest: false }" style="margin-bottom: 12px;">
    <button type="button" @click="showSuggest = !showSuggest"
            style="background: none; border: 1px solid #D1D5DB; border-radius: 6px; padding: 6px 12px; font-size: 12px; color: #6B7280; cursor: pointer; font-weight: 600; transition: all 0.2s;"
            onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='none'">
        ✏️ {{ __('Suggérer une modification') }}
    </button>

    <div x-show="showSuggest" x-cloak x-transition style="margin-top: 12px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: var(--r-base); padding: 20px;">
        @auth
            <form action="{{ $route }}" method="POST">
                @csrf
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #6B7280; margin-bottom: 4px;">{{ __('Champ concerné') }}</label>
                    <select name="field" class="form-control" style="border-radius: 8px; height: 38px; font-size: 14px;" required>
                        @foreach($fields as $value => $label)
                            <option value="{{ $value }}">{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #6B7280; margin-bottom: 4px;">{{ __('Votre suggestion') }} *</label>
                    <textarea name="suggested_value" class="form-control" rows="3" maxlength="2000" style="border-radius: 8px; font-size: 14px;" placeholder="{{ __('Entrez la correction proposée...') }}" required></textarea>
                </div>
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #6B7280; margin-bottom: 4px;">{{ __('Pourquoi ?') }} <span style="color: #9CA3AF;">({{ __('optionnel') }})</span></label>
                    <textarea name="reason" class="form-control" rows="2" maxlength="500" style="border-radius: 8px; font-size: 14px;" placeholder="{{ __('Source, erreur, etc.') }}"></textarea>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" @click="showSuggest = false" style="background: none; border: none; color: #6B7280; font-size: 13px; cursor: pointer;">{{ __('Annuler') }}</button>
                    <button type="submit" style="background: var(--c-primary); color: #fff; border: none; border-radius: var(--r-btn); padding: 8px 20px; font-size: 13px; font-weight: 600; cursor: pointer;">{{ __('Envoyer') }}</button>
                </div>
            </form>
        @else
            <div style="text-align: center; padding: 12px;">
                <button type="button" @click="$dispatch('open-auth-modal', { message: '{{ __('Connectez-vous pour suggérer une modification.') }}' })"
                    style="background: var(--c-primary); color: #fff; border: none; border-radius: var(--r-btn); padding: 10px 24px; font-weight: 600; cursor: pointer; font-size: 14px;">
                    🔐 {{ __('Se connecter pour suggérer') }}
                </button>
            </div>
        @endauth
    </div>
</div>
@endif
