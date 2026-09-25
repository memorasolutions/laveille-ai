<?php

declare(strict_types=1);

namespace Modules\Signature\Services;

use Modules\Signature\Models\Signature;

/**
 * Rendu HTML canonique d'une signature - table-based, styles exclusivement en ligne, aucune
 * propriété CSS moderne (flex/grid/position), attributs width/height codés en dur sur chaque
 * image (section 9 et 12.3 du plan). Tous les champs texte sont échappés avant interpolation
 * (section 10 - assainissement du HTML produit).
 *
 * ÉCART ASSUMÉ AU PLAN (section 4) : le plan demande UNE fonction de rendu partagée entre
 * l'aperçu, la copie et le téléchargement - en pratique cette fonction unique est `renderSignature`
 * en JavaScript (public/assets/tools/signature-courriel/signature-render.js), seule exécutable
 * côté navigateur sans aller-retour serveur (contrainte « zéro latence » de la section 4). Cette
 * classe PHP est un SECOND moteur, volontairement tenu identique champ pour champ et gabarit pour
 * gabarit au moteur JS (même contrat de données, mêmes 4 gabarits, mêmes règles d'échappement et
 * de dimensionnement), nécessaire pour deux usages que le JS ne peut pas couvrir : la route serveur
 * `/export/outlook-classique/{token}` (disponibilité garantie même si le JS échoue) et les tests
 * Pest (rendu et assainissement) - Pest ne peut pas exécuter le moteur JS. Toute modification d'un
 * gabarit doit être répercutée dans les DEUX fichiers - signalé explicitement pour ne pas être lu
 * comme une violation silencieuse du DRY strict du projet.
 */
final class SignatureRenderer
{
    private const FONT_STACK = [
        'Arial' => "Arial, Helvetica, sans-serif",
        'Helvetica' => "Helvetica, Arial, sans-serif",
        'Verdana' => "Verdana, Geneva, sans-serif",
        'Georgia' => "Georgia, 'Times New Roman', serif",
        'Tahoma' => "Tahoma, Geneva, sans-serif",
    ];

    private const SOCIAL_ICONS = [
        'linkedin' => '💼',
        'facebook' => '📘',
        'instagram' => '📸',
        'x' => '✖️',
        'youtube' => '▶️',
        'website' => '🌐',
    ];

    private const SOCIAL_LABELS = [
        'linkedin' => 'LinkedIn',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'x' => 'X',
        'youtube' => 'YouTube',
        'website' => 'Site web',
    ];

    /**
     * @param  array<string, mixed>  $content  contrat de section 11.2 (JSON validé, jamais un
     *                                          schéma libre - voir SignatureContentValidator)
     * @param  array<string, array{url: string, width: int, height: int}>  $images  clé = rôle
     *                                          (logo|portrait|banniere), déjà résolu en URL publique
     */
    public static function render(array $content, string $template, array $images = []): string
    {
        $template = in_array($template, Signature::TEMPLATES, true) ? $template : 'minimal';
        $f = self::normalize($content);
        $accent = self::safeHexColor($f['accent_color'] ?? null) ?? '#064E5A';
        $fontStack = self::FONT_STACK[$f['font_family']] ?? self::FONT_STACK['Arial'];

        return match ($template) {
            'professionnel' => self::renderProfessionnel($f, $images, $accent, $fontStack),
            'portrait' => self::renderPortrait($f, $images, $accent, $fontStack),
            'compact' => self::renderCompact($f, $images, $accent, $fontStack),
            default => self::renderMinimal($f, $images, $accent, $fontStack),
        };
    }

    /**
     * Autorise seulement http:// et https:// (section 10) - jamais javascript: ni un autre
     * schéma actif. Une valeur vide ou absente est considérée sûre (champ facultatif).
     */
    public static function isSafeUrl(?string $url): bool
    {
        $url = trim((string) $url);
        if ($url === '') {
            return true;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return is_string($scheme) && in_array(strtolower($scheme), ['http', 'https'], true);
    }

    private static function safeHexColor(?string $hex): ?string
    {
        $hex = trim((string) $hex);

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $hex) === 1 ? $hex : null;
    }

