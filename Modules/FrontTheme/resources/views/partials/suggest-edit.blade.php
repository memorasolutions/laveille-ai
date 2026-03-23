{{-- Composant réutilisable — bouton "Suggérer une modification"
     Usage: @include('fronttheme::partials.suggest-edit', ['model' => $tool, 'route' => route('directory.suggestions.store', $tool->slug), 'fields' => [...]])
     $model : le modèle concerné (Tool, Term, etc.)
     $route : URL du POST
     $fields : tableau associatif [value => label] des champs modifiables
--}}
@auth
<div x-data="{ showSuggest: false }" style="margin-bottom: 12px;">
    <button type="button" @click="showSuggest = !showSuggest"
            style="background: none; border: 1px solid #D1D5DB; border-radius: 6px; padding: 6px 12px; font-size: 12px; color: #6B7280; cursor: pointer; font-weight: 600; transition: all 0.2s;"
            onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='none'">
        ✏️ {{ __('Suggérer une modification') }}
    </button>
    <div x-show="showSuggest" x-cloak style="margin-top: 12px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 12px; padding: 20px;">
        <form action="{{ $route }}" method="POST">
            @csrf
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #6B7280; margin-bottom: 4px;">{{ __('Champ concerné') }}</label>
                <select name="field" class="form-control" style="border-radius: 8px; height: 38px; font-size: 14px;" required>
                    @foreach($fields ?? ['description' => 'Description', 'other' => 'Autre'] as $value => $label)
                        <option value="{{ $value }}">{{ __($label) }}</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #6B7280; margin-bottom: 4px;">{{ __('Votre suggestion') }} *</label>
                <textarea name="suggested_value" class="form-control" rows="3" style="border-radius: 8px; font-size: 14px;" placeholder="{{ __('Entrez la correction proposée...') }}" required></textarea>
            </div>
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #6B7280; margin-bottom: 4px;">{{ __('Pourquoi ?') }} <span style="color: #9CA3AF;">({{ __('optionnel') }})</span></label>
                <textarea name="reason" class="form-control" rows="2" style="border-radius: 8px; font-size: 14px;" placeholder="{{ __('Source, erreur, etc.') }}"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" @click="showSuggest = false" style="background: none; border: none; color: #6B7280; font-size: 13px; cursor: pointer;">{{ __('Annuler') }}</button>
                <button type="submit" style="background: var(--c-primary); color: #fff; border: none; border-radius: 8px; padding: 8px 20px; font-size: 13px; font-weight: 600; cursor: pointer;">{{ __('Envoyer') }}</button>
            </div>
        </form>
    </div>
</div>
@endauth
