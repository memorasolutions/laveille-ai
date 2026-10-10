{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Widgets de la barre latérale mobile (hamburger), alimentés par la même source que le desktop
     (Modules\FrontTheme\Services\HeaderNavService, ticket #3013). --}}
@foreach($nav as $node)
    @if(isset($node['children']))
    <div class="widget link-widget">
        <div class="widget-title"><h3>{{ $node['label'] }}</h3></div>
        <ul>
            @foreach(\Modules\FrontTheme\Services\HeaderNavService::surfaceItems($node, 'sidebar') as $row)
            <li><a href="{{ $row['url'] }}">{{ $row['emoji'] !== '' ? $row['emoji'].' ' : '' }}@if($row['strong'])<strong>{{ $row['label'] }}</strong>@else{{ $row['label'] }}@endif</a></li>
            @endforeach
            @if(isset($node['sidebar_cta']))<li><a href="{{ $node['cta']['url'] }}"><strong>{{ $node['sidebar_cta']['label'] }}</strong></a></li>@endif
        </ul>
    </div>
    @elseif(isset($node['sidebar']))
    <div class="widget link-widget">
        <div class="widget-title"><h3>{{ $node['sidebar']['title'] }}</h3></div>
        <ul>
            <li><a href="{{ $node['url'] }}">{{ $node['sidebar']['emoji'] !== '' ? $node['sidebar']['emoji'].' ' : '' }}{{ $node['sidebar']['label'] }}</a></li>
        </ul>
    </div>
    @endif
@endforeach
