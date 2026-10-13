<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ajout du terme « PWA (application web progressive) » : site web qui s'installe et s'utilise comme
 * une application, depuis le navigateur, sans magasin d'applications. Demande du fondateur
 * (2026-10-13), en lien avec la fiche annuaire LucidNest : faire du mot « PWA » un terme defini que
 * l'auto-lien du site pourra expliquer partout.
 *
 * CONTROLE ANTI-DOUBLON (2026-10-13) : balayage du sitemap de production, famille de motifs
 * pwa|progressive|web-app|application-web|service-worker|installable|offline|hors-ligne sur 562
 * slugs du glossaire = 0 resultat. Aucun terme parent (« navigateur », « application web »)
 * n'existe : broader_slugs/narrower_slugs restent vides plutot que de fabriquer une parente fausse.
 *
 * VALIDATION CROISEE DES FAITS : Perplexity sonar-pro (repli documente, pp_search tombait en defi
 * Cloudflare) + connaissance etablie, trois sources reelles verifiees vivantes (HTTP 200) : MDN FR,
 * web.dev (Google) et le billet d'origine d'Alex Russell (2015). Origine du terme confirmee :
 * propose en 2015 par Frances Berriman (conceptrice) et Alex Russell (ingenieur Google/Chrome).
 *
 * ALIAS : « PWA », « application web progressive » et variantes. « PWA » est un acronyme, donc
 * match_strategy = case_sensitive (eviter un faux appariement sur une sous-chaine en minuscules).
 *
 * Typographie quebecoise (OQLF) : espace insecable U+00A0 devant chaque deux-points, aucune espace
 * avant ; ! ?, guillemets droits, aucun tiret cadratin.
 *
 * Migration idempotente (slug deja present = ignore). down() retire seulement ce terme.
 */
