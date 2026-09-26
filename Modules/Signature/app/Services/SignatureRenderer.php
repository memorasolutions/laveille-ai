<?php

declare(strict_types=1);

namespace Modules\Signature\Services;

/**
 * Rendu HTML canonique d'une signature - table-based, styles exclusivement en ligne, aucune
 * propriété CSS moderne (flex/grid/position), attributs width/height codés en dur sur chaque
 * image (section 9 et 12.3 du plan). Tous les champs texte sont échappés avant interpolation
 * (section 10 - assainissement du HTML produit).
 *
 * ÉCART ASSUMÉ AU PLAN (section 4) : le plan demande UNE fonction de rendu partagée entre
 * l'aperçu, la copie et le téléchargement - en pratique cette fonction unique est `renderSignature`
 * en JavaScript (public/assets/tools/signature-courriel/signature-render.js), seule exécutable
 * côté navigateur sans aller-retour serveur (contrainte « zéro latence » de la section 4). Cette
 * classe PHP est un SECOND moteur, volontairement tenu identique champ pour champ et gabarit pour
 * gabarit au moteur JS (même contrat de données, mêmes 8 gabarits depuis le LOT 2, mêmes règles
 * d'échappement et de dimensionnement), nécessaire pour deux usages que le JS ne peut pas couvrir :
 * la route serveur
 * `/export/outlook-classique/{token}` (disponibilité garantie même si le JS échoue) et les tests
 * Pest (rendu et assainissement) - Pest ne peut pas exécuter le moteur JS. Toute modification d'un
 * gabarit doit être répercutée dans les DEUX fichiers - signalé explicitement pour ne pas être lu
 * comme une violation silencieuse du DRY strict du projet.
 *
 * REFONTE LOT 1 (2026-09-25) : `render()` ne contient plus une méthode par gabarit. Il lit
 * l'agencement du gabarit dans {@see SignatureTemplateRegistry} (registre déclaratif, source
 * unique) et l'assemble à partir de composants de bloc réutilisables (identité, contact, réseaux,
 * mentions, CTA, image). Deux assembleurs génériques suffisaient pour les 4 gabarits du LOT 1 :
 * l'agencement `standard` (minimal/professionnel/portrait - une rangée image + colonne de texte,
 * dont seuls la position de l'image, l'alignement et le cadre varient) et l'agencement `compact`,
 * gardé à part parce que sa règle de composition (en-tête une ligne, contact groupé) diffère
 * réellement de la colonne de texte standard - ce n'est pas une ressemblance de forme à fusionner
 * (règle DRY du projet : fusionner seulement ce qui encode la même règle).
 *
 * LOT 2 (2026-09-25) : un 3e assembleur, `assembleVertical` (blocs empilés), complète la liste -
 * plafond volontaire de 3 familles. Les 4 nouveaux gabarits (vertical, banniere, executive, social)
 * n'ajoutent QUE des propriétés de registre et, pour un seul besoin réel (la bannière cliquable),
 * un composant de bloc supplémentaire (`blocBanniere`) - jamais un 4e assembleur. `image_role` peut
 * désormais être `null` dans le registre (executive, banniere) : `assembleStandard`/`assembleCompact`
 * traitent alors l'absence d'image comme un cas normal, pas une exception.
 *
 * LOT 3 (2026-09-25) - fonctions de complétude, TOUTES au niveau du CONTENU (jamais du registre,
 * qui reste une affaire d'agencement PAR gabarit) :
 *   - `pronouns` (optionnel, max 30) : rendu discrètement après le nom, dans TOUS les gabarits qui
 *     affichent une identité ({@see self::pronounsHtml()}), y compris l'en-tête dense du gabarit
 *     compact ({@see self::blocEnTeteCompact()}).
 *   - `portrait_shape` ∈ {carre, rond}, défaut `carre` : `rond` FORCE un cercle sur le PORTRAIT
 *     (jamais le logo), ajouté en fin de style pour gagner sur tout arrondi déjà posé par le
 *     gabarit ({@see self::resolveImageStyle()} - la dernière déclaration d'une même propriété
 *     l'emporte dans un attribut `style=""`). `carre` (défaut) ne change RIEN au style du gabarit -
 *     le brief ne décrit un effet que pour `rond` ; non-régression stricte garantie par construction.
 *   - `font_scale` ∈ {petite, moyenne, grande}, défaut `moyenne` (facteur 1.0 - AUCUN changement de
 *     sortie par rapport à avant ce lot). Multiplie les tailles de police de BASE via
 *     {@see self::scalePx()}, toujours en px entiers (jamais rem/em - compatibilité Outlook).
 *   - Sécurité mode sombre (angle mort nommé par le club des sages) : {@see self::wrap()} pose
 *     désormais un fond blanc EXPLICITE sur l'enveloppe (protège le texte si l'hôte devient sombre
 *     sans toucher au reste) et les liens de contact ({@see self::blocContact()},
 *     {@see self::blocContactCompact()}, {@see self::blocReseaux()}) portent leur PROPRE couleur
 *     explicite au lieu d'un `color:inherit` - un moteur de conversion sombre qui ne réécrit que les
 *     éléments porteurs d'une valeur de couleur littérale ne doit jamais en oublier un. Aucune
 *     media query dark spécifique (sur-ingénierie exclue par le brief) - seulement des couleurs
 *     robustes, jamais une propriété de plus qu'il n'en faut.
 *   - `social` (LOT 2) renforcé : la QC visuelle du 2026-09-25 l'a jugé trop proche de `minimal`.
 *     {@see self::blocReseaux()} rend désormais chaque réseau sur SA PROPRE ligne quand
 *     `$emphasis` est vrai (changement de STRUCTURE, pas seulement de taille) avec une icône
 *     nettement agrandie - jamais utilisé par les 7 autres gabarits, sortie inchangée pour eux.
 *   - Masquage des blocs vides : déjà assuré PAR CONSTRUCTION depuis le LOT 1/2 (chaque composant
 *     renvoie une chaîne vide s'il n'a rien à afficher) - vérifié maintenant par un test dédié
 *     couvrant les 8 gabarits avec des données minimales, aucun changement de code requis ici.
 */
