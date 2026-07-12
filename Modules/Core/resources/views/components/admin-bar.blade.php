{{-- Modules/Core/resources/views/components/admin-bar.blade.php
     Barre flottante admin top-right, visible uniquement view_admin_panel.
     Usage:
     @include('core::components.admin-bar', [
         'actions' => [
             ['label' => 'Éditer', 'icon' => 'pencil', 'url' => route('admin.directory.edit', $tool->id)],
             ['label' => 'Modération', 'icon' => 'shield', 'url' => route('admin.directory.moderation'), 'target' => '_blank'],
             ['divider' => true],
             ['label' => 'Supprimer', 'icon' => 'trash-2', 'url' => route('admin.directory.destroy', $tool->id), 'method' => 'DELETE', 'confirm' => 'Supprimer cet outil ?', 'danger' => true],
         ],
         'label' => 'Outil admin',
         'model' => $tool,                                              // optionnel : ajoute "Modifié il y a X"
         'editUrl' => route('admin.directory.edit', $tool->id),         // optionnel : ajoute le toggle Lecture/Édition
     ])

     $model et $editUrl fusionnent ici les anciens widgets flottants admin-activity-mini.blade.php
     et mode-toggle.blade.php (jusqu'ici 3 widgets empilés indépendamment, cf. le commentaire
     "sticky repoussé à 210px" de table-of-contents.blade.php - clutter déjà documenté) - 2026-07-12.
     Gate resserrée : "Modifié il y a X" était visible @auth (n'importe quel connecté) via
     admin-activity-mini.blade.php ; elle est désormais @can('view_admin_panel') comme le reste
     de cette barre - resserrement volontaire (moindre privilège), assumé.
--}}

@can('view_admin_panel')
@php
    $barLabel = $label ?? __('Admin');
    $barActions = $actions ?? [];
    $stateActions = [];

    if (isset($model) && $model?->id && class_exists(\Spatie\Activitylog\Models\Activity::class)) {
        $lastActivity = \Spatie\Activitylog\Models\Activity::query()
            ->where('subject_type', get_class($model))
            ->where('subject_id', $model->id)
            ->latest()
            ->first();
        if ($lastActivity) {
            $modifiedLabel = __('Modifié') . ' ' . $lastActivity->created_at->diffForHumans();
            if ($lastActivity->causer) {
                $modifiedLabel .= ' · ' . $lastActivity->causer->name;
            }
            $stateActions[] = ['info' => true, 'label' => $modifiedLabel, 'icon' => 'file-clock'];
        }
    }

    if (isset($editUrl)) {
        $stateActions[] = [
            'alpineClick' => 'editMode = !editMode',
            'labelExpr' => "editMode ? '✏️ Édition' : '👁 Lecture'",
            'icon' => 'pencil',
        ];
    }

    if (!empty($stateActions)) {
        if (!empty($barActions)) {
            $stateActions[] = ['divider' => true];
        }
        $barActions = array_merge($stateActions, $barActions);
    }
@endphp

<div class="core-admin-bar" role="toolbar" aria-label="{{ __('Outils admin') }}"
    @if(isset($editUrl))
        x-data="{ editMode: localStorage.getItem('laveille.edit_mode') === 'true', init() { document.body.classList.toggle('edit-mode', this.editMode); } }"
        x-effect="localStorage.setItem('laveille.edit_mode', editMode); document.body.classList.toggle('edit-mode', editMode);"
    @endif
>
    <div class="core-admin-bar__inner">
        <span class="core-admin-bar__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
        </span>
        <span class="core-admin-bar__label">{{ $barLabel }}</span>
        @if(count($barActions) && view()->exists('core::components.admin-action-menu'))
            <span class="core-admin-bar__separator" aria-hidden="true"></span>
            @include('core::components.admin-action-menu', ['actions' => $barActions])
        @endif
    </div>
</div>

<style>
    .core-admin-bar { position: fixed; top: 80px; right: 16px; z-index: 9000; pointer-events: auto; font-family: var(--f-body, system-ui, -apple-system, sans-serif); }
    .core-admin-bar__inner { display: inline-flex; align-items: center; gap: 8px; background: #ffffff; border: 1px solid rgba(0,0,0,0.06); border-radius: 12px; padding: 8px 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04); transition: box-shadow 0.2s ease; }
    .core-admin-bar__inner:hover { box-shadow: 0 2px 6px rgba(0,0,0,0.08), 0 8px 24px rgba(0,0,0,0.06); }
    .core-admin-bar__icon { display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 8px; background: var(--c-primary, #064E5A); color: #ffffff; flex-shrink: 0; }
    .core-admin-bar__icon svg { width: 14px; height: 14px; }
    .core-admin-bar__label { font-size: 13px; font-weight: 600; color: var(--c-dark, #1A1D23); letter-spacing: -0.01em; white-space: nowrap; user-select: none; }
    .core-admin-bar__separator { width: 1px; height: 20px; background: rgba(0,0,0,0.08); border-radius: 1px; flex-shrink: 0; }
    @media (max-width: 767px) { .core-admin-bar { right: 8px; top: 72px; } .core-admin-bar__inner { padding: 6px 8px; gap: 6px; border-radius: 10px; } .core-admin-bar__label { display: none; } .core-admin-bar__icon { width: 26px; height: 26px; } }
    @media print { .core-admin-bar { display: none !important; } }

    @if(isset($editUrl))
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
    @endif
</style>

@if(isset($editUrl))
    <script>
        (function () {
            var editUrl = @json($editUrl);
            document.addEventListener('click', function (e) {
                if (!document.body.classList.contains('edit-mode')) return;
                var target = e.target;
                // Ignore les clics à l'intérieur de la barre admin elle-même (badge + menu)
                if (target.closest && target.closest('.core-admin-bar')) return;
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
@endif
@endcan
