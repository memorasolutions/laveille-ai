{{-- Composant réutilisable : menu d'actions (kebab ⋮), admin ET front-end utilisateur.
     Renommé de admin-action-menu -> action-menu (2026-07-19, retour Gemini 85/100 sur la
     généralisation : garder "admin" dans le nom d'un composant maintenant affiché sur des pages
     utilisateur comme "Mes liens courts" aurait été trompeur pour les futurs lecteurs du code).
     Usage (mode routes, POST/DELETE classiques) :
     @include('core::components.action-menu', ['actions' => [
         ['label' => 'Modifier', 'icon' => 'pencil', 'url' => route('admin.xxx.edit', $item)],
         ['label' => 'Voir', 'icon' => 'eye', 'url' => route('xxx.show', $item), 'target' => '_blank'],
         ['divider' => true],
         ['label' => 'Supprimer', 'icon' => 'trash-2', 'url' => route('admin.xxx.destroy', $item), 'method' => 'DELETE', 'confirm' => 'Supprimer ?', 'danger' => true],
     ]])

     Usage (mode Livewire, wire:click) : remplace 'url' par 'wireClick'. La
     confirmation en 2 temps (Confirmer/Annuler inline) N'EST PAS gérée ici -
     'wireClick' appelle une méthode qui existe déjà dans le composant Livewire
     (ex. confirmCohortRemoval($id)) et qui bascule un état interne affiché par
     LA VUE Livewire elle-même (jamais de popup native, jamais de logique de
     confirmation dupliquée dans ce menu).
     @include('core::components.action-menu', ['actions' => [
         ['label' => 'Renommer', 'icon' => 'pencil', 'wireClick' => "startRenameCohort({$cohort->id})"],
         ['label' => 'Supprimer', 'icon' => 'trash-2', 'wireClick' => "confirmCohortRemoval({$cohort->id})", 'danger' => true],
     ]])

     Variante 'wireClick' + confirmation via la modale du thème (event Alpine
     'confirm-action', jamais de popup navigateur native) : ajouter 'wireConfirm'.
     La méthode Livewire cible reste responsable de l'autorisation serveur.
     @include('core::components.action-menu', ['actions' => [
         ['label' => 'Supprimer', 'icon' => 'trash-2', 'wireClick' => "deleteMessage({$msg->id})", 'wireConfirm' => 'Supprimer ce message ?', 'danger' => true],
     ]])
--}}
@php $menuId = 'action-' . uniqid(); @endphp
{{-- Chargement garanti de lucide.js (2026-07-19) : ce composant dépend de data-lucide pour ses
     icônes (déclencheur ET items du menu). Les layouts admin le chargent déjà en <script> classique
     + appellent lucide.createIcons() eux-mêmes - mais le layout front-end "Mon espace"
     (auth::layouts.user-frontend) ne le charge PAS du tout, ce qui rendait les icônes invisibles
     sur toute nouvelle page front-end migrée vers ce composant sans que son auteur y pense (bug
     constaté sur 2 pages lors de cette même migration). Pour ne plus jamais dépendre de la mémoire
     de chaque futur usage, le composant s'auto-suffit : @assets déduplique nativement (une seule
     exécution par page quel que soit le nombre d'instances du composant), et le garde
     `typeof lucide === 'undefined'` évite un double chargement du script sur les pages qui le
     chargent déjà via leur layout (juste un createIcons() de sécurité dans ce cas, opération
     idempotente). --}}
