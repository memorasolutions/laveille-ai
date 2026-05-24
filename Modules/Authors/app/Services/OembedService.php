<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

final class OembedService
{
    private const PATTERNS = [
        'youtube' => '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([\w-]+)/i',
        'twitter' => '/(?:twitter|x)\.com\/(\w+)\/status\/(\d+)/i',
        'spotify' => '/open\.spotify\.com\/(track|episode|album|playlist)\/([\w]+)/i',
        'loom' => '/loom\.com\/share\/([\w-]+)/i',
        'vimeo' => '/vimeo\.com\/(\d+)/i',
        'codepen' => '/codepen\.io\/([\w-]+)\/pen\/(\w+)/i',
    ];

    public function detect(string $url): ?array
    {
        foreach (self::PATTERNS as $provider => $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return match ($provider) {
                    'youtube' => ['provider' => 'youtube', 'id' => $matches[1], 'url' => $url],
                    'twitter' => ['provider' => 'twitter', 'id' => $matches[2], 'user' => $matches[1], 'url' => $url],
                    'spotify' => ['provider' => 'spotify', 'id' => $matches[2], 'type' => $matches[1], 'url' => $url],
                    'loom' => ['provider' => 'loom', 'id' => $matches[1], 'url' => $url],
                    'vimeo' => ['provider' => 'vimeo', 'id' => $matches[1], 'url' => $url],
                    'codepen' => ['provider' => 'codepen', 'id' => $matches[2], 'user' => $matches[1], 'url' => $url],
                    default => null,
                };
            }
        }

        return null;
    }

    public function toEmbedHtml(string $url): ?string
    {
        $match = $this->detect($url);
        if ($match === null) {
            return null;
        }

        $id = $match['id'];
        $provider = $match['provider'];

        return match ($provider) {
            'youtube' => '<div class="lv-embed lv-embed--youtube"><iframe src="https://www.youtube-nocookie.com/embed/'.e($id).'" loading="lazy" allowfullscreen title="YouTube" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"></iframe></div>',
            'twitter' => '<blockquote class="twitter-tweet lv-embed lv-embed--twitter"><a href="'.e($url).'" rel="noopener" target="_blank">Voir le post sur X</a></blockquote>',
            'spotify' => '<iframe class="lv-embed lv-embed--spotify" src="https://open.spotify.com/embed/'.e($match['type']).'/'.e($id).'?utm_source=oembed" loading="lazy" width="100%" height="232" frameborder="0" allow="autoplay; clipboard-write; encrypted-media"></iframe>',
            'loom' => '<div class="lv-embed lv-embed--loom"><iframe src="https://www.loom.com/embed/'.e($id).'" loading="lazy" allowfullscreen webkitallowfullscreen mozallowfullscreen></iframe></div>',
            'vimeo' => '<div class="lv-embed lv-embed--vimeo"><iframe src="https://player.vimeo.com/video/'.e($id).'" loading="lazy" allowfullscreen></iframe></div>',
            'codepen' => '<iframe class="lv-embed lv-embed--codepen" src="https://codepen.io/'.e($match['user']).'/embed/'.e($id).'?height=300&theme-id=light" loading="lazy" allowfullscreen></iframe>',
            default => null,
        };
    }

    public function transformMarkdown(string $markdown): string
    {
        return (string) preg_replace_callback(
            '/^[ \t]*(https?:\/\/[^\s]+)\s*$/im',
            fn ($m) => $this->toEmbedHtml(trim($m[1])) ?? $m[0],
            $markdown
        );
    }
}
