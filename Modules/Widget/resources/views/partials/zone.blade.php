@php
    $widgets = \Modules\Widget\Services\WidgetService::getWidgetsForZone($zone ?? '');
@endphp

@foreach($widgets as $widget)
    @include('widget::partials.types.' . $widget->type, ['widget' => $widget])
@endforeach
