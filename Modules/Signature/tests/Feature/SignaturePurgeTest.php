<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Signature\Models\Signature;
use Modules\Signature\Models\SignatureImage;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
    // Disque PRIVÉ de quarantaine (B2) - distinct du disque public servi par la route d'image.
    Storage::fake('local');
});

function sigOldSignature(array $overrides = []): Signature
{
    $sig = new Signature();
    $sig->template = 'minimal';
    $sig->content = ['first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com'];
    $sig->admin_token_hash = hash('sha256', 'jeton-purge-'.uniqid());
    $sig->status = Signature::STATUS_ACTIVE;
    $sig->last_owner_activity_at = $overrides['last_owner_activity_at'] ?? null;
    $sig->last_image_loaded_on = $overrides['last_image_loaded_on'] ?? null;
    if (array_key_exists('user_id', $overrides)) {
        $sig->user_id = $overrides['user_id'];
    }
    if (array_key_exists('reminder_email', $overrides)) {
        $sig->reminder_email = $overrides['reminder_email'];
    }
    if (array_key_exists('reminder_sent_at', $overrides)) {
        $sig->reminder_sent_at = $overrides['reminder_sent_at'];
    }
    $sig->save();

    return $sig;
}

test('aucune purge tant que le propriétaire a été actif dans les 6 derniers mois', function (): void {
    $sig = sigOldSignature(['last_owner_activity_at' => now()->subMonths(5)]);

    $this->artisan('signature:purge-expired')->assertExitCode(0);

    expect($sig->fresh()->status)->toBe(Signature::STATUS_ACTIVE);
});

test('aucune purge tant qu\'une image a été chargée dans les 6 derniers mois, même sans activité du propriétaire', function (): void {
    $sig = sigOldSignature([
        'last_owner_activity_at' => now()->subMonths(8),
        'last_image_loaded_on' => now()->subDays(10),
    ]);

    $this->artisan('signature:purge-expired')->assertExitCode(0);

    expect($sig->fresh()->status)->toBe(Signature::STATUS_ACTIVE);
});

test('purge effective après 6 mois SANS activité ET SANS chargement d\'image - contenu et fichiers déplacés en quarantaine', function (): void {
    $sig = sigOldSignature(['last_owner_activity_at' => now()->subMonths(7)]);
    $image = SignatureImage::create([
        'signature_id' => $sig->id,
        'role' => SignatureImage::ROLE_LOGO,
        'path' => 'signature-derivatives/signature-'.$sig->id.'/logo-x.png',
        'width' => 192,
        'height' => 96,
        'display_width' => 96,
        'display_height' => 48,
    ]);
    Storage::disk('public')->put($image->path, 'contenu-derivee');

    $this->artisan('signature:purge-expired')->assertExitCode(0);

    $sig->refresh();
    $image->refresh();

    expect($sig->status)->toBe(Signature::STATUS_PURGED)
        ->and($sig->purged_at)->not->toBeNull()
        ->and($sig->content)->toBe([])
        // Le fichier a disparu du disque PUBLIC (déplacé, jamais copié puis oublié) ...
        ->and(Storage::disk('public')->exists('signature-derivatives/signature-'.$sig->id.'/logo-x.png'))->toBeFalse()
        ->and($image->path)->toBeNull()
        // Largeur/hauteur CONSERVÉES après purge (section 6.4) - le rectangle transparent de
        // repli doit connaître les dimensions exactes.
        ->and($image->width)->toBe(192)
        ->and($image->height)->toBe(96);
});

test('B2 - une purge crée l\'archive JSONL ET déplace le fichier vers la quarantaine (jamais une suppression nue)', function (): void {
    $sig = sigOldSignature(['last_owner_activity_at' => now()->subMonths(7)]);
    $image = SignatureImage::create([
        'signature_id' => $sig->id,
        'role' => SignatureImage::ROLE_LOGO,
        'path' => 'signature-derivatives/signature-'.$sig->id.'/logo-quarantaine.png',
        'width' => 96, 'height' => 40,
    ]);
    Storage::disk('public')->put($image->path, 'contenu-a-quarantiner');

    $this->artisan('signature:purge-expired')->assertExitCode(0);

    $archiveFile = 'signature-purge-archive/'.now()->toDateString().'.jsonl';
    expect(Storage::disk('local')->exists($archiveFile))->toBeTrue();

    $lines = array_filter(explode("\n", (string) Storage::disk('local')->get($archiveFile)));
    $entry = null;
    foreach ($lines as $line) {
        $decoded = json_decode($line, true);
        if (($decoded['signature_id'] ?? null) === $sig->id) {
            $entry = $decoded;
        }
    }

    expect($entry)->not->toBeNull()
        ->and($entry['content']['first_name'])->toBe('Marie')
        ->and($entry['reason'])->toBe('purge-expired');

    $quarantinePath = $entry['images'][0]['quarantine_path'];
    expect(Storage::disk('local')->exists($quarantinePath))->toBeTrue()
        ->and(Storage::disk('local')->get($quarantinePath))->toBe('contenu-a-quarantiner');
});