final class SignatureRenderer
{
    private const FONT_STACK = [
        'Arial' => "Arial, Helvetica, sans-serif",
        'Helvetica' => "Helvetica, Arial, sans-serif",
        'Verdana' => "Verdana, Geneva, sans-serif",
        'Georgia' => "Georgia, 'Times New Roman', serif",
        'Tahoma' => "Tahoma, Geneva, sans-serif",
    ];

    /**
     * LOT 3 - même patron que FONT_STACK ci-dessus, DÉLIBÉRÉMENT indépendant des constantes
     * publiques de {@see SignatureContentValidator} (PORTRAIT_SHAPES/FONT_SCALES) : cette classe
     * reste PURE (aucune dépendance sur Illuminate\Validation, voir docblock de classe et le rapport
     * du LOT 1 - un script autonome sans Laravel doit pouvoir l'appeler). Une valeur inconnue
     * retombe simplement sur le défaut, exactement comme FONT_STACK retombe sur Arial.
     */
    private const FONT_SCALE_FACTORS = [
        'petite' => 0.9,
        'moyenne' => 1.0,
        'grande' => 1.15,
    ];

    private const PORTRAIT_SHAPES = ['carre', 'rond'];

    private const SOCIAL_ICONS = [
        'linkedin' => '💼',
        'facebook' => '📘',
        'instagram' => '📸',
        'x' => '✖️',
        'youtube' => '▶️',
        'website' => '🌐',
    ];

    private const SOCIAL_LABELS = [
        'linkedin' => 'LinkedIn',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'x' => 'X',
        'youtube' => 'YouTube',
        'website' => 'Site web',
    ];

    /**
     * @param  array<string, mixed>  $content  contrat de section 11.2 (JSON validé, jamais un
     *                                          schéma libre - voir SignatureContentValidator)
     * @param  array<string, array{url: string, width: int, height: int}>  $images  clé = rôle
     *                                          (logo|portrait|banniere), déjà résolu en URL publique
     */
    public static function render(array $content, string $template, array $images = []): string
    {
        $template = SignatureTemplateRegistry::has($template) ? $template : 'minimal';
        $f = self::normalize($content);
        $accent = self::safeHexColor($f['accent_color'] ?? null) ?? '#064E5A';
        $fontStack = self::FONT_STACK[$f['font_family']] ?? self::FONT_STACK['Arial'];
        // LOT 3 : facteur d'échelle des tailles de police de base - 1.0 (moyenne, défaut) ne change
        // RIEN à la sortie par rapport à avant ce lot (non-régression).
        $fontScale = self::FONT_SCALE_FACTORS[$f['font_scale']] ?? 1.0;
        $def = SignatureTemplateRegistry::definition($template);

        $body = match ($def['layout']) {
            'compact' => self::assembleCompact($def, $f, $images, $accent, $fontStack, $fontScale),
            'vertical' => self::assembleVertical($def, $f, $images, $accent, $fontStack, $fontScale),
            default => self::assembleStandard($def, $f, $images, $accent, $fontStack, $fontScale),
        };

        // Propriété TRANSVERSE (LOT 2) : n'importe quel agencement peut porter une bannière
        // cliquable en plus de son corps habituel - jamais un 4e assembleur pour ce seul besoin
        // (voir docblock de SignatureTemplateRegistry).
        if (! empty($def['banner_role'])) {
            $body .= self::blocBanniere($images[$def['banner_role']] ?? null, $f);
        }

        return $body;
    }

    /**
     * Autorise seulement http:// et https:// (section 10) - jamais javascript: ni un autre
     * schéma actif. Une valeur vide ou absente est considérée sûre (champ facultatif).
     */
    public static function isSafeUrl(?string $url): bool
    {
        $url = trim((string) $url);
        if ($url === '') {
            return true;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return is_string($scheme) && in_array(strtolower($scheme), ['http', 'https'], true);
    }

    private static function safeHexColor(?string $hex): ?string
    {
        $hex = trim((string) $hex);

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $hex) === 1 ? $hex : null;
    }

