<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * S90 #43 — Outils populaires manquants (référencés dans EditorialCollectionsSeeder).
 *
 * 13 outils mainstream 2026 absents de la DB : Mistral, Tome, Decktopus,
 * Jasper AI, Copy.ai, DeepL, Reverso, Aider, Stable Diffusion, Ideogram,
 * Flux AI, tl;dv, Fathom.video.
 *
 * Idempotent : skip si slug existe. URLs validées via curl HEAD S90.
 */
class MissingPopularToolsSeeder extends Seeder
{
    public function run(): void
    {
        $tools = [
            ['name' => 'Mistral', 'slug' => 'mistral', 'url' => 'https://chat.mistral.ai',
             'short' => "L'IA conversationnelle française open-source.",
             'long' => "Mistral AI propose des modèles de langage open-source performants conçus en France, alternative européenne à ChatGPT. Le Chat est leur interface conversationnelle gratuite avec respect strict du RGPD.",
             'pricing' => 'freemium', 'tagline' => "L'IA conversationnelle française."],
            ['name' => 'Tome', 'slug' => 'tome', 'url' => 'https://tome.app',
             'short' => "Présentations interactives générées par IA.",
             'long' => "Tome utilise l'IA pour transformer une simple description en présentation visuelle complète : plan, slides, illustrations et animations en quelques secondes.",
             'pricing' => 'freemium', 'tagline' => "Présentations IA en quelques secondes."],
            ['name' => 'Decktopus', 'slug' => 'decktopus', 'url' => 'https://decktopus.com',
             'short' => "Outil de création de présentations alimenté par IA.",
             'long' => "Decktopus génère des présentations professionnelles à partir d'un sujet, avec design adapté, contenu structuré et notes d'animation incluses.",
             'pricing' => 'freemium', 'tagline' => "Présentations professionnelles en 5 minutes."],
            ['name' => 'Jasper AI', 'slug' => 'jasper-ai', 'url' => 'https://www.jasper.ai',
             'short' => "Plateforme IA de marketing et création de contenu.",
             'long' => "Jasper AI est une suite IA pour rédacteurs marketing : articles de blog, posts réseaux sociaux, campagnes email, copywriting publicitaire avec ton de marque adaptable.",
             'pricing' => 'paid', 'tagline' => "L'IA marketing pour entreprises."],
            ['name' => 'Copy.ai', 'slug' => 'copyai', 'url' => 'https://www.copy.ai',
             'short' => "Génération automatisée de contenu marketing par IA.",
             'long' => "Copy.ai automatise la création de copywriting, emails commerciaux, posts sociaux et workflows marketing avec des modèles préconçus pour entreprises et freelances.",
             'pricing' => 'freemium', 'tagline' => "Workflows marketing automatisés."],
            ['name' => 'DeepL', 'slug' => 'deepl', 'url' => 'https://www.deepl.com',
             'short' => "Traducteur IA neuronal de référence pour le français.",
             'long' => "DeepL offre des traductions de qualité supérieure à Google Translate sur les langues européennes, avec un support natif du français et un excellent rendu du contexte.",
             'pricing' => 'freemium', 'tagline' => "La traduction IA de référence en français."],
            ['name' => 'Reverso', 'slug' => 'reverso', 'url' => 'https://www.reverso.net',
             'short' => "Traduction, dictionnaire et conjugaison IA.",
             'long' => "Reverso combine traduction contextuelle, dictionnaire bilingue, conjugueur de verbes et correcteur grammatical en français, anglais, espagnol et plus.",
             'pricing' => 'freemium', 'tagline' => "Traduire, conjuguer, apprendre."],
            ['name' => 'Aider', 'slug' => 'aider', 'url' => 'https://aider.chat',
             'short' => "Assistant de programmation IA pour terminal.",
             'long' => "Aider est un pair-programmeur IA en ligne de commande qui édite directement votre dépôt Git via Claude/GPT, avec gestion des commits et compréhension multi-fichiers.",
             'pricing' => 'free', 'tagline' => "Pair-programmeur IA open-source."],
            ['name' => 'Stable Diffusion', 'slug' => 'stable-diffusion', 'url' => 'https://stability.ai',
             'short' => "Modèle open-source de génération d'images par IA.",
             'long' => "Stable Diffusion est le modèle phare de Stability AI : générateur d'images open-source utilisable localement ou via API, base technique de nombreux outils créatifs IA.",
             'pricing' => 'freemium', 'tagline' => "Génération d'images open-source."],
            ['name' => 'Ideogram', 'slug' => 'ideogram', 'url' => 'https://ideogram.ai',
             'short' => "Génération d'images IA spécialisée dans le texte intégré.",
             'long' => "Ideogram excelle dans la génération d'images contenant du texte lisible et stylisé : logos, affiches, packaging, mèmes — domaine où la plupart des autres IA échouent.",
             'pricing' => 'freemium', 'tagline' => "L'IA qui sait écrire dans les images."],
            ['name' => 'Flux AI', 'slug' => 'flux-ai', 'url' => 'https://flux1.ai',
             'short' => "Générateur d'images photoréalistes par IA Black Forest Labs.",
             'long' => "Flux par Black Forest Labs offre des images photoréalistes ultra-qualitatives, considéré comme un des meilleurs modèles open-source de génération d'images en 2025-2026.",
             'pricing' => 'freemium', 'tagline' => "Photoréalisme IA de pointe."],
            ['name' => 'tl;dv', 'slug' => 'tldv', 'url' => 'https://tldv.io',
             'short' => "Notes IA automatiques pour Zoom, Meet et Teams.",
             'long' => "tl;dv enregistre, transcrit et résume vos réunions vidéo avec timestamps, identification des intervenants et extraits cliquables. Plan gratuit généreux.",
             'pricing' => 'freemium', 'tagline' => "Vos réunions résumées automatiquement."],
            ['name' => 'Fathom', 'slug' => 'fathom-video', 'url' => 'https://fathom.video',
             'short' => "Assistant IA gratuit pour réunions vidéo.",
             'long' => "Fathom enregistre, transcrit et résume vos appels Zoom, Google Meet et Microsoft Teams gratuitement et sans limite, avec intégrations CRM (HubSpot, Salesforce).",
             'pricing' => 'freemium', 'tagline' => "Réunions résumées 100% gratuit."],
        ];

        $now = Carbon::now()->toDateTimeString();
        $slugExpr = "JSON_UNQUOTE(JSON_EXTRACT(slug, '$.\"fr_CA\"'))";
        $jsonField = static fn (string $v): string => json_encode(['fr_CA' => $v, 'fr' => $v, 'en' => $v], JSON_UNESCAPED_UNICODE);

        foreach ($tools as $t) {
            if (DB::table('directory_tools')->whereRaw("$slugExpr = ?", [$t['slug']])->exists()) {
                $this->command?->info("[SKIP] {$t['slug']} déjà existant");
                continue;
            }

            $id = DB::table('directory_tools')->insertGetId([
                'name' => $jsonField($t['name']),
                'slug' => $jsonField($t['slug']),
                'url' => $t['url'],
                'short_description' => $jsonField($t['short']),
                'description' => $jsonField($t['long']),
                'unique_value' => $t['tagline'],
                'pricing' => $t['pricing'],
                'has_education_pricing' => 0,
                'education_pricing_type' => null,
                'status' => 'published',
                'lifecycle_status' => 'active',
                'is_featured' => 0,
                'sort_order' => 0,
                'website_type' => 'website',
                'has_api_access' => 0,
                'is_multimodal' => 0,
                'enrichment_version' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->command?->info("[OK] {$t['slug']} créé (id=$id)");
        }
    }
}
