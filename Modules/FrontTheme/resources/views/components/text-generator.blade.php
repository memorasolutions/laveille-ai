<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{--
    Générateur de prompt interactif embarqué dans le contenu d'un article (ex-shortcode WordPress
    [text_generator ...]). Remplace un texte statique par un vrai formulaire : l'utilisateur remplit
    les champs, le composant substitue chaque {variable} du template et affiche le résultat copiable.
    Réutilise le pattern déjà en place dans le constructeur de prompts (Modules/Tools) : substitution
    de variables + navigator.clipboard.writeText() + toast global "toast-show".

    Props :
    - titre       (string)  Titre affiché en en-tête.
    - icon        (string)  Emoji/icône affiché à côté du titre.
    - buttonText  (string)  Texte du bouton de génération.
    - copyText    (string)  Texte du bouton copier (état repos).
    - copiedText  (string)  Texte du bouton copier (état "copié").
    - couleur     (string)  Couleur d'accent (hex) pour le bouton et les bordures.
    - columns     (int)     Nombre de colonnes de la grille de champs (1 ou 2).
    - introHtml   (string)  Bloc HTML d'introduction (autorisé, provient du contenu éditorial de confiance).
    - fields      (array)   Liste de champs : ['name'=>, 'type'=>'input|textarea|select', 'label'=>,
                             'required'=>bool, 'placeholder'=>, 'options'=>[...], 'rows'=>int, 'col'=>1|2]
    - template    (string)  Texte contenant des {nom_champ} à substituer par les valeurs saisies.
--}}
@props([
    'titre' => '',
    'icon' => '🖥️',
    'buttonText' => 'Générer mon prompt',
    'copyText' => '📋 Copier le prompt',
    'copiedText' => 'Prompt copié !',
    'couleur' => '#0B7285',
    'columns' => 2,
    'introHtml' => '',
    'fields' => [],
    'template' => '',
])

@php
    $lvGenId = 'text-gen-' . Str::random(8);
@endphp

<div
    class="lv-text-generator"
    style="border:1px solid rgba(6,78,90,0.12); border-radius: var(--r-base, 12px); padding: 1.5rem; margin: 1.5rem 0; background: #fff;"
    x-data="lvTextGenerator({
        fields: @js($fields),
        template: @js($template),
        copyLabel: @js($copyText),
        copiedLabel: @js($copiedText),
    })"
    x-init="init()"
