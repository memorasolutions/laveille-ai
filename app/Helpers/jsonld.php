<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Helpers Schema.org JSON-LD — centralisation Person/Organization auteur.
 *
 * #237 P27 (v1.18.3) : harmonisation auteur cross-vues.
 * Avant : Article author "La veille" / BlogPosting author "Stephane" sans accent.
 * Après : Person @id canonique, accents préservés, knowsAbout cohérent EEAT.
 *
 * NOTE Blade @context : ces arrays contiennent la clé '@context' qui est
 * interceptée par Blade Laravel 11 si utilisée dans `{!! ... !!}` brut.
 * Toujours pré-encoder via json_encode() depuis un bloc @php{} ou un
 * controller PHP (jamais inline brut dans .blade.php).
 */

if (! function_exists('lv_jsonld_author_stephane')) {
    /**
     * Person Schema.org canonique pour Stéphane Lapointe (auteur principal).
     *
     * Réutilisé par BlogPosting/Article/NewsArticle/HowTo author field.
     * Réf. Schema.org : https://schema.org/Person
     *
     * @return array<string,mixed>
     */
    function lv_jsonld_author_stephane(): array
    {
        $baseUrl = rtrim((string) (config('app.url') ?? 'https://laveille.ai'), '/');
        $authorUrl = $baseUrl.'/auteur/stephane-lapointe';

        return [
            '@type' => 'Person',
            '@id' => $authorUrl.'#person',
            'name' => 'Stéphane Lapointe',
            'url' => $authorUrl,
            'jobTitle' => "Fondateur MEMORA solutions, auteur, expert IA / Loi 25",
            'worksFor' => [
                '@type' => 'Organization',
                'name' => 'MEMORA solutions',
                'url' => 'https://memora.solutions',
            ],
            'sameAs' => [
                'https://www.linkedin.com/in/lapointestephane',
                'https://memora.solutions',
            ],
            'knowsAbout' => [
                'Intelligence artificielle',
                'Loi 25 (Québec)',
                'RGPD',
                'AI Act',
                'Gouvernance numérique',
                'Cybersécurité PME',
                'Transformation numérique',
            ],
        ];
    }
}

if (! function_exists('lv_jsonld_author_from_profile')) {
    function lv_jsonld_author_from_profile(\Modules\Authors\Models\AuthorProfile $profile): array
    {
        $baseUrl = rtrim((string) (config('app.url') ?? 'https://laveille.ai'), '/');
        $slug = (string) $profile->slug;
        $personId = "{$baseUrl}/@{$slug}#person";
        $url = "{$baseUrl}/@{$slug}";
        $name = $profile->user?->name ?? $slug;

        $person = [
            '@type' => 'Person',
            '@id' => $personId,
            'name' => $name,
            'url' => $url,
        ];

        if ($profile->bio) {
            $person['description'] = $profile->bio;
        }

        if (! empty($profile->social_links)) {
            $person['sameAs'] = array_values($profile->social_links);
        }

        if (! empty($profile->qualifications)) {
            $person['knowsAbout'] = $profile->qualifications;
            $person['jobTitle'] = $profile->qualifications[0];
        }

        if ($profile->profile_image) {
            $person['image'] = [
                '@type' => 'ImageObject',
                'url' => $profile->profile_image,
            ];
        }

        return $person;
    }
}

if (! function_exists('lv_jsonld_author_website')) {
    function lv_jsonld_author_website(\Modules\Authors\Models\AuthorProfile $profile): array
    {
        $baseUrl = rtrim((string) (config('app.url') ?? 'https://laveille.ai'), '/');
        $slug = (string) $profile->slug;
        $url = "{$baseUrl}/@{$slug}";
        $name = ($profile->user?->name ?? $slug) . ' · laveille.ai';

        return [
            '@type' => 'WebSite',
            '@id' => "{$baseUrl}/@{$slug}#website",
            'name' => $name,
            'url' => $url,
            'inLanguage' => 'fr-CA',
            'publisher' => ['@id' => "{$baseUrl}/@{$slug}#person"],
            'description' => $profile->manifesto ?? $profile->bio ?? '',
        ];
    }
}

if (! function_exists('lv_jsonld_publisher')) {
    /**
     * Organization Schema.org canonique pour publisher "La veille".
     *
     * @return array<string,mixed>
     */
    function lv_jsonld_publisher(): array
    {
        $baseUrl = rtrim((string) (config('app.url') ?? 'https://laveille.ai'), '/');

        return [
            '@type' => 'Organization',
            '@id' => $baseUrl.'/#organization',
            'name' => 'La veille',
            'url' => $baseUrl,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $baseUrl.'/images/logo-avatar.png',
            ],
        ];
    }
}
