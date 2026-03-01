<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@component('mail::message')
# {{ __('Bonjour') }} {{ $user->name }},

{{ __('Voici un récapitulatif de vos notifications récentes :') }}

@component('mail::table')
| {{ __('Titre') }} | {{ __('Message') }} | {{ __('Date') }} |
|-------|---------|------|
@foreach($notifications as $notification)
| {{ $notification->data['title'] ?? __('Sans titre') }} | {{ Str::limit($notification->data['message'] ?? '', 100) }} | {{ $notification->created_at->format('d/m/Y H:i') }} |
@endforeach
@endcomponent

@component('mail::button', ['url' => route('user.notifications')])
{{ __('Voir toutes les notifications') }}
@endcomponent

{{ __('Cordialement') }},<br>
{{ config('app.name') }}
@endcomponent
