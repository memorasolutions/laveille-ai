{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Un méga-menu : déclencheur + panneau desktop + repli mobile <ul class="sub-menu">.
     2026-09-15 (#2589) : les trois panneaux sont ancrés en left:0 ; le recadrage automatique de
     mega-menu.js s'occupe du débordement sans jamais rompre cet ancrage. --}}
<li class="menu-item-has-children has-mega-menu" x-data="megaMenu('{{ $node['id'] }}')">
    <button type="button" class="lv-mega-declencheur" x-ref="bouton" @click="toggle()" :aria-expanded="open" aria-controls="lv-mega-{{ $node['id'] }}">{{ $node['label'] }}<span class="lv-mega-chevron" aria-hidden="true">&#9662;</span></button>
    <div x-show="open" x-ref="panneau" id="lv-mega-{{ $node['id'] }}" x-cloak x-transition.opacity.duration.100ms
        style="position:absolute;left:0;right:0;top:100%;background:#fff;border-radius:16px;box-shadow:0 12px 36px rgba(0,0,0,0.14);padding:{{ $node['layout']['padding'] }}px;z-index:9999;border:1px solid #E5E7EB;max-height:calc(100vh - 170px);overflow-y:auto;overscroll-behavior:contain;"
        @click.outside="close()"
        aria-label="{{ $node['aria'] }}">
        <div style="display:grid;grid-template-columns:{{ $node['layout']['columns'] }};gap:{{ $node['layout']['gap'] }}px;">
            @foreach($node['children'] as $group)
            <div>
                <div style="font-family:var(--f-heading,'Plus Jakarta Sans',sans-serif);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--c-text-muted,#6E7687);margin-bottom:10px;">{{ $group['group'] }}</div>
                @foreach($group['items'] as $item)
                <a href="{{ $item['url'] }}" style="display:flex;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;{{ (empty($item['tail']) || ($group['margin_last'] ?? false)) ? 'margin-bottom:2px;' : '' }}" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                    <i class="{{ $item['icon'] }}" aria-hidden="true" style="font-size:16px;line-height:24px;width:20px;text-align:center;flex:0 0 20px;color:var(--c-primary,#064E5A);"></i>
                    <div><div style="font-weight:700;font-size:14px;color:var(--c-dark,#1A1D23);">{{ $item['label'] }}</div><div style="font-size:12px;color:var(--c-text-muted,#6E7687);">{{ $item['subtitle'] }}</div></div>
                </a>
                @endforeach
            </div>
            @endforeach
        </div>
        @if(isset($node['cta']))
        <div style="border-top:1px solid #E5E7EB;margin-top:18px;padding-top:14px;text-align:center;">
            <a href="{{ $node['cta']['url'] }}" style="font-size:13px;font-weight:700;color:var(--c-primary,#064E5A);text-decoration:none!important;">{{ $node['cta']['label'] }} →</a>
        </div>
        @endif
    </div>
    {{-- Fallback sub-menu mobile --}}
    <ul class="sub-menu">
        @foreach(\Modules\FrontTheme\Services\HeaderNavService::surfaceItems($node, 'mobile') as $row)
        <li><a href="{{ $row['url'] }}">{{ $row['emoji'] !== '' ? $row['emoji'].' ' : '' }}{{ $row['label'] }}</a></li>
        @endforeach
        @if(isset($node['mobile_cta']))<li><a href="{{ $node['cta']['url'] }}">{{ $node['mobile_cta']['label'] }}</a></li>@endif
    </ul>
</li>
