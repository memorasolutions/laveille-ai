<x-emails.base title="Bienvenue sur {{ config('app.name') }}">
    <p>Bonjour {{ $user->name }},</p>

    <p>Bienvenue sur <strong>{{ config('app.name') }}</strong> ! Votre compte a été créé avec succès.</p>

    <p>Vous pouvez dès maintenant accéder à votre tableau de bord :</p>

    <x-slot:action url="{{ url('/admin') }}">
        Accéder au tableau de bord
    </x-slot:action>

    <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>

    <p>Cordialement,<br>L'équipe {{ config('app.name') }}</p>
</x-emails.base>
