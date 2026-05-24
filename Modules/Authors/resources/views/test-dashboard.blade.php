<!DOCTYPE html>
<html lang="fr-CA">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>[TEST LOCAL] Dashboard auteur</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400..800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: #F8FAFB; font-family: 'DM Sans', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
    @livewireStyles
</head>
<body>
    <div class="max-w-7xl mx-auto py-8 px-4">
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-6 text-center text-sm text-amber-800">
            ⚠️ <strong>Mode TEST LOCAL</strong> — Ce dashboard est accessible sans auth pour validation Playwright. Retiré en prod.
        </div>
        @livewire('authors.author-dashboard', ['authorProfileId' => $authorProfileId])
    </div>
    @livewireScripts
</body>
</html>