    /** @return array<string, mixed> */
    private static function normalize(array $content): array
    {
        return [
            'first_name' => trim((string) ($content['first_name'] ?? '')),
            'last_name' => trim((string) ($content['last_name'] ?? '')),
            'job_title' => trim((string) ($content['job_title'] ?? '')),
            'organization' => trim((string) ($content['organization'] ?? '')),
            'email' => trim((string) ($content['email'] ?? '')),
            'phone' => trim((string) ($content['phone'] ?? '')),
            'mobile' => trim((string) ($content['mobile'] ?? '')),
            'website' => self::isSafeUrl($content['website'] ?? null) ? trim((string) ($content['website'] ?? '')) : '',
            'address' => trim((string) ($content['address'] ?? '')),
            'tagline' => trim((string) ($content['tagline'] ?? '')),
            'cta_text' => trim((string) ($content['cta_text'] ?? '')),
            'cta_url' => self::isSafeUrl($content['cta_url'] ?? null) ? trim((string) ($content['cta_url'] ?? '')) : '',
            'accent_color' => $content['accent_color'] ?? null,
            'font_family' => $content['font_family'] ?? 'Arial',
            'social_links' => is_array($content['social_links'] ?? null) ? $content['social_links'] : [],
            'mention_lines' => is_array($content['mention_lines'] ?? null) ? $content['mention_lines'] : [],
        ];
    }

    private static function e(mixed $value): string
    {
        return htmlspecialchars(trim((string) $value), ENT_QUOTES, 'UTF-8');
    }

    private static function fullName(array $f): string
    {
        return trim($f['first_name'].' '.$f['last_name']);
    }

    /**
     * <img> avec width/height codés en dur (jamais seulement en CSS - sinon Outlook de bureau
     * affiche l'image à sa taille native). N'affiche RIEN si les dimensions sont inconnues plutôt
     * que de violer cette règle.
     */
    private static function imgTag(?array $image, string $alt, string $extraStyle = ''): string
    {
        if (! $image || empty($image['url']) || empty($image['width']) || empty($image['height'])) {
            return '';
        }

        return sprintf(
            '<img src="%s" width="%d" height="%d" alt="%s" style="display:block;border:0;outline:none;text-decoration:none;%s">',
            self::e($image['url']),
            (int) $image['width'],
            (int) $image['height'],
            self::e($alt),
            $extraStyle
        );
    }

    private static function socialRow(array $links, string $fontStack): string
    {
        $links = array_values(array_filter($links, fn ($l) => is_array($l) && ! empty($l['url']) && self::isSafeUrl($l['url'])));

        if ($links === []) {
            return '';
        }

        $parts = [];
        foreach ($links as $link) {
            $platform = (string) ($link['platform'] ?? 'website');
            $icon = self::SOCIAL_ICONS[$platform] ?? '🔗';
            $label = self::SOCIAL_LABELS[$platform] ?? self::e($platform);
            $parts[] = sprintf(
                '<a href="%s" style="color:inherit;text-decoration:none;font-family:%s;font-size:12px;">%s %s</a>',
                self::e($link['url']),
                $fontStack,
                $icon,
                $label
            );
        }

        return '<tr><td style="padding-top:6px;">'.implode(
            ' &nbsp;·&nbsp; ',
            $parts
        ).'</td></tr>';
    }