return new class extends Migration
{
    private const NEW_SLUG = 'pwa';

    private function resolveCategoryId(string $slug): ?int
    {
        return Category::where('slug->fr_CA', $slug)->value('id')
            ?? Category::where('slug->fr', $slug)->value('id');
    }

    private function term(): array
    {
        return [
            'name' => 'PWA (application web progressive)',
            'slug' => self::NEW_SLUG,
            'cat_slug' => 'outils-et-techniques',
            'acronym_full' => 'Progressive Web App',
            'definition' => "Une application web progressive, ou PWA (de l'anglais progressive web app), est un site web conçu avec les technologies habituelles du Web (HTML, CSS, JavaScript), mais qui se comporte comme une application installée. Deux briques techniques le permettent : le service worker, un petit programme qui tourne en arrière-plan et met des données en cache, et le manifeste d'application, un fichier qui décrit le nom, les icônes et le mode d'affichage. Grâce à elles, une PWA peut s'ajouter à l'écran d'accueil d'un téléphone ou au bureau d'un ordinateur, se lancer en plein écran, fonctionner même sans connexion et, parfois, envoyer des notifications. Elle s'installe directement depuis le navigateur, sans magasin d'applications, et se met à jour toute seule comme un site web. Un même code sert ainsi sur toutes les plateformes, et elle est normalement servie en HTTPS, pour la sécurité.",
            'analogy' => "C'est un site web qui s'installe sur l'appareil et garde une copie de lui-même, pour s'ouvrir comme une application, même hors ligne.",
            'example' => "Des services comme Pinterest ou Spotify offrent une version PWA : on l'ajoute à l'écran d'accueil depuis le navigateur, et elle s'ouvre ensuite comme une application, sans passer par un magasin.",
            'did_you_know' => "Le terme a été proposé en 2015 par la conceptrice Frances Berriman et l'ingénieur de Google Alex Russell, pour nommer ces sites qui deviennent, peu à peu, aussi pratiques qu'une application.",
            'one_sentence_answer' => "Une PWA (application web progressive) est un site web qui s'installe et s'utilise comme une application, souvent même hors ligne, directement depuis le navigateur et sans magasin d'applications.",
            'faq' => [
                [
                    'question' => "Faut-il la télécharger dans l'App Store ou Google Play?",
                    'answer' => "Non. Une PWA s'installe directement depuis le navigateur, en quelques secondes, sans magasin d'applications ni approbation préalable. C'est l'un de ses principaux atouts, en particulier pour les petites équipes.",
                ],
                [
                    'question' => 'Une PWA fonctionne-t-elle sans connexion Internet?',
                    'answer' => "En partie, oui. Grâce au service worker, elle garde en cache de quoi s'afficher et rester utilisable hors ligne ou sur une connexion faible, selon ce que son créateur a prévu.",
                ],
                [
                    'question' => 'Quelle différence avec une application native?',
                    'answer' => "Une application native est développée pour un système précis (iOS, Android) et distribuée par son magasin; une PWA repose sur les technologies du Web, un seul code sert partout, et elle se met à jour comme un site.",
                ],
            ],
            'sources' => [
                [
                    'label' => 'Applications web progressives (PWA), documentation MDN',
                    'url' => 'https://developer.mozilla.org/fr/docs/Web/Progressive_web_apps',
                    'year' => 2026,
                    'author' => 'Contributeurs de MDN (Mozilla)',
                ],
                [
                    'label' => 'Progressive Web Apps, web.dev',
                    'url' => 'https://web.dev/explore/progressive-web-apps',
                    'year' => 2026,
                    'author' => 'Google (web.dev)',
                ],
                [
                    'label' => 'Progressive Web Apps: Escaping Tabs Without Losing Our Soul',
                    'url' => 'https://infrequently.org/2015/06/progressive-apps-escaping-tabs-without-losing-our-soul/',
                    'year' => 2015,
                    'author' => 'Alex Russell',
                ],
            ],
            'aliases' => ['PWA', 'application web progressive', 'applications web progressives', 'progressive web app', 'progressive web apps', 'web app progressive'],
            'broader_slugs' => [],
            'narrower_slugs' => [],
            'difficulty' => 'beginner',
            'icon' => '📲',
            'type' => 'explainer',
            'match_strategy' => 'case_sensitive',
        ];
    }

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! class_exists(Term::class) || ! class_exists(Category::class)) {
            echo "[glossaire] modele Term/Category absent, ignore\n";

            return;
        }

        $t = $this->term();

        if (Term::where('slug->fr_CA', $t['slug'])->exists()) {
            echo "[glossaire] slug deja present, skip : {$t['slug']}\n";

            return;
        }

        $term = new Term();

        foreach (['name', 'slug', 'definition', 'analogy', 'example', 'did_you_know', 'one_sentence_answer'] as $tf) {
            $term->setTranslations($tf, ['fr_CA' => $t[$tf], 'fr' => $t[$tf]]);
        }

        $term->acronym_full = $t['acronym_full'];
        $term->faq = $t['faq'];
        $term->sources = $t['sources'];
        $term->aliases = $t['aliases'];
        $term->broader_slugs = $t['broader_slugs'];
        $term->narrower_slugs = $t['narrower_slugs'];
        $term->difficulty = $t['difficulty'];
        $term->icon = $t['icon'];
        $term->type = $t['type'];
        $term->match_strategy = $t['match_strategy'];
        $term->dictionary_category_id = $this->resolveCategoryId($t['cat_slug'])
            ?? $this->resolveCategoryId('intelligence-artificielle');
        $term->hero_image = 'images/glossaire/'.self::NEW_SLUG.'.webp';
        $term->is_published = true;
        $term->sort_order = 1021;
        $term->save();

        echo "[glossaire] terme ajoute : {$t['slug']}\n";
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }

        Term::where('slug->fr_CA', self::NEW_SLUG)->delete();
        echo "[glossaire] terme retire : ".self::NEW_SLUG."\n";
    }
};
