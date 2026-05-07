<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Directory\Models\Tool;

header('Content-Type: text/plain; charset=utf-8');

// 42 outils manquants depuis sheet ACADEE (Profile Picture AI/ProfilePicture.ai dédupliqué)
// Format : [name, slug_fr, url, short_desc_fr, pricing, ecosystem_tag]
$tools = [
    // Automatisation
    ['Browse.ai', 'browse-ai', 'https://www.browse.ai/', 'Extraire des données d\'un site web automatiquement avec un robot IA configurable.', 'paid', null],
    ['DiffBot', 'diffbot', 'https://www.diffbot.com/', 'Extraction de données structurées depuis des sites web via API IA.', 'freemium', null],
    ['Bardeen AI', 'bardeen-ai', 'https://www.bardeen.ai/', 'Automatiser facilement des workflows sur le web avec une IA d\'orchestration.', 'freemium', null],
    ['Mind Studio', 'mind-studio', 'https://mindstudio.ai/', 'Créer des automatisations, des apps et des agents IA sans code.', 'freemium', null],
    // Branding
    ['Namelix', 'namelix', 'https://namelix.com', 'Génère des noms de marque courts et mémorables avec vérification de disponibilité de domaine.', 'free', null],
    // Créative
    ['Google Veo 3', 'google-veo-3', 'https://gemini.google.com', 'Génère des vidéos en 4K avec son à partir de texte (Google).', 'freemium', 'google'],
    ['Soundraw', 'soundraw', 'https://soundraw.io', 'Crée des musiques originales selon humeur et genre.', 'paid', null],
    ['Soundful', 'soundful', 'https://soundful.com', 'Génère des pistes musicales personnalisables.', 'freemium', null],
    ['Lumen5', 'lumen5', 'https://lumen5.com', 'Transforme des articles de blog en vidéos engageantes.', 'freemium', null],
    ['Magisto', 'magisto', 'https://www.magisto.com', 'Vidéo marketing automatisée à partir de photos et clips.', 'freemium', null],
    ['Kling', 'kling', 'https://www.kling.ai', 'IA générative de vidéos à partir d\'images ou de texte.', 'paid', null],
    ['Opus.pro', 'opus-pro', 'https://www.opus.pro', 'Transforme des vidéos longues en clips viraux automatiquement.', 'freemium', null],
    ['AutoDraw', 'autodraw', 'https://www.autodraw.com', 'Dessine automatiquement à partir de croquis basiques (Google).', 'free', 'google'],
    ['Reve.art', 'reve-art', 'https://reveai.org', 'Crée des images IA à partir de descriptions textuelles.', 'free', null],
    ['Profile Picture AI', 'profile-picture-ai', 'https://profilepicture.ai/', 'Crée des photos de profil IA stylisées à partir de selfies (350+ styles).', 'paid', null],
    ['Hedra', 'hedra', 'https://www.hedra.ai', 'Créateur IA de vidéos marketing et sociales avec avatars parlants.', 'freemium', null],
    ['Haiper', 'haiper', 'https://www.haiper.ai', 'Plateforme IA pour vidéos génératives à partir de texte ou d\'images.', 'freemium', null],
    ['Beehiiv', 'beehiiv', 'https://www.beehiiv.com/', 'Créer et gérer vos newsletters avec l\'IA, monétisation incluse.', 'freemium', null],
    ['Vidnoz AI', 'vidnoz-ai', 'https://www.vidnoz.com/', 'Crée des vidéos à partir de texte avec avatars animés.', 'freemium', null],
    ['Stitch', 'stitch', 'https://stitch.withgoogle.com/', 'Transforme des prompts en interfaces UI haute-fidélité (Google Labs), export Figma ou code.', 'free', 'google'],
    // Dev/Coding
    ['Debuild', 'debuild', 'https://debuild.app/', 'Permet de construire des applications web sans écrire de code.', 'freemium', null],
    ['Locofy.ai', 'locofy-ai', 'https://locofy.ai/', 'Crée des sites web à partir de maquettes avec IA (design to code).', 'freemium', null],
    ['Autocode', 'autocode', 'https://autocode.com/', 'Développement d\'applications avec peu de codage via un IDE IA low-code.', 'freemium', null],
    // Productivité
    ['PDF GPT', 'pdf-gpt', 'https://www.pdfgpt.io/', 'Analyse vos PDF et posez toutes vos questions via un chatbot IA.', 'paid', null],
    ['Ask PDF', 'ask-pdf', 'https://askyourpdf.com/fr', 'Un chatbot IA pour interagir avec vos fichiers PDF.', 'freemium', null],
    ['Whimsical AI', 'whimsical-ai', 'https://whimsical.com/ai/ai-mind-maps', 'Créer des mindmaps intelligentes avec l\'IA.', 'paid', null],
    ['Magical AI', 'magical-ai', 'https://www.getmagical.com/ai', 'Outil parfait pour gagner 1h de travail par jour, automatisations bureautiques.', 'freemium', null],
    ['Spinach', 'spinach', 'https://www.spinach.io/', 'Prend des notes à votre place durant toutes vos réunions Zoom/Meet/Teams.', 'freemium', null],
    ['Timely app', 'timely-app', 'https://timelyapp.com/', 'Tracker de temps automatisé par IA.', 'freemium', null],
    ['Clockify', 'clockify', 'https://clockify.me/', 'Suit le temps passé sur les projets et génère des rapports détaillés.', 'freemium', null],
    ['Google AI Studio', 'google-ai-studio', 'https://aistudio.google.com/prompts/new_chat', 'Environnement de développement pour prototyper avec les modèles IA générative de Google (Gemini).', 'freemium', 'google'],
    ['Yadulink', 'yadulink', 'https://yadul.ink/', 'Plateforme française d\'automatisation de prospection LinkedIn (séquences ciblées + IA).', 'paid', null],
    ['Replicate', 'replicate', 'https://replicate.com/', 'Plateforme cloud pour exécuter et déployer des modèles IA open-source via API simple.', 'paid', null],
    ['Workspace Studio', 'workspace-studio', 'https://workspace.google.com/', 'Outil intégré à Google Workspace pour créer et déployer des agents IA Gemini personnalisés.', 'paid', 'google'],
    // Réunions
    ['Noota', 'noota', 'https://www.noota.io/fr', 'Prend des notes pour vous durant toutes vos réunions (FR).', 'paid', null],
    ['Transkriptor', 'transkriptor', 'https://transkriptor.com/', 'Transcription, traduction, sommaires automatiques, identification des speakers.', 'freemium', null],
    // Textuel
    ['Qwen', 'qwen', 'https://qwen.alibaba.com', 'LLM développé par Alibaba avec des performances solides.', 'free', null],
    ['Meta AI', 'meta-ai', 'https://ai.meta.com', 'Assistant IA Meta utilisant le modèle LLaMA, intégré à Instagram et WhatsApp.', 'free', 'meta'],
    ['xAI Grok', 'xai-grok', 'https://grok.x.ai', 'Assistant IA développé par xAI (Elon Musk), analyse et génération de contenu.', 'freemium', 'xai'],
    ['Type.ai', 'type-ai', 'https://type.ai', 'Assistant d\'écriture IA avec commandes et ajustements de style.', 'free', null],
    ['Redaction.io', 'redaction-io', 'https://redaction.io', 'Génère des articles optimisés SEO à partir d\'un mot-clé.', 'paid', null],
    ['Slogan Generator', 'slogan-generator-oberlo', 'https://www.oberlo.com/tools/slogan-generator', 'Génère des slogans accrocheurs pour entreprises et campagnes (Oberlo).', 'free', null],
];

