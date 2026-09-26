<?php

declare(strict_types=1);

namespace Modules\Signature\Services;

/**
 * Schéma de disposition (wireframe) ABSTRAIT d'un gabarit de signature - jamais un aperçu réel
 * (aucune donnée de contenu, aucun texte), dans l'esprit du sélecteur de gabarits de HubSpot.
 *
 * REJET DU FONDATEUR (2026-09-26) qui a motivé cette classe : l'étape 1 de l'assistant montrait de
 * simples BOUTONS TEXTE (« Minimal », « Professionnel »...) dans des cases vides, et la galerie
 * modale (LOT 4) montrait de VRAIS mini-aperçus en iframe (moteur `renderSignature()` + jeu de
 * données d'exemple) qui rendaient mal une fois réduits à l'échelle d'une vignette. Les deux
 * défauts partageaient la même cause : aucun des deux emplacements ne montrait la FORME de la
 * disposition, seulement soit rien (le bouton texte), soit trop (une signature complète miniature,
 * illisible).
 *
 * SOURCE UNIQUE, ZÉRO DUPLICATION (contrainte du brief) : {@see self::svg()} lit UNIQUEMENT les
 * propriétés d'agencement de {@see SignatureTemplateRegistry} (`layout`, `image_side`,
 * `image_role`, `frame_border_top`, `text_divider_with_image`, `name_divider`, `social_emphasis`,
 * `image_position`, `centered`, `contact_divider`, `banner_role`) - jamais une bascule sur la CLÉ
 * du gabarit. Un gabarit qui ne changerait QUE de libellé ou de couleur d'accent produit donc
 * exactement le même schéma qu'un autre aux mêmes propriétés, ce qui est le comportement voulu (le
 * schéma illustre l'AGENCEMENT, pas l'identité du gabarit).
 *
 * {@see self::map()} calcule les schémas des 14 gabarits UNE fois par requête (coût négligeable,
 * aucune donnée utilisateur en jeu) et est sérialisé par les 3 contrôleurs qui servent l'éditeur
 * (`window.SIGNATURE_TEMPLATE_WIREFRAMES`, voir editor.blade.php) - EXACTEMENT le même patron que
 * `SignatureTemplateRegistry::definitions()`/`window.SIGNATURE_TEMPLATES`. Le sélecteur de l'étape 1
 * ET la galerie modale lisent tous deux ce même objet via l'unique méthode JS
 * `templateWireframe(tpl)` (signature-core.js) - aucune logique de dessin dupliquée côté client,
 * plus aucun iframe de rendu réel dans la galerie.
 *
 * Choix PHP plutôt que JS (documenté dans le rapport livré) : le schéma ne dépend d'AUCUNE saisie
 * utilisateur (contrairement à l'aperçu réel, qui doit suivre le contenu en direct sans aller-retour
 * serveur - voir docblock de {@see SignatureRenderer}) : il ne dépend que du registre, déjà en PHP.
 * Le calculer côté serveur permet un test Pest direct et simple (`SignatureWireframeTest.php`) sans
 * exécuter de moteur JS depuis les tests, et évite d'alourdir signature-core.js d'un second
 * générateur de dessin.
 *
 * Primitives fixes (mêmes couleurs et proportions pour tous les gabarits - seule la POSITION varie) :
 *   - avatar (portrait) : carré à coins arrondis bleu (#2563EB) avec une icône « personne » blanche;
 *   - logo : rectangle gris clair (#C7CDD6) sans icône, pour le distinguer visuellement de l'avatar;
 *   - lignes de texte : barres grises horizontales, largeurs décroissantes;
 *   - réseaux sociaux : 3 cercles pleins (bleu/rose/orange);
 *   - séparateurs : traits fins, verticaux (entre image et texte) ou horizontaux (cadre du haut,
 *     filet sous le nom, filet identité/contact).
 */
final class SignatureWireframeRenderer
{
    private const AVATAR_BG = '#2563EB';

    private const AVATAR_ICON = '#FFFFFF';

    private const GRAY = '#C7CDD6';

    private const ACCENT = '#2563EB';

    private const SOCIAL_COLORS = ['#2563EB', '#E5407A', '#E5533A'];

    private const LEFT = 14.0;

    private const RIGHT = 146.0;

