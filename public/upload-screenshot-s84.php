<?php
/**
 * Endpoint upload one-shot screenshots S84 #21
 * Reçoit POST { token, slug, b64 } → écrit public/screenshots/{slug}.jpg si plus gros que existant
 * Garde-fou : refuse écrire si fichier existant >= 50KB (vraie capture)
 * Auto-delete via flag ?finish=1 final
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: application/json; charset=utf-8');

$EXPECTED_TOKEN = 's84-21-' . md5(env('APP_KEY', 'fallback'));

if (isset($_GET['finish']) && $_GET['finish'] === $EXPECTED_TOKEN) {
    @unlink(__FILE__);
    echo json_encode(['ok' => true, 'msg' => 'self-deleted']);
    exit;
}

$token = $_POST['token'] ?? '';
$slug = $_POST['slug'] ?? '';
$b64 = $_POST['b64'] ?? '';

if ($token !== $EXPECTED_TOKEN) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'err' => 'invalid token']);
    exit;
}
if (! $slug || ! preg_match('/^[a-z0-9-]+$/i', $slug)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'err' => 'invalid slug']);
    exit;
}
if (! $b64) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'err' => 'no b64']);
    exit;
}

$bin = base64_decode($b64, true);
if ($bin === false || strlen($bin) < 5000) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'err' => 'b64 decode KO or too small', 'size' => strlen($bin ?: '')]);
    exit;
}

$path = __DIR__ . "/screenshots/{$slug}.jpg";
$existingSize = file_exists($path) ? filesize($path) : 0;

// Garde-fou anti-overwrite : ne PAS écraser une vraie capture déjà présente (>= 50KB)
if ($existingSize >= 50000) {
    echo json_encode(['ok' => true, 'msg' => 'preserved', 'existing' => $existingSize, 'incoming' => strlen($bin)]);
    exit;
}

$tmp = $path . '.tmp.' . bin2hex(random_bytes(4));
file_put_contents($tmp, $bin);
rename($tmp, $path);

// Update DB pour pointer fichier
\Illuminate\Support\Facades\DB::table('directory_tools')
    ->whereJsonContains('slug->fr_CA', $slug)
    ->update([
        'screenshot' => "screenshots/{$slug}.jpg",
        'updated_at' => now(),
    ]);

echo json_encode(['ok' => true, 'msg' => 'written', 'size' => strlen($bin), 'replaced' => $existingSize > 0]);
