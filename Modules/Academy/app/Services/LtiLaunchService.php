<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Consumer LTI 1.3 minimal (Academy branche des outils EXTERNES, jamais
 * l'inverse). Implémente le flux OIDC Third-Party Initiated Login puis la
 * validation du jeton d'identité (id_token) reçu de l'outil, selon le
 * standard 1EdTech LTI 1.3 Core.
 *
 * HORS PÉRIMÈTRE (MVP, voir Modules/Academy/tests/Feature/AcademyLtiTest.php
 * pour le détail assumé) : Deep Linking, Assignment and Grade Services (AGS),
 * Names and Roles Provisioning Service (NRPS). Ce service ne fait QUE lancer
 * un outil en lecture (LtiResourceLinkRequest) ; aucune note ni liste
 * d'apprenants n'est échangée avec l'outil externe.
 *
 * SÉCURITÉ :
 *  - state = jeton opaque anti-CSRF, à usage UNIQUE (Cache::pull = lecture +
 *    suppression atomique, anti-rejeu du même state) ;
 *  - nonce = anti-rejeu du jeton d'identité lui-même, revérifié dans le
 *    callback contre la valeur posée en cache à l'initiation ;
 *  - iss/aud/deployment_id/message_type sont TOUS revérifiés explicitement,
 *    aucune confiance implicite dans la seule signature valide du jeton ;
 *  - le JWKS distant est mis en cache (1 h) via le cache Laravel natif, jamais
 *    interrogé directement par ce service à chaque lancement.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Academy\Exceptions\LtiLaunchException;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\LtiToolRegistration;

class LtiLaunchService
{
    /** Durée de vie du state/nonce en cache (minutes) : fenêtre de lancement raisonnable. */
    private const STATE_TTL_MINUTES = 10;

    /** Durée de mise en cache du JWKS distant (minutes) : évite un appel réseau à chaque lancement. */
    private const JWKS_TTL_MINUTES = 60;

    /**
     * Étape 1 du flux OIDC LTI 1.3 (Third-Party Initiated Login) : redirige
     * l'apprenant vers l'« auth_login_url » de l'outil externe, avec un state
     * et un nonce opaques posés en cache (anti-CSRF / anti-rejeu).
     */
    public function initiateLogin(LtiToolRegistration $tool, User $user, LessonItem $item): RedirectResponse
    {
        $nonce = Str::random(40);
        $state = Str::random(40);

        Cache::put(
            "academy_lti_state_{$state}",
            [
                'nonce'               => $nonce,
                'lesson_item_id'      => $item->id,
                'user_id'             => $user->id,
                'tool_registration_id' => $tool->id,
            ],
            now()->addMinutes(self::STATE_TTL_MINUTES),
        );

        $query = [
            // « iss » = NOTRE plateforme (Academy), pas celle de l'outil externe.
            'iss'            => config('app.url'),
            'login_hint'     => (string) $user->id,
            'client_id'      => $tool->client_id,
            'target_link_uri' => route('academy.lti.callback'),
            'lti_message_hint' => (string) $item->id,
            'state'          => $state,
            'nonce'          => $nonce,
            'response_type'  => 'id_token',
            'response_mode'  => 'form_post',
            'scope'          => 'openid',
            'prompt'         => 'none',
        ];

        return redirect()->away($tool->auth_login_url . '?' . http_build_query($query));
    }

    /**
     * Étape 2 du flux : valide le jeton d'identité (id_token) reçu de l'outil
     * externe après authentification OIDC. Retourne l'outil, l'item de leçon
     * visé et les claims décodés si TOUTES les vérifications passent.
     *
     * Cette méthode laisse volontairement fuir LtiLaunchException ainsi que
     * toute exception firebase/php-jwt (SignatureInvalidException,
     * ExpiredException, BeforeValidException) ou HTTP : c'est le CONTRÔLEUR
     * qui les attrape toutes pour journaliser en détail et répondre un
     * message générique à l'utilisateur (aucun détail technique exposé).
     *
     * @return array{tool: LtiToolRegistration, lesson_item_id: int, claims: \stdClass}
     */
    public function handleCallback(Request $request): array
    {
        $state   = (string) $request->input('state', '');
        $idToken = (string) $request->input('id_token', '');

        if ($state === '' || $idToken === '') {
            throw new LtiLaunchException('lti_callback_missing_state_or_token');
        }

        // Lecture + suppression atomique : anti-rejeu du même state (le 2e essai
        // avec le même state ne trouve plus rien en cache).
        $stateData = Cache::pull("academy_lti_state_{$state}");
        if (! is_array($stateData)) {
            throw new LtiLaunchException('lti_callback_state_not_found_or_expired');
        }

        $tool = LtiToolRegistration::find($stateData['tool_registration_id'] ?? null);
        if (! $tool instanceof LtiToolRegistration) {
            throw new LtiLaunchException('lti_callback_tool_registration_not_found');
        }

        $jwks = Cache::remember(
            "academy_lti_jwks_{$tool->id}",
            now()->addMinutes(self::JWKS_TTL_MINUTES),
            fn () => Http::get($tool->jwks_url)->throw()->json(),
        );

        if (! is_array($jwks)) {
            throw new LtiLaunchException('lti_callback_jwks_invalid_response');
        }

        // Alg par défaut RS256 (signature asymétrique standard LTI 1.3) si la
        // clé JWKS n'annonce pas explicitement son « alg ».
        $keySet = JWK::parseKeySet($jwks, 'RS256');
        $claims = JWT::decode($idToken, $keySet);

        // « exp » est déjà vérifié par la librairie (ExpiredException levée plus
        // haut si expiré) : aucune revérification manuelle ici.

        if (! isset($claims->iss) || (string) $claims->iss !== $tool->issuer) {
            throw new LtiLaunchException('lti_callback_issuer_mismatch');
        }

        if (! $this->audienceMatches($claims->aud ?? null, $tool->client_id)) {
            throw new LtiLaunchException('lti_callback_audience_mismatch');
        }

        $tokenNonce  = (string) ($claims->nonce ?? '');
        $cachedNonce = (string) ($stateData['nonce'] ?? '');
        if ($tokenNonce === '' || ! hash_equals($cachedNonce, $tokenNonce)) {
            throw new LtiLaunchException('lti_callback_nonce_mismatch');
        }

        $deploymentId = (string) ($claims->{'https://purl.imsglobal.org/spec/lti/claim/deployment_id'} ?? '');
        if ($deploymentId === '' || ! hash_equals($tool->deployment_id, $deploymentId)) {
            throw new LtiLaunchException('lti_callback_deployment_id_mismatch');
        }

        $messageType = (string) ($claims->{'https://purl.imsglobal.org/spec/lti-1p0/claim/message_type'} ?? '');
        if ($messageType !== 'LtiResourceLinkRequest') {
            throw new LtiLaunchException('lti_callback_unexpected_message_type');
        }

        return [
            'tool'           => $tool,
            'lesson_item_id' => (int) $stateData['lesson_item_id'],
            'claims'         => $claims,
        ];
    }

    /**
     * Le claim « aud » du JWT peut être une chaîne unique OU un tableau de
     * chaînes (RFC 7519) : le client_id de l'outil doit s'y trouver dans les
     * deux cas.
     */
    private function audienceMatches(mixed $aud, string $clientId): bool
    {
        if (is_string($aud)) {
            return hash_equals($clientId, $aud);
        }

        if (is_array($aud)) {
            foreach ($aud as $candidate) {
                if (is_string($candidate) && hash_equals($clientId, $candidate)) {
                    return true;
                }
            }
        }

        return false;
    }
}
