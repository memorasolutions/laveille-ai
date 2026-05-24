<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

final class GoogleDriveEmbedService
{
    public function parse(string $url): ?array
    {
        try {
            if (! $this->isValidGoogleUrl($url)) {
                return null;
            }

            $id = $this->extractIdFromUrl($url);
            if ($id === null) {
                return null;
            }

            $host = parse_url($url, PHP_URL_HOST);
            if ($host === false || $host === null) {
                return null;
            }

            $type = $this->determineType((string) $host, $url);
            if ($type === null) {
                return null;
            }

            $embedUrl = $this->buildEmbedUrl($type, $id);
            if ($embedUrl === null) {
                return null;
            }

            return [
                'type' => $type,
                'id' => $id,
                'embed_url' => $embedUrl,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function renderIframe(string $url, int $height = 480): string
    {
        try {
            $parsed = $this->parse($url);
            if ($parsed === null) {
                return $this->getFallbackHtml($url);
            }

            $embedUrl = htmlspecialchars($parsed['embed_url'], ENT_QUOTES, 'UTF-8');
            $title = match ($parsed['type']) {
                'doc' => 'Google Document',
                'sheet' => 'Google Spreadsheet',
                'slide' => 'Google Slides',
                'drive' => 'Google Drive file',
                default => 'Google embed',
            };

            return sprintf(
                '<iframe src="%s" width="100%%" height="%d" frameborder="0" sandbox="allow-scripts allow-same-origin allow-popups" loading="lazy" title="%s"></iframe>',
                $embedUrl,
                $height,
                htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
            );
        } catch (\Throwable $e) {
            return $this->getFallbackHtml($url);
        }
    }

    public function isValidGoogleUrl(string $url): bool
    {
        try {
            $host = parse_url($url, PHP_URL_HOST);
            if ($host === false || $host === null) {
                return false;
            }

            $allowedHosts = [
                'docs.google.com',
                'drive.google.com',
                'sheets.google.com',
                'slides.google.com',
            ];

            return in_array(strtolower((string) $host), $allowedHosts, true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function extractIdFromUrl(string $url): ?string
    {
        try {
            $pattern = '/[\/-]([a-zA-Z0-9_-]{25,100})(?:[\/?#]|$)/';
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getFallbackHtml(string $url): string
    {
        try {
            $escapedUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            return sprintf(
                '<div class="google-embed-fallback"><p>Contenu Google non intégrable. <a href="%s" target="_blank" rel="noopener noreferrer">Voir le document</a>.</p></div>',
                $escapedUrl
            );
        } catch (\Throwable $e) {
            return '<div class="google-embed-fallback"><p>Contenu indisponible.</p></div>';
        }
    }

    private function determineType(string $host, string $url): ?string
    {
        $host = strtolower($host);
        if ($host === 'drive.google.com') {
            return 'drive';
        }
        if (str_contains($url, '/document/')) {
            return 'doc';
        }
        if (str_contains($url, '/spreadsheets/')) {
            return 'sheet';
        }
        if (str_contains($url, '/presentation/')) {
            return 'slide';
        }
        return match ($host) {
            'docs.google.com' => 'doc',
            'sheets.google.com' => 'sheet',
            'slides.google.com' => 'slide',
            default => null,
        };
    }

    private function buildEmbedUrl(string $type, string $id): ?string
    {
        $id = rawurlencode($id);
        return match ($type) {
            'doc' => "https://docs.google.com/document/d/{$id}/preview",
            'sheet' => "https://docs.google.com/spreadsheets/d/{$id}/preview",
            'slide' => "https://docs.google.com/presentation/d/{$id}/preview",
            'drive' => "https://drive.google.com/file/d/{$id}/preview",
            default => null,
        };
    }
}
