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
 * Ajout du terme « CGNAT (Carrier-Grade NAT) », technique de partage d'adresses IPv4 par les
 * fournisseurs d'acces a Internet.
 *
 * CONTROLE ANTI-DOUBLON (2026-10-07) : aucun slug du glossaire ne porte la notion (balayage du
 * sitemap de production, famille de motifs cgnat|carrier|grade|nat|lsn|nat444 : 0 vrai resultat,
 * les seuls grep sur « nat » etaient halluci-NAT-ion / ordi-NAT-eur). Aucun terme parent reseau
 * n'existe (ni NAT, ni IPv4, ni adresse IP) : broader_slugs/narrower_slugs restent vides plutot
 * que de fabriquer une parente fausse. La fiche « vpn » existe et est nommee dans la FAQ, ou
 * l'auto-lien la relie (le VPN est une voie de contournement du CGNAT, pas un parent).
 *
 * VALIDATION CROISEE DES FAITS (2 sources independantes concordantes) : Perplexity sonar-pro
 * (famille Perplexity, repli documente car pp_search tombait en defi Cloudflare) + Codex (recherche
 * web, 0 token Anthropic). Codex a apporte deux garde-fous retenus : (a) NE PAS ecrire « tous les
 * RIR sont epuises » (le RIPE NCC redistribue encore des adresses recuperees) ; (b) le RFC 6598 est
 * coecrit par un ingenieur de l'operateur canadien Rogers (fait verifiable, angle QC/Canada honnete),
 * sans pretendre a un taux de deploiement canadien precis (non prouve).
 *
 * ALIAS : « CGNAT », « Carrier-Grade NAT », « Carrier Grade NAT », « NAT444 », « Large Scale NAT »,
 * « Large-Scale NAT ». Le mot nu « NAT » et l'acronyme ambigu « LSN » sont VOLONTAIREMENT absents
 * (risque de faux liens). « CGNAT » est un acronyme, donc match_strategy = case_sensitive.
 *
 * Typographie quebecoise (OQLF) : espace insecable U+00A0 devant chaque deux-points, aucune espace
 * avant ; ! ?, guillemets droits, aucun tiret cadratin.
 *
 * Migration idempotente (slug deja present = ignore). down() retire seulement ce terme.
 */
return new class extends Migration
{
    private const NEW_SLUG = 'cgnat';

    private function resolveCategoryId(string $slug): ?int
    {
        return Category::where('slug->fr_CA', $slug)->value('id')
            ?? Category::where('slug->fr', $slug)->value('id');
    }

    private function term(): array
    {
        return [
            'name' => 'CGNAT (Carrier-Grade NAT)',
            'slug' => self::NEW_SLUG,
            'cat_slug' => 'outils-et-techniques',
            'acronym_full' => 'Carrier-Grade Network Address Translation',
            'definition' => "Le CGNAT (Carrier-Grade NAT, ou traduction d'adresses de classe opérateur) est une technique par laquelle un fournisseur d'accès à Internet (FAI) fait partager une même adresse IPv4 publique à de nombreux abonnés, parfois des centaines. Le routeur du domicile effectue déjà une première traduction d'adresses; l'équipement du fournisseur en ajoute une seconde, d'où le nom de double NAT ou NAT444. Pour relier ses équipements à ceux des abonnés, le fournisseur puise souvent dans la plage 100.64.0.0/10, réservée à cet usage par le RFC 6598. Le but est d'étirer un stock d'adresses IPv4 devenu rare, sans en acheter de nouvelles. Le CGNAT ne crée pourtant aucune adresse et ne remplace pas IPv6 : il prolonge la vie d'IPv4. Son revers principal est qu'un abonné placé derrière lui ne peut généralement pas recevoir de connexion entrante non sollicitée, ce qui complique l'auto-hébergement, certains jeux en ligne et la voix sur IP.",
            'analogy' => "Comme un immeuble dont tous les locataires partagent une seule adresse postale : le courrier sort sans peine, mais une lettre entrante ne sait à quelle porte frapper.",
            'example' => "Beaucoup de forfaits Internet mobiles, et certains forfaits fixes, placent l'abonné derrière un CGNAT : une caméra ou un petit serveur installé à la maison devient alors injoignable depuis l'extérieur, et la redirection de port configurée sur le routeur n'y change rien.",
            'did_you_know' => "Le RFC 6598, qui réserve la plage d'adresses du CGNAT, a été coécrit en 2012 par un ingénieur de l'opérateur canadien Rogers : le partage d'adresses IPv4 se préparait déjà chez nous.",
            'one_sentence_answer' => "Le CGNAT est une technique par laquelle un fournisseur d'accès fait partager une même adresse IPv4 publique à de nombreux abonnés, pour économiser des adresses devenues rares, au prix de connexions entrantes le plus souvent bloquées.",
            'faq' => [
                [
                    'question' => 'Comment savoir si je suis derrière un CGNAT?',
                    'answer' => "Comparez l'adresse IP publique affichée par votre routeur à celle qu'un site de vérification d'adresse IP vous montre. Si elles diffèrent, ou si l'adresse du routeur se situe entre 100.64.0.0 et 100.127.255.255, vous êtes probablement derrière un CGNAT. Dans le doute, votre fournisseur peut le confirmer.",
                ],
                [
                    'question' => 'Le CGNAT met-il ma sécurité ou ma vie privée en cause?',
                    'answer' => "Ce n'est pas son but. Il complique surtout l'attribution : comme plusieurs abonnés partagent une même adresse, une adresse IP et une heure ne suffisent plus à désigner une personne, ce qui a des conséquences pour les enquêtes. La géolocalisation par adresse IP devient aussi moins précise, puisqu'elle pointe l'équipement du fournisseur plutôt que votre domicile.",
                ],
                [
                    'question' => 'Comment contourner un CGNAT pour héberger un service chez soi?',
                    'answer' => "Les voies courantes sont de demander au fournisseur une adresse IPv4 publique dédiée (parfois payante), d'activer IPv6 lorsque le service le permet, ou de passer par un relais : réseau privé virtuel (VPN), tunnel inverse ou service d'exposition. Aucune redirection de port sur le routeur domestique ne suffit à elle seule.",
                ],
            ],
            'sources' => [
                [
                    'label' => 'RFC 6598, IANA-Reserved IPv4 Prefix for Shared Address Space',
                    'url' => 'https://datatracker.ietf.org/doc/html/rfc6598',
                    'year' => 2012,
                    'author' => 'J. Weil, V. Kuarsingh (Rogers), C. Donley, C. Liljenstolpe et M. Azinger (IETF)',
                ],
                [
                    'label' => 'RFC 6888, Common Requirements for Carrier-Grade NATs (CGNs)',
                    'url' => 'https://datatracker.ietf.org/doc/html/rfc6888',
                    'year' => 2013,
                    'author' => 'S. Perreault et coll. (IETF)',
                ],
                [
                    'label' => "RFC 6269, Issues with IP Address Sharing",
                    'url' => 'https://datatracker.ietf.org/doc/html/rfc6269',
                    'year' => 2011,
                    'author' => 'M. Ford et coll. (IETF)',
                ],
            ],
            'aliases' => ['CGNAT', 'Carrier-Grade NAT', 'Carrier Grade NAT', 'NAT444', 'Large Scale NAT', 'Large-Scale NAT'],
            'broader_slugs' => [],
            'narrower_slugs' => [],
            'difficulty' => 'advanced',
            'icon' => '🔀',
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
        $term->sort_order = 1020;
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