    private static function mentionRows(array $lines, string $fontStack): string
    {
        $lines = array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== ''));
        if ($lines === []) {
            return '';
        }

        $out = '';
        foreach (array_slice($lines, 0, 6) as $line) {
            // M4.5 : #374151 (déjà utilisé par contactRows/titleRow ci-dessous) plutôt que
            // #6b7280 - à 10px, #6b7280 tombe sous le ratio 7:1 exigé par WCAG 2.2 AAA sur fond
            // blanc.
            $out .= sprintf(
                '<tr><td style="font-family:%s;font-size:10px;color:#374151;padding-top:2px;">%s</td></tr>',
                $fontStack,
                self::e($line)
            );
        }

        return $out;
    }

    private static function ctaRow(array $f, string $accent, string $fontStack): string
    {
        if ($f['cta_text'] === '' || $f['cta_url'] === '') {
            return '';
        }

        return sprintf(
            '<tr><td style="padding-top:8px;"><a href="%s" style="display:inline-block;padding:8px 14px;background-color:%s;color:#ffffff;font-family:%s;font-size:12px;font-weight:bold;text-decoration:none;border-radius:4px;">%s</a></td></tr>',
            self::e($f['cta_url']),
            self::e($accent),
            $fontStack,
            self::e($f['cta_text'])
        );
    }

    private static function contactRows(array $f, string $fontStack): string
    {
        $rows = '';
        $line = function (string $text) use (&$rows, $fontStack): void {
            if ($text === '') {
                return;
            }
            $rows .= sprintf('<tr><td style="font-family:%s;font-size:12px;color:#374151;padding-top:2px;">%s</td></tr>', $fontStack, $text);
        };

        if ($f['phone'] !== '') {
            $line('☎ '.self::e($f['phone']));
        }
        if ($f['mobile'] !== '') {
            $line('📱 '.self::e($f['mobile']));
        }
        if ($f['email'] !== '') {
            $line('✉ <a href="mailto:'.self::e($f['email']).'" style="color:inherit;text-decoration:none;">'.self::e($f['email']).'</a>');
        }
        if ($f['website'] !== '') {
            $line('🔗 <a href="'.self::e($f['website']).'" style="color:inherit;text-decoration:none;">'.self::e($f['website']).'</a>');
        }
        if ($f['address'] !== '') {
            $line('📍 '.self::e($f['address']));
        }

        return $rows;
    }

    private static function nameRow(array $f, string $accent, string $fontStack, string $size = '16px'): string
    {
        $name = self::fullName($f);

        return sprintf(
            '<tr><td style="font-family:%s;font-size:%s;font-weight:bold;color:%s;padding-bottom:1px;">%s</td></tr>',
            $fontStack,
            $size,
            self::e($accent),
            self::e($name)
        );
    }

    private static function titleRow(array $f, string $fontStack): string
    {
        $bits = array_filter([$f['job_title'], $f['organization']]);
        if ($bits === []) {
            return '';
        }

        return sprintf(
            '<tr><td style="font-family:%s;font-size:13px;color:#374151;padding-bottom:4px;">%s</td></tr>',
            $fontStack,
            // Règle 10 : jamais de tiret cadratin - un simple tiret entouré d'espaces à la place.
            self::e(implode(' - ', $bits))
        );
    }

    private static function taglineRow(array $f, string $fontStack): string
    {
        if ($f['tagline'] === '') {
            return '';
        }

        // M4.5 : même correctif de contraste que mentionRows() ci-dessus (#374151, jamais #6b7280).
        return sprintf(
            '<tr><td style="font-family:%s;font-size:11px;font-style:italic;color:#374151;padding-top:4px;">%s</td></tr>',
            $fontStack,
            self::e($f['tagline'])
        );
    }

    private static function wrap(string $inner): string
    {
        return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;border-collapse:collapse;"><tr><td>'.$inner.'</td></tr></table>';
    }

    private static function textColumn(array $f, array $images, string $accent, string $fontStack, string $nameSize = '16px'): string
    {
        $html = '<table role="presentation" cellpadding="0" cellspacing="0" border="0">';
        $html .= self::nameRow($f, $accent, $fontStack, $nameSize);
        $html .= self::titleRow($f, $fontStack);
        $html .= self::contactRows($f, $fontStack);
        $html .= self::taglineRow($f, $fontStack);
        $html .= self::ctaRow($f, $accent, $fontStack);
        $html .= self::socialRow($f['social_links'], $fontStack);
        $html .= self::mentionRows($f['mention_lines'], $fontStack);
        $html .= '</table>';

        return $html;
    }

    private static function renderMinimal(array $f, array $images, string $accent, string $fontStack): string
    {
        $logo = self::imgTag($images['logo'] ?? null, 'Logo '.self::fullName($f));
        $inner = '<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>';
        if ($logo !== '') {
            $inner .= '<td style="padding-right:10px;vertical-align:middle;">'.$logo.'</td>';
        }
        $inner .= '<td style="vertical-align:middle;border-left:'.($logo !== '' ? '2px solid '.self::e($accent).';padding-left:10px;' : '0;').'">';
        $inner .= self::textColumn($f, $images, $accent, $fontStack, '15px');
        $inner .= '</td></tr></table>';

        return self::wrap($inner);
    }

    private static function renderProfessionnel(array $f, array $images, string $accent, string $fontStack): string
    {
        $logo = self::imgTag($images['logo'] ?? null, 'Logo '.$f['organization']);
        $inner = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-top:3px solid '.self::e($accent).';"><tr>';
        $inner .= '<td style="padding-top:10px;vertical-align:top;">';
        $inner .= self::textColumn($f, $images, $accent, $fontStack, '17px');
        $inner .= '</td>';
        if ($logo !== '') {
            $inner .= '<td style="padding-top:10px;padding-left:16px;vertical-align:top;">'.$logo.'</td>';
        }
        $inner .= '</tr></table>';

        return self::wrap($inner);
    }

    private static function renderPortrait(array $f, array $images, string $accent, string $fontStack): string
    {
        $portrait = self::imgTag($images['portrait'] ?? null, 'Photo de '.self::fullName($f), 'border-radius:6px;');
        $inner = '<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>';
        if ($portrait !== '') {
            $inner .= '<td style="padding-right:14px;vertical-align:top;">'.$portrait.'</td>';
        }
        $inner .= '<td style="vertical-align:top;">';
        $inner .= self::textColumn($f, $images, $accent, $fontStack, '16px');
        $inner .= '</td></tr></table>';

        return self::wrap($inner);
    }

    private static function renderCompact(array $f, array $images, string $accent, string $fontStack): string
    {
        $logo = self::imgTag($images['logo'] ?? null, 'Logo '.$f['organization'], 'max-width:80px;');
        $name = self::fullName($f);
        $bits = array_filter([$f['job_title'], $f['organization']]);
        // Règle 10 : jamais de tiret cadratin.
        $header = self::e(trim($name.($bits !== [] ? ' - '.implode(' - ', $bits) : '')));

        $inner = '<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>';
        if ($logo !== '') {
            $inner .= '<td style="padding-right:8px;vertical-align:middle;">'.$logo.'</td>';
        }
        $inner .= '<td style="vertical-align:middle;">';
        $inner .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0">';
        $inner .= sprintf('<tr><td style="font-family:%s;font-size:13px;font-weight:bold;color:%s;">%s</td></tr>', $fontStack, self::e($accent), $header);
        $contact = array_filter([
            $f['phone'] !== '' ? '☎ '.self::e($f['phone']) : '',
            $f['email'] !== '' ? '✉ <a href="mailto:'.self::e($f['email']).'" style="color:inherit;text-decoration:none;">'.self::e($f['email']).'</a>' : '',
            $f['website'] !== '' ? '🔗 <a href="'.self::e($f['website']).'" style="color:inherit;text-decoration:none;">'.self::e($f['website']).'</a>' : '',
        ]);
        if ($contact !== []) {
            $inner .= sprintf('<tr><td style="font-family:%s;font-size:11px;color:#374151;">%s</td></tr>', $fontStack, implode(' &nbsp;|&nbsp; ', $contact));
        }
        $inner .= self::socialRow($f['social_links'], $fontStack);
        $inner .= '</table>';
        $inner .= '</td></tr></table>';

        return self::wrap($inner);
    }
}
