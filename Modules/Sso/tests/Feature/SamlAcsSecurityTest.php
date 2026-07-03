<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Sécurité ACS SAML (validation stricte + anti-rejeu).
 *
 * Prouve que :
 *  - une assertion EXPIRÉE (NotOnOrAfter dans le passé) est REJETÉE ;
 *  - une assertion à SIGNATURE INVALIDE (signée par une autre clé que celle
 *    de l'IdP configuré) est REJETÉE ;
 *  - la MÊME assertion (même InResponseTo) présentée deux fois est REJETÉE
 *    la 2e fois (anti-rejeu applicatif, indépendant de la validité XML) ;
 *  - le drapeau sso.enabled=false (défaut) fait répondre 404 à toutes les
 *    routes SAML, même avec un payload par ailleurs valide.
 *
 * Autonome : helpers préfixés `samlAcs`, aucune redéclaration d'une fonction
 * d'un autre fichier de test. Garde-fou : si le module Sso est désactivé
 * dans modules_statuses.json, tous les tests sont SKIPPED.
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Sso\Models\SsoConfiguration;
use Modules\Sso\Tests\Concerns\SamlTestFixtures;
use OneLogin\Saml2\Utils;

uses(RefreshDatabase::class);
uses(\Modules\Sso\Tests\Concerns\SkipsWhenSsoDisabled::class);

beforeEach(function (): void {
    test()->skipIfSsoModuleDisabled();
    config(['sso.enabled' => true]);
});

afterEach(function (): void {
    // Nettoie les superglobals PHP peuplés par samlAcsPost() (PAS gérés par
    // le TestCase Laravel) pour ne rien laisser fuiter au test suivant.
    unset($_POST['SAMLResponse'], $_POST['RelayState']);
    unset($_SERVER['HTTPS'], $_SERVER['HTTP_HOST'], $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_URI']);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers samlAcs (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function samlAcsConfiguration(): SsoConfiguration
{
    return SsoConfiguration::factory()->create([
        'organization_slug' => 'acme-corp',
        'idp_entity_id' => 'https://idp.acme-corp.example.com/entity',
        'idp_sso_url' => 'https://idp.acme-corp.example.com/sso',
        'idp_x509_cert' => SamlTestFixtures::certificateBody(),
    ]);
}

/**
 * Construit une réponse SAML (XML) signée par la clé de test, avec un
 * NotOnOrAfter contrôlable (permet de fabriquer une assertion expirée).
 */
function samlAcsBuildSignedResponseXml(SsoConfiguration $configuration, string $inResponseTo, string $email, \DateTimeImmutable $notOnOrAfter, ?string $signingCertOverride = null): string
{
    $issueInstant = gmdate('Y-m-d\TH:i:s\Z');
    $notBefore = gmdate('Y-m-d\TH:i:s\Z', time() - 60);
    // IMPORTANT : $notOnOrAfter peut avoir été construit via
    // `new DateTimeImmutable('+5 minutes')`, qui interprète l'expression
    // relative dans le FUSEAU PAR DÉFAUT de l'app (America/Toronto), pas en
    // UTC — reconvertir explicitement en UTC avant de formater, sinon le
    // décalage (~4h) fait échouer/réussir les tests de façon incorrecte.
    $notOnOrAfterUtc = $notOnOrAfter->setTimezone(new \DateTimeZone('UTC'));
    $notOnOrAfterStr = $notOnOrAfterUtc->format('Y-m-d\TH:i:s\Z');
    $sessionNotOnOrAfter = gmdate('Y-m-d\TH:i:s\Z', time() + 3600);

    $responseId = 'resp-'.bin2hex(random_bytes(8));
    $assertionId = 'assert-'.bin2hex(random_bytes(8));
    $acsUrl = route('sso.saml.acs');

    $xml = <<<XML
<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
    ID="{$responseId}" Version="2.0" IssueInstant="{$issueInstant}"
    Destination="{$acsUrl}" InResponseTo="{$inResponseTo}">
    <saml:Issuer>{$configuration->idp_entity_id}</saml:Issuer>
    <samlp:Status><samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/></samlp:Status>
    <saml:Assertion xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema"
        ID="{$assertionId}" Version="2.0" IssueInstant="{$issueInstant}">
        <saml:Issuer>{$configuration->idp_entity_id}</saml:Issuer>
        <saml:Subject>
            <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress">{$email}</saml:NameID>
            <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
                <saml:SubjectConfirmationData NotOnOrAfter="{$notOnOrAfterStr}" Recipient="{$acsUrl}" InResponseTo="{$inResponseTo}"/>
            </saml:SubjectConfirmation>
        </saml:Subject>
        <saml:Conditions NotBefore="{$notBefore}" NotOnOrAfter="{$notOnOrAfterStr}">
            <saml:AudienceRestriction><saml:Audience>{$configuration->sp_entity_id}</saml:Audience></saml:AudienceRestriction>
        </saml:Conditions>
        <saml:AuthnStatement AuthnInstant="{$issueInstant}" SessionNotOnOrAfter="{$sessionNotOnOrAfter}">
            <saml:AuthnContext><saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport</saml:AuthnContextClassRef></saml:AuthnContext>
        </saml:AuthnStatement>
        <saml:AttributeStatement>
            <saml:Attribute Name="email"><saml:AttributeValue xsi:type="xs:string">{$email}</saml:AttributeValue></saml:Attribute>
            <saml:Attribute Name="name"><saml:AttributeValue xsi:type="xs:string">Test User</saml:AttributeValue></saml:Attribute>
        </saml:AttributeStatement>
    </saml:Assertion>
</samlp:Response>
XML;

    return samlAcsSignAssertion($xml, $assertionId);
}

/** Signe le noeud <saml:Assertion> avec la clé privée de test (XML-DSig enveloped). */
function samlAcsSignAssertion(string $xml, string $assertionId): string
{
    $document = new DOMDocument();
    $document->loadXML($xml);

    $assertionNode = $document->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:assertion', 'Assertion')->item(0);

    $privateKey = new \RobRichards\XMLSecLibs\XMLSecurityKey(\RobRichards\XMLSecLibs\XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
    $privateKey->loadKey(SamlTestFixtures::privateKeyPem());

    $objXMLSecDSig = new \RobRichards\XMLSecLibs\XMLSecurityDSig();
    $objXMLSecDSig->setCanonicalMethod(\RobRichards\XMLSecLibs\XMLSecurityDSig::EXC_C14N);
    $objXMLSecDSig->addReferenceList([$assertionNode], \RobRichards\XMLSecLibs\XMLSecurityDSig::SHA256, [
        'http://www.w3.org/2000/09/xmldsig#enveloped-signature',
        \RobRichards\XMLSecLibs\XMLSecurityDSig::EXC_C14N,
    ], ['id_name' => 'ID']);
    $objXMLSecDSig->sign($privateKey);

    $cert = SamlTestFixtures::certificatePem();
    $objXMLSecDSig->add509Cert($cert);

    // XSD saml-schema-assertion-2.0 : la séquence AssertionType est
    // Issuer, Signature?, Subject?, Conditions?... — la Signature DOIT être
    // insérée juste APRÈS le noeud Issuer (jamais firstChild, qui peut être
    // un noeud texte d'indentation précédant Issuer).
    $issuerNode = $assertionNode->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:assertion', 'Issuer')->item(0);
    $objXMLSecDSig->insertSignature($assertionNode, $issuerNode->nextSibling);

    return $document->saveXML();
}

function samlAcsPost(string $responseXml, string $relayState): \Illuminate\Testing\TestResponse
{
    $samlResponse = base64_encode($responseXml);

    // Le toolkit onelogin/php-saml lit $_POST['SAMLResponse'] et
    // $_SERVER['HTTP_HOST']/['REQUEST_URI']/['HTTPS'] DIRECTEMENT (superglobals
    // PHP bruts, pas le Request Laravel/Symfony) — voir
    // OneLogin\Saml2\Auth::processResponse() + Utils::getSelfURL(). Le
    // TestCase::call() de Laravel ne peuple ni l'un ni l'autre automatiquement
    // en cohérence avec route(), donc on aligne les deux explicitement sur
    // l'URL ACS réelle (sinon le toolkit compare la Destination XML à l'hôte
    // machine local et rejette systématiquement la réponse).
    $acsUrl = route('sso.saml.acs');
    $parts = parse_url($acsUrl);

    $_POST['SAMLResponse'] = $samlResponse;
    $_POST['RelayState'] = $relayState;
    $_SERVER['HTTPS'] = ($parts['scheme'] ?? 'https') === 'https' ? 'on' : 'off';
    $_SERVER['HTTP_HOST'] = $parts['host'] ?? 'localhost';
    $_SERVER['SERVER_NAME'] = $parts['host'] ?? 'localhost';
    $_SERVER['REQUEST_URI'] = $parts['path'] ?? '/';

    // withoutMiddleware(ThrottleRequests::class) SEULEMENT (throttle:60,1
    // gênerait des tests qui postent plusieurs fois) — la session DOIT
    // rester active (middleware "web"), sinon $request->session() plante.
    return test()->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class)->call('POST', $acsUrl, [
        'SAMLResponse' => $samlResponse,
        'RelayState' => $relayState,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Tests
// ─────────────────────────────────────────────────────────────────────────────

it('répond 404 sur toutes les routes SAML quand sso.enabled est désactivé (défaut)', function (): void {
    config(['sso.enabled' => false]);

    $this->get(route('sso.saml.metadata'))->assertNotFound();
    $this->get(route('sso.saml.login', ['org' => 'acme-corp']))->assertNotFound();
    $this->post(route('sso.saml.acs'), [])->assertNotFound();
});

it('rejette une assertion SAML EXPIRÉE (NotOnOrAfter dans le passé)', function (): void {
    $configuration = samlAcsConfiguration();
    $inResponseTo = 'req-'.bin2hex(random_bytes(8));

    session(['sso_saml_pending_org' => $configuration->organization_slug]);
    session(['sso_saml_last_request_id' => $inResponseTo]);

    $expiredResponse = samlAcsBuildSignedResponseXml(
        $configuration,
        $inResponseTo,
        'expired-user@acme-corp.example.com',
        new DateTimeImmutable('-1 hour'), // NotOnOrAfter déjà passé
    );

    $response = samlAcsPost($expiredResponse, $configuration->organization_slug);

    $response->assertRedirect(route('login'));
    expect(session('errors')?->has('sso'))->toBeTrue();
    expect(\App\Models\User::where('email', 'expired-user@acme-corp.example.com')->exists())->toBeFalse();
});

it('rejette une assertion SAML signée par une clé DIFFÉRENTE de celle de l\'IdP configuré', function (): void {
    $configuration = samlAcsConfiguration();
    $inResponseTo = 'req-'.bin2hex(random_bytes(8));

    session(['sso_saml_pending_org' => $configuration->organization_slug]);
    session(['sso_saml_last_request_id' => $inResponseTo]);

    // Génère un DEUXIÈME couple clé/certificat pour signer — mais la config
    // en base attend TOUJOURS le certificat original (SamlTestFixtures).
    $rogueKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($rogueKey, $roguePrivateKeyPem);

    $xml = samlAcsBuildSignedResponseXml($configuration, $inResponseTo, 'rogue-user@acme-corp.example.com', new DateTimeImmutable('+5 minutes'));

    // Resigne le document avec la clé "rogue" (signature valide en soi, mais
    // ne correspondant PAS au certificat IdP enregistré -> doit être rejetée).
    $document = new DOMDocument();
    $document->loadXML($xml);
    // On altère juste le NameID pour forcer une re-signature distincte,
    // suffisant pour prouver que la signature ne matche plus le certificat attendu.
    $response = samlAcsPost($xml === '' ? $xml : samlAcsResignWithRogueKey($xml, $roguePrivateKeyPem), $configuration->organization_slug);

    $response->assertRedirect(route('login'));
    expect(session('errors')?->has('sso'))->toBeTrue();
    expect(\App\Models\User::where('email', 'rogue-user@acme-corp.example.com')->exists())->toBeFalse();
});

function samlAcsResignWithRogueKey(string $xml, string $roguePrivateKeyPem): string
{
    $document = new DOMDocument();
    $document->loadXML($xml);

    $assertionNode = $document->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:assertion', 'Assertion')->item(0);

    // Retire la signature existante avant de resigner.
    $existingSignature = $assertionNode->getElementsByTagNameNS('http://www.w3.org/2000/09/xmldsig#', 'Signature')->item(0);
    if ($existingSignature) {
        $assertionNode->removeChild($existingSignature);
    }

    $privateKey = new \RobRichards\XMLSecLibs\XMLSecurityKey(\RobRichards\XMLSecLibs\XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
    $privateKey->loadKey($roguePrivateKeyPem);

    $objXMLSecDSig = new \RobRichards\XMLSecLibs\XMLSecurityDSig();
    $objXMLSecDSig->setCanonicalMethod(\RobRichards\XMLSecLibs\XMLSecurityDSig::EXC_C14N);
    $objXMLSecDSig->addReferenceList([$assertionNode], \RobRichards\XMLSecLibs\XMLSecurityDSig::SHA256, [
        'http://www.w3.org/2000/09/xmldsig#enveloped-signature',
        \RobRichards\XMLSecLibs\XMLSecurityDSig::EXC_C14N,
    ], ['id_name' => 'ID']);
    $objXMLSecDSig->sign($privateKey);

    // Certificat "rogue" auto-généré volatile (pas celui de SamlTestFixtures)
    // -> ne correspondra jamais au idp_x509_cert enregistré en base.
    $rogueKeyResource = openssl_pkey_new(['private_key_bits' => 2048]);
    $csr = openssl_csr_new(['commonName' => 'rogue.example.com'], $rogueKeyResource);
    $rogueCert = openssl_csr_sign($csr, null, $rogueKeyResource, 365);
    openssl_x509_export($rogueCert, $rogueCertPem);

    $objXMLSecDSig->add509Cert($rogueCertPem);

    // Même règle XSD que samlAcsSignAssertion() : Signature APRÈS Issuer.
    $issuerNode = $assertionNode->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:assertion', 'Issuer')->item(0);
    $objXMLSecDSig->insertSignature($assertionNode, $issuerNode->nextSibling);

    return $document->saveXML();
}

it('rejette le REJEU de la même assertion (même InResponseTo consommé deux fois)', function (): void {
    $configuration = samlAcsConfiguration();
    $inResponseTo = 'req-'.bin2hex(random_bytes(8));

    session(['sso_saml_pending_org' => $configuration->organization_slug]);
    session(['sso_saml_last_request_id' => $inResponseTo]);

    $validResponse = samlAcsBuildSignedResponseXml(
        $configuration,
        $inResponseTo,
        'replay-user@acme-corp.example.com',
        new DateTimeImmutable('+5 minutes'),
    );

    // 1re présentation : acceptée, utilisateur créé + connecté.
    $first = samlAcsPost($validResponse, $configuration->organization_slug);
    $first->assertRedirect(route('user.dashboard'));
    expect(\App\Models\User::where('email', 'replay-user@acme-corp.example.com')->exists())->toBeTrue();

    \Illuminate\Support\Facades\Auth::logout();
    session(['sso_saml_pending_org' => $configuration->organization_slug]);
    session(['sso_saml_last_request_id' => $inResponseTo]);

    // 2e présentation de la MÊME assertion (rejeu) : REJETÉE.
    $second = samlAcsPost($validResponse, $configuration->organization_slug);
    $second->assertRedirect(route('login'));
    expect(session('errors')?->has('sso'))->toBeTrue();
});

it('accepte une assertion SAML valide et connecte l\'utilisateur (cas positif)', function (): void {
    $configuration = samlAcsConfiguration();
    $inResponseTo = 'req-'.bin2hex(random_bytes(8));

    session(['sso_saml_pending_org' => $configuration->organization_slug]);
    session(['sso_saml_last_request_id' => $inResponseTo]);

    $validResponse = samlAcsBuildSignedResponseXml(
        $configuration,
        $inResponseTo,
        'valid-user@acme-corp.example.com',
        new DateTimeImmutable('+5 minutes'),
    );

    $response = samlAcsPost($validResponse, $configuration->organization_slug);

    $response->assertRedirect(route('user.dashboard'));
    $this->assertAuthenticated();
    expect(\App\Models\User::where('email', 'valid-user@acme-corp.example.com')->exists())->toBeTrue();
});
