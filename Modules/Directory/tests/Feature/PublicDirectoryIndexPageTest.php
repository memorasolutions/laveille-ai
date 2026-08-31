<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Preuve HTTP de bout en bout de /annuaire (directory.index) - mandat #1939 : cette route était
 * IMPOSSIBLE à atteindre dans la suite de tests avant ce fichier, faute d'une requête portable
 * (« HAVING clause on a non-aggregate query », mesuré le 2026-08-27, reconfirmé identique le
 * 2026-08-31). Modules/Directory/tests/Feature/PublicListCachePurgeOnPublishTest.php documentait
 * la limitation et contournait via l'accueil ; ce fichier-ci teste la VRAIE route directement,
 * middleware + contrôleur (PublicDirectoryController::index()) + vue.
 *
 * Cause exacte, corrigée dans le contrôleur (pas seulement contournée côté test, contrairement à
 * FIELD()/JSON_UNQUOTE qui sont de vraies limitations sqlite) : ->having('community_votes_count',
 * '>', 0) référençait un ALIAS de withCount() sans GROUP BY - MySQL l'accepte (extension non
 * standard), sqlite le refuse. Remplacé par ->has('communityVotes', '>', 0), portable, déjà
 * l'idiome utilisé plus bas dans le même fichier (compare()).
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

/** Construction directe (pas de ToolFactory dans ce module - même convention que les autres tests Directory). */
function makeDirectoryIndexTestTool(string $suffixe, array $overrides = []): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->url = 'https://index-test-'.$suffixe.'-'.uniqid().'.example';
    $tool->pricing = $overrides['pricing'] ?? 'free';
    $tool->status = $overrides['status'] ?? 'published';
    $tool->is_featured = false;
    if (! empty($overrides['lifecycle_status'])) {
        $tool->lifecycle_status = $overrides['lifecycle_status'];
    }
    $tool->setTranslation('name', 'fr_CA', $overrides['name'] ?? 'Outil '.$suffixe);
    $tool->setTranslation('slug', 'fr_CA', 'outil-index-'.$suffixe.'-'.uniqid());
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test suffisamment long pour ne pas être considéré comme mince.');
    $tool->save();

    if (! empty($overrides['category_id'])) {
        $tool->categories()->sync([$overrides['category_id']]);
    }

    return $tool;
}

function addDirectoryIndexTestVotes(Tool $tool, int $nombre): void
{
    for ($i = 0; $i < $nombre; $i++) {
        $tool->communityVotes()->create(['user_id' => User::factory()->create()->id]);
    }
}

// ── Preuve de bout en bout : la route répond et respecte la visibilité published()/notArchived() ──

it('rend /annuaire avec 200 et affiche uniquement les outils publies et non archives', function () {
    $marqueurPublie = 'OutilPublieMarqueur'.uniqid();
    $marqueurEnAttente = 'OutilEnAttenteMarqueur'.uniqid();
    $marqueurArchive = 'OutilArchiveMarqueur'.uniqid();

    makeDirectoryIndexTestTool('publie', ['name' => $marqueurPublie, 'status' => 'published']);
    makeDirectoryIndexTestTool('attente', ['name' => $marqueurEnAttente, 'status' => 'pending']);
    makeDirectoryIndexTestTool('archive', ['name' => $marqueurArchive, 'status' => 'published', 'lifecycle_status' => 'archived']);

    $response = $this->get(route('directory.index'));

    $response->assertOk();
    $response->assertSee($marqueurPublie, false);
    $response->assertDontSee($marqueurEnAttente, false);
    $response->assertDontSee($marqueurArchive, false);
});

// ── Régression #1939 : la section "plus votés" (HAVING community_votes_count > 0) ──────────
//
// Avant le correctif, cette requête FAISAIT CRASHER toute la page sur sqlite (mesuré ci-dessus).
// Preuve ici que la donnée assemblée est aussi CORRECTE : exclut les outils à 0 vote, trie par
// nombre de votes décroissant - exactement le contrat de ->having('community_votes_count','>',0)
// ->orderByDesc('community_votes_count') d'origine, désormais porté par ->has(...).

it('le bloc "plus votes" exclut les outils a 0 vote et trie par nombre de votes decroissant', function () {
    $troisVotes = makeDirectoryIndexTestTool('trois-votes', ['name' => 'Outil trois votes '.uniqid()]);
    $unVote = makeDirectoryIndexTestTool('un-vote', ['name' => 'Outil un vote '.uniqid()]);
    $zeroVote = makeDirectoryIndexTestTool('zero-vote', ['name' => 'Outil zero vote '.uniqid()]);

    addDirectoryIndexTestVotes($troisVotes, 3);
    addDirectoryIndexTestVotes($unVote, 1);
    // $zeroVote : aucun vote ajouté volontairement.

    $response = $this->get(route('directory.index'));

    $response->assertOk();
    $response->assertViewHas('topVoted', function ($topVoted) use ($troisVotes, $unVote, $zeroVote) {
        $ids = $topVoted->pluck('id')->all();

        return $ids === [$troisVotes->id, $unVote->id]
            && ! in_array($zeroVote->id, $ids, true);
    });
});

it('le bloc "plus votes" reste une collection vide (sans planter la page) quand personne n\'a vote', function () {
    makeDirectoryIndexTestTool('sans-vote-du-tout', ['name' => 'Outil sans aucun vote '.uniqid()]);

    $response = $this->get(route('directory.index'));

    $response->assertOk();
    $response->assertViewHas('topVoted', fn ($topVoted) => $topVoted->count() === 0);
});

// ── Filtres existants (applyDirectoryFilters), exerces via la vraie route ───────────────────

it('le filtre ?pricing= ne retourne que les outils de ce tarif dans la grille principale', function () {
    $marqueurGratuit = 'OutilGratuitMarqueur'.uniqid();
    $marqueurPayant = 'OutilPayantMarqueur'.uniqid();

    makeDirectoryIndexTestTool('gratuit', ['name' => $marqueurGratuit, 'pricing' => 'free']);
    makeDirectoryIndexTestTool('payant', ['name' => $marqueurPayant, 'pricing' => 'paid']);

    $response = $this->get(route('directory.index', ['pricing' => 'free']));

    $response->assertOk();
    $response->assertViewHas('tools', function ($tools) use ($marqueurGratuit, $marqueurPayant) {
        $noms = $tools->pluck('name')->all();

        return in_array($marqueurGratuit, $noms, true) && ! in_array($marqueurPayant, $noms, true);
    });
});

it('le filtre ?q= ne retourne que les outils dont le nom correspond', function () {
    $marqueurTrouve = 'ChercheOutil'.uniqid();
    $marqueurAbsent = 'IntrouvableOutil'.uniqid();

    makeDirectoryIndexTestTool('recherche-trouve', ['name' => $marqueurTrouve]);
    makeDirectoryIndexTestTool('recherche-absent', ['name' => $marqueurAbsent]);

    $response = $this->get(route('directory.index', ['q' => $marqueurTrouve]));

    $response->assertOk();
    $response->assertViewHas('tools', function ($tools) use ($marqueurTrouve, $marqueurAbsent) {
        $noms = $tools->pluck('name')->all();

        return in_array($marqueurTrouve, $noms, true) && ! in_array($marqueurAbsent, $noms, true);
    });
});
