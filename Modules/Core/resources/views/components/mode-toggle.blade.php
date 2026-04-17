{{-- Modules/Core/resources/views/components/mode-toggle.blade.php
     Toggle "Lecture ↔ Édition" pour fiches publiques (best practice admin 2026 #5 90/100).
     Visible uniquement view_admin_panel. Persiste dans localStorage.
     Usage:
     @include('core::components.mode-toggle', ['editUrl' => route('admin.directory.edit', $tool->id)])
     En mode Édition, les éléments marqués [data-editable="<champ>"] montrent un crayon ✏️.
     Click sur un [data-editable] redirige vers $editUrl?focus=<champ>.
--}}

@can('view_admin_panel')
    <style>
        .core-mode-toggle-btn {
            position: fixed;
            top: 130px;
            right: 16px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 12px;
            background: #ffffff;
            border: 1px solid rgba(0,0,0,0.06);
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            font-size: 13px;
            font-weight: 600;
            color: var(--c-dark, #1A1D23);
            cursor: pointer;
            z-index: 8999;
            font-family: var(--f-body, system-ui, -apple-system, sans-serif);
            user-select: none;
        }
        .core-mode-toggle-btn:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.12); }
        @media (max-width: 767px) {
            .core-mode-toggle-btn { top: 120px; right: 8px; transform: scale(0.92); transform-origin: top right; }
        }
        @media print { .core-mode-toggle-btn { display: none !important; } }
        body.edit-mode [data-editable] {
            outline: 2px dashed rgba(194, 65, 12, 0.45);
            outline-offset: 4px;
            position: relative;
            cursor: pointer;
        }
        body.edit-mode [data-editable]::after {
            content: '✏️';
            position: absolute;
            top: -10px;
            right: -10px;
            background: #ffffff;
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 50%;
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 13px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.12);
            z-index: 9000;
            line-height: 1;
        }
    </style>

    <button
        type="button"
        x-data="{
            editMode: localStorage.getItem('laveille.edit_mode') === 'true',
            init() { document.body.classList.toggle('edit-mode', this.editMode); }
        }"
        x-effect="localStorage.setItem('laveille.edit_mode', editMode); document.body.classList.toggle('edit-mode', editMode);"
        @click="editMode = !editMode"
        class="core-mode-toggle-btn"
        :aria-pressed="editMode ? 'true' : 'false'"
        aria-label="{{ __('Basculer entre mode lecture et mode édition') }}"
    >
        <span x-text="editMode ? '✏️ Édition' : '👁 Lecture'"></span>
    </button>

    <script>
        (function () {
            var editUrl = @json($editUrl);
            document.addEventListener('click', function (e) {
                if (!document.body.classList.contains('edit-mode')) return;
                var target = e.target;
                // Ignore le bouton toggle lui-même
                if (target.closest && target.closest('.core-mode-toggle-btn')) return;
                while (target && (!target.getAttribute || !target.getAttribute('data-editable'))) {
                    target = target.parentElement;
                }
                if (target && target.getAttribute && target.getAttribute('data-editable')) {
                    e.preventDefault();
                    e.stopPropagation();
                    var field = target.getAttribute('data-editable');
                    window.location = editUrl + (editUrl.indexOf('?') > -1 ? '&' : '?') + 'focus=' + encodeURIComponent(field);
                }
            }, true);
        })();
    </script>
@endcan
