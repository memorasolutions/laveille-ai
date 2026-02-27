@php
    $hasChildren = $item->children->isNotEmpty();
    $isActive = $item->isActive();
    $url = $item->resolveUrl();
@endphp

<li class="{{ $itemClass }} {{ $isActive ? 'active' : '' }} {{ $hasChildren ? 'has-children' : '' }} {{ $item->css_classes }}">
    <a href="{{ $url }}" target="{{ $item->target }}" class="{{ $linkClass }} {{ $isActive ? 'active' : '' }}">
        @if($item->icon)
            <i data-lucide="{{ $item->icon }}"></i>
        @endif
        {{ $item->title }}
    </a>
    @if($hasChildren)
    <ul>
        @foreach($item->children as $child)
            @include('menu::components._item', ['item' => $child, 'itemClass' => $itemClass, 'linkClass' => $linkClass, 'depth' => $depth + 1])
        @endforeach
    </ul>
    @endif
</li>