    /** @return array<string, mixed> */
    private static function normalize(array $content): array
    {
        return [
            'first_name' => trim((string) ($content['first_name'] ?? '')),
            'last_name' => trim((string) ($content['last_name'] ?? '')),
            'job_title' => trim((string) ($content['job_title'] ?? '')),
            'organization' => trim((string) ($content['organization'] ?? '')),
            'email' => trim((string) ($content['email'] ?? '')),
            'phone' => trim((string) ($content['phone'] ?? '')),
            'mobile' => trim((string) ($content['mobile'] ?? '')),
            'website' => self::isSafeUrl($content['website'] ?? null) ? trim((string) ($content['website'] ?? '')) : '',
            'address' => trim((string) ($content['address'] ?? '')),
            'tagline' => trim((string) ($content['tagline'] ?? '')),
            'cta_text' => trim((string) ($content['cta_text'] ?? '')),
            'cta_url' => self::isSafeUrl($content['cta_url'] ?? null) ? trim((string) ($content['cta_url'] ?? '')) : '',
            'accent_color' => $content['accent_color'] ?? null,
            'font_family' => $content['font_family'] ?? 'Arial',
            'social_links' => is_array($content['social_links'] ?? null) ? $content['social_links'] : [],
            'mention_lines' => is_array($content['mention_lines'] ?? null) ? $content['mention_lines'] : [],
            // LOT 3 (2026-09-25).
            'pronouns' => trim((string) ($content['pronouns'] ?? '')),
            'portrait_shape' => in_array($content['portrait_shape'] ?? null, self::PORTRAIT_SHAPES, true)
                ? $content['portrait_shape']
                : 'carre',
            'font_scale' => is_string($content['font_scale'] ?? null) && array_key_exists($content['font_scale'], self::FONT_SCALE_FACTORS)
                ? $content['font_scale']
                : 'moyenne',
        ];
    }

    private static function e(mixed $value): string
    {
        return htmlspecialchars(trim((string) $value), ENT_QUOTES, 'UTF-8');
    }

    private static function fullName(array $f): string
    {
        return trim($f['first_name'].' '.$f['last_name']);
    }

    /**
     * LOT 3 - multiplie une taille de police de BASE (ex. `15px`) par le facteur d'échelle courant,
     * toujours arrondie à l'entier et rendue en px (jamais rem/em - compatibilité Outlook). À
     * facteur 1.0 (moyenne, défaut), retourne EXACTEMENT `$sizePx` - c'est ce qui garantit la
     * non-régression des tailles littérales déjà couvertes par les tests existants.
     */
    private static function scalePx(string $sizePx, float $scale): string
    {
        $base = (int) preg_replace('/\D+/', '', $sizePx);

        return max(1, (int) round($base * $scale)).'px';
    }

    /**
     * LOT 3 - pronoms (optionnel) : suffixe discret ajouté APRÈS le nom, jamais avant, dans un
     * `<span>` de poids normal (le nom reste seul en gras) et de couleur secondaire explicite
     * (#374151, cohérente avec le reste du texte secondaire du moteur). Chaîne vide sans pronoms -
     * garantit que la sortie par défaut reste inchangée caractère pour caractère.
     */
    private static function pronounsHtml(array $f, float $scale): string
    {
        if ($f['pronouns'] === '') {
            return '';
        }

        return ' <span style="font-weight:normal;font-size:'.self::scalePx('12px', $scale).';color:#374151;">('.self::e($f['pronouns']).')</span>';
    }

    /**
     * LOT 3 - résout le style d'image en tenant compte de `portrait_shape`. `rond` ajoute
     * `border-radius:50%` EN FIN de chaîne (une déclaration CSS répétée dans le même attribut
     * `style=""` retient la DERNIÈRE - un cercle plein gagne donc même sur un gabarit qui posait
     * déjà un arrondi partiel, ex. le gabarit `portrait`) et N'AFFECTE QUE le rôle `portrait`,
     * jamais `logo`. `carre` (défaut) ne touche à rien : le style du gabarit reste EXACTEMENT ce
     * qu'il était avant ce lot (non-régression).
     *
     * @param  array<string, mixed>  $def
     * @param  array<string, mixed>  $f
     */
    private static function resolveImageStyle(?string $imageRole, array $def, array $f): string
    {
        $style = (string) ($def['image_style'] ?? '');
        if ($imageRole === 'portrait' && $f['portrait_shape'] === 'rond') {
            $style .= 'border-radius:50%;';
        }

        return $style;
    }

    /**
     * LOT 3 - fond blanc EXPLICITE sur l'enveloppe de la signature (angle mort du mode sombre,
     * nommé par le club des sages) : si le client courriel bascule le corps du message en sombre
     * sans réécrire ce bloc, le texte (couleurs foncées, inchangées) reste lisible sur SON PROPRE
     * fond clair plutôt que de se retrouver sur un fond sombre qu'il n'a jamais prévu. Aucune media
     * query dark spécifique (exclu par le brief) - une seule déclaration explicite, proportionnée.
     */
    private static function wrap(string $inner): string
    {
        return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;border-collapse:collapse;"><tr><td style="background-color:#ffffff;">'.$inner.'</td></tr></table>';
    }

    // ------------------------------------------------------------------
    // Assembleurs (un par famille d'agencement du registre)
    // ------------------------------------------------------------------

