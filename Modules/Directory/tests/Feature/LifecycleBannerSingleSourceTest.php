<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Preuve que la fiche (/annuaire/{slug}, rendue par Modules/Core/resources/views/components/
 * lifecycle-banner.blade.php) affiche EXACTEMENT le message que renvoie l'accesseur du modèle
 * getLifecycleBannerMessageAttribute() (Modules/Core/app/Traits/HasLifecycleStatus.php). Avant ce
 * correctif, le composant Blade portait sa PROPRE table $messages (7 clés), recopiée à la main et
 * déjà divergente de l'accesseur (qui n'en couvrait que 2 : closed, scam - le reste retombait sur
 * le repli générique « Statut : <libellé> »). La carte de la liste (Modules/Directory/resources/
 * views/public/index.blade.php, clé lifecycleBannerMsg) lit ce MÊME accesseur sans code
 * intermédiaire - aucun test séparé n'est nécessaire pour prouver qu'elle partage la source : le
 * lire une fois ici suffit, il n'y a rien à diverger côté carte.
 *
 * Statuts couverts - CORRECTIF au périmètre demandé : le brief d'origine parlait de « 7 statuts
 * non actifs », mais is_lifecycle_active (HasLifecycleStatus::getIsLifecycleActiveAttribute())
 * classe 'beta' comme ACTIF (avec 'active') - le bandeau ne s'affiche donc JAMAIS pour 'beta'
 * (@if(! $tool->is_lifecycle_active) dans lifecycle-banner.blade.php). Il n'y a que 6 statuts
 * réellement non actifs, et ce sont les seuls qu'un test de RENDU peut exercer : closed, acquired,
 * renamed, pivoted, paused, scam. 'beta' garde son texte dans l'accesseur (fidélité au composant
 * d'origine) mais reste du code mort, inatteignable via le bandeau - inchangé par ce correctif.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Directory\Models\Tool;
use Tests\Concerns\RegistersMysqlSqliteCompatFunctions;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);
uses(RegistersMysqlSqliteCompatFunctions::class);

beforeEach(fn () => $this->registerMysqlSqliteCompatFunctions());

/** Construction directe (pas de ToolFactory dans ce module - même convention que les tests voisins). */
function makeLifecycleBannerTestTool(string $statut): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil bandeau '.$statut);
    $tool->setTranslation('slug', 'fr_CA', 'outil-bandeau-'.$statut.'-'.uniqid());
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test suffisamment long pour ne pas être considéré comme mince.');
    $tool->url = 'https://exemple-bandeau-'.$statut.'-'.uniqid().'.test';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->lifecycle_status = $statut;
    $tool->save();
    $tool->refresh();

    return $tool;
}

/**
 * Extrait le texte EXACT (espaces normalisés) du <h3> DU BANDEAU de cycle de vie - ciblé via
 * [role="alert"] (attribut posé par lifecycle-banner.blade.php, pas de classe/id dédié sur le
 * composant). Une simple assertSee() ne suffit PAS ici : c'est une recherche de SOUS-CHAÎNE, et
 * mesuré en écrivant ce test, l'ancien texte de l'accesseur pour 'closed' (sans point final)
 * était un sous-ensemble littéral de l'ancien texte du composant Blade (avec point final) - la
 * divergence aurait échappé à assertSee(). L'égalité stricte ci-dessous la détecte. Un premier
 * essai au regex générique <h3>...</h3> capturait le MAUVAIS <h3> (celui du fil d'Ariane/titre de
 * page) - mesuré en le lançant, corrigé en ciblant [role="alert"] avec DomCrawler (déjà une
 * dépendance du projet via symfony/dom-crawler).
 */
function extraireTexteBandeauLifecycle(string $html): string
{
    $crawler = new \Symfony\Component\DomCrawler\Crawler($html);
    $noeuds = $crawler->filter('[role="alert"] h3');

    expect($noeuds->count())->toBe(1, 'Le bandeau [role="alert"] > h3 du cycle de vie est introuvable ou ambigu dans la réponse.');

    return trim(preg_replace('/\s+/', ' ', $noeuds->first()->text()));
}

$statutsNonActifs = ['closed', 'acquired', 'renamed', 'pivoted', 'paused', 'scam'];

foreach ($statutsNonActifs as $statut) {
    test("la fiche du statut lifecycle '{$statut}' affiche exactement le message de l'accesseur du modele", function () use ($statut) {
        $tool = makeLifecycleBannerTestTool($statut);

        // Valeur de référence : l'accesseur du modèle, SOURCE UNIQUE depuis ce correctif.
        $messageAttendu = $tool->lifecycle_banner_message;

        expect($messageAttendu)->not->toBe('')
            ->and($messageAttendu)->not->toStartWith('Statut : '); // sinon le statut est retombé sur le repli générique, pas sur son propre texte

        $response = $this->get(route('directory.show', $tool->slug));

        $response->assertOk();

        // Le bandeau de la fiche doit contenir MOT POUR MOT (égalité stricte, pas sous-chaîne)
        // le texte de l'accesseur - preuve qu'il n'existe plus de deuxième table de messages qui
        // pourrait diverger, même par un simple point final oublié.
        $texteRendu = extraireTexteBandeauLifecycle($response->getContent());

        expect($texteRendu)->toBe($messageAttendu);
    });
}

test("le statut lifecycle inconnu en base ('archived') retombe sur le repli generique cote accesseur", function () {
    // 37 fiches portent 'archived' en base (hors des 8 statuts de lifecycleStatuses()) - le repli
    // "Statut : <libelle>" doit rester actif pour ce cas.
    //
    // Pas de verification HTTP ici (mesure faite en ecrivant ce test, CORRECTIF a l'hypothese de
    // depart : /annuaire/{slug} repond 404 pour un statut 'archived', regle prealable au present
    // correctif et sans rapport avec lui - PublicDirectoryIndexPageTest.php prouve deja que ces
    // fiches sont exclues de la grille publique ; ici on constate qu'elles sont aussi exclues de
    // la fiche individuelle). Le bandeau ne peut donc jamais se rendre pour ce statut en
    // production ; seul l'accesseur est verifiable.
    $tool = makeLifecycleBannerTestTool('archived');

    expect($tool->lifecycle_banner_message)->toBe('Statut : archived');
});
