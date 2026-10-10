{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Entrées de 1er niveau de la navigation desktop. Source unique : Modules\FrontTheme\Services\HeaderNavService (ticket #3013).
     Les trois méga-menus ont le même squelette (partials/nav/mega-panel) ; seules les données diffèrent. --}}
@foreach($nav as $node)
    @if(isset($node['children']))
        @include('fronttheme::partials.nav.mega-panel', ['node' => $node])
    @else
        <li><a href="{{ $node['url'] }}">{{ $node['label'] }}</a></li>
    @endif
@endforeach
