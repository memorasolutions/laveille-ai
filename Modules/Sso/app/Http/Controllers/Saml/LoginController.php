<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Sso\Http\Controllers\Saml;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sso\Models\SsoConfiguration;
use Modules\Sso\Services\SamlSettingsBuilder;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Settings;

/**
 * GET /sso/saml/login?org=slug — Initie l'AuthnRequest SAML vers l'IdP de
 * l'organisation. L'ID de la requête (utilisé ensuite pour valider
 * InResponseTo à l'ACS, protection anti-rejeu) est conservé en SESSION
 * Laravel — jamais côté client, jamais dans l'URL.
 */
class LoginController extends Controller
{
    public function __construct(private readonly SamlSettingsBuilder $settingsBuilder)
    {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        abort_if(! config('sso.enabled'), 404);

        $organizationSlug = (string) $request->query('org', '');
        abort_if($organizationSlug === '', 400, 'Paramètre "org" requis.');

        $configuration = SsoConfiguration::query()
            ->where('organization_slug', $organizationSlug)
            ->active()
            ->firstOrFail();

        $settingsArray = $this->settingsBuilder->build($configuration, route('sso.saml.acs'), route('sso.saml.metadata'));
        $settingsArray['strict'] = (bool) config('sso.saml.strict', true);

        $auth = new Auth($settingsArray);

        // Conserve l'organisation ciblée pour la retrouver à l'ACS (le
        // RelayState transporte AUSSI le slug, redondance volontaire —
        // voir AcsController::resolveOrganization()).
        session(['sso_saml_pending_org' => $organizationSlug]);

        $ssoUrl = $auth->login($organizationSlug, [], false, false, true);

        // $stay=true retourne l'URL au lieu de rediriger directement,
        // permet à Laravel de gérer la redirection (testabilité + logs).
        session(['sso_saml_last_request_id' => $auth->getLastRequestID()]);

        return redirect()->away($ssoUrl);
    }
}
