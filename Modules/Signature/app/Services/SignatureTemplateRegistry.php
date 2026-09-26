<?php

declare(strict_types=1);

namespace Modules\Signature\Services;

use Modules\Signature\Models\SignatureImage;

/**
 * Registre déclaratif UNIQUE des gabarits de signature (LOT 1 - refonte du 2026-09-25, complété
 * par le LOT 2 du même jour : 4 gabarits supplémentaires).
 *
 * Chaque entrée décrit l'AGENCEMENT d'un gabarit - jamais sa logique de rendu, qui reste dans les
 * composants privés de {@see SignatureRenderer}. `SignatureRenderer::render()` lit ce registre pour
 * assembler les blocs ; {@see \Modules\Signature\Models\Signature::templates()} en dérive la liste
 * des gabarits valides, pour ne plus jamais entretenir deux listes séparées. Le moteur JS
 * (public/assets/tools/signature-courriel/signature-render.js) consomme le MÊME registre, sérialisé
 * en JSON par le contrôleur qui sert l'éditeur (`window.SIGNATURE_TEMPLATES`, voir
 * {@see self::definitions()}) - ajouter un gabarit ici suffit, aucune fonction JS dédiée requise.
 *
 * TROIS familles d'agencement, décrites par la clé `layout` (plafond volontaire - le principe
 * directeur du LOT 2 est d'ajouter des PROPRIÉTÉS et des composants réutilisables, jamais un
 * assembleur par gabarit) :
 *   - `standard` : une rangée table à une ou deux cellules (image + colonne de texte complète),
 *     avec position de l'image, alignement vertical, séparateur/cadre optionnels - couvre minimal,
 *     professionnel, portrait, executive et social. `image_role` peut être `null` (executive) : la
 *     cellule d'image disparaît alors entièrement, la colonne de texte occupe toute la largeur.
 *   - `compact` : composition dense propre au gabarit compact (en-tête une ligne, contact groupé
 *     en ligne) - couvre compact et banniere. `image_role` peut aussi être `null` (banniere) :
 *     l'identité visuelle du gabarit vient alors de la bannière (`banner_role`), pas d'un logo.
 *   - `vertical` : blocs EMPILÉS (image, puis identité, contact, réseaux, mentions, CTA) - un seul
 *     nouvel agencement pour LE seul besoin qui n'entre dans aucun des deux précédents (mobile et
 *     signatures longues).
 *
 * Propriétés transverses, valables sur N'IMPORTE QUELLE famille (jamais un agencement de plus) :
 *   - `banner_role` : quand renseignée, {@see SignatureRenderer::render()} ajoute EN PLUS, après
 *     l'agencement principal, une bannière cliquable pleine largeur (rôle d'image `banniere`,
 *     {@see SignatureImage::ROLE_BANNIERE}) - jamais un 4e agencement pour ce seul besoin.
 *   - `social_emphasis` : réseaux sociaux rendus plus visibles (icônes plus grandes, ligne dédiée
 *     avec séparateur) - une propriété de {@see SignatureRenderer::blocReseaux()}, jamais un
 *     agencement à part (gabarit `social`).
 *   - `name_divider` : ajoute un filet sous le nom dans le bloc identité - une propriété de
 *     {@see SignatureRenderer::blocIdentite()}, utilisée par `executive` pour son « séparateur net »
 *     sans dupliquer la logique d'identité.
 *   - `label` : libellé d'AFFICHAGE en français correct (avec ses accents) - ajout pragmatique du
 *     LOT 2. La clé technique du gabarit (ex. `banniere`, volontairement sans accent - ASCII simple
 *     pour une valeur stockée en base et validée par `Rule::in()`) ne peut pas servir de libellé
 *     visible tel quel (« Banniere » perdrait l'accent, « Executive » n'est pas un mot français) :
 *     l'éditeur lit ce champ plutôt que de capitaliser la clé brute.
 *   - `category`/`hint` (LOT 4, galerie de mises en page) : `category` regroupe les gabarits par
 *     famille VISIBLE à l'oeil (`classiques`, `photo`, `vertical`, `banniere`, `reseaux`) - une
 *     catégorisation éditoriale, pas la famille `layout` technique (un gabarit `standard` peut très
 *     bien vivre dans la catégorie `photo`). `hint` est une courte phrase FR (« quand l'utiliser »)
 *     affichée sous chaque vignette de la galerie. Les DEUX sont sérialisés tels quels au JS (voir
 *     {@see self::definitions()}), jamais consommés par les assembleurs de rendu.
 *
 * LOT 4 (2026-09-26) - 6 nouveaux gabarits (photo_droite, logo_gauche, logo_bas, photo_centree,
 * deux_colonnes, coordonnees_sous_nom), toujours dans les 3 familles existantes. Trois propriétés
 * transverses de plus, toutes propres à l'agencement `vertical` (jamais un 4e assembleur) :
 *   - `image_position` (`top` par défaut, ou `bottom`) : place le bloc image APRÈS les réseaux
 *     plutôt qu'en tête - {@see SignatureRenderer::assembleVertical()}. Absente (donc `top`), la
 *     sortie du gabarit `vertical` d'origine reste EXACTEMENT celle d'avant ce lot.
 *   - `centered` (bool, défaut faux) : centre tout le contenu (texte ET image) - un choix de mise en
 *     page volontairement simple (un seul `text-align:center` sur le conteneur, hérité par le texte;
 *     l'image reçoit `margin:0 auto` en plus de son style habituel). Absente, sortie inchangée.
 *   - `contact_divider` (bool, défaut faux) : insère un filet horizontal fin entre le bloc identité
 *     et le bloc contact - distinct de `name_divider` (qui souligne le nom LUI-MÊME, à l'intérieur
 *     de sa propre cellule). Absente, sortie inchangée.
 */
