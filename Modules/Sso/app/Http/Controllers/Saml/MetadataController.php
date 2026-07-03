<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Sso\Http\Controllers\Saml;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sso\Models\SsoConfiguration;
use Modules\Sso\Services\SamlSettingsBuilder;
use OneLogin\Saml2\Settings;
use Symfony\Component\HttpFoundation\Response;

/**
 * GET /sso/saml/metadata[?org=slug] — Métadonnées SP (XML), à coller côté
 * configuration de l'IdP client. Si aucune organisation n'est précisée,
 * expose des métadonnées SP GÉNÉRIQUES (entity_id/ACS uniquement, sans IdP
 * spécifique) — utile pour amorcer la configuration avant que l'organisation
 * existe en base.
 */
class MetadataController extends Controller
{
    public function __construct(private readonly SamlSettingsBuilder $settingsBuilder)
    {
    }

    public function __invoke(Request $request): Response
    {
        abort_if(! config('sso.enabled'), 404);

        $organizationSlug = (string) $request->query('org', '');
        $configuration = $organizationSlug !== ''
            ? SsoConfiguration::query()->where('organization_slug', $organizationSlug)->active()->first()
            : null;

        abort_if($organizationSlug !== '' && ! $configuration, 404);

        $settingsArray = $configuration
            ? $this->settingsBuilder->build($configuration, route('sso.saml.acs'), route('sso.saml.metadata'))
            : $this->genericSettings();

        $settings = new Settings($settingsArray, true);
        $metadata = $settings->getSPMetadata();

        $errors = $settings->validateMetadata($metadata);
        abort_if(! empty($errors), 500, 'Métadonnées SP invalides : '.implode(', ', $errors));

        return response($metadata, 200, ['Content-Type' => 'text/xml']);
    }

    private function genericSettings(): array
    {
        return [
            'strict' => (bool) config('sso.saml.strict', true),
            'sp' => [
                'entityId' => config('sso.saml.sp_entity_id'),
                'assertionConsumerService' => [
                    'url' => route('sso.saml.acs'),
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                ],
                'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
            ],
            // IdP minimal factice — requis par le toolkit pour générer le XML,
            // sans configuration réelle tant qu'aucune organisation n'est ciblée.
            'idp' => [
                'entityId' => 'urn:sso:generic',
                'singleSignOnService' => ['url' => route('sso.saml.login'), 'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'],
                'x509cert' => '',
            ],
        ];
    }
}
