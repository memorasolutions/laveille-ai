<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

final class ImagePromptBuilderService
{
    private const STYLE_MAP = [
        'realiste' => 'realistic',
        'illustration' => 'illustration',
        'minimaliste' => 'minimalist',
        'corporate' => 'corporate',
        '3d' => '3d render',
        'isometric' => 'isometric',
        'watercolor' => 'watercolor painting',
        'photographie' => 'photograph',
        'bd' => 'comic book style',
        'anime' => 'anime style',
    ];

    private const MOOD_MAP = [
        'lumineux' => 'bright, well-lit',
        'sombre' => 'dark, moody',
        'neutre' => 'neutral lighting',
        'dramatique' => 'dramatic lighting',
        'joyeux' => 'cheerful, vibrant',
        'professionnel' => 'professional, clean',
    ];

    public function build(array $params): array
    {
        $subject = $params['subject'];
        $style = $params['style'];
        $colors = $params['colors'] ?? [];
        $mood = $params['mood'];
        $targetAi = $params['target_ai'];
        $aspectRatio = $params['aspect_ratio'] ?? '16:9';
        $quality = $params['quality'] ?? 'standard';
        $composition = $params['composition'] ?? 'paysage_16_9';

        $colorPalette = implode(', ', $colors);
        $aiStyle = self::STYLE_MAP[$style] ?? $style;
        $aiMood = self::MOOD_MAP[$mood] ?? $mood;
        $negativePrompt = '';
        $prompt = '';
        $openUrl = '';

        switch ($targetAi) {
            case 'dalle3':
                $prompt = "A {$aiMood} {$aiStyle} of {$subject}, color palette: {$colorPalette}.";
                $openUrl = 'https://chat.openai.com/?model=dall-e-3&q='.urlencode($prompt);
                break;

            case 'midjourney':
                $qParam = $quality === 'hd' ? '2' : '1';
                $prompt = "{$subject}, {$aiStyle}, {$aiMood}, colors: {$colorPalette} --ar {$aspectRatio} --style raw --v 7 --q {$qParam}";
                $openUrl = 'https://discord.com/channels/@me';
                break;

            case 'imagen3':
            case 'gemini_imagen':
                $prompt = "Create a {$aiStyle} image of {$subject} in a {$aiMood} atmosphere. Color palette: {$colorPalette}. Composition: {$composition}, aspect ratio {$aspectRatio}.";
                $openUrl = 'https://gemini.google.com/app?prompt='.urlencode($prompt);
                break;

            case 'flux_pro':
                $prompt = "Professional high-resolution {$aiStyle} of {$subject}, {$aiMood}, color scheme: {$colorPalette}. Composition: {$composition}, aspect ratio {$aspectRatio}.";
                $openUrl = 'https://replicate.com/black-forest-labs/flux-pro';
                break;

            case 'stable_diffusion':
                $prompt = "({$subject}:1.3), ({$aiStyle}:1.2), {$aiMood}, color palette: {$colorPalette}, aspect ratio {$aspectRatio}";
                $negativePrompt = 'lowres, blurry, text, watermark, deformed, ugly';
                if (in_array($style, ['realiste', 'photographie'], true)) {
                    $negativePrompt .= ', cartoon, drawing, anime, illustration';
                } elseif (in_array($style, ['anime', 'bd'], true)) {
                    $negativePrompt .= ', photograph, realistic, 3d render';
                } elseif (in_array($style, ['3d', 'isometric'], true)) {
                    $negativePrompt .= ', 2d, flat, hand drawn';
                }
                $openUrl = 'https://stablediffusionweb.com/';
                break;

            default:
                $prompt = $subject;
                $openUrl = '';
        }

        return [
            'prompt' => $prompt,
            'negative_prompt' => $negativePrompt,
            'open_urls' => [$targetAi => $openUrl],
        ];
    }
}
