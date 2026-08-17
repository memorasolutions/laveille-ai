<?php

declare(strict_types=1);

namespace Modules\News\Services;

use Illuminate\Support\Str;
use Modules\News\Models\NewsArticle;

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
 * RÉVISION 2026-08-17 (design doc, section "Révision 2026-08-17 - prompt d'orchestration Claude
 * Code CLI") : le prompt cible désormais Claude Code CLI comme exécutant complet (rédaction +
 * preuve + écriture bornée via `php artisan news:apply` + image). build() a donc changé de
 * signature - il prend l'objet NewsArticle complet (id, slug, empreinte, updated_at nécessaires
 * aux métadonnées de fraîcheur) plutôt que le seul texte source. Panel de 5 IA unanime : la
 * commande bornée est la SEULE porte d'écriture, jamais d'Eloquent/SQL direct par l'agent.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */
class CompositionPromptBuilder
{
    /**
     * Version du gabarit de prompt d'orchestration. Incrémentée à CHAQUE changement de contenu
     * de _composition_prompt_template.blade.php - journalisée par NewsApplyCommand (canal
     * 'composition') pour savoir, a posteriori, sous quelle version d'instructions une fiche a
     * été composée.
     */
    public const PROMPT_TEMPLATE_VERSION = '2026-08-17.1';

    /**
     * ACTION : signature changée de (string $sourceText, string $title, string $angle) à
     * (NewsArticle $article, string $angle) - révision 2026-08-17. Le gabarit a désormais besoin
     * de l'id, du slug, de l'empreinte SHA-256 et de updated_at de la fiche (métadonnées de
     * fraîcheur repassées telles quelles à news:apply) - les prendre un par un aurait fait
     * grossir la signature à 6+ paramètres positionnels fragiles à l'ordre ; l'objet NewsArticle
     * est le paramètre naturel puisque l'appelant (NewsCompositionController::generatePrompt) l'a
     * déjà sous la main.
     * MCP: SELF (<5 lignes)
     * RAISON: seul appelant existant, mis à jour dans le même lot - aucune signature externe
     * cassée en dehors du module.
     */
    public function build(NewsArticle $article, string $angle): string
    {
        $title = trim($article->seo_title ?: $article->title);
        $angle = trim($angle);

        return view('news::admin._composition_prompt_template', [
            'articleId' => $article->id,
            'slug' => $article->slug,
            'title' => $title,
            'angle' => $angle,
            'sourceText' => (string) $article->internal_source_text,
            // Nonce à usage unique généré à CHAQUE appel (spotlighting) - jamais réutilisé d'une
            // génération à l'autre, jamais persisté : son seul rôle est de rendre les délimiteurs
            // du bloc source imprévisibles pour un texte source hostile qui tenterait de les
            // imiter.
            'nonce' => Str::random(8),
            // Empreinte garantie non nulle par l'appelant (NewsCompositionController::
            // generatePrompt backfille source_content_hash AVANT d'appeler build() si elle
            // manquait) - c'est cette même valeur, telle quelle, que news:apply comparera.
            'sourceHash' => (string) $article->source_content_hash,
            'updatedAt' => $article->updated_at?->toIso8601String(),
            'promptVersion' => self::PROMPT_TEMPLATE_VERSION,
            // Réutilise EXACTEMENT le même gabarit d'image que generateImagePrompt() (Phase D) -
            // aucun texte d'image dupliqué, l'étape 4 du prompt principal l'inclut tel quel.
            'imagePrompt' => $this->buildImagePrompt($title, $angle),
        ])->render();
    }

    /**
     * ACTION : prompt d'image (design doc section 5.3) - jamais de bouton « générer », le libellé
     * assume le flux manuel (« copier le prompt et ouvrir Gemini »). Contrairement à build()
     * ci-dessus, ne nécessite PAS le texte source : le style est fixe (identité visuelle du site,
     * gabarit _composition_image_prompt_template), seuls le titre et l'angle varient par fiche.
     * Appelée à la fois par NewsCompositionController::generateImagePrompt() (bouton dédié,
     * inchangé) et par build() ci-dessus (étape 4 du prompt d'orchestration).
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
