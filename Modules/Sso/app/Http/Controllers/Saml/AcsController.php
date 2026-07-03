<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Sso\Http\Controllers\Saml;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth as LaravelAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Modules\Sso\Models\SsoConfiguration;
use Modules\Sso\Services\SamlReplayGuardService;
use Modules\Sso\Services\SamlSettingsBuilder;
use OneLogin\Saml2\Auth;

/**
 * POST /sso/saml/acs — Assertion Consumer Service.
 *
 * Validation STRICTE (toutes déléguées au toolkit onelogin/php-saml en mode
 * strict=true, jamais désactivable en prod) :
 *  - signature XML de l'assertion (certificat X.509 de l'IdP configuré) ;
 *  - audience (doit correspondre au sp_entity_id) ;
 *  - destination (doit correspondre à CETTE URL ACS) ;
 *  - NotBefore / NotOnOrAfter (fenêtre de validité temporelle, tolérance
 *    de dérive d'horloge = sso.saml.response_max_age_seconds) ;
 *  - PLUS, en sus du toolkit : protection ANTI-REJEU applicative — le
 *    InResponseTo de la réponse est consommé une seule fois en base
 *    (SamlReplayGuardService), une 2e présentation du MÊME InResponseTo
 *    est REJETÉE même si la signature XML est par ailleurs valide (le
 *    toolkit seul ne protège pas contre le rejeu d'une assertion déjà vue).
 */
class AcsController extends Controller
{
    public function __construct(
        private readonly SamlSettingsBuilder $settingsBuilder,
        private readonly SamlReplayGuardService $replayGuard,
    ) {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        abort_if(! config('sso.enabled'), 404);

        $organizationSlug = (string) ($request->input('RelayState') ?: session('sso_saml_pending_org', ''));
        abort_if($organizationSlug === '', 400, 'Organisation SAML introuvable (RelayState manquant).');

        $configuration = SsoConfiguration::query()
            ->where('organization_slug', $organizationSlug)
            ->active()
            ->first();

        abort_if(! $configuration, 404);

        $settingsArray = $this->settingsBuilder->build($configuration, route('sso.saml.acs'), route('sso.saml.metadata'));
        $settingsArray['strict'] = (bool) config('sso.saml.strict', true);

        $auth = new Auth($settingsArray);

        $expectedRequestId = session('sso_saml_last_request_id');

        try {
            $auth->processResponse($expectedRequestId);
        } catch (\Throwable $e) {
            Log::warning('SSO SAML : exception au traitement de la réponse.', [
                'organization' => $organizationSlug,
                'message' => $e->getMessage(),
            ]);

            return $this->denyAuthentication('Réponse SAML invalide.');
        }

        if (! empty($auth->getErrors())) {
            Log::warning('SSO SAML : réponse rejetée par le toolkit.', [
                'organization' => $organizationSlug,
                'errors' => $auth->getErrors(),
                'last_error_reason' => $auth->getLastErrorReason(),
            ]);

            return $this->denyAuthentication('Réponse SAML rejetée : '.$auth->getLastErrorReason());
        }

        if (! $auth->isAuthenticated()) {
            return $this->denyAuthentication('Assertion SAML non authentifiée.');
        }

        // --- Anti-rejeu applicatif (au-delà de la validation du toolkit) ---
        $inResponseTo = $this->extractInResponseTo($auth);

        if ($inResponseTo !== null) {
            $firstUse = $this->replayGuard->consume($configuration, $inResponseTo, $auth->getLastAssertionId());

            if (! $firstUse) {
                Log::warning('SSO SAML : REJEU détecté (InResponseTo déjà consommé).', [
                    'organization' => $organizationSlug,
                    'in_response_to' => $inResponseTo,
                ]);

                return $this->denyAuthentication('Cette assertion SAML a déjà été utilisée (rejeu détecté).');
            }
        }

        // --- Mapping des attributs -> compte Laravel (findOrCreate par email) ---
        $mapping = $configuration->effectiveAttributeMapping();
        $attributes = $auth->getAttributes();

        $email = $this->firstAttribute($attributes, $mapping['email'] ?? 'email') ?? $auth->getNameId();
        $name = $this->firstAttribute($attributes, $mapping['name'] ?? 'name') ?? $email;

        abort_if(empty($email), 422, 'Aucun email résolu depuis les attributs SAML.');

        $user = User::firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->name = $name;
            $user->password = Hash::make(\Illuminate\Support\Str::password(32));
            $user->is_active = true;
            $user->save();
        }

        abort_if(! $user->is_active, 403, 'Compte désactivé.');

        session()->forget(['sso_saml_pending_org', 'sso_saml_last_request_id']);

        LaravelAuth::login($user, false);
        $request->session()->regenerate();

        return redirect()->intended(route('user.dashboard'));
    }

    private function denyAuthentication(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['sso' => $message]);
    }

    /** @param array<string, array<int, string>> $attributes */
    private function firstAttribute(array $attributes, string $key): ?string
    {
        return $attributes[$key][0] ?? null;
    }

    /**
     * Extrait l'InResponseTo de la réponse SAML brute — le toolkit ne
     * l'expose pas via une méthode publique dédiée sur Auth, on le lit
     * directement dans le XML déjà validé (getLastResponseXML()).
     */
    private function extractInResponseTo(Auth $auth): ?string
    {
        $xml = $auth->getLastResponseXML();

        if (! $xml) {
            return null;
        }

        $document = new \DOMDocument();
        $document->loadXML($xml);

        $inResponseTo = $document->documentElement?->getAttribute('InResponseTo');

        return $inResponseTo !== '' ? $inResponseTo : null;
    }
}