    /**
     * Agencement `standard` (minimal, professionnel, portrait, executive, social) : une rangée
     * table à une cellule image (optionnelle) et une cellule colonne de texte. Tout ce qui
     * distingue ces gabarits - position de l'image, alignement vertical, cadre du haut,
     * séparateur, mise en avant des réseaux, filet sous le nom - vient du registre, jamais d'une
     * méthode dédiée par gabarit. `image_role` peut être `null` (executive) : la cellule d'image
     * disparaît alors entièrement, la colonne de texte occupe toute la largeur de la rangée.
     *
     * @param  array<string, mixed>  $def
     * @param  array<string, mixed>  $f
     * @param  array<string, array{url: string, width: int, height: int}>  $images
     */
    private static function assembleStandard(array $def, array $f, array $images, string $accent, string $fontStack, float $fontScale = 1.0): string
    {
        $imageRole = $def['image_role'] ?? null;
        $image = $imageRole !== null
            ? self::blocImage($images[$imageRole] ?? null, $def['image_alt'] ?? '', self::resolveImageStyle($imageRole, $def, $f), $f)
            : '';

        $paddingTop = $def['cell_padding_top'] !== null ? 'padding-top:'.$def['cell_padding_top'].';' : '';
        $gapSide = $def['image_side'] === 'left' ? 'padding-right:' : 'padding-left:';

        $imageCellStyle = $paddingTop.$gapSide.$def['image_gap'].';vertical-align:'.$def['valign'].';';

        $textCellStyle = $paddingTop.'vertical-align:'.$def['valign'].';';
        if ($def['text_divider_with_image']) {
            $textCellStyle .= $image !== ''
                ? 'border-left:2px solid '.self::e($accent).';padding-left:10px;'
                : 'border-left:0;';
        }

        $textColumn = self::blocColonneTexte(
            $f,
            $accent,
            $fontStack,
            $def['name_size'],
            (bool) ($def['social_emphasis'] ?? false),
            (bool) ($def['name_divider'] ?? false),
            $fontScale
        );

        $imageCell = $image !== '' ? '<td style="'.$imageCellStyle.'">'.$image.'</td>' : '';
        $textCell = '<td style="'.$textCellStyle.'">'.$textColumn.'</td>';
        $cells = $def['image_side'] === 'left' ? $imageCell.$textCell : $textCell.$imageCell;

        $tableAttrs = 'role="presentation" cellpadding="0" cellspacing="0" border="0"';
        if ($def['frame_border_top'] !== null) {
            $tableAttrs .= ' style="border-top:'.$def['frame_border_top'].' solid '.self::e($accent).';"';
        }

        return self::wrap('<table '.$tableAttrs.'><tr>'.$cells.'</tr></table>');
    }

    /**
     * Agencement `vertical` (LOT 2) : blocs EMPILÉS dans une seule colonne - image (optionnelle),
     * puis identité, contact, accroche, réseaux, mentions et CTA, chacun réutilisé tel quel (les
     * composants de bloc produisent déjà des `<tr>` autonomes, compatibles avec une table à une
     * seule colonne comme avec la colonne de texte des agencements ci-dessus). Bon pour mobile et
     * les signatures longues.
     *
     * LOT 4 (2026-09-26) - 3 propriétés transverses de plus, TOUTES à défaut neutre (absentes du
     * gabarit `vertical` d'origine, sortie inchangée pour lui) :
     *   - `image_position` (`top` par défaut, ou `bottom`) : place l'image après les réseaux plutôt
     *     qu'en tête (gabarit `logo_bas`).
     *   - `centered` (bool) : `text-align:center` sur le conteneur (hérité par tout le texte, y
     *     compris le bouton CTA en `display:inline-block`) + `margin:0 auto` sur l'image, pour un
     *     bloc entièrement centré (gabarit `photo_centree`). Repli assumé et documenté : Outlook de
     *     bureau (moteur Word) applique `text-align` de façon moins fiable qu'un client web/mobile -
     *     dégradation gracieuse (contenu lisible, simplement aligné à gauche), jamais un plantage.
     *   - `contact_divider` (bool) : filet horizontal fin entre le bloc identité et le bloc contact
     *     (gabarit `coordonnees_sous_nom`) - à ne pas confondre avec `name_divider`, qui souligne le
     *     nom LUI-MÊME. Contenu non vide (`&nbsp;`) pour ne jamais ressembler à une cellule orpheline
     *     laissée par erreur.
     *
     * @param  array<string, mixed>  $def
     * @param  array<string, mixed>  $f
     * @param  array<string, array{url: string, width: int, height: int}>  $images
     */
    private static function assembleVertical(array $def, array $f, array $images, string $accent, string $fontStack, float $fontScale = 1.0): string
    {
        $imageRole = $def['image_role'] ?? null;
        $centered = (bool) ($def['centered'] ?? false);
        $imageExtraStyle = $imageRole !== null ? self::resolveImageStyle($imageRole, $def, $f) : '';
        if ($centered) {
            $imageExtraStyle .= 'margin:0 auto;';
        }
        $image = $imageRole !== null
            ? self::blocImage($images[$imageRole] ?? null, $def['image_alt'] ?? '', $imageExtraStyle, $f)
            : '';

        $imagePosition = $def['image_position'] ?? 'top';
        $imageGap = self::e($def['image_gap'] ?? '10px');
        $imageRow = $image !== '' ? '<tr><td style="padding-'.($imagePosition === 'bottom' ? 'top' : 'bottom').':'.$imageGap.';">'.$image.'</td></tr>' : '';
        $reseauxHtml = self::blocReseaux($f['social_links'], $fontStack, $accent, (bool) ($def['social_emphasis'] ?? false), $fontScale);
        $contactDividerRow = ($def['contact_divider'] ?? false)
            ? '<tr><td style="padding-top:8px;padding-bottom:8px;border-top:1px solid '.self::e($accent).';font-size:1px;line-height:1px;">&nbsp;</td></tr>'
            : '';

        $tableStyle = $centered ? ' style="text-align:center;"' : '';
        $html = '<table role="presentation" cellpadding="0" cellspacing="0" border="0"'.$tableStyle.'>';
        if ($imagePosition !== 'bottom') {
            $html .= $imageRow;
        }
        $html .= self::blocIdentite($f, $accent, $fontStack, $def['name_size'] ?? '16px', (bool) ($def['name_divider'] ?? false), $fontScale);
        $html .= $contactDividerRow;
        $html .= self::blocContact($f, $fontStack, $fontScale);
        $html .= self::blocTagline($f, $fontStack, $fontScale);
        if ($imagePosition !== 'bottom') {
            $html .= $reseauxHtml;
        }
        $html .= self::blocMentions($f['mention_lines'], $fontStack, $fontScale);
        $html .= self::blocCta($f, $accent, $fontStack, $fontScale);
        if ($imagePosition === 'bottom') {
            $html .= $reseauxHtml;
            $html .= $imageRow;
        }
        $html .= '</table>';

        return self::wrap($html);
    }