test('B2 - la restauration depuis la quarantaine redonne son contenu et son image à une signature purgée', function (): void {
    $sig = sigOldSignature(['last_owner_activity_at' => now()->subMonths(7)]);
    $image = SignatureImage::create([
        'signature_id' => $sig->id,
        'role' => SignatureImage::ROLE_LOGO,
        'path' => 'signature-derivatives/signature-'.$sig->id.'/logo-restaurable.png',
        'width' => 120, 'height' => 60,
    ]);
    Storage::disk('public')->put($image->path, 'contenu-original');

    $this->artisan('signature:purge-expired')->assertExitCode(0);
    expect($sig->fresh()->status)->toBe(Signature::STATUS_PURGED);

    $this->artisan('signature:restore-from-quarantine', ['signature_id' => $sig->id])
        ->assertExitCode(0);

    $sig->refresh();
    $image->refresh();

    expect($sig->status)->toBe(Signature::STATUS_ACTIVE)
        ->and($sig->purged_at)->toBeNull()
        ->and($sig->content['first_name'])->toBe('Marie')
        ->and($image->path)->toBe('signature-derivatives/signature-'.$sig->id.'/logo-restaurable.png')
        ->and($image->purged_at)->toBeNull()
        ->and(Storage::disk('public')->exists($image->path))->toBeTrue()
        ->and(Storage::disk('public')->get($image->path))->toBe('contenu-original');
});

test('B2 - après le délai de rétention, signature:purge-quarantaine vide l\'archive et la quarantaine (donnée définitivement perdue, comme prévu)', function (): void {
    $sig = sigOldSignature(['last_owner_activity_at' => now()->subMonths(7)]);
    $image = SignatureImage::create([
        'signature_id' => $sig->id,
        'role' => SignatureImage::ROLE_LOGO,
        'path' => 'signature-derivatives/signature-'.$sig->id.'/logo-expire.png',
        'width' => 96, 'height' => 40,
    ]);
    Storage::disk('public')->put($image->path, 'contenu-expire');

    $this->travelTo(now()->subDays(31), function () {
        $this->artisan('signature:purge-expired')->assertExitCode(0);
    });

    $archiveFile = 'signature-purge-archive/'.now()->subDays(31)->toDateString().'.jsonl';
    expect(Storage::disk('local')->exists($archiveFile))->toBeTrue();

    $this->artisan('signature:purge-quarantaine')->assertExitCode(0);

    expect(Storage::disk('local')->exists($archiveFile))->toBeFalse()
        ->and(Storage::disk('local')->exists('signature-quarantaine/'.$sig->id))->toBeFalse();

    // Passé le délai, la restauration échoue proprement (plus rien à restaurer) - jamais une
    // erreur fatale.
    $this->artisan('signature:restore-from-quarantine', ['signature_id' => $sig->id])
        ->assertExitCode(1);
});

test('après purge, la route d\'image sert un rectangle transparent aux dimensions EXACTES de l\'original, jamais un 404 ni un visuel visible', function (): void {
    $sig = sigOldSignature(['last_owner_activity_at' => now()->subMonths(7)]);
    $image = SignatureImage::create([
        'signature_id' => $sig->id,
        'role' => SignatureImage::ROLE_LOGO,
        'path' => 'signature-derivatives/signature-'.$sig->id.'/logo-x.png',
        'width' => 150,
        'height' => 60,
    ]);
    Storage::disk('public')->put($image->path, 'contenu-derivee');

    $this->artisan('signature:purge-expired')->assertExitCode(0);
    $image->refresh();

    $response = $this->get(route('signature.asset', ['public_id' => $image->public_id, 'ext' => 'png']));

    $response->assertOk()->assertHeader('Content-Type', 'image/png');

    $imageInfo = getimagesizefromstring($response->getContent());
    expect($imageInfo[0])->toBe(150)
        ->and($imageInfo[1])->toBe(60);
});

