<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    private const API_URL = 'https://openrouter.ai/api/v1/chat/completions';

    private const FRENCH_COMMON_WORDS = [
        'le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'et', 'ou',
        'est', 'sont', 'pour', 'avec', 'dans', 'sur', 'par', 'au', 'aux',
        'ce', 'cette', 'qui', 'que', 'pas', 'plus', 'nous', 'vous', 'tout',
        'faire', 'avoir', 'peut', 'aussi', 'entre', 'comme', 'mais', 'ses',
    ];

    /**
     * Traduit un texte via OpenRouter GPT-5.
     * Retourne le texte original si deja en FR, si erreur, ou si cle API absente.
     */
    public static function translate(string $text, string $from = 'en', string $to = 'fr'): string
    {
        if (empty(trim($text)) || $from === $to) {
            return $text;
        }

        if ($to === 'fr' && self::looksLikeFrench($text)) {
            return $text;
        }

        $cacheKey = 'translation_'.md5($text.$from.$to);

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($text, $from, $to) {
            $apiKey = config('services.openrouter.api_key', env('OPENROUTER_API_KEY'));
            if (! $apiKey) {
                return $text;
            }

            $systemPrompt = "Tu es un traducteur professionnel {$from} vers {$to}. "
                .'Traduis le texte suivant de maniere naturelle et fluide. '
                .'Retourne UNIQUEMENT la traduction, sans commentaire ni explication.';

            foreach (['openai/gpt-5', 'openai/gpt-5-mini'] as $model) {
                try {
                    $response = Http::timeout(30)
                        ->withoutVerifying()
                        ->withHeaders([
                            'Authorization' => 'Bearer '.$apiKey,
                            'Content-Type' => 'application/json',
                        ])
                        ->post(self::API_URL, [
                            'model' => $model,
                            'messages' => [
                                ['role' => 'system', 'content' => $systemPrompt],
                                ['role' => 'user', 'content' => $text],
                            ],
                            'temperature' => 0.3,
                        ]);

                    if ($response->successful()) {
                        $result = $response->json('choices.0.message.content', '');
                        $trimmed = trim($result);
                        if ($trimmed !== '') {
                            return $trimmed;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("TranslationService: {$model} failed", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $text;
        });
    }

    /**
     * Detection heuristique simple : le texte est-il deja en francais ?
     */
    private static function looksLikeFrench(string $text): bool
    {
        $words = preg_split('/[\s\p{P}]+/u', mb_strtolower($text));
        $words = array_filter($words, fn ($w) => mb_strlen($w) > 1);

        if (count($words) < 5) {
            return false;
        }

        $frenchCount = 0;
        foreach ($words as $word) {
            if (in_array($word, self::FRENCH_COMMON_WORDS, true)) {
                $frenchCount++;
            }
        }

        return ($frenchCount / count($words)) > 0.3;
    }
}