    /**
     * Agencement `compact` (compact, banniere) : logo (optionnel) puis un bloc dense de 2-3 lignes
     * (en-tête nom + poste/organisation sur une seule ligne, contact groupé en ligne, réseaux
     * sociaux). Pas de tagline, pas de CTA texte, pas de mentions, pas d'adresse ni de cellulaire :
     * c'est la règle de cet agencement, pas un oubli. `image_role` peut être `null` (banniere) :
     * l'identité visuelle du gabarit vient alors de la bannière ajoutée par `render()`, pas d'un
     * logo à côté du texte.
     *
     * @param  array<string, mixed>  $def
     * @param  array<string, mixed>  $f
     * @param  array<string, array{url: string, width: int, height: int}>  $images
     */
    private static function assembleCompact(array $def, array $f, array $images, string $accent, string $fontStack, float $fontScale = 1.0): string
    {
        $imageRole = $def['image_role'] ?? null;
        $image = $imageRole !== null
            ? self::blocImage($images[$imageRole] ?? null, $def['image_alt'] ?? '', self::resolveImageStyle($imageRole, $def, $f), $f)
            : '';

        $inner = '<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>';
        if ($image !== '') {
            $inner .= '<td style="padding-right:'.$def['image_gap'].';vertical-align:middle;">'.$image.'</td>';
        }
        $inner .= '<td style="vertical-align:middle;">';
        $inner .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0">';
        $inner .= self::blocEnTeteCompact($f, $accent, $fontStack, $fontScale);
        $inner .= self::blocContactCompact($f, $fontStack, $fontScale);
        $inner .= self::blocReseaux($f['social_links'], $fontStack, $accent, false, $fontScale);
        $inner .= '</table>';
        $inner .= '</td></tr></table>';

        return self::wrap($inner);
    }

    // ------------------------------------------------------------------
    // Composants de bloc (réutilisés par les assembleurs ci-dessus)
    // ------------------------------------------------------------------

    /**
     * Bloc image (logo|portrait) : <img> avec width/height codés en dur (jamais seulement en CSS -
     * sinon Outlook de bureau affiche l'image à sa taille native). N'affiche RIEN si les dimensions
     * sont inconnues plutôt que de violer cette règle. Le texte alternatif dépend du rôle et du
     * gabarit (`$altMode`, résolu par {@see imageAlt()}) - un logo se décrit par l'organisation sur
     * certains gabarits et par le nom complet sur d'autres, un portrait toujours par le nom complet.
     *
     * @param  array{url: string, width: int, height: int}|null  $image
     * @param  array<string, mixed>  $f
     */
    private static function blocImage(?array $image, string $altMode, string $extraStyle, array $f): string
    {
        if (! $image || empty($image['url']) || empty($image['width']) || empty($image['height'])) {
            return '';
        }

        // La taille RENDUE suit display_width/display_height (curseur « Taille d'affichage »),
        // pas les dimensions intrinseques du fichier - miroir exact de blocImage() cote JS.
        $w = (int) ($image['display_width'] ?? $image['width']);
        $h = (int) ($image['display_height'] ?? (int) round(($image['height'] / $image['width']) * $w));

        return sprintf(
            '<img src="%s" width="%d" height="%d" alt="%s" style="display:block;border:0;outline:none;text-decoration:none;%s">',
            self::e($image['url']),
            $w,
            $h,
            self::e(self::imageAlt($altMode, $f)),
            $extraStyle
        );
    }

