<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Shop\Gelato\Moderation;

/**
 * Point d'extension de modération du contenu client (texte et images de l'éditeur).
 *
 * Implémentation par défaut : NullContentModerator (laisse tout passer). Aucun service tiers n'est intégré
 * (règle fondateur 18 : aucune dépendance sans proposition). Pour brancher un vrai modérateur :
 * implémenter ce contrat puis indiquer la classe dans config('shop.editor.moderator')
 * (ou lier le contrat dans un service provider). Une implémentation DOIT retourner un refus
 * explicite (ModerationResult::reject) et ne jamais lever d'exception pour un refus normal.
 */
interface ContentModeratorContract
{
    public function checkText(string $text): ModerationResult;

    /** @param string $binary contenu brut de l'image téléversée, avant envoi au moteur */
    public function checkImage(string $binary, string $mime): ModerationResult;
}
