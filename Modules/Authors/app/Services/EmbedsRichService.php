<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

use InvalidArgumentException;
use Throwable;

final class EmbedsRichService
{
    public function parseSlashCommand(string $content): array
    {
        $content = trim($content);

        if (! str_starts_with($content, '/')) {
            return ['type' => 'text', 'config' => [], 'html' => htmlspecialchars($content, ENT_QUOTES, 'UTF-8')];
        }

        $parts = explode(' ', $content, 2);
        $command = ltrim($parts[0], '/');
        $payload = $parts[1] ?? '';

        try {
            return match ($command) {
                'poll' => $this->handlePoll($payload),
                'mermaid' => ['type' => 'mermaid', 'config' => [], 'html' => $this->renderMermaid($payload)],
                'code' => $this->handleCode($payload),
                'youtube' => ['type' => 'youtube', 'config' => [], 'html' => $this->renderYoutube($payload)],
                'spotify' => ['type' => 'spotify', 'config' => [], 'html' => $this->renderSpotify($payload)],
                'twitter' => ['type' => 'twitter', 'config' => [], 'html' => $this->renderTwitter($payload)],
                'bluesky' => ['type' => 'bluesky', 'config' => [], 'html' => $this->renderBluesky($payload)],
                'figma' => ['type' => 'figma', 'config' => [], 'html' => $this->renderFigma($payload)],
                'codepen' => ['type' => 'codepen', 'config' => [], 'html' => $this->renderCodepen($payload)],
                'github' => ['type' => 'github', 'config' => [], 'html' => $this->renderGithub($payload)],
                'instagram' => ['type' => 'instagram', 'config' => [], 'html' => $this->renderInstagram($payload)],
                'tiktok' => ['type' => 'tiktok', 'config' => [], 'html' => $this->renderTiktok($payload)],
                default => ['type' => 'text', 'config' => [], 'html' => htmlspecialchars($content, ENT_QUOTES, 'UTF-8')],
            };
        } catch (Throwable $e) {
            return ['type' => 'error', 'config' => [], 'html' => '<div class="embed-error">Embed invalide.</div>'];
        }
    }

    public function getAllCommands(): array
    {
        return [
            'poll' => '/poll Question?Option1|Option2|Option3',
            'mermaid' => '/mermaid graph TD; A-->B;',
            'code' => '/code <?php echo "Hello"; ?>|php',
            'youtube' => '/youtube https://youtu.be/ID',
            'spotify' => '/spotify https://open.spotify.com/track/ID',
            'twitter' => '/twitter https://twitter.com/user/status/ID',
            'bluesky' => '/bluesky https://bsky.app/profile/user/post/ID',
            'figma' => '/figma https://www.figma.com/file/ID/...',
            'codepen' => '/codepen https://codepen.io/user/pen/ID',
            'github' => '/github https://gist.github.com/user/GIST_ID',
            'instagram' => '/instagram https://www.instagram.com/p/ID/',
            'tiktok' => '/tiktok https://www.tiktok.com/@user/video/ID',
        ];
    }

    private function handlePoll(string $payload): array
    {
        if (! str_contains($payload, '?')) {
            throw new InvalidArgumentException('Invalid poll format');
        }

        [$question, $optionsRaw] = explode('?', $payload, 2);
        $options = array_values(array_filter(array_map('trim', explode('|', $optionsRaw))));

        if (count($options) < 2) {
            throw new InvalidArgumentException('Poll requires at least 2 options');
        }

        return [
            'type' => 'poll',
            'config' => ['question' => trim($question), 'options' => $options],
            'html' => $this->renderPoll(['question' => trim($question), 'options' => $options]),
        ];
    }

    private function handleCode(string $payload): array
    {
        $parts = explode('|', $payload, 2);
        $code = $parts[0] ?? '';
        $lang = $parts[1] ?? 'php';

        return ['type' => 'code', 'config' => [], 'html' => $this->renderCode($code, $lang)];
    }

