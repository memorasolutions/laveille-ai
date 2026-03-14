<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Services;

class SentimentService
{
    public function __construct(
        protected AiService $aiService
    ) {}

    /**
     * @return array{sentiment: string, confidence: float, summary: string}
     */
    public function analyze(string $text): array
    {
        $systemPrompt = 'Analyze the sentiment of the following customer message. Return ONLY valid JSON: {"sentiment": "positive|neutral|negative|urgent", "confidence": 0.0-1.0, "summary": "brief reason"}. No additional text.';

        $response = $this->aiService->chat($text, $systemPrompt);

        $data = json_decode($response, true);

        $validSentiments = ['positive', 'neutral', 'negative', 'urgent'];

        if (is_array($data)
            && isset($data['sentiment'], $data['confidence'], $data['summary'])
            && in_array($data['sentiment'], $validSentiments)) {
            return [
                'sentiment' => $data['sentiment'],
                'confidence' => (float) $data['confidence'],
                'summary' => (string) $data['summary'],
            ];
        }

        return [
            'sentiment' => 'neutral',
            'confidence' => 0.0,
            'summary' => '',
        ];
    }
}
