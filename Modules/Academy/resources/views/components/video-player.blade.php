{{--
    Composant : academy::components.video-player
    Auteur    : MEMORA solutions <info@memora.ca>

    Paramètres :
    - $playerUrl  : URL d'embed ScreenPal (depuis payload['player_url'])
    - $title      : Titre de la leçon (pour l'attribut title de l'iframe, a11y)

    SÉCURITÉ :
    - Ce composant NE DOIT être rendu QUE par la vue parente après vérification de l'accès.
    - L'URL player_url ne doit jamais être exposée dans le DOM si l'accès est refusé.
    - Le filigrane CSS (watermark) est superposé au-dessus de l'iframe.
    - pointer-events:none sur le filigrane → ne bloque pas la lecture vidéo.
    - aria-hidden=true sur le filigrane → invisible aux lecteurs d'écran.

    CONFIG SCREENPAL RECOMMANDÉE (doc, pas du code) :
    ─────────────────────────────────────────────────
    1. Visibilité de la vidéo : « Unlisted » ou « Private » (jamais « Public »).
       Une vidéo « Public » peut être cherchée sur ScreenPal.com et partagée librement.
    2. Domain lock : dans ScreenPal > Share > Embed settings, activer « Restrict to domains »
       et entrer uniquement laveille.ai (et www.laveille.ai).
       → Toute tentative d'embed depuis un autre domaine est rejetée par ScreenPal, peu importe
       le domaine ScreenPal utilisé pour servir le player (générique ou marque blanche).
    3. Domaine du lien player_url : go.screenpal.com (générique, zéro marque memora, zéro
       dépendance CNAME/certificat SSL tierce). share.screenpal.com est déprécié (ne résout
       plus) ; media.memora.solutions (CNAME marque blanche) reste possible mais inutile ici.
    4. NE PAS utiliser le lien de partage (go.screenpal.com/watch/…) comme player_url :
       utiliser l'URL d'embed (go.screenpal.com/player/…) qui répond au domain lock.
    5. Le payload seeder contient domain_lock: true comme rappel de cette exigence.
    6. Clic droit (« Copy Video Link »/« Copy Embed Code ») : ScreenPal injecte ce menu
       lui-même (pas le navigateur), donc AUCUN code côté laveille.ai ne peut l'affecter
       (iframe cross-origin). Le désactiver se fait UNIQUEMENT dans le tableau de bord
       ScreenPal : Bibliothèque > Gérer la vidéo > Partager > Contrôles du lecteur >
       Actions du spectateur > désactiver « Copy URL » et « Embed Video » (plan Solo Max/Team).
    7. Le domain lock protège l'INTÉGRATION (iframe) mais PAS la page « watch » directe sur
       screenpal.com/go.screenpal.com : quelqu'un avec le lien watch exact pourrait la
       regarder hors laveille.ai si la visibilité est « Unlisted ». Risque accepté standard
       (identique à Vimeo/YouTube) : l'ID n'est jamais exposé publiquement par l'app.
       Le script « player_appearance » (snippet officiel sp-embed-player de ScreenPal)
       a été testé et retourne 404 de façon fiable même avec Referer autorisé — NE PAS
       en dépendre, l'iframe seule (déjà en place) est plus robuste.
    ─────────────────────────────────────────────────
--}}
@props(['playerUrl', 'title' => 'Vidéo de leçon', 'poster' => null, 'aspectRatio' => null])

@php
    // Identifiant de l'utilisateur connecté (email ou nom, avec fallback)
    $watermarkLabel = auth()->check()
        ? (auth()->user()->name ?? auth()->user()->email ?? 'Utilisateur')
        : '';

    // Timestamp court (affiché à l'initialisation Alpine, mis à jour dynamiquement)
    $nowLabel = now()->setTimezone('America/Toronto')->format('Y-m-d H:i');

    // Ratio d'aspect dynamique (ex. ScreenPal fournit 1.320866 pour une vidéo non 16:9).
    // Repli 16/9 si absent/invalide, rétrocompatible avec toutes les vidéos existantes.
    $ratioCss = (is_numeric($aspectRatio) && $aspectRatio > 0) ? (string) $aspectRatio : '16/9';
@endphp

<div
    class="academy-video-wrapper"
    style="position: relative; width: 100%; aspect-ratio: {{ $ratioCss }}; background: #000 @if($poster) center / cover no-repeat url('{{ $poster }}') @endif; border-radius: 8px; overflow: hidden;"
>
    {{-- ── Iframe ScreenPal ── --}}
    <iframe
        src="{{ $playerUrl }}"
        title="{{ $title }}"
        frameborder="0"
        allowfullscreen
        allow="autoplay; fullscreen"
        sandbox="allow-same-origin allow-scripts allow-presentation"
        loading="lazy"
        style="position: absolute; inset: 0; width: 100%; height: 100%; border: none;"
        referrerpolicy="strict-origin-when-cross-origin"
    ></iframe>

    {{--
        ── Filigrane CSS dynamique ──
        - pointer-events: none   → l'utilisateur clique/interagit avec la vidéo normalement
        - aria-hidden="true"     → invisible aux lecteurs d'écran (pas de pollution a11y)
        - background répété en diagonale de tuiles texte semi-transparentes
        - Mis à jour en temps réel via Alpine (horodatage)
    --}}
    <div
        class="academy-watermark"
        aria-hidden="true"
        x-data="{ wLabel: '' }"
        x-init="
            const name = {{ json_encode($watermarkLabel) }};
            const updateLabel = () => {
                const now = new Date();
                const pad = n => String(n).padStart(2, '0');
                const ts = now.getFullYear() + '-' +
                           pad(now.getMonth() + 1) + '-' +
                           pad(now.getDate()) + ' ' +
                           pad(now.getHours()) + ':' +
                           pad(now.getMinutes());
                wLabel = name + ' · ' + ts;
            };
            updateLabel();
            setInterval(updateLabel, 60000);
        "
        style="
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 10;
            overflow: hidden;
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            align-content: flex-start;
            gap: 0;
        "
    >
        {{-- Tuiles répétées en grille décalée --}}
        @for ($row = 0; $row < 8; $row++)
            @for ($col = 0; $col < 5; $col++)
                <span
                    x-text="wLabel"
                    style="
                        display: block;
                        flex: 0 0 20%;
                        padding: {{ $row * 60 }}px 0 0 {{ ($col + ($row % 2 === 0 ? 0 : 2)) * 20 }}%;
                        font-size: 0.6rem;
                        font-family: monospace;
                        color: rgba(255,255,255,0.22);
                        white-space: nowrap;
                        transform: rotate(-25deg);
                        transform-origin: left center;
                        user-select: none;
                        line-height: 1;
                    "
                ></span>
            @endfor
        @endfor
    </div>
</div>
