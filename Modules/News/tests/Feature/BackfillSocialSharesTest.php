<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Le marqueur « déjà publié sur les réseaux » sert à ne PAS republier en double. Une date fausse
 * est donc pire que pas de date : elle ferait croire qu'une actualité est partie alors qu'elle
 * ne l'est pas, ou l'inverse. D'où les contrôles sur la date réelle et sur le refus d'écraser.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

function bssSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source réseaux', 'url' => 'https://bss.exemple.com/rss',
        'language' => 'fr', 'active' => true,
    ]);
}

function bssFiche(int $src, string $slug): NewsArticle
{
    static $i = 0;
    $i++;

    return NewsArticle::create([
        'news_source_id' => $src, 'title' => "Fiche {$i}", 'guid' => "g-bss-{$i}-".uniqid(),
        'url' => "https://exemple.com/bss-{$i}", 'slug' => $slug,
        'pub_date' => now()->subDays(40), 'is_published' => true, 'seo_status' => 'index',
    ]);
}

function bssFichier(string $contenu): string
{
    $c = tempnam(sys_get_temp_dir(), 'bss').'.txt';
    file_put_contents($c, $contenu);

    return $c;
}

it('marque une actualité à la DATE RÉELLE de sa publication, jamais aujourd\'hui', function () {
    $f = bssFiche(bssSource()->id, 'mon-actu-partagee');
    $fic = bssFichier("mon-actu-partagee|2026-08-21\n");

    $this->artisan("news:backfill-social-shares {$fic} --plateforme=facebook --appliquer")->assertSuccessful();

    $f->refresh();
    expect($f->facebook_shared_at)->not->toBeNull();
    expect($f->facebook_shared_at->format('Y-m-d'))->toBe('2026-08-21');
    expect($f->facebook_shared_at->isToday())->toBeFalse();
    // Midi, pour qu'un décalage de fuseau ne fasse pas basculer la date d'un jour.
    expect($f->facebook_shared_at->format('H'))->toBe('12');
    expect($f->linkedin_shared_at)->toBeNull();

    unlink($fic);
});

it('refuse une plateforme inconnue', function () {
    $fic = bssFichier("peu-importe|2026-08-21\n");
    $this->artisan("news:backfill-social-shares {$fic} --plateforme=twitter --appliquer")->assertFailed();
    unlink($fic);
});

it("n'écrit rien sans --appliquer", function () {
    $f = bssFiche(bssSource()->id, 'simulation-seulement');
    $fic = bssFichier("simulation-seulement|2026-08-21\n");

    $this->artisan("news:backfill-social-shares {$fic} --plateforme=facebook")->assertSuccessful();

    expect($f->fresh()->facebook_shared_at)->toBeNull();
    unlink($fic);
});

// Écraser une date existante effacerait une information vraie par une information supposée.
it('ne remplace JAMAIS une date déjà posée sans --ecraser', function () {
    $f = bssFiche(bssSource()->id, 'deja-marquee');
    $f->forceFill(['facebook_shared_at' => now()->subDays(90)->setTime(12, 0)])->save();
    $ancienne = $f->fresh()->facebook_shared_at->format('Y-m-d');

    $fic = bssFichier("deja-marquee|2026-08-21\n");
    $this->artisan("news:backfill-social-shares {$fic} --plateforme=facebook --appliquer")->assertSuccessful();

    expect($f->fresh()->facebook_shared_at->format('Y-m-d'))->toBe($ancienne);
    unlink($fic);
});

it('rejette une date absurde au lieu de la corriger en silence', function () {
    $f = bssFiche(bssSource()->id, 'date-absurde');
    // 2026-13-45 n'existe pas. createFromFormat seul la reporterait sur un autre mois.
    $fic = bssFichier("date-absurde|2026-13-45\n");

    $this->artisan("news:backfill-social-shares {$fic} --plateforme=facebook --appliquer")->assertSuccessful();

    expect($f->fresh()->facebook_shared_at)->toBeNull();
    unlink($fic);
});

it('ignore les commentaires et les lignes vides sans les compter', function () {
    $f = bssFiche(bssSource()->id, 'avec-commentaires');
    $fic = bssFichier("# un commentaire\n\navec-commentaires|2026-08-21\n\n# un autre\n");

    $this->artisan("news:backfill-social-shares {$fic} --plateforme=linkedin --appliquer")->assertSuccessful();

    expect($f->fresh()->linkedin_shared_at?->format('Y-m-d'))->toBe('2026-08-21');
    unlink($fic);
});

it('signale un slug introuvable sans interrompre le reste du fichier', function () {
    $f = bssFiche(bssSource()->id, 'celle-qui-existe');
    $fic = bssFichier("slug-qui-nexiste-pas|2026-08-20\ncelle-qui-existe|2026-08-21\n");

    $this->artisan("news:backfill-social-shares {$fic} --plateforme=facebook --appliquer")->assertSuccessful();

    // La ligne suivante doit avoir été traitée malgré l'échec de la précédente.
    expect($f->fresh()->facebook_shared_at?->format('Y-m-d'))->toBe('2026-08-21');
    unlink($fic);
});
