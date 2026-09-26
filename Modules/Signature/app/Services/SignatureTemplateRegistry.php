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