    private static function imageAlt(string $mode, array $f): string
    {
        return match ($mode) {
            'logo_organization' => 'Logo '.$f['organization'],
            'portrait_fullname' => 'Photo de '.self::fullName($f),
            // Bannière (LOT 2) : le texte alternatif OBLIGATOIRE reprend le texte de l'appel à
            // l'action quand il existe (c'est ce que la bannière fait, cliquée), un repli générique
            // sinon - jamais vide (règle WCAG image cliquable, section 9 du plan).
            'banner_cta' => $f['cta_text'] !== '' ? $f['cta_text'] : 'Bannière de '.self::fullName($f),
            default => 'Logo '.self::fullName($f),
        };
    }

    /**
     * Bloc réseaux sociaux (icône + libellé). Deux rendus, tous deux pilotés par le registre
     * (`social_emphasis`, gabarit `social` du LOT 2), jamais deux fonctions : le rendu par défaut
     * (séparés par un point médian, une seule ligne - inchangé depuis le LOT 1, `#374151` explicite
     * remplace `color:inherit` depuis le LOT 3, sécurité mode sombre) et le rendu EN AVANT quand
     * `$emphasis` est vrai.
     *
     * RENFORCÉ AU LOT 3 (2026-09-25) : la QC visuelle a jugé le rendu EN AVANT du LOT 2 (icône
     * 18px sur la même ligne unique que le rendu par défaut) trop proche de `minimal` à l'oeil. Le
     * changement porte maintenant sur la STRUCTURE, pas seulement la taille : CHAQUE réseau occupe
     * SA PROPRE ligne, avec une icône nettement agrandie - jamais utilisé par les 7 autres gabarits
     * (`$emphasis` faux partout ailleurs), sortie inchangée pour eux.
     *
     * @param  array<int, array{platform?: string, url?: string}>  $links
     */
    private static function blocReseaux(array $links, string $fontStack, string $accent = '', bool $emphasis = false, float $fontScale = 1.0): string
    {
        $links = array_values(array_filter($links, fn ($l) => is_array($l) && ! empty($l['url']) && self::isSafeUrl($l['url'])));

        if ($links === []) {
            return '';
        }

        if (! $emphasis) {
            $parts = [];
            foreach ($links as $link) {
                $platform = (string) ($link['platform'] ?? 'website');
                $icon = self::SOCIAL_ICONS[$platform] ?? '🔗';
                $label = self::SOCIAL_LABELS[$platform] ?? self::e($platform);
                $parts[] = sprintf(
                    '<a href="%s" style="color:#374151;text-decoration:none;font-family:%s;font-size:%s;">%s %s</a>',
                    self::e($link['url']),
                    $fontStack,
                    self::scalePx('12px', $fontScale),
                    $icon,
                    $label
                );
            }

            return '<tr><td style="padding-top:6px;">'.implode(' &nbsp;·&nbsp; ', $parts).'</td></tr>';
        }

        $accentSafe = self::e($accent !== '' ? $accent : '#064E5A');
        $rows = '';
        foreach ($links as $index => $link) {
            $platform = (string) ($link['platform'] ?? 'website');
            $icon = self::SOCIAL_ICONS[$platform] ?? '🔗';
            $label = self::SOCIAL_LABELS[$platform] ?? self::e($platform);
            $cellStyle = $index === 0
                ? 'padding-top:10px;border-top:1px solid '.$accentSafe.';'
                : 'padding-top:6px;';
            $rows .= sprintf(
                '<tr><td style="%s"><a href="%s" style="color:#374151;text-decoration:none;font-family:%s;font-size:%s;"><span style="font-size:%s;">%s</span> %s</a></td></tr>',
                $cellStyle,
                self::e($link['url']),
                $fontStack,
                self::scalePx('13px', $fontScale),
                self::scalePx('20px', $fontScale),
                $icon,
                $label
            );
        }

        return $rows;
    }

    /**
     * Bloc bannière (LOT 2, gabarit `banniere`) : image pleine largeur, cliquable vers `cta_url`
     * quand ce champ est renseigné (simple `<img>` sinon - une bannière sans lien reste une image
     * valide). Réutilise {@see self::blocImage()} (mode `banner_cta`) plutôt que de dupliquer la
     * construction de la balise `<img>` - DRY strict. N'affiche RIEN si l'image n'existe pas
     * (masquage propre, aucun cadre ni espace orphelin) : un gabarit `banniere` sans bannière
     * téléversée reste une signature valide, juste incomplète.
     *
     * @param  array{url: string, width: int, height: int}|null  $image
     * @param  array<string, mixed>  $f
     */
    private static function blocBanniere(?array $image, array $f): string
    {
        $img = self::blocImage($image, 'banner_cta', 'max-width:100%;', $f);
        if ($img === '') {
            return '';
        }

        $content = $f['cta_url'] !== ''
            ? '<a href="'.self::e($f['cta_url']).'" style="display:block;border:0;text-decoration:none;">'.$img.'</a>'
            : $img;

        return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;border-collapse:collapse;margin-top:10px;"><tr><td>'.$content.'</td></tr></table>';
    }