@assets
<script>
(function () {
    function ensureActionMenuIcons() {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); return; }
        var s = document.createElement('script');
        s.src = '{{ asset('build/nobleui/plugins/lucide/lucide.min.js') }}';
        s.onload = function () { lucide.createIcons(); };
        document.head.appendChild(s);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureActionMenuIcons);
    } else {
        ensureActionMenuIcons();
    }
})();
</script>
@endassets
{{-- Généralisé (2026-07-19, skill /100 + validation Codex 94/100 + Gemini 85/100) : réutilisé tel
     quel sur les pages front-end utilisateur (ex. Mes liens courts, Mes journaux) en plus des 41
     usages admin déjà en place - un seul composant DRY, jamais dupliqué. Correctifs apportés
     suite à l'audit + revue croisée : (1) déclencheur passé de 38×38 à 44×44px = cible tactile
     WCAG 2.2 AAA (2.5.5), conforme à la charte AAA du projet ; (2) positionnement du popup en
     `position: fixed` (coordonnées viewport calculées via getBoundingClientRect, PAS un
     `right:0; top:100%` relatif au parent) - flip vertical (ouvre vers le haut si pas assez
     d'espace en dessous) + shift horizontal (reste dans le viewport). `fixed` plutôt que
     `absolute` est un choix délibéré (risque #3 signalé par Gemini) : un `absolute` aurait pu
     être rogné par un ancêtre `overflow:hidden`/`position:relative` mal placé (ex. une carte aux
     coins arrondis dans un tableau admin) alors que `fixed` est positionné par rapport au
     viewport, insensible à ce genre de conteneur - sans dépendance JS externe type Floating UI,
     cohérent avec le reste du site qui n'a pas de build lourd ; (3) fermeture au clavier (Escape)
     avec retour de focus au déclencheur, ET fermeture au défilement de la page (un menu `fixed`
     resterait sinon visuellement détaché de son bouton déclencheur pendant qu'on scrolle) - en
     plus du clic extérieur déjà géré. Recalcul de position UNIQUEMENT à l'ouverture (pas de
     tracking resize en continu) : compromis simplicité/robustesse volontaire. --}}
<div x-data="{
        open: false,
        top: 0,
        left: 0,
        positionMenu() {
            this.$nextTick(() => {
                const btn = this.$refs.trigger;
                const menu = this.$refs.menu;
                if (!btn || !menu) return;
                const btnRect = btn.getBoundingClientRect();
                const menuRect = menu.getBoundingClientRect();
                const spaceBelow = window.innerHeight - btnRect.bottom;
                const spaceAbove = btnRect.top;
                const openUp = spaceBelow < menuRect.height + 8 && spaceAbove > spaceBelow;
                this.top = openUp ? Math.max(8, btnRect.top - menuRect.height - 4) : Math.min(btnRect.bottom + 4, window.innerHeight - 8);
                const leftIfRightAligned = btnRect.right - menuRect.width;
                this.left = leftIfRightAligned >= 8 ? leftIfRightAligned : Math.max(8, Math.min(btnRect.left, window.innerWidth - menuRect.width - 8));
            });
        },
        menuStyle() {
            return `position: fixed; top: ${this.top}px; left: ${this.left}px; min-width: 180px; max-height: 70vh; overflow-y: auto; background: #fff; border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,0.12); border: 1px solid #e5e7eb; padding: 4px 0; z-index: 1080;`;
        }
     }"
     @keydown.escape.window="if (open) { open = false; $refs.trigger.focus(); }"
     @scroll.window="open = false"
     class="position-relative" style="display: inline-block;">
    {{-- Déclencheur (2026-07-19, signalé par l'utilisateur : "trop petit, on les voit presque pas") :
         le caractère unicode ⋮ dans un bouton .btn-outline-secondary SANS remplissage était peu
         visible - hors du conteneur .user-space (qui a un override charte.css assombrissant le
         texte), le gris Bootstrap par défaut (#6c757d, ~4.6:1) ne respecte même pas l'AAA (7:1) visé
         par la charte du projet. Remplacé par une icône lucide nette (cohérente avec les items du
         menu, qui l'utilisent déjà) sur un fond REMPLI (pas juste un contour) en gris clair, avec une
         couleur d'icône #374151 (~10.7:1 sur blanc, largement AAA) - style entièrement autoporté en
         ligne, indépendant de toute classe Bootstrap/override de page pour un rendu identique partout. --}}
    <button x-ref="trigger" @click="open = !open; positionMenu()" @click.outside="open = false"
            style="border-radius: 8px; padding: 0; width: 44px; height: 44px; display: inline-flex; align-items: center; justify-content: center; background: #F3F4F6; border: 1px solid #D1D5DB; color: #374151; cursor: pointer;"
            onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'"
            aria-label="{{ __('Actions') }}" aria-haspopup="true" :aria-expanded="open">
        <i data-lucide="more-vertical" style="width: 22px; height: 22px;"></i>
    </button>
    <div x-ref="menu" x-show="open" x-cloak x-transition.opacity.duration.150ms :style="menuStyle()">
        @foreach($actions as $action)
            @if(isset($action['divider']) && $action['divider'])
                <div style="border-top: 1px solid #f3f4f6; margin: 4px 0;"></div>
            @elseif(isset($action['info']))
                {{-- Ligne informative non-cliquable (ex. "Modifié il y a X") --}}
                <div style="display: flex; align-items: center; gap: 8px; padding: 8px 14px; font-size: 12px; font-style: italic; color: #6B7280;">
                    @if(isset($action['icon']))<i data-lucide="{{ $action['icon'] }}" style="width: 14px; height: 14px;"></i>@endif
                    {{ $action['label'] }}
                </div>
            @elseif(isset($action['alpineClick']))
                {{-- Bouton avec expression Alpine brute (ex. toggle Lecture/Édition) - le menu ne se
                     referme pas automatiquement, contrairement aux autres items, pour laisser
                     l'utilisateur voir l'état changer avant de fermer lui-même. --}}
                <button type="button"
                        @click="{{ $action['alpineClick'] }}"
                        style="display: flex; align-items: center; gap: 8px; width: 100%; padding: 8px 14px; min-height: 44px; box-sizing: border-box; border: none; background: none; cursor: pointer; font-size: 13px; color: {{ isset($action['danger']) && $action['danger'] ? '#991B1B' : '#374151' }}; text-align: left;"
                        onmouseover="this.style.background='{{ isset($action['danger']) && $action['danger'] ? '#FEF2F2' : '#F9FAFB' }}'"
                        onmouseout="this.style.background='transparent'">
                    @if(isset($action['icon']))<i data-lucide="{{ $action['icon'] }}" style="width: 14px; height: 14px;"></i>@endif
                    @if(isset($action['labelExpr']))<span x-text="{{ $action['labelExpr'] }}"></span>@else{{ $action['label'] }}@endif
                </button>
            @elseif(isset($action['wireClick']))
                <button type="button"
                        @if(isset($action['wireConfirm']))
                            @click="open = false; $dispatch('confirm-action', { title: @js(__('Confirmer')), message: @js($action['wireConfirm']), action: () => $wire.{{ $action['wireClick'] }} })"
                        @else
                            wire:click="{{ $action['wireClick'] }}" @click="open = false"
                        @endif
                        style="display: flex; align-items: center; gap: 8px; width: 100%; padding: 8px 14px; min-height: 44px; box-sizing: border-box; border: none; background: none; cursor: pointer; font-size: 13px; color: {{ isset($action['danger']) && $action['danger'] ? '#991B1B' : '#374151' }}; text-align: left;"
                        onmouseover="this.style.background='{{ isset($action['danger']) && $action['danger'] ? '#FEF2F2' : '#F9FAFB' }}'"
                        onmouseout="this.style.background='transparent'">
                    @if(isset($action['icon']))<i data-lucide="{{ $action['icon'] }}" style="width: 14px; height: 14px;"></i>@endif
                    {{ $action['label'] }}
                </button>
            @elseif(isset($action['method']) && $action['method'] !== 'GET')
                <form action="{{ $action['url'] }}" method="POST" style="margin: 0;" x-data>
                    @csrf
                    @if($action['method'] !== 'POST') @method($action['method']) @endif
                    <button type="{{ isset($action['confirm']) ? 'button' : 'submit' }}"
                            @if(isset($action['confirm'])) @click="$dispatch('confirm-action', { title: @js(__('Confirmer')), message: @js($action['confirm']), action: () => $el.closest('form').submit() })" @endif
                            style="display: flex; align-items: center; gap: 8px; width: 100%; padding: 8px 14px; min-height: 44px; box-sizing: border-box; border: none; background: none; cursor: pointer; font-size: 13px; color: {{ isset($action['danger']) && $action['danger'] ? '#991B1B' : '#374151' }}; text-align: left;"
                            onmouseover="this.style.background='{{ isset($action['danger']) && $action['danger'] ? '#FEF2F2' : '#F9FAFB' }}'"
                            onmouseout="this.style.background='transparent'">
                        @if(isset($action['icon']))<i data-lucide="{{ $action['icon'] }}" style="width: 14px; height: 14px;"></i>@endif
                        {{ $action['label'] }}
                    </button>
                </form>
            @else
                <a href="{{ $action['url'] }}"
                   @if(isset($action['target'])) target="{{ $action['target'] }}" rel="noopener" @endif
                   style="display: flex; align-items: center; gap: 8px; padding: 8px 14px; min-height: 44px; box-sizing: border-box; font-size: 13px; color: {{ isset($action['danger']) && $action['danger'] ? '#991B1B' : '#374151' }}; text-decoration: none;"
                   onmouseover="this.style.background='{{ isset($action['danger']) && $action['danger'] ? '#FEF2F2' : '#F9FAFB' }}'" onmouseout="this.style.background='transparent'">
                    @if(isset($action['icon']))<i data-lucide="{{ $action['icon'] }}" style="width: 14px; height: 14px;"></i>@endif
                    {{ $action['label'] }}
                </a>
            @endif
        @endforeach
    </div>
</div>
