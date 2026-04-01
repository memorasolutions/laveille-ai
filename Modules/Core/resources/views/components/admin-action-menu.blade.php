{{-- Composant réutilisable : menu actions admin (kebab ⋮)
     Usage: @include('core::components.admin-action-menu', ['actions' => [
         ['label' => 'Modifier', 'icon' => 'pencil', 'url' => route('admin.xxx.edit', $item)],
         ['label' => 'Voir', 'icon' => 'eye', 'url' => route('xxx.show', $item), 'target' => '_blank'],
         ['divider' => true],
         ['label' => 'Supprimer', 'icon' => 'trash-2', 'url' => route('admin.xxx.destroy', $item), 'method' => 'DELETE', 'confirm' => 'Supprimer ?', 'danger' => true],
     ]])
--}}
@php $menuId = 'action-' . uniqid(); @endphp
<div x-data="{ open: false }" class="position-relative" style="display: inline-block;">
    <button @click="open = !open" @click.outside="open = false"
            class="btn btn-sm btn-outline-secondary"
            style="border-radius: 6px; padding: 4px 8px; line-height: 1; font-size: 18px; min-width: 32px;"
            aria-label="{{ __('Actions') }}" aria-haspopup="true" :aria-expanded="open">
        &#8942;
    </button>
    <div x-show="open" x-cloak x-transition.opacity.duration.150ms
         style="position: absolute; right: 0; top: 100%; margin-top: 4px; min-width: 180px; background: #fff; border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,0.12); border: 1px solid #e5e7eb; padding: 4px 0; z-index: 50;">
        @foreach($actions as $action)
            @if(isset($action['divider']) && $action['divider'])
                <div style="border-top: 1px solid #f3f4f6; margin: 4px 0;"></div>
            @elseif(isset($action['method']) && $action['method'] !== 'GET')
                <form action="{{ $action['url'] }}" method="POST" style="margin: 0;">
                    @csrf
                    @if($action['method'] !== 'POST') @method($action['method']) @endif
                    <button type="submit"
                            @if(isset($action['confirm'])) onclick="return confirm('{{ $action['confirm'] }}')" @endif
                            style="display: flex; align-items: center; gap: 8px; width: 100%; padding: 8px 14px; border: none; background: none; cursor: pointer; font-size: 13px; color: {{ isset($action['danger']) && $action['danger'] ? '#DC2626' : '#374151' }}; text-align: left;"
                            onmouseover="this.style.background='{{ isset($action['danger']) && $action['danger'] ? '#FEF2F2' : '#F9FAFB' }}'"
                            onmouseout="this.style.background='transparent'">
                        @if(isset($action['icon']))<i data-lucide="{{ $action['icon'] }}" style="width: 14px; height: 14px;"></i>@endif
                        {{ $action['label'] }}
                    </button>
                </form>
            @else
                <a href="{{ $action['url'] }}"
                   @if(isset($action['target'])) target="{{ $action['target'] }}" @endif
                   style="display: flex; align-items: center; gap: 8px; padding: 8px 14px; font-size: 13px; color: {{ isset($action['danger']) && $action['danger'] ? '#DC2626' : '#374151' }}; text-decoration: none;"
                   onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                    @if(isset($action['icon']))<i data-lucide="{{ $action['icon'] }}" style="width: 14px; height: 14px;"></i>@endif
                    {{ $action['label'] }}
                </a>
            @endif
        @endforeach
    </div>
</div>
