<?php

declare(strict_types=1);

namespace Modules\News\Services;

/**
 * Écran de composition manuelle (design doc "Actus - composition manuelle assistée", 2026-08-15,
 * section 5.1 et 7 / Phase B) - génère le prompt texte que le propriétaire copie dans ses outils
 * d'IA externes pour rédiger le titre et le résumé publiés d'UNE actualité, à partir du texte
 * source collé (jamais publié) et d'un angle éditorial optionnel.
 *
 * Calqué sur ConcentrePromptBuilder (même mécanique : construction de variables puis rendu d'un
 * gabarit Blade dédié) - pas de fusion des deux services : le Concentré assemble plusieurs URLs
 * sur une semaine, la composition traite le texte intégral d'UNE seule actualité déjà collée en
 * base. Rien à mutualiser au-delà du principe "un service génère, une vue rend le texte".
 *
 * Le standard imposé au prompt généré (règle unique du liant, interdiction de la paraphrase sur
 * chiffres/dates/citations, autorisation explicite de dire "aucune source") est arrêté par le
 * panel en trois rounds - section 5.1 et 10 du design doc. NE PAS le modifier sans revoir le
 * design doc.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */
class CompositionPromptBuilder
{
    public function build(string $sourceText, string $title, string $angle): string
    {
        return view('news::admin._composition_prompt_template', [
            'title' => trim($title),
            'angle' => trim($angle),
            'sourceText' => $sourceText,
        ])->render();
    }

    /**
     * ACTION : prompt d'image (design doc section 5.3) - jamais de bouton « générer », le libellé
     * assume le flux manuel (« copier le prompt et ouvrir Gemini »). Contrairement à build()
     * ci-dessus, ne nécessite PAS le texte source : le style est fixe (identité visuelle du site,
     * gabarit _composition_image_prompt_template), seuls le titre et l'angle varient par fiche.
     * MCP: SELF (<5 lignes, même mécanique que build() juste au-dessus)
     * RAISON: garde-fou "l'écran est un assistant de composition, jamais un générateur" (5.3).
     */
    public function buildImagePrompt(string $title, string $angle): string
    {
        return view('news::admin._composition_image_prompt_template', [
            'title' => trim($title),
            'angle' => trim($angle),
        ])->render();
    }
}