>
    @if($titre)
        <div class="lv-text-generator__header" style="display:flex; align-items:center; gap:0.6rem; margin-bottom:1rem;">
            <span aria-hidden="true" style="font-size:1.4rem;">{{ $icon }}</span>
            <h4 style="margin:0; font-family: var(--f-heading); font-weight:700; color: var(--c-dark);">{{ $titre }}</h4>
        </div>
    @endif

    @if($introHtml)
        <div class="lv-text-generator__intro" style="margin-bottom:1.25rem;">{!! $introHtml !!}</div>
    @endif

    <form @submit.prevent="generate()" class="lv-text-generator__form">
        <div class="row">
            @foreach($fields as $field)
                @php
                    $colClass = (($field['col'] ?? 1) == 2 && (int) $columns >= 2) ? 'col-md-6' : 'col-md-12';
                    $fieldId = $lvGenId . '-' . $field['name'];
                @endphp
                <div class="{{ $colClass }} mb-3">
                    <label for="{{ $fieldId }}" class="form-label fw-medium" style="font-size:0.9rem;">
                        {{ $field['label'] ?? $field['name'] }}
                        @if($field['required'] ?? false)
                            <span style="color:#DC2626;" aria-hidden="true">*</span>
                        @endif
                    </label>

                    @if(($field['type'] ?? 'input') === 'textarea')
                        <textarea
                            id="{{ $fieldId }}"
                            class="form-control"
                            rows="{{ $field['rows'] ?? 3 }}"
                            x-model="values['{{ $field['name'] }}']"
                            placeholder="{{ $field['placeholder'] ?? '' }}"
                            @if($field['required'] ?? false) aria-required="true" @endif
                        ></textarea>
                    @elseif(($field['type'] ?? 'input') === 'select')
                        <select
                            id="{{ $fieldId }}"
                            class="form-control"
                            x-model="values['{{ $field['name'] }}']"
                            @if($field['required'] ?? false) aria-required="true" @endif
                        >
                            <option value="">{{ __('-- Sélectionnez --') }}</option>
                            @foreach($field['options'] ?? [] as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    @else
                        <input
                            type="text"
                            id="{{ $fieldId }}"
                            class="form-control"
                            x-model="values['{{ $field['name'] }}']"
                            placeholder="{{ $field['placeholder'] ?? '' }}"
                            @if($field['required'] ?? false) aria-required="true" @endif
                        >
                    @endif
                </div>
            @endforeach
        </div>

        <div x-show="showValidation && !isValid" x-transition class="alert alert-danger small p-2 mb-3" style="font-size:0.85rem;">
            {{ __('Veuillez remplir les champs obligatoires avant de générer le prompt.') }}
        </div>

        <button
            type="submit"
            class="ct-btn"
            style="background: {{ $couleur }}; color:#fff; border:none; padding:0.65rem 1.25rem; border-radius: var(--r-base, 10px); font-weight:600;"
        >
            {{ $buttonText }}
        </button>
    </form>

    <template x-if="result">
        <div class="lv-text-generator__result mt-3">
            <div
                class="p-3 rounded"
                style="background: rgba(6,78,90,0.05); white-space: pre-wrap; font-family: monospace; font-size: 0.85rem; line-height:1.6; max-height:420px; overflow-y:auto; border-left: 3px solid {{ $couleur }};"
                x-text="result"
            ></div>
            <button
                type="button"
                class="ct-btn ct-btn-outline mt-2"
                @click="copy()"
                x-text="copied ? copiedLabel : copyLabel"
            ></button>
        </div>
    </template>
</div>

{{--
    IMPORTANT : ce composant peut être rendu isolément via Blade::render() (ex. contenu d'article
    stocké en base, cf. Modules/FrontTheme/resources/views/blog/show.blade.php) — un rendu séparé du
    cycle de compilation normal de la vue. @push()/@once() partagent la pile de sections avec la vue
    "hôte" en cours de compilation : les utiliser ici corrompt cette pile et casse le @endsection
    principal ("Cannot end a section without first starting one"). Le script est donc inliné ici avec
    une garde JS anti-duplication plutôt que poussé via @push.
--}}
<script>
    (function () {
        if (window.__lvTextGeneratorRegistered) { return; }
        window.__lvTextGeneratorRegistered = true;
        document.addEventListener('alpine:init', function () {
                Alpine.data('lvTextGenerator', function (config) {
                    return {
                        fields: config.fields || [],
                        template: config.template || '',
                        copyLabel: config.copyLabel || 'Copier',
                        copiedLabel: config.copiedLabel || 'Copié !',
                        values: {},
                        result: '',
                        copied: false,
                        showValidation: false,

                        init: function () {
                            var self = this;
                            this.fields.forEach(function (f) {
                                self.values[f.name] = '';
                            });
                        },

                        get isValid() {
                            var self = this;
                            return this.fields.every(function (f) {
                                if (!f.required) return true;
                                var v = self.values[f.name];
                                return !!(v && String(v).trim().length > 0);
                            });
                        },

                        generate: function () {
                            if (!this.isValid) {
                                this.showValidation = true;
                                return;
                            }
                            this.showValidation = false;
                            var text = this.template;
                            var self = this;
                            this.fields.forEach(function (f) {
                                var val = self.values[f.name] || '';
                                text = text.split('{' + f.name + '}').join(val);
                            });
                            this.result = text;
                            this.copied = false;
                            this.$nextTick(function () {
                                var el = self.$el.querySelector('.lv-text-generator__result');
                                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                            });
                        },

                        copy: function () {
                            var self = this;
                            navigator.clipboard.writeText(this.result).then(function () {
                                self.copied = true;
                                try {
                                    window.dispatchEvent(new CustomEvent('toast-show', { detail: { message: self.copiedLabel, variant: 'success', duration: 2000 } }));
                                } catch (e) {}
                                setTimeout(function () { self.copied = false; }, 2000);
                            });
                        },
                    };
                });
            });
    })();
</script>
