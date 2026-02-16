<x-mail::message>
# {{ $title ?? config('app.name') }}

{{ $slot }}

@isset($actionUrl)
<x-mail::button :url="$actionUrl" :color="$actionColor ?? 'primary'">
{{ $actionText ?? 'Voir' }}
</x-mail::button>
@endisset

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