    public function renderPoll(array $config): string
    {
        $question = htmlspecialchars((string) $config['question'], ENT_QUOTES, 'UTF-8');
        $options = json_encode($config['options'], JSON_THROW_ON_ERROR);

        return <<<HTML
<div x-data='{ q: {$options}, selected: null, voted: false }' class="poll-embed bg-white p-4 rounded shadow border border-gray-200 my-4">
    <h3 class="font-bold mb-3">{$question}</h3>
    <template x-for="(opt, i) in q" :key="i">
        <button type="button" class="block w-full text-left mb-2 p-2 border rounded hover:bg-gray-50" @click="selected = i; voted = true" x-text="opt"></button>
    </template>
    <p x-show="voted" class="text-sm text-green-600 mt-2">Merci pour ton vote (local seulement)</p>
</div>
HTML;
    }

    public function renderMermaid(string $diagram): string
    {
        return '<pre class="mermaid">'.htmlspecialchars($diagram, ENT_QUOTES, 'UTF-8').'</pre>';
    }

    public function renderCode(string $code, string $lang = 'php'): string
    {
        return '<pre><code class="language-'.htmlspecialchars($lang, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($code, ENT_QUOTES, 'UTF-8').'</code></pre>';
    }

    public function renderYoutube(string $url): string
    {
        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $m)) {
            $id = $m[1];
            return "<iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/{$id}\" frameborder=\"0\" allow=\"accelerometer; encrypted-media; picture-in-picture\" allowfullscreen sandbox=\"allow-scripts allow-same-origin allow-popups\" class=\"w-full\"></iframe>";
        }
        return '<div class="embed-error">URL YouTube invalide</div>';
    }

    public function renderSpotify(string $url): string
    {
        if (preg_match('%spotify\.com/(track|playlist|album)/([a-zA-Z0-9]+)%', $url, $m)) {
            $type = $m[1];
            $id = $m[2];
            $height = $type === 'track' ? '80' : '380';
            return "<iframe style=\"border-radius:12px\" src=\"https://open.spotify.com/embed/{$type}/{$id}\" width=\"100%\" height=\"{$height}\" frameBorder=\"0\" loading=\"lazy\" sandbox=\"allow-scripts allow-same-origin allow-popups\"></iframe>";
        }
        return '<div class="embed-error">URL Spotify invalide</div>';
    }

    public function renderTwitter(string $url): string
    {
        return '<blockquote class="twitter-tweet"><a href="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'"></a></blockquote><script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>';
    }

    public function renderBluesky(string $url): string
    {
        if (preg_match('%bsky\.app/profile/([^/]+)/post/([^/]+)%', $url, $m)) {
            return '<iframe src="https://embed.bsky.app/post/'.$m[1].'/'.$m[2].'" width="100%" height="400" frameborder="0" sandbox="allow-scripts allow-same-origin" loading="lazy"></iframe>';
        }
        return '<div class="embed-error">URL Bluesky invalide</div>';
    }

    public function renderFigma(string $url): string
    {
        if (! str_contains($url, 'figma.com')) {
            return '<div class="embed-error">URL Figma invalide</div>';
        }
        return '<iframe src="https://www.figma.com/embed?embed_host=laveille&url='.urlencode($url).'" width="100%" height="500" frameborder="0" sandbox="allow-scripts allow-same-origin" loading="lazy"></iframe>';
    }

    public function renderCodepen(string $url): string
    {
        if (preg_match('%codepen\.io/([^/]+)/pen/([^/]+)%', $url, $m)) {
            return "<iframe height=\"300\" style=\"width: 100%;\" src=\"https://codepen.io/{$m[1]}/embed/{$m[2]}\" frameborder=\"no\" loading=\"lazy\" sandbox=\"allow-scripts allow-same-origin allow-popups\"></iframe>";
        }
        return '<div class="embed-error">URL CodePen invalide</div>';
    }

    public function renderGithub(string $url): string
    {
        if (preg_match('%gist\.github\.com/([^/]+)/([a-f0-9]+)%', $url, $m)) {
            return '<script src="https://gist.github.com/'.$m[1].'/'.$m[2].'.js"></script>';
        }
        return '<div class="embed-error">URL Gist invalide</div>';
    }

    public function renderInstagram(string $url): string
    {
        return '<blockquote class="instagram-media" data-instgrm-permalink="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'"><a href="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'"></a></blockquote><script async src="//www.instagram.com/embed.js"></script>';
    }

    public function renderTiktok(string $url): string
    {
        return '<blockquote class="tiktok-embed" cite="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'"><a href="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'">TikTok</a></blockquote><script async src="https://www.tiktok.com/embed.js"></script>';
    }
}