test('B1 - un signal NUL ne condamne jamais : une signature toute RÉCENTE sans last_owner_activity_at ni last_image_loaded_on n\'est PAS purgée (ancrage sur created_at)', function (): void {
    $sig = sigOldSignature(); // last_owner_activity_at et last_image_loaded_on nuls, created_at = maintenant

    $this->artisan('signature:purge-expired')->assertExitCode(0);

    expect($sig->fresh()->status)->toBe(Signature::STATUS_ACTIVE);
});

test('B1 - une signature ANCIENNE (created_at) sans aucune activité ni chargement dépassant le seuil est purgée (ancre = created_at, à défaut de last_owner_activity_at)', function (): void {
    $sig = sigOldSignature();
    $sig->created_at = now()->subMonths(7);
    $sig->save();

    $this->artisan('signature:purge-expired')->assertExitCode(0);

    expect($sig->fresh()->status)->toBe(Signature::STATUS_PURGED);
});

test('B1 - sans AUCUNE ancre connue (last_owner_activity_at et created_at nuls), la signature n\'est jamais éligible à la purge (protection par défaut, niveau modèle)', function (): void {
    $sig = new Signature([
        'template' => 'minimal',
        'content' => ['first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com'],
    ]);
    // Ni sauvegardée ni horodatée : simule l'absence totale d'ancre, scénario qu'aucune écriture
    // normale ne produit plus désormais (DEFAULT CURRENT_TIMESTAMP en base sur created_at), mais
    // que le code doit refuser de purger sans preuve d'ancienneté.
    expect($sig->purgeAnchor())->toBeNull()
        ->and($sig->isEligibleForPurge(6))->toBeFalse();
});

test('M1.4 - une signature de MEMBRE n\'est JAMAIS purgée automatiquement tant que le compte existe, même après des années d\'inactivité', function (): void {
    $user = \App\Models\User::factory()->create();
    $sig = sigOldSignature([
        'user_id' => $user->id,
        'last_owner_activity_at' => now()->subYears(3),
    ]);

    $this->artisan('signature:purge-expired')->assertExitCode(0);

    expect($sig->fresh()->status)->toBe(Signature::STATUS_ACTIVE);
});

test('M1.3 - une signature anonyme avec rappel opt-in n\'est PAS purgée si le rappel n\'a jamais été envoyé, même après 7 mois', function (): void {
    $sig = sigOldSignature([
        'last_owner_activity_at' => now()->subMonths(7),
        'reminder_email' => 'rappel@example.com',
        'reminder_sent_at' => null,
    ]);

    $this->artisan('signature:purge-expired')->assertExitCode(0);

    expect($sig->fresh()->status)->toBe(Signature::STATUS_ACTIVE);
});

test('M1.3 - une signature anonyme avec rappel opt-in n\'est PAS purgée tant que 14 jours ne se sont pas écoulés depuis l\'envoi du rappel', function (): void {
    $sig = sigOldSignature([
        'last_owner_activity_at' => now()->subMonths(7),
        'reminder_email' => 'rappel@example.com',
        'reminder_sent_at' => now()->subDays(5),
    ]);

    $this->artisan('signature:purge-expired')->assertExitCode(0);

    expect($sig->fresh()->status)->toBe(Signature::STATUS_ACTIVE);
});

test('M1.3 - une signature anonyme avec rappel opt-in est purgée une fois le rappel envoyé depuis plus de 14 jours', function (): void {
    $sig = sigOldSignature([
        'last_owner_activity_at' => now()->subMonths(7),
        'reminder_email' => 'rappel@example.com',
        'reminder_sent_at' => now()->subDays(20),
    ]);

    $this->artisan('signature:purge-expired')->assertExitCode(0);

    expect($sig->fresh()->status)->toBe(Signature::STATUS_PURGED);
});

test('le mode --dry-run ne supprime rien', function (): void {
    $sig = sigOldSignature(['last_owner_activity_at' => now()->subMonths(7)]);

    $this->artisan('signature:purge-expired --dry-run')->assertExitCode(0);

    expect($sig->fresh()->status)->toBe(Signature::STATUS_ACTIVE);
});
