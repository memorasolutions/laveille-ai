<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Sso\Services;

use Modules\Sso\Models\SsoConfiguration;

/**
 * Construit le tableau de paramètres attendu par OneLogin\Saml2\Settings
 * (onelogin/php-saml) à partir d'une organisation (sso_configurations).
 *
 * « strict » reste TOUJOURS true en dehors des tests explicites — c'est ce
 * réglage qui active les validations strictes du toolkit (signature,
 * audience, destination, fenêtres temporelles) exigées par la spec.
 */
class SamlSettingsBuilder
{
    public function build(SsoConfiguration $configuration, string $acsUrl, string $metadataUrl): array
    {
        return [
            'strict' => (bool) config('sso.saml.strict', true),
            'debug' => (bool) config('sso.saml.debug', false),

            'sp' => [
                'entityId' => $configuration->sp_entity_id ?: config('sso.saml.sp_entity_id'),
                'assertionConsumerService' => [
                    'url' => $acsUrl,
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                ],
                'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
            ],

            'idp' => [
                'entityId' => $configuration->idp_entity_id,
                'singleSignOnService' => [
                    'url' => $configuration->idp_sso_url,
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ],
                'x509cert' => $this->normalizeCertificate($configuration->idp_x509_cert),
            ],

            // Fenêtre de tolérance d'horloge (dérive raisonnable IdP <-> SP).
            'security' => [
                'wantAssertionsSigned' => true,
                'wantMessagesSigned' => false,
                'wantNameIdEncrypted' => false,
                'requestedAuthnContext' => false,
            ],
        ];
    }

    /**
     * Le toolkit accepte un certificat X.509 sans en-têtes BEGIN/END — on
     * normalise pour accepter les deux formats (stocké nu ou avec en-têtes).
     */
    private function normalizeCertificate(string $cert): string
    {
        $cert = str_replace(["-----BEGIN CERTIFICATE-----", "-----END CERTIFICATE-----", "\r"], '', $cert);

        return trim($cert);
    }
}
