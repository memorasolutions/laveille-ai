<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.user-frontend')

@section('title', __('Journal d\'activité') . ' - ' . config('app.name'))

@section('user-content')

<div style="display: flex !important; justify-content: space-between !important; align-items: flex-start !important; flex-wrap: wrap !important; margin-bottom: 20px;">
    <div>
        <h2 style="font-family: var(--f-heading, inherit); font-weight: 700; margin: 0 0 5px;">{{ __('Journal d\'activité') }}</h2>
        <p style="color: #777; margin: 0;">{{ __('Historique de vos actions sur le compte.') }}</p>
    </div>
</div>

<div style="display: flex; flex-direction: column; gap: 10px;">
    @forelse($activities as $activity)
    <div style="background: #fff; border: 1px solid #e5e5e5; border-radius: 6px; padding: 14px 16px; display: flex; align-items: flex-start; gap: 12px;">
        <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(11,114,133,0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <span style="color: var(--c-primary); font-size: 16px;">
                @switch($activity->description)
                    @case('created') + @break
                    @case('updated') ✎ @break
                    @case('deleted') ✕ @break
                    @default ● @break
                @endswitch
            </span>
        </div>
        <div style="flex: 1; min-width: 0;">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                <span style="font-weight: 600; font-size: 14px; color: #333;">
                    {{ match($activity->description) {
                        'created' => __('Création'),
                        'updated' => __('Modification'),
                        'deleted' => __('Suppression'),
                        default => ucfirst($activity->description)
                    } }}
                </span>
                <span style="display: inline-block; padding: 2px 8px; font-size: 11px; font-weight: 600; color: #777; background: #f3f4f6; border-radius: 4px;">
                    {{ $activity->log_name }}
                </span>
            </div>
            <p style="font-size: 12px; color: #999; margin: 0;">{{ $activity->created_at->diffForHumans() }}</p>
        </div>
    </div>
    @empty
    <div style="background: #fff; border: 1px solid #e5e5e5; border-radius: 6px; padding: 40px 16px; text-align: center; color: #999;">
        <p style="font-size: 32px; margin: 0 0 10px;">📋</p>
        <p style="margin: 0 0 5px;">{{ __('Aucune activité enregistrée.') }}</p>
        <p style="font-size: 12px; margin: 0;">{{ __('Vos actions sur le compte apparaîtront ici.') }}</p>
    </div>
    @endforelse
</div>

@if($activities->hasPages())
<div style="margin-top: 15px;">{{ $activities->links() }}</div>
@endif

@endsection
