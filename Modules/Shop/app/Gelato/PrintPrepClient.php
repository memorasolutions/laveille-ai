<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Shop\Gelato;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

/**
 * Client du moteur « print-prep » (projet mcp_gelato).
 * HTTP local en priorité (/v1/prepare), repli CLI (JSON stdin -> JSON stdout)
 * seulement si le service HTTP est injoignable ou non configuré.
 * Le secret vient toujours de la configuration (env), jamais du code.
 */
class PrintPrepClient
{
    /** @param array{url?:?string,secret?:?string,cli_path?:?string,node_binary?:string,timeout?:int,connect_timeout?:int}|null $config */
    public function __construct(private ?array $config = null)
    {
        $this->config ??= (array) config('shop.print_prep', []);
    }

    /**
     * @param array<string,mixed> $payload artworkUrl|artworkBase64, printArea, productUid, overrideDimensionsMm...
     * @return array<string,mixed> réponse "prepared" du moteur
     * @throws PrintPrepException
     */
    public function prepare(array $payload): array
    {
        return $this->call('POST', '/v1/prepare', $payload);
    }

    /** @return array<string,mixed> @throws PrintPrepException */
    public function validate(array $payload): array
    {
        return $this->call('POST', '/v1/validate', $payload);
    }

    /**
     * Téléverse une image brute vers le moteur (POST /v1/assets, HTTP seulement, pas de repli CLI).
     * Le moteur vérifie le type réel, le plafond de pixels et refuse le SVG.
     *
     * @return array{assetHash:string,format:string,width:int,height:int}
     * @throws PrintPrepException
     */
    public function uploadAsset(string $binary, string $mime = 'application/octet-stream'): array
    {
        if ($this->baseUrl() === '') {
            throw new PrintPrepException(PrintPrepException::NOT_CONFIGURED, 'Moteur print-prep non configuré (GELATO_PREP_URL).');
        }

        try {
            $response = $this->http($this->secret())->withBody($binary, $mime)->post($this->baseUrl().'/v1/assets');
        } catch (ConnectionException $e) {
            throw new PrintPrepException(PrintPrepException::UNREACHABLE, 'print-prep injoignable : '.$e->getMessage());
        }

        return $this->interpret($response);
    }

    /**
     * Rend une spec déclarative côté moteur (POST /v1/render-spec). Le serveur ne fait jamais confiance au pixel client.
     *
     * @param array<string,mixed> $spec
     * @return array<string,mixed> status, contentHash, publicUrl, specHash, validation, mockupPreviewPath, elements
     * @throws PrintPrepException refus 422 (HORS_ZONE_SECURITE, ...) ou panne
     */
    public function renderSpec(array $spec): array
    {
        return $this->call('POST', '/v1/render-spec', $spec);
    }

    /** @return array<string,mixed> @throws PrintPrepException */
    public function health(): array
    {
        return $this->call('GET', '/v1/health');
    }

    private function call(string $method, string $path, array $payload = []): array
    {
        if ($this->baseUrl() !== '') {
            try {
                return $this->viaHttp($method, $path, $payload);
            } catch (PrintPrepException $e) {
                // Repli CLI uniquement sur panne de transport, jamais sur un refus du moteur.
                if ($e->errorCode !== PrintPrepException::UNREACHABLE || ! $this->cliAvailable($path)) {
                    throw $e;
                }
            }
        }

        if ($this->cliAvailable($path)) {
            return $this->viaCli($payload);
        }

        throw new PrintPrepException(PrintPrepException::NOT_CONFIGURED, 'Moteur print-prep non configuré (GELATO_PREP_URL / GELATO_PREP_CLI).');
    }

    private function viaHttp(string $method, string $path, array $payload): array
    {
        $secret = $this->secret();

        try {
            $response = $this->http($secret)->send($method, $this->baseUrl().$path, $method === 'GET' ? [] : ['json' => $payload]);
        } catch (ConnectionException $e) {
            throw new PrintPrepException(PrintPrepException::UNREACHABLE, 'print-prep injoignable : '.$e->getMessage());
        }

        return $this->interpret($response);
    }

    private function secret(): string
    {
        $secret = (string) ($this->config['secret'] ?? '');
        if ($secret === '') {
            throw new PrintPrepException(PrintPrepException::NOT_CONFIGURED, 'GELATO_PREP_SECRET absent.');
        }

        return $secret;
    }

    private function http(string $secret): PendingRequest
    {
        return Http::withHeaders(['X-Gelato-Prep-Secret' => $secret])
            ->acceptJson()
            ->timeout((int) ($this->config['timeout'] ?? 60))
            ->connectTimeout((int) ($this->config['connect_timeout'] ?? 5));
    }

    private function interpret(Response $response): array
    {
        $body = $response->json();
        $body = is_array($body) ? $body : [];

        if ($response->successful()) {
            return $body;
        }

        $code = match ($response->status()) {
            401 => PrintPrepException::UNAUTHORIZED,
            503 => PrintPrepException::NOT_CONFIGURED,
            default => (string) ($body['code'] ?? $body['error']['code'] ?? ($response->status() === 422 ? PrintPrepException::NON_CONFORME : PrintPrepException::INVALID_RESPONSE)),
        };

        throw new PrintPrepException(
            $code,
            (string) ($body['message'] ?? $body['error']['message'] ?? "print-prep HTTP {$response->status()}"),
            (array) ($body['details'] ?? $body['error']['details'] ?? []),
        );
    }

    /** Le CLI ne couvre que /prepare (contrat). */
    private function cliAvailable(string $path): bool
    {
        return $path === '/v1/prepare' && ! empty($this->config['cli_path']);
    }

    private function viaCli(array $payload): array
    {
        $process = new Process(
            [(string) ($this->config['node_binary'] ?? 'node'), (string) $this->config['cli_path']],
            null,
            null,
            json_encode($payload, JSON_UNESCAPED_SLASHES),
            (float) ($this->config['timeout'] ?? 60),
        );
        $process->run();

        $out = json_decode($process->getOutput(), true);
        if (! is_array($out)) {
            throw new PrintPrepException(PrintPrepException::INVALID_RESPONSE, 'Sortie CLI print-prep illisible : '.mb_substr($process->getErrorOutput(), 0, 300));
        }

        if ($process->getExitCode() === 0) {
            return $out;
        }

        throw new PrintPrepException(
            (string) ($out['code'] ?? $out['error']['code'] ?? PrintPrepException::NON_CONFORME),
            (string) ($out['message'] ?? $out['error']['message'] ?? 'Refus print-prep (CLI).'),
            (array) ($out['details'] ?? $out['error']['details'] ?? []),
        );
    }

    private function baseUrl(): string
    {
        return rtrim((string) ($this->config['url'] ?? ''), '/');
    }
}
