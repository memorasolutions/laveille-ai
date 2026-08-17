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
 * NOTE DATÉE 2026-08-17 (fin de journée, PROMPT_TEMPLATE_VERSION 2026-08-17.2, un seul
 * incrément pour l'ensemble des addenda ci-dessous) : décision du propriétaire qui renverse
 * l'arbitrage "l'agent ne publie jamais" du panel du même jour. Quatre changements dans le même
 * gabarit :
 * - ÉTAPE 6 - PUBLICATION (après l'image) : l'agent exécute lui-même
 *   `php artisan news:apply --publish` en toute fin de flux et rapporte le lien public direct.
 * - ÉTAPE 5 - RÉVISION ADVERSARIALE (nouvelle, entre l'image et la publication) : relecture
 *   obligatoire de la fiche telle qu'appliquée sur trois axes (vrai, vérifiable, parfaitement
 *   vulgarisé) avant que la porte de publication ne s'ouvre.
 * - Règle de rédaction n°7 à l'ÉTAPE 1 (« recherche avant rédaction ») : une inconnue factuelle
 *   ouverte doit être cherchée (pp_search) avant d'invoquer « aucune source » comme raccourci.
 * - structured_summary (résumé MACHINE de la collecte, prioritaire sur summary côté fiche
 *   publique) est désormais effacé par `news:apply --payload` et par le bouton manuel
 *   Publier-et-purger - voir NewsArticle::logStructuredSummaryOverride().
 *
 * NOTE DATÉE 2026-08-17 (2e révision de la journée, PROMPT_TEMPLATE_VERSION 2026-08-17.3) -
 * synthèse du panel de 5 IA + 2 décisions du propriétaire. Renumérote les étapes du gabarit
 * (7 au lieu de 6) :
 * - ÉTAPE 3 - VERDICT DE DIVERGENCE (nouvelle, entre la preuve éditoriale et l'écriture bornée) :
 *   compare le texte média à l'original retrouvé, déclare CONCORDANT/IMPRÉCIS/CONTRADICTOIRE ;
 *   en cas d'écart, le fait de l'original prime toujours et l'écart est énoncé dans la fiche.
 * - ÉTAPE 2 - PREUVE ÉDITORIALE accepte un 3e type de paire "primary_fact" (citation exacte de
 *   l'original, source_url obligatoire, préséance sur "fact" en cas d'écart) - non vérifiable
 *   automatiquement contre l'original (non stocké en base), contrairement à "fact".
 * - ÉTAPE 4 - ÉCRITURE BORNÉE (ex-étape 3, mécanique inchangée) : le payload accepte désormais
 *   "primary_sources" (tableau {label, url, note?}) et, à l'étape 6, "image_credit".
 * - ÉTAPE 5 - RÉVISION ADVERSARIALE (mécanique et axes existants conservés) : ajoute le test de
 *   retrait, l'audit des omissions délibérées, la reconstitution aveugle et la porte de sortie
 *   « RESTER EN BROUILLON : [motif] » - verdict non contournable qui arrête le cycle.
 * - ÉTAPE 6 - PHOTO (remplace l'ex-étape 4 - IMAGE, décision du propriétaire) : l'illustration
 *   n'est plus générée par IA mais cherchée dans une banque libre de droits (MCP stock-photos),
 *   APRÈS la révision (texte figé) plutôt qu'avant - décision unanime du panel 4/5. Interdit
 *   absolu : photo de presse/éditoriale/agence (réclamation PicRights déjà reçue par le projet).
 * - ÉTAPE 7 - PUBLICATION (ex-étape 6, garde-fous inchangés) : affiche désormais la fiche finale
 *   à l'écran avant --publish, et le rapport final liste verdict de divergence, trouvailles de la
 *   révision, omissions délibérées, reconstitution aveugle et traçabilité des recherches.
 * - buildImagePrompt() ci-dessous alimente maintenant l'étape 6 (consigne de recherche photo) et
 *   non plus une génération d'image IA - voir sa documentation mise à jour plus bas.
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
    public const PROMPT_TEMPLATE_VERSION = '2026-08-17.3';

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
     * ACTION : consigne de recherche photo (design doc section 5.3, RÉVISION 2026-08-17.3 -
     * décision du propriétaire) - ne produit plus un prompt de génération d'image IA, mais la
     * consigne de recherche suivie pour choisir une PHOTO libre de droits à l'étape 6 du prompt
     * d'orchestration. Jamais de bouton « générer », le libellé assume le flux manuel (« copier
     * la consigne et chercher dans la banque de photos »). Contrairement à build() ci-dessus, ne
     * nécessite PAS le texte source : la structure de la consigne est fixe (gabarit
     * _composition_image_prompt_template), seuls le titre et l'angle varient par fiche. Appelée à
     * la fois par NewsCompositionController::generateImagePrompt() (bouton dédié, inchangé) et
     * par build() ci-dessus (étape 6 du prompt d'orchestration, après la révision adversariale).
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
