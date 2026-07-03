<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Fabrique un couple clé privée / certificat X.509 auto-signé UNIQUEMENT pour
 * les tests (signature d'assertions SAML factices). Généré une fois par
 * process de test (cache statique) via openssl (extension PHP openssl),
 * jamais commité, jamais utilisé hors du contexte de test.
 */

declare(strict_types=1);

namespace Modules\Sso\Tests\Concerns;

class SamlTestFixtures
{
    private static ?array $pair = null;

    /** @return array{private_key: string, certificate: string} */
    public static function keyPair(): array
    {
        if (self::$pair !== null) {
            return self::$pair;
        }

        $privateKeyResource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $csr = openssl_csr_new(
            ['commonName' => 'sso-test-idp.example.com'],
            $privateKeyResource,
            ['digest_alg' => 'sha256']
        );

        $x509 = openssl_csr_sign($csr, null, $privateKeyResource, 365, ['digest_alg' => 'sha256']);

        openssl_x509_export($x509, $certificatePem);
        openssl_pkey_export($privateKeyResource, $privateKeyPem);

        return self::$pair = [
            'private_key' => $privateKeyPem,
            'certificate' => $certificatePem,
        ];
    }

    public static function certificatePem(): string
    {
        return self::keyPair()['certificate'];
    }

    public static function privateKeyPem(): string
    {
        return self::keyPair()['private_key'];
    }

    /** Certificat X.509 nu (sans les en-têtes BEGIN/END), format stocké en base. */
    public static function certificateBody(): string
    {
        $pem = self::certificatePem();
        $pem = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----'], '', $pem);

        return trim($pem);
    }
}
