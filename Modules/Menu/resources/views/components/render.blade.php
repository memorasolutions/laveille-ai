@props(['location', 'class' => '', 'itemClass' => '', 'linkClass' => ''])

@php
    $menu = app(\Modules\Menu\Services\MenuService::class)->getByLocation($location);
@endphp

@if($menu && $menu->items->isNotEmpty())
<ul {{ $attributes->merge(['class' => $class]) }}>
    @foreach($menu->items as $item)
        @include('menu::components._item', ['item' => $item, 'itemClass' => $itemClass, 'linkClass' => $linkClass, 'depth' => 0])
    @endforeach
</ul>
@endif
