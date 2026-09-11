<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!DOCTYPE html>
<html lang="fr-CA">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Nouvel article · Mon espace auteur · La veille de Stef</title>

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
    <div class="max-w-4xl mx-auto py-8 px-4">
        <a href="{{ route('authors.dashboard') }}"
           class="inline-flex items-center gap-2 text-[#0B7285] font-semibold mb-6 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0B7285]">
            ← Retour au tableau de bord
        </a>

        <h1 class="text-2xl font-bold text-gray-900 mb-6">Nouvel article</h1>

        @livewire('authors.author-editor', ['authorProfile' => $authorProfile])
    </div>

    {{-- @stack('head')/'styles' rendus ICI (pas dans <head>) : cette page n'utilise pas
         @extends, donc les @push('head')/@push('styles') du composant enfant
         (Modules/Authors/resources/views/livewire/author-editor.blade.php) ne sont collectés
         qu'APRÈS son rendu (ordre séquentiel Blade sans indirection @extends), même
         contrainte déjà documentée dans test-editor.blade.php. --}}
    @stack('head')
    @stack('styles')
    @livewireScripts
    @stack('scripts')
</body>
</html>