    /**
     * Schéma SVG complet (balise `<svg>` autonome, viewBox 0 0 160 120 - ratio 4:3) d'un seul
     * gabarit. Décoratif (`aria-hidden`) : le libellé du gabarit reste porté par le texte visible à
     * côté, jamais par le schéma lui-même.
     */
    public static function svg(string $template): string
    {
        $def = SignatureTemplateRegistry::definition($template);
        $layout = $def['layout'] ?? 'standard';

        $inner = match ($layout) {
            'compact' => self::assembleCompact($def),
            'vertical' => self::assembleVertical($def),
            default => self::assembleStandard($def),
        };

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 120" width="100%" height="100%" '
            .'preserveAspectRatio="xMidYMid meet" aria-hidden="true" focusable="false">'.$inner.'</svg>';
    }

    /**
     * Les 14 schémas du registre, clé = gabarit - source unique sérialisée par les 3 contrôleurs
     * qui servent l'éditeur (`window.SIGNATURE_TEMPLATE_WIREFRAMES`).
     *
     * @return array<string, string>
     */
    public static function map(): array
    {
        $out = [];
        foreach (SignatureTemplateRegistry::templates() as $tpl) {
            $out[$tpl] = self::svg($tpl);
        }

        return $out;
    }

    // ------------------------------------------------------------------
    // Assembleurs (un par famille d'agencement du registre - même découpage que SignatureRenderer)
    // ------------------------------------------------------------------

    /**
     * Agencement `standard` (minimal, professionnel, portrait, executive, social, photo_droite,
     * logo_gauche, deux_colonnes) : image (optionnelle, logo ou portrait) d'un côté, colonne de
     * lignes de texte de l'autre - purement dérivé des propriétés du registre, jamais de la clé du
     * gabarit.
     *
     * @param  array<string, mixed>  $def
     */
    private static function assembleStandard(array $def): string
    {
        $imageRole = $def['image_role'] ?? null;
        $imageSide = $def['image_side'] ?? 'left';
        $valign = $def['valign'] ?? 'top';
        $hasFrameBorder = ! empty($def['frame_border_top']);
        $hasTextDivider = ! empty($def['text_divider_with_image']);
        $hasNameDivider = ! empty($def['name_divider']);
        $socialEmphasis = ! empty($def['social_emphasis']);
        $bigName = self::sizePx((string) ($def['name_size'] ?? '15px')) >= 20;

        $out = '';
        $top = 16.0;
        if ($hasFrameBorder) {
            $out .= self::hline(self::LEFT, self::RIGHT, $top, true, 3);
            $top += 14.0;
        } else {
            $top += 6.0;
        }

        $portraitSize = 26.0;
        $logoW = 38.0;
        $logoH = 16.0;
        $gap = 12.0;

        $textX = self::LEFT;
        $textWidth = self::RIGHT - self::LEFT;
        $imgX = null;
        $imgY = $top;
        $imgW = $imageRole === 'portrait' ? $portraitSize : $logoW;

        if ($imageRole !== null) {
            if ($imageSide === 'right') {
                $imgX = self::RIGHT - $imgW;
                $textX = self::LEFT;
                $textWidth = $imgX - $gap - self::LEFT;
            } else {
                $imgX = self::LEFT;
                $textX = self::LEFT + $imgW + $gap;
                $textWidth = self::RIGHT - $textX;
            }
            if ($valign === 'middle') {
                $imgY = $top + 14.0;
            }

            $out .= $imageRole === 'portrait'
                ? self::avatar($imgX, $imgY, $portraitSize)
                : self::rect($imgX, $imgY, $logoW, $logoH, self::GRAY);
        }

        if ($hasTextDivider && $imgX !== null) {
            $dividerX = $imageSide === 'right' ? $imgX - $gap / 2 : $imgX + $imgW + $gap / 2;
            $out .= self::vline($dividerX, $top, $top + 40.0);
        }

        // Lignes de texte (nom, poste/organisation, contact) - largeurs décroissantes.
        $nameH = $bigName ? 6.0 : 4.0;
        $w1 = min($textWidth * 0.85, $textWidth);
        $w2 = min($textWidth * 0.62, $textWidth);
        $w3 = min($textWidth * 0.45, $textWidth);

        $y = $top;
        $out .= self::line($textX, $y, $w1, $nameH);
        if ($hasNameDivider) {
            $out .= self::hline($textX, $textX + $w1, $y + $nameH + 3.0, true);
            $y += 4.0;
        }
        $y += $nameH + 7.0;
        $out .= self::line($textX, $y, $w2, 4.0);
        $y += 4.0 + 7.0;
        $out .= self::line($textX, $y, $w3, 4.0);
        $y += 4.0 + 10.0;

        if ($socialEmphasis) {
            $out .= self::hline($textX, $textX + $textWidth, $y);
            $y += 8.0;
            $out .= self::socials($textX, $y + 7.0, true);
        } else {
            $out .= self::socials($textX, $y + 5.0, false);
        }

        return $out;
    }

