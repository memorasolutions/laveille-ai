<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Modération des commentaires · Authors · laveille.ai</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
    <style>
        .sr-only { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
        .focus\:not-sr-only:focus { position:static; width:auto; height:auto; margin:0; clip:auto; }
        body { background:#F8FAFB; }
    </style>
</head>
<body>
    <a href="#cmq-main" class="sr-only focus:not-sr-only" style="display:inline-block; padding:12px 16px; background:#064E5A; color:white;">Aller au contenu principal</a>

    <header style="background:white; box-shadow:0 1px 3px rgba(6,78,90,0.1);">
        <div style="max-width:1200px; margin:0 auto; padding:24px;">
            <h1 style="font-size:28px; font-weight:700; color:#064E5A; margin:0;">💬 Modération des commentaires</h1>
            <p style="color:#5A6270; margin:8px 0 0; font-size:14px;">Approuver, marquer comme spam ou supprimer les commentaires des mini-sites auteurs.</p>
        </div>
    </header>

    <main id="cmq-main" style="max-width:1200px; margin:0 auto; padding:32px 24px;">
        @livewire('authors.comment-moderation-queue')
    </main>

    @livewireScripts
</body>
</html>
