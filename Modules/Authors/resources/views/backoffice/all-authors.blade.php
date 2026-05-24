<!DOCTYPE html>
<html lang="fr-CA">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tous les auteurs · Backoffice · La veille de Stef</title>
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,600,700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js" defer></script>
    @livewireStyles
</head>
<body class="bg-gray-50" style="font-family: 'Plus Jakarta Sans', system-ui, sans-serif;">
    <a href="#lv-main" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:bg-[#064E5A] focus:text-white focus:px-4 focus:py-2 focus:rounded">Aller au contenu principal</a>

    <header class="bg-[#064E5A] text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <a href="{{ url('/') }}" class="font-bold">← La veille de Stef</a>
            <span class="text-sm opacity-80">Backoffice · Auteurs</span>
        </div>
    </header>

    <main id="lv-main" role="main" class="max-w-7xl mx-auto p-6">
        @livewire('authors.all-authors-viewer')
    </main>

    @livewireScripts
</body>
</html>