    /**
     * Bloc mentions légales (jusqu'à 6 lignes).
     *
     * @param  array<int, string>  $lines
     */
    private static function blocMentions(array $lines, string $fontStack, float $fontScale = 1.0): string
    {
        $lines = array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== ''));
        if ($lines === []) {
            return '';
        }

        $out = '';
        foreach (array_slice($lines, 0, 6) as $line) {
            // M4.5 : #374151 (déjà utilisé par blocContact/titleRow ci-dessous) plutôt que
            // #6b7280 - à 10px, #6b7280 tombe sous le ratio 7:1 exigé par WCAG 2.2 AAA sur fond
            // blanc.
            $out .= sprintf(
                '<tr><td style="font-family:%s;font-size:%s;color:#374151;padding-top:2px;">%s</td></tr>',
                $fontStack,
                self::scalePx('10px', $fontScale),
                self::e($line)
            );
        }

        return $out;
    }

    /**
     * Bloc CTA (bouton d'appel à l'action) - n'apparaît que si le texte ET l'URL sont fournis.
     *
     * @param  array<string, mixed>  $f
     */
    private static function blocCta(array $f, string $accent, string $fontStack, float $fontScale = 1.0): string
    {
        if ($f['cta_text'] === '' || $f['cta_url'] === '') {
            return '';
        }

        return sprintf(
            '<tr><td style="padding-top:8px;"><a href="%s" style="display:inline-block;padding:8px 14px;background-color:%s;color:#ffffff;font-family:%s;font-size:%s;font-weight:bold;text-decoration:none;border-radius:4px;">%s</a></td></tr>',
            self::e($f['cta_url']),
            self::e($accent),
            $fontStack,
            self::scalePx('12px', $fontScale),
            self::e($f['cta_text'])
        );
    }

    /**
     * Bloc contact complet (téléphone, cellulaire, courriel, site, adresse) - une ligne par champ
     * renseigné, dans cet ordre fixe. LOT 3 : les liens portent leur PROPRE couleur explicite
     * (#374151, celle de la cellule) au lieu de `color:inherit` - sécurité mode sombre, voir
     * docblock de classe.
     *
     * @param  array<string, mixed>  $f
     */
    private static function blocContact(array $f, string $fontStack, float $fontScale = 1.0): string
    {
        $rows = '';
        $size = self::scalePx('12px', $fontScale);
        $line = function (string $text) use (&$rows, $fontStack, $size): void {
            if ($text === '') {
                return;
            }
            $rows .= sprintf('<tr><td style="font-family:%s;font-size:%s;color:#374151;padding-top:2px;">%s</td></tr>', $fontStack, $size, $text);
        };

        if ($f['phone'] !== '') {
            $line('☎ '.self::e($f['phone']));
        }
        if ($f['mobile'] !== '') {
            $line('📱 '.self::e($f['mobile']));
        }
        if ($f['email'] !== '') {
            $line('✉ <a href="mailto:'.self::e($f['email']).'" style="color:#374151;text-decoration:none;">'.self::e($f['email']).'</a>');
        }
        if ($f['website'] !== '') {
            $line('🔗 <a href="'.self::e($f['website']).'" style="color:#374151;text-decoration:none;">'.self::e($f['website']).'</a>');
        }
        if ($f['address'] !== '') {
            $line('📍 '.self::e($f['address']));
        }

        return $rows;
    }

    /**
     * `$divider` (LOT 2, gabarit `executive`) ajoute un filet sous le nom - une propriété du bloc
     * identité, jamais un agencement distinct. Quand `$divider` est faux (tous les gabarits du
     * LOT 1), la sortie reste EXACTEMENT celle d'avant le LOT 2, caractère pour caractère.
     * `$fontScale` (LOT 3, défaut 1.0) multiplie `$size` - voir {@see self::scalePx()}. Les pronoms
     * (LOT 3) s'ajoutent APRÈS le nom, jamais avant - voir {@see self::pronounsHtml()}.
     */
    private static function nameRow(array $f, string $accent, string $fontStack, string $size, bool $divider = false, float $fontScale = 1.0): string
    {
        $name = self::fullName($f);
        $style = 'font-family:'.$fontStack.';font-size:'.self::scalePx($size, $fontScale).';font-weight:bold;color:'.self::e($accent).';';
        $style .= $divider
            ? 'padding-bottom:8px;border-bottom:2px solid '.self::e($accent).';'
            : 'padding-bottom:1px;';

        return sprintf('<tr><td style="%s">%s%s</td></tr>', $style, self::e($name), self::pronounsHtml($f, $fontScale));
    }

    private static function titleRow(array $f, string $fontStack, float $fontScale = 1.0): string
    {
        $bits = array_filter([$f['job_title'], $f['organization']]);
        if ($bits === []) {
            return '';
        }

        return sprintf(
            '<tr><td style="font-family:%s;font-size:%s;color:#374151;padding-bottom:4px;">%s</td></tr>',
            $fontStack,
            self::scalePx('13px', $fontScale),
            // Règle 10 : jamais de tiret cadratin - un simple tiret entouré d'espaces à la place.
            self::e(implode(' - ', $bits))
        );
    }

    /**
     * Bloc identité (nom complet + poste/organisation + pronoms). `$nameDivider` (LOT 2) : voir
     * {@see self::nameRow()}. `$fontScale` (LOT 3, défaut 1.0) : voir {@see self::scalePx()}.
     *
     * @param  array<string, mixed>  $f
     */
    private static function blocIdentite(array $f, string $accent, string $fontStack, string $nameSize, bool $nameDivider = false, float $fontScale = 1.0): string
    {
        return self::nameRow($f, $accent, $fontStack, $nameSize, $nameDivider, $fontScale).self::titleRow($f, $fontStack, $fontScale);
    }

    private static function blocTagline(array $f, string $fontStack, float $fontScale = 1.0): string
    {
        if ($f['tagline'] === '') {
            return '';
        }

        // M4.5 : même correctif de contraste que blocMentions() ci-dessus (#374151, jamais #6b7280).
        return sprintf(
            '<tr><td style="font-family:%s;font-size:%s;font-style:italic;color:#374151;padding-top:4px;">%s</td></tr>',
            $fontStack,
            self::scalePx('11px', $fontScale),
            self::e($f['tagline'])
        );
    }

    /**
     * Colonne de texte complète de l'agencement `standard` : assemble identité, contact, tagline,
     * CTA, réseaux et mentions, dans cet ordre fixe. `$socialEmphasis`/`$nameDivider` (LOT 2,
     * défaut faux) : propriétés transverses, voir {@see self::blocReseaux()}/{@see self::nameRow()}
     * - à faux (tous les gabarits du LOT 1), la sortie reste inchangée. `$fontScale` (LOT 3,
     * défaut 1.0) : voir {@see self::scalePx()}.
     *
     * @param  array<string, mixed>  $f
     */
    private static function blocColonneTexte(
        array $f,
        string $accent,
        string $fontStack,
        string $nameSize,
        bool $socialEmphasis = false,
        bool $nameDivider = false,
        float $fontScale = 1.0
    ): string {
        $html = '<table role="presentation" cellpadding="0" cellspacing="0" border="0">';
        $html .= self::blocIdentite($f, $accent, $fontStack, $nameSize, $nameDivider, $fontScale);
        $html .= self::blocContact($f, $fontStack, $fontScale);
        $html .= self::blocTagline($f, $fontStack, $fontScale);
        $html .= self::blocCta($f, $accent, $fontStack, $fontScale);
        $html .= self::blocReseaux($f['social_links'], $fontStack, $accent, $socialEmphasis, $fontScale);
        $html .= self::blocMentions($f['mention_lines'], $fontStack, $fontScale);
        $html .= '</table>';

        return $html;
    }

    /**
     * En-tête dense du gabarit compact : nom complet et poste/organisation joints sur UNE ligne.
     * LOT 3 : pronoms insérés APRÈS le nom (jamais après le poste/organisation) - le nom et les
     * bits sont donc échappés SÉPARÉMENT (au lieu d'une seule chaîne jointe puis échappée), ce qui
     * ne change RIEN au résultat pour le séparateur ` - ` (aucun caractère HTML spécial) mais permet
     * d'insérer le `<span>` de pronoms au bon endroit.
     *
     * @param  array<string, mixed>  $f
     */
    private static function blocEnTeteCompact(array $f, string $accent, string $fontStack, float $fontScale = 1.0): string
    {
        $bits = array_filter([$f['job_title'], $f['organization']]);
        $header = self::e(self::fullName($f)).self::pronounsHtml($f, $fontScale);
        if ($bits !== []) {
            // Règle 10 : jamais de tiret cadratin.
            $header .= ' - '.self::e(implode(' - ', $bits));
        }

        return sprintf(
            '<tr><td style="font-family:%s;font-size:%s;font-weight:bold;color:%s;">%s</td></tr>',
            $fontStack,
            self::scalePx('13px', $fontScale),
            self::e($accent),
            $header
        );
    }

    /**
     * Contact dense du gabarit compact : téléphone, courriel et site seulement (pas de cellulaire
     * ni d'adresse - c'est la règle du gabarit), joints sur une seule ligne par une barre verticale.
     * LOT 3 : liens en couleur EXPLICITE (#374151) au lieu de `color:inherit` - sécurité mode sombre.
     *
     * @param  array<string, mixed>  $f
     */
    private static function blocContactCompact(array $f, string $fontStack, float $fontScale = 1.0): string
    {
        $contact = array_filter([
            $f['phone'] !== '' ? '☎ '.self::e($f['phone']) : '',
            $f['email'] !== '' ? '✉ <a href="mailto:'.self::e($f['email']).'" style="color:#374151;text-decoration:none;">'.self::e($f['email']).'</a>' : '',
            $f['website'] !== '' ? '🔗 <a href="'.self::e($f['website']).'" style="color:#374151;text-decoration:none;">'.self::e($f['website']).'</a>' : '',
        ]);

        if ($contact === []) {
            return '';
        }

        return sprintf('<tr><td style="font-family:%s;font-size:%s;color:#374151;">%s</td></tr>', $fontStack, self::scalePx('11px', $fontScale), implode(' &nbsp;|&nbsp; ', $contact));
    }
}
