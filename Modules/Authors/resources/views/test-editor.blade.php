<!DOCTYPE html>
<html lang="fr-CA">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>[TEST LOCAL] Éditeur auteur (Tiptap)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400..800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { background: #F8FAFB; font-family: 'DM Sans', sans-serif; margin: 0; }
        [x-cloak] { display: none !important; }
        .sr-only { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
    </style>
    @livewireStyles
</head>
<body>
    <div style="max-width: 900px; margin: 0 auto; padding: 32px 20px;">
        <div style="background:#FEF3C7; border:1px solid #FDE68A; border-radius:8px; padding:12px; margin-bottom:24px; text-align:center; font-size:13px; color:#92400E;">
            ⚠️ <strong>Mode TEST LOCAL</strong> – Éditeur accessible sans auth pour validation Playwright. Retiré en prod.
        </div>
        @livewire('authors.author-editor', ['authorProfile' => $authorProfile])
    </div>
    {{-- @stack('head')/'styles' rendus ICI (pas dans <head>) : @livewire() ci-dessus n'utilise
         PAS @extends, donc le @push('head') du composant (Modules/Authors/resources/views/
         livewire/author-editor.blade.php) n'est collecté qu'APRÈS son rendu (ordre séquentiel
         Blade sans indirection @extends). Sur les pages réelles du site (@extends(layout)),
         l'évaluation enfant-d'abord de Blade règle ça nativement — ce layout de test n'a pas
         cette indirection, d'où ce placement en fin de document. --}}
    @stack('head')
    @stack('styles')
    @livewireScripts
    @stack('scripts')
</body>
</html>