final class SignatureTemplateRegistry
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private const DEFINITIONS = [
        'minimal' => [
            'layout' => 'standard',
            'label' => 'Minimal',
            'category' => 'classiques',
            'hint' => 'Logo compact à gauche et mise en page sobre - le bon choix pour un usage quotidien.',
            'name_size' => '15px',
            'image_role' => 'logo',
            'image_alt' => 'logo_fullname',
            'image_style' => '',
            'image_side' => 'left',
            'image_gap' => '10px',
            'valign' => 'middle',
            'frame_border_top' => null,
            'text_divider_with_image' => true,
            'cell_padding_top' => null,
        ],
        'professionnel' => [
            'layout' => 'standard',
            'label' => 'Professionnel',
            'category' => 'classiques',
            'hint' => 'Logo à droite avec filet supérieur coloré - une allure corporative et structurée.',
            'name_size' => '17px',
            'image_role' => 'logo',
            'image_alt' => 'logo_organization',
            'image_style' => '',
            'image_side' => 'right',
            'image_gap' => '16px',
            'valign' => 'top',
            'frame_border_top' => '3px',
            'text_divider_with_image' => false,
            'cell_padding_top' => '10px',
        ],
        'portrait' => [
            'layout' => 'standard',
            'label' => 'Portrait',
            'category' => 'photo',
            'hint' => 'Photo à gauche du texte - une approche personnelle et chaleureuse.',
            'name_size' => '16px',
            'image_role' => 'portrait',
            'image_alt' => 'portrait_fullname',
            'image_style' => 'border-radius:6px;',
            'image_side' => 'left',
            'image_gap' => '14px',
            'valign' => 'top',
            'frame_border_top' => null,
            'text_divider_with_image' => false,
            'cell_padding_top' => null,
        ],
        'compact' => [
            'layout' => 'compact',
            'label' => 'Compact',
            'category' => 'classiques',
            'hint' => 'Bloc dense sur 2 à 3 lignes - idéal pour une signature courte et discrète.',
            'image_role' => 'logo',
            'image_alt' => 'logo_organization',
            'image_style' => 'max-width:80px;',
            'image_gap' => '8px',
        ],

        // ------------------------------------------------------------------
        // LOT 2 (2026-09-25) - 4 nouveaux gabarits, chacun un agencement des 3 familles ci-dessus
        // combiné à une ou deux propriétés transverses, jamais un 4e assembleur.
        // ------------------------------------------------------------------

        'vertical' => [
            'layout' => 'vertical',
            'label' => 'Vertical',
            'category' => 'vertical',
            'hint' => 'Portrait en haut, coordonnées empilées dessous - très lisible sur mobile.',
            'name_size' => '16px',
            'image_role' => 'portrait',
            'image_alt' => 'portrait_fullname',
            'image_style' => 'border-radius:50%;',
            'image_gap' => '12px',
        ],
        'banniere' => [
            // layout compact (identité + contact denses) + banner_role : voir docblock de classe.
            // Aucune image logo/portrait sur ce gabarit - l'identité visuelle vient de la bannière.
            'layout' => 'compact',
            'label' => 'Bannière',
            'category' => 'banniere',
            'hint' => 'Bannière cliquable pleine largeur sous les coordonnées - pour une promotion ou un événement.',
            'image_role' => null,
            'image_alt' => null,
            'image_style' => '',
            'image_gap' => '8px',
            'banner_role' => SignatureImage::ROLE_BANNIERE,
        ],
        'executive' => [
            // layout standard, sans image (identité forte SEULE), name_divider pour le séparateur
            // net demandé - aucune propriété nouvelle qui ne soit pas déjà générique.
            'layout' => 'standard',
            'label' => 'Direction',
            'category' => 'classiques',
            'hint' => 'Identité forte sans image, filet net sous le nom - la sobriété d\'une signature de direction.',
            'name_size' => '22px',
            'image_role' => null,
            'image_alt' => null,
            'image_style' => '',
            'image_side' => 'left',
            'image_gap' => '0',
            'valign' => 'top',
            'frame_border_top' => '4px',
            'text_divider_with_image' => false,
            'cell_padding_top' => '14px',
            'name_divider' => true,
        ],
        'social' => [
            // layout standard (même agencement que minimal) + social_emphasis - la SEULE
            // différence réelle avec minimal est la mise en avant des réseaux sociaux.
            'layout' => 'standard',
            'label' => 'Réseaux sociaux',
            'category' => 'reseaux',
            'hint' => 'Réseaux sociaux mis en avant, chacun sur sa propre ligne avec une icône agrandie.',
            'name_size' => '15px',
            'image_role' => 'logo',
            'image_alt' => 'logo_fullname',
            'image_style' => '',
            'image_side' => 'left',
            'image_gap' => '10px',
            'valign' => 'middle',
            'frame_border_top' => null,
            'text_divider_with_image' => true,
            'cell_padding_top' => null,
            'social_emphasis' => true,
        ],

        // ------------------------------------------------------------------
        // LOT 4 (2026-09-26) - 6 nouveaux gabarits pour la galerie de mises en page. Chacun réutilise
        // une famille d'agencement EXISTANTE (standard/compact/vertical) - jamais un 4e assembleur.
        // Voir le docblock de classe pour les 3 propriétés transverses ajoutées à `vertical`
        // (`image_position`, `centered`, `contact_divider`), toutes à défaut neutre (sortie des
        // gabarits LOT 1-3 inchangée - non-régression garantie par construction, voir tests).
        // ------------------------------------------------------------------

        'photo_droite' => [
            // Miroir exact de `portrait` : seule `image_side` change (droite au lieu de gauche).
            'layout' => 'standard',
            'label' => 'Photo à droite',
            'category' => 'photo',
            'hint' => 'Photo à droite du texte - le miroir du gabarit Portrait, pour varier la composition.',
            'name_size' => '16px',
            'image_role' => 'portrait',
            'image_alt' => 'portrait_fullname',
            'image_style' => 'border-radius:6px;',
            'image_side' => 'right',
            'image_gap' => '14px',
            'valign' => 'top',
            'frame_border_top' => null,
            'text_divider_with_image' => false,
            'cell_padding_top' => null,
        ],
        'logo_gauche' => [
            // Miroir de `professionnel` : logo à gauche plutôt qu'à droite, mêmes filet supérieur et
            // alignement en haut - un rendu visuellement distinct de `minimal` (qui place aussi le
            // logo à gauche, mais centré verticalement, sans filet, avec un séparateur latéral).
            'layout' => 'standard',
            'label' => 'Logo à gauche',
            'category' => 'classiques',
            'hint' => 'Agencement professionnel avec filet supérieur coloré, logo placé à gauche du texte.',
            'name_size' => '17px',
            'image_role' => 'logo',
            'image_alt' => 'logo_organization',
            'image_style' => '',
            'image_side' => 'left',
            'image_gap' => '16px',
            'valign' => 'top',
            'frame_border_top' => '3px',
            'text_divider_with_image' => false,
            'cell_padding_top' => '10px',
        ],
        'logo_bas' => [
            // Agencement `vertical`, logo + réseaux groupés tout en bas (`image_position` = `bottom`)
            // - coordonnées d'abord, identité visuelle en dernier plutôt qu'en tête.
            'layout' => 'vertical',
            'label' => 'Logo en bas',
            'category' => 'vertical',
            'hint' => 'Coordonnées d\'abord, logo et réseaux sociaux groupés tout en bas de la signature.',
            'name_size' => '16px',
            'image_role' => 'logo',
            'image_alt' => 'logo_organization',
            'image_style' => '',
            'image_gap' => '10px',
            'image_position' => 'bottom',
        ],
        'photo_centree' => [
            // Variante CENTRÉE de `vertical` (`centered` = true) : portrait rond au-dessus du nom,
            // tout le bloc (texte et image) aligné au centre plutôt qu'à gauche.
            'layout' => 'vertical',
            'label' => 'Photo centrée',
            'category' => 'vertical',
            'hint' => 'Portrait centré au-dessus du nom, texte et coordonnées alignés au centre.',
            'name_size' => '16px',
            'image_role' => 'portrait',
            'image_alt' => 'portrait_fullname',
            'image_style' => 'border-radius:50%;',
            'image_gap' => '12px',
            'centered' => true,
        ],
        'deux_colonnes' => [
            // Version « photo » de `minimal` : portrait (pas un logo) + séparateur vertical net,
            // aligné au centre - deux colonnes franches, photo et texte de part et d'autre du filet.
            'layout' => 'standard',
            'label' => 'Deux colonnes',
            'category' => 'photo',
            'hint' => 'Photo et texte en deux colonnes nettes, séparées par un filet vertical nettement visible.',
            'name_size' => '16px',
            'image_role' => 'portrait',
            'image_alt' => 'portrait_fullname',
            'image_style' => 'border-radius:6px;',
            'image_side' => 'left',
            'image_gap' => '16px',
            'valign' => 'middle',
            'frame_border_top' => null,
            'text_divider_with_image' => true,
            'cell_padding_top' => null,
        ],
        'coordonnees_sous_nom' => [
            // Agencement `vertical`, SANS aucune image, avec un filet horizontal distinct
            // (`contact_divider`) entre le bloc identité et le bloc contact - à ne pas confondre avec
            // `executive` (filet collé SOUS le nom lui-même, nom agrandi 22px, cadre 4px en haut) :
            // ici le nom garde une taille normale et le filet sépare deux BLOCS, pas deux lignes.
            'layout' => 'vertical',
            'label' => 'Coordonnées sous le nom',
            'category' => 'vertical',
            'hint' => 'Identité en haut, un filet horizontal, puis les coordonnées complètes dessous.',
            'name_size' => '16px',
            'image_role' => null,
            'image_alt' => null,
            'image_style' => '',
            'image_gap' => '10px',
            'contact_divider' => true,
        ],
    ];

    /**
     * Liste des gabarits valides, DANS L'ORDRE du registre - source unique consommée par
     * {@see \Modules\Signature\Models\Signature::templates()}.
     *
     * @return list<string>
     */
    public static function templates(): array
    {
        return array_keys(self::DEFINITIONS);
    }

    public static function has(string $template): bool
    {
        return array_key_exists($template, self::DEFINITIONS);
    }

    /**
     * @return array<string, mixed>
     */
    public static function definition(string $template): array
    {
        return self::DEFINITIONS[$template] ?? self::DEFINITIONS['minimal'];
    }

    /**
     * Le registre COMPLET, sérialisable en JSON tel quel - consommé par l'éditeur
     * (`window.SIGNATURE_TEMPLATES`, injecté par le contrôleur qui sert la vue) pour que le moteur
     * de rendu JS (signature-render.js) lise la MÊME définition d'agencement que le moteur PHP,
     * plutôt que de la dupliquer gabarit par gabarit (LOT 2).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return self::DEFINITIONS;
    }
}
