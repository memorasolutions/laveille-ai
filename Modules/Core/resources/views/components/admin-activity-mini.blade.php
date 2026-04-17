{{-- Modules/Core/resources/views/components/admin-activity-mini.blade.php
     Affiche la dernière activity (Spatie LogsActivity) en inline compact.
     Usage : @include('core::components.admin-activity-mini', ['model' => $tool])
     Auto-silencieux si package absent ou aucune activity enregistrée. --}}

@auth
@php
    $activity = null;
    if (class_exists(\Spatie\Activitylog\Models\Activity::class) && isset($model) && $model?->id) {
        $activity = \Spatie\Activitylog\Models\Activity::query()
            ->where('subject_type', get_class($model))
            ->where('subject_id', $model->id)
            ->latest()
            ->first();
    }
@endphp

@if($activity)
<div class="core-admin-activity-mini" role="status" aria-live="polite">
    <span aria-hidden="true" class="core-admin-activity-mini__icon">📝</span>
    <span class="core-admin-activity-mini__text">
        {{ __('Modifié') }} {{ $activity->created_at->diffForHumans() }}@if($activity->causer) · {{ $activity->causer->name }}@endif
    </span>
</div>

<style>
    .core-admin-activity-mini {
        position: fixed; top: 132px; right: 16px; z-index: 8999;
        display: inline-flex; align-items: center; gap: 6px;
        font-family: var(--f-body, system-ui, -apple-system, sans-serif);
        font-size: 11px; font-weight: 500; color: #4B5563; font-style: italic;
        padding: 5px 10px; background: #ffffff;
        border: 1px solid rgba(0,0,0,0.06); border-radius: 8px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .core-admin-activity-mini__icon { flex-shrink: 0; font-size: 10px; line-height: 1; }
    @media (max-width: 767px) {
        .core-admin-activity-mini { top: 118px; right: 8px; font-size: 10px; padding: 4px 8px; max-width: 240px; }
    }
    @media print { .core-admin-activity-mini { display: none !important; } }
</style>
@endif
@endauth
