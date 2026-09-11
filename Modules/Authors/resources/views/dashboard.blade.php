<!DOCTYPE html>
<html lang="fr-CA">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Mon espace auteur · La veille de Stef</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400..800&amp;family=DM+Sans:wght@400;500;600&amp;display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body { background: #F8FAFB; font-family: 'DM Sans', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>

    @livewireStyles
</head>
<body>
    <div class="max-w-7xl mx-auto py-8 px-4">
        @if($authorProfile)
            @livewire('authors.author-dashboard', ['authorProfileId' => $authorProfile->id])
        @else
            <div class="bg-white rounded-xl shadow p-8 text-center">
                <h1 class="text-2xl font-bold text-gray-900">Mon espace auteur</h1>
                <p class="mt-4 text-gray-600">
                    Ton compte n'a pas encore de profil d'auteur. Il n'y a donc rien à modifier ici pour l'instant.
                </p>
                <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ url('/') }}" class="inline-flex items-center justify-center rounded-lg bg-[#0B7285] px-5 py-3 font-semibold text-white transition-colors hover:bg-[#095C6B] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0B7285]">
                        Retour à l'accueil
                    </a>
                    <a href="{{ url('/auteur/upgrade') }}" class="inline-flex items-center justify-center rounded-lg border border-[#0B7285] px-5 py-3 font-semibold text-[#0B7285] transition-colors hover:bg-[#0B7285]/5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0B7285]">
                        Découvrir l'espace auteur
                    </a>
                </div>
            </div>
        @endif
    </div>

    @livewireScripts
</body>
</html>
