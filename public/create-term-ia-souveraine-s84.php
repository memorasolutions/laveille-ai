<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

use Modules\Dictionary\Models\Term;

// Vérification absence
$existing = Term::where('slug->fr_CA', 'ia-souveraine')->first();
if ($existing) {
    echo "ALREADY EXISTS id={$existing->id}\n";
    @unlink(__FILE__);
    exit;
}

// Création
$term = Term::create([
    'name' => ['fr_CA' => 'IA souveraine'],
    'slug' => ['fr_CA' => 'ia-souveraine'],
    'definition' => ['fr_CA' => 'Une intelligence artificielle développée, hébergée et contrôlée sur le territoire d\'un pays, d\'une région ou d\'une organisation, afin de garantir indépendance technologique, protection des données sensibles et respect des lois locales (RGPD, Loi 25, etc.).'],
    'analogy' => ['fr_CA' => 'C\'est comme cultiver ses légumes dans son potager plutôt que de tout importer : on contrôle ce qu\'on consomme, d\'où ça vient et qui peut y accéder.'],
    'example' => ['fr_CA' => 'Mistral en France, BLOOM en Europe, certaines initiatives québécoises (CRIAQ, OBVIA) — modèles dont les poids, l\'infrastructure et la gouvernance restent locaux.'],
    'did_you_know' => ['fr_CA' => 'Enjeu géostratégique 2025-2026 majeur : la France a investi 109 G€ dans l\'IA souveraine en février 2025, le Canada développe sa propre stratégie pour réduire la dépendance aux GAFAM US et hyperscalers chinois.'],
    'difficulty' => 'intermediate',
    'icon' => '🛡️',
    'type' => 'explainer',
    'is_published' => true,
    'match_strategy' => 'loose',
    'sort_order' => 0,
    'hero_image' => 'images/glossaire/ia-souveraine.png',
]);

echo "CREATED id={$term->id} name='IA souveraine' slug='ia-souveraine'\n";

// Génération image GD palette teal/orange/cream cohérente charte
$w = 1200; $h = 630;
$img = imagecreatetruecolor($w, $h);
$teal = imagecolorallocate($img, 6, 78, 90);     // --c-primary #064E5A
$orange = imagecolorallocate($img, 154, 42, 6);  // accent
$cream = imagecolorallocate($img, 250, 245, 235);
$white = imagecolorallocate($img, 255, 255, 255);

// Background gradient teal
imagefilledrectangle($img, 0, 0, $w, $h, $teal);
// Bande décorative orange
imagefilledrectangle($img, 0, 0, $w, 8, $orange);
imagefilledrectangle($img, 0, $h - 8, $w, $h, $orange);

// Texte (police par défaut GD car pas de TTF garanti)
$titleX = 80; $titleY = 220;
imagestring($img, 5, $titleX, $titleY, 'IA SOUVERAINE', $cream);
imagestring($img, 4, $titleX, $titleY + 60, 'Independance technologique', $white);
imagestring($img, 3, $titleX, $titleY + 110, 'Donnees protegees, gouvernance locale', $white);
imagestring($img, 3, $titleX, $titleY + 170, 'La veille de Stef - Glossaire IA Quebec', $orange);

// Sauvegarde PNG + JPG (cohérent avec pipeline pré-existant)
$dirs = [
    __DIR__ . '/images/glossaire',
];
foreach ($dirs as $d) { if (!is_dir($d)) @mkdir($d, 0755, true); }
$pngPath = __DIR__ . '/images/glossaire/ia-souveraine.png';
$jpgPath = __DIR__ . '/images/glossaire/ia-souveraine.jpg';
imagepng($img, $pngPath);
imagejpeg($img, $jpgPath, 88);
imagedestroy($img);

echo "IMAGE PNG: $pngPath (" . filesize($pngPath) . " bytes)\n";
echo "IMAGE JPG: $jpgPath (" . filesize($jpgPath) . " bytes)\n";

// Verification page
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->call('view:clear');
echo "VIEW CLEARED\n";

@unlink(__FILE__);
echo "SCRIPT SELF-DELETED\n";
