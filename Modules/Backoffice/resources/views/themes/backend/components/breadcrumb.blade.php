{{--
    Admin breadcrumb component.
    Usage: @section('breadcrumbs')
        <x-backoffice::themes.backend.components.breadcrumb :items="[
            ['label' => 'SEO', 'route' => 'admin.seo.index'],
            ['label' => 'Redirections'],
        ]" />
    @endsection

    Or shorter via the alias:
    <x-admin-breadcrumb :items="[['label' => 'Section'], ['label' => 'Page']]" />
--}}
@props(['items' => []])

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        @foreach($items as $item)
            @if(!$loop->last && !empty($item['route']))
                <li class="breadcrumb-item"><a href="{{ route($item['route'], $item['params'] ?? []) }}">{{ $item['label'] }}</a></li>
            @else
                <li class="breadcrumb-item active" aria-current="page">{{ $item['label'] }}</li>
            @endif
        @endforeach
    </ol>
</nav>