$created = 0; $skipped = 0; $errors = 0;
foreach ($tools as [$name, $slug, $url, $desc, $pricing, $ecosystem]) {
    try {
        // Re-check absence pour idempotence
        $exists = Tool::where('slug->fr_CA', $slug)->orWhere('url', $url)->first();
        if ($exists) {
            echo "SKIP (id={$exists->id}) {$name}\n";
            $skipped++;
            continue;
        }
        $tool = new Tool();
        $tool->setTranslation('name', 'fr_CA', $name);
        $tool->setTranslation('slug', 'fr_CA', $slug);
        $tool->setTranslation('description', 'fr_CA', $desc);
        $tool->setTranslation('short_description', 'fr_CA', Str::limit($desc, 140));
        $tool->url = $url;
        $tool->status = 'pending'; // user décidera publication
        $tool->pricing = $pricing;
        $tool->ecosystem_tag = $ecosystem;
        $tool->lifecycle_status = 'active';
        $tool->sort_order = 0;
        $tool->save();
        echo "CREATED id={$tool->id} {$name} ({$slug})\n";
        $created++;
    } catch (\Throwable $e) {
        echo "ERROR {$name}: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n=== RÉSUMÉ ===\nCreated: $created\nSkipped: $skipped\nErrors: $errors\n";
@unlink(__FILE__);
echo "AUTO-DELETE OK\n";