    /**
     * Agencement `compact` (compact, banniere) : logo optionnel + bloc dense de lignes, ou 2 lignes
     * compactes puis un grand rectangle (la bannière) quand `banner_role` est renseigné.
     *
     * @param  array<string, mixed>  $def
     */
    private static function assembleCompact(array $def): string
    {
        $imageRole = $def['image_role'] ?? null;
        $hasBanner = ! empty($def['banner_role']);

        $out = '';
        $y = 18.0;

        $textX = self::LEFT;
        if ($imageRole === 'logo') {
            $out .= self::rect(self::LEFT, $y, 34.0, 14.0, self::GRAY);
            $textX = self::LEFT + 34.0 + 10.0;
        }
        $fullWidth = self::RIGHT - self::LEFT;
        $textWidth = self::RIGHT - $textX;

        // En-tête dense : une ligne "nom" à côté du logo (ou pleine largeur sans logo).
        $out .= self::line($textX, $y + 2.0, min($textWidth * 0.8, $textWidth), 4.0);

        // Contact groupé, dense, toujours pleine largeur (sous le logo/l'en-tête).
        $y2 = $y + 14.0 + 8.0;
        $out .= self::line(self::LEFT, $y2, $fullWidth * 0.9, 4.0);
        $y3 = $y2 + 4.0 + 7.0;
        $out .= self::line(self::LEFT, $y3, $fullWidth * 0.62, 4.0);

        if ($hasBanner) {
            $bannerY = $y3 + 4.0 + 14.0;
            $out .= self::rect(self::LEFT, $bannerY, $fullWidth, 26.0, self::GRAY, 6.0);
        } else {
            $out .= self::socials(self::LEFT, $y3 + 4.0 + 10.0, false);
        }

        return $out;
    }

    /**
     * Agencement `vertical` (vertical, logo_bas, photo_centree, coordonnees_sous_nom) : blocs
     * empilés. `image_position`/`centered`/`contact_divider` (propriétés transverses du registre,
     * voir docblock de {@see SignatureTemplateRegistry}) pilotent entièrement la disposition -
     * jamais une bascule sur la clé du gabarit.
     *
     * @param  array<string, mixed>  $def
     */
    private static function assembleVertical(array $def): string
    {
        $imageRole = $def['image_role'] ?? null;
        $imagePosition = $def['image_position'] ?? 'top';
        $centered = ! empty($def['centered']);
        $hasContactDivider = ! empty($def['contact_divider']);
        $socialEmphasis = ! empty($def['social_emphasis']);

        $centerX = (self::LEFT + self::RIGHT) / 2;
        $out = '';
        $y = 16.0;

        $imageHeight = match ($imageRole) {
            'portrait' => 26.0,
            'logo' => 16.0,
            default => 0.0,
        };
        $imageAt = function (float $y) use ($imageRole, $centered, $centerX): string {
            if ($imageRole === 'portrait') {
                $x = $centered ? $centerX - 13.0 : self::LEFT;

                return self::avatar($x, $y, 26.0);
            }
            if ($imageRole === 'logo') {
                $x = $centered ? $centerX - 19.0 : self::LEFT;

                return self::rect($x, $y, 38.0, 16.0, self::GRAY);
            }

            return '';
        };

        if ($imageRole !== null && $imagePosition !== 'bottom') {
            $out .= $imageAt($y);
            $y += $imageHeight + 10.0;
        }

        // Ligne d'identité (nom).
        $nameWidth = $centered ? 60.0 : 74.0;
        $nameX = $centered ? $centerX - $nameWidth / 2 : self::LEFT;
        $out .= self::line($nameX, $y, $nameWidth, 5.0);
        $y += 5.0 + 6.0;

        if ($hasContactDivider) {
            $out .= self::hline(self::LEFT, self::RIGHT, $y);
            $y += 9.0;
        } else {
            $y += 3.0;
        }

        // Lignes de contact (2, décroissantes).
        foreach ([58.0, 40.0] as $w) {
            $lw = $centered ? $w * 0.8 : $w;
            $lx = $centered ? $centerX - $lw / 2 : self::LEFT;
            $out .= self::line($lx, $y, $lw, 4.0);
            $y += 4.0 + 7.0;
        }

        if ($imageRole !== null && $imagePosition === 'bottom') {
            // Coordonnées d'abord, logo/portrait ET réseaux groupés tout en bas (logo_bas).
            $y += 4.0;
            $out .= $imageAt($y);
            $imgX = $centered ? $centerX - ($imageRole === 'portrait' ? 13.0 : 19.0) : self::LEFT;
            $out .= self::socials($imgX + ($imageRole === 'portrait' ? 26.0 : 38.0) + 10.0, $y + $imageHeight / 2, false);
        } else {
            $socialsX = $centered ? $centerX - 24.0 : self::LEFT;
            $out .= self::socials($socialsX, $y + 5.0, $socialEmphasis);
        }

        return $out;
    }

