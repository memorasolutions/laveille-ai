<?php

declare(strict_types=1);

namespace Modules\AI\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class YouTubeService
{
    public function extractTranscript(string $url, string $lang = 'fr'): ?array
    {
        $videoId = self::getVideoId($url);
        if (! $videoId) {
            Log::warning('YouTubeService: URL invalide', ['url' => $url]);

            return null;
        }

        return Cache::remember("yt_transcript_{$videoId}", 86400, function () use ($url, $lang, $videoId) {
            if (! self::isAvailable()) {
                Log::warning('YouTubeService: Node.js ou script introuvable');

                return null;
            }

            try {
                $result = Process::timeout(90)->run([
                    self::getNodePath(),
                    base_path('scripts/extract-transcript.mjs'),
                    $url,
                    $lang,
                ]);

                $json = json_decode(trim($result->output()), true);

                if (! is_array($json) || empty($json['success'])) {
                    Log::warning('YouTubeService: extraction echouee', [
                        'video_id' => $videoId,
                        'error' => $json['error'] ?? 'reponse invalide',
                    ]);

                    return null;
                }

                return [
                    'video_id' => $json['video_id'],
                    'transcript' => $json['transcript'],
                    'segments' => $json['segments'] ?? [],
                ];
            } catch (\Throwable $e) {
                Log::warning('YouTubeService: erreur extraction', ['exception' => $e->getMessage()]);

                return null;
            }
        });
    }

    public function summarize(string $transcript, ?string $videoId = null): ?string
    {
        $cacheKey = 'yt_summary_'.($videoId ?? md5($transcript));

        return Cache::remember($cacheKey, 86400, function () use ($transcript) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.env('OPENROUTER_API_KEY'),
                    'HTTP-Referer' => config('app.url'),
                ])->timeout(120)->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => 'deepseek/deepseek-chat',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Resume cette transcription YouTube en francais. Format : un titre, 5-8 points cles en puces, une conclusion. Sois concis et informatif.',
                        ],
                        [
                            'role' => 'user',
                            'content' => mb_substr($transcript, 0, 15000),
                        ],
                    ],
                ]);

                if ($response->failed()) {
                    Log::warning('YouTubeService: API OpenRouter echouee', ['status' => $response->status()]);

                    return null;
                }

                return $response->json('choices.0.message.content');
            } catch (\Throwable $e) {
                Log::warning('YouTubeService: erreur resume', ['exception' => $e->getMessage()]);

                return null;
            }
        });
    }

    public function summarizeFromMeta(string $videoTitle, string $channelName, string $toolName, string $toolDescription, string $videoDescription = ''): ?string
    {
        if (empty($videoTitle)) {
            return null;
        }

        $cacheKey = 'yt_meta_summary_'.md5($videoTitle.$videoDescription);

        return Cache::remember($cacheKey, 86400, function () use ($videoTitle, $channelName, $toolName, $toolDescription, $videoDescription) {
            try {
                $context = "Titre : {$videoTitle}";
                if ($channelName) {
                    $context .= "\nChaine YouTube : {$channelName}";
                }
                if ($toolName) {
                    $context .= "\nOutil concerné : {$toolName} - {$toolDescription}";
                }
                if ($videoDescription) {
                    $context .= "\n\nDescription de la vidéo :\n{$videoDescription}";
                }

                $hasDescription = ! empty($videoDescription) && strlen($videoDescription) > 50;
                $systemPrompt = $hasDescription
                    ? 'A partir de la description YouTube et des metadonnees de cette video, genere un resume structure en francais. Inclus : les points cles abordes, le public cible, et ce que le spectateur va apprendre. Format : un paragraphe introductif + 3-5 puces des points cles. Sois precis et informatif. Ne mentionne PAS que tu te bases sur la description.'
                    : 'A partir du titre et du contexte de cette video YouTube, genere un court resume en francais (3-5 phrases) de ce que la video couvre. Sois informatif et utile.';

                $response = Http::withoutVerifying()->withHeaders([
                    'Authorization' => 'Bearer '.env('OPENROUTER_API_KEY'),
                    'HTTP-Referer' => config('app.url'),
                ])->timeout(60)->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => 'deepseek/deepseek-chat',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $context],
                    ],
                ]);

                if ($response->failed()) {
                    return null;
                }

                return $response->json('choices.0.message.content');
            } catch (\Throwable $e) {
                Log::warning('YouTubeService: erreur resume meta', ['exception' => $e->getMessage()]);

                return null;
            }
        });
    }

    public static function getVideoId(string $url): ?string
    {
        $patterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/(?:embed|shorts)\/([a-zA-Z0-9_-]{11})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    public static function getNodePath(): string
    {
        return env('BROWSERSHOT_NODE_PATH', trim(shell_exec('which node 2>/dev/null') ?: '/usr/local/bin/node'));
    }

    public static function isAvailable(): bool
    {
        return file_exists(self::getNodePath()) && file_exists(base_path('scripts/extract-transcript.mjs'));
    }
}
