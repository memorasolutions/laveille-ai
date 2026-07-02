<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Levée quand le lancement (initiation ou callback) d'un outil externe LTI 1.3
 * échoue une validation de sécurité (état/nonce absent ou rejoué, signature,
 * émetteur, audience, deployment_id, type de message). Le message porté par
 * cette exception est TECHNIQUE et INTERNE (journalisation uniquement) : il
 * n'est jamais affiché tel quel à l'utilisateur final.
 */

declare(strict_types=1);

namespace Modules\Academy\Exceptions;

class LtiLaunchException extends \RuntimeException
{
    //
}