    // ------------------------------------------------------------------
    // Primitives (les 5 formes du brief - couleurs et proportions FIXES, seule la position varie)
    // ------------------------------------------------------------------

    private static function rect(float $x, float $y, float $w, float $h, string $fill, float $rx = 4.0): string
    {
        return sprintf(
            '<rect x="%s" y="%s" width="%s" height="%s" rx="%s" fill="%s"/>',
            self::n($x), self::n($y), self::n($w), self::n($h), self::n($rx), $fill
        );
    }

    /**
     * Avatar (photo/portrait) - carré à coins arrondis bleu avec une icône « personne » blanche
     * simple (un cercle = tête, un arc = épaules), comme chez HubSpot.
     */
    private static function avatar(float $x, float $y, float $size = 26.0): string
    {
        $headR = $size * 0.16;
        $headCy = $y + $size * 0.36;
        $shoulderTopY = $y + $size * 0.52;
        $shoulderY = $y + $size * 0.82;

        return self::rect($x, $y, $size, $size, self::AVATAR_BG, $size * 0.22)
            .sprintf('<circle cx="%s" cy="%s" r="%s" fill="%s"/>', self::n($x + $size / 2), self::n($headCy), self::n($headR), self::AVATAR_ICON)
            .sprintf(
                '<path d="M %s %s Q %s %s %s %s" stroke="%s" stroke-width="%s" fill="none" stroke-linecap="round"/>',
                self::n($x + $size * 0.22), self::n($shoulderY),
                self::n($x + $size / 2), self::n($shoulderTopY),
                self::n($x + $size * 0.78), self::n($shoulderY),
                self::AVATAR_ICON, self::n($size * 0.12)
            );
    }

    /** Une ligne de texte (barre grise, coins arrondis). */
    private static function line(float $x, float $y, float $w, float $h = 4.0): string
    {
        return self::rect($x, $y, max(1.0, $w), $h, self::GRAY, $h / 2);
    }

    /** 3 cercles pleins (réseaux sociaux) - bleu, rose, orange, dans cet ordre fixe. */
    private static function socials(float $x, float $y, bool $big = false): string
    {
        $r = $big ? 7.0 : 5.0;
        $out = '';
        $cx = $x + $r;
        foreach (self::SOCIAL_COLORS as $color) {
            $out .= sprintf('<circle cx="%s" cy="%s" r="%s" fill="%s"/>', self::n($cx), self::n($y), self::n($r), $color);
            $cx += $r * 2 + 6.0;
        }

        return $out;
    }

    /** Séparateur vertical (entre image et texte). */
    private static function vline(float $x, float $y1, float $y2): string
    {
        return sprintf('<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="%s" stroke-width="1.5"/>', self::n($x), self::n($y1), self::n($x), self::n($y2), self::GRAY);
    }

    /** Séparateur horizontal (cadre du haut, filet sous le nom, filet identité/contact). */
    private static function hline(float $x1, float $x2, float $y, bool $accent = false, float $strokeWidth = 1.5): string
    {
        return sprintf(
            '<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="%s" stroke-width="%s"/>',
            self::n($x1), self::n($y), self::n($x2), self::n($y), $accent ? self::ACCENT : self::GRAY, self::n($strokeWidth)
        );
    }

    /** Même patron que {@see SignatureRenderer::scalePx()} - extrait le nombre d'une valeur "22px". */
    private static function sizePx(string $value): int
    {
        return (int) preg_replace('/\D+/', '', $value);
    }

    /** Nombre compact (aucun zéro inutile) - allège le SVG, aucun effet visuel. */
    private static function n(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
