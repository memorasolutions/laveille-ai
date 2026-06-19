<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme « Firmware » (micrologiciel) au glossaire (cat 2 « Concepts fondamentaux »).
 * Dérivés/synonymes FR gérés en aliases (micrologiciel, microprogramme) — auto-link.
 * Image via le compte Gemini de l'utilisateur (Playwright). Sources vérifiées 200.
 * Anti-doublon par slug. RÉVERSIBLE (down()).
 */
return new class extends Migration
{
    private function terms(): array
    {
        return [
            [
                'slug' => 'firmware',
                'name' => 'Firmware (micrologiciel)',
                'cat' => 2, 'type' => 'concept', 'difficulty' => 'intermediate', 'icon' => '⚙️',
                'aliases' => ['micrologiciel', 'Micrologiciel', 'microprogramme', 'Microprogramme', 'firmwares', 'Firmware'],
                'definition' => "Le firmware — en français micrologiciel (terme recommandé par l'Office québécois de la langue française) ou microprogramme — est un logiciel de très bas niveau intégré directement dans un appareil électronique pour en piloter le matériel. Contrairement à une application classique, il n'est pas installé sur un disque par l'utilisateur : il est gravé dans une mémoire non volatile (ROM, EEPROM ou, le plus souvent depuis le milieu des années 1990, mémoire flash) qui conserve son contenu même hors tension. Le firmware occupe ainsi une position intermédiaire entre le matériel (hardware, les composants physiques) et le logiciel (software, les programmes et le système d'exploitation) : il fait le pont entre les deux en exposant les fonctions de base que le reste du système peut utiliser. On en trouve partout — carte mère d'ordinateur (BIOS puis UEFI), routeur Wi-Fi, imprimante, disque SSD, téléviseur, montre connectée, automobile, objet connecté (IoT). Dès la mise sous tension, c'est le firmware qui s'exécute en premier : il initialise les composants, vérifie le matériel, puis cède la main au système d'exploitation. Un firmware spécialisé appelé bootloader (chargeur d'amorçage) gère précisément cette étape de démarrage et le chargement de l'OS ou de l'application principale. Parce qu'il est aujourd'hui stocké en mémoire flash, le firmware peut être mis à jour sans remplacer la puce : c'est l'opération de « flashage » ou mise à jour du micrologiciel (firmware update), parfois diffusée à distance par OTA (over-the-air) pour les téléphones et les objets connectés. Ces mises à jour corrigent des bogues, ajoutent des fonctions ou comblent des failles ; un firmware obsolète ou compromis est d'ailleurs une cible privilégiée des cyberattaques, car il s'exécute sous le système d'exploitation, avant toute protection logicielle.",
                'analogy' => "Le firmware est comme l'instinct d'un être vivant : ce n'est ni un organe (le matériel) ni une pensée apprise (les applications), mais l'ensemble des réflexes câblés qui font fonctionner le corps dès la naissance — respirer, faire battre le cœur — sans qu'on ait à y penser. De même, dès qu'on allume un appareil, son firmware sait déjà comment animer ses composants.",
                'example' => "Quand le voyant de votre routeur Wi-Fi clignote après que vous avez cliqué sur « Mettre à jour le micrologiciel » dans son interface, vous flashez son firmware : la puce mémoire flash du routeur reçoit une nouvelle version du logiciel embarqué qui pilote ses antennes et ses ports, souvent pour corriger une faille de sécurité. Sur un PC, appuyer sur Suppr ou F2 au démarrage ouvre justement le réglage du firmware UEFI de la carte mère.",
                'did_you_know' => "Le mot « firmware » a été forgé en 1967 par l'ingénieur Ascher Opler dans la revue Datamation pour désigner un état « ferme » (firm), intermédiaire entre le matériel (hard) et le logiciel (soft). À l'origine, il ne désignait que le microcode figé en mémoire morte ; son sens s'est élargi avec l'arrivée des mémoires flash réinscriptibles, qui ont rendu les micrologiciels modifiables.",
                'one_sentence_answer' => "Le firmware (micrologiciel) est un logiciel de bas niveau gravé dans la mémoire non volatile d'un appareil pour piloter son matériel dès l'allumage, à mi-chemin entre le matériel et le logiciel.",
                'faq' => [
                    ['question' => "Quelle différence entre firmware, logiciel et matériel ?", 'answer' => "Le matériel (hardware) désigne les composants physiques ; le logiciel (software) les programmes et le système d'exploitation, installés et facilement modifiables ; le firmware est un logiciel spécial intégré au matériel, stocké dans une mémoire non volatile, qui fait le lien entre les deux et pilote les fonctions de base de l'appareil dès son démarrage."],
                    ['question' => "Faut-il mettre à jour le firmware de ses appareils ?", 'answer' => "Oui. Les mises à jour de firmware (firmware update, parfois OTA à distance) corrigent des bogues, améliorent les performances et surtout comblent des failles de sécurité. Comme le firmware s'exécute avant le système d'exploitation, un micrologiciel obsolète est une cible de choix pour les attaques : il faut l'installer depuis la source officielle du fabricant."],
                ],
                'sources' => [
                    ['label' => "Wikipédia — Micrologiciel (firmware)", 'url' => "https://fr.wikipedia.org/wiki/Micrologiciel"],
                    ['label' => "Wikipédia — UEFI (firmware de carte mère)", 'url' => "https://fr.wikipedia.org/wiki/UEFI"],
                ],
            ],
        ];
    }

    public function up(): void
    {
        if (! class_exists(Term::class)) {
            echo "[glossaire] modèle Term absent — ignoré\n";
            return;
        }

        // Cette migration insère des données avec des FK vers dictionary_categories
        // qui n'existent que sur MySQL (seedées en prod). SQLite en tests = skip.
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->terms() as $t) {
            if (Term::where('slug->fr_CA', $t['slug'])->exists()) {
                echo "[glossaire] slug déjà présent, skip : {$t['slug']}\n";
                continue;
            }
            $term = new Term();
            foreach (['name', 'slug', 'definition', 'analogy', 'example', 'did_you_know', 'one_sentence_answer'] as $tf) {
                $term->setTranslations($tf, ['fr_CA' => $t[$tf], 'fr' => $t[$tf]]);
            }
            $term->faq = $t['faq'];
            $term->sources = $t['sources'];
            $term->aliases = $t['aliases'];
            $term->difficulty = $t['difficulty'];
            $term->icon = $t['icon'];
            $term->type = $t['type'];
            $term->dictionary_category_id = $t['cat'];
            $term->hero_image = 'images/glossaire/'.$t['slug'].'.webp';
            $term->is_published = true;
            $term->match_strategy = 'loose';
            $term->save();
            echo "[glossaire] inséré : {$t['slug']} (id={$term->id})\n";
        }
    }

    public function down(): void
    {
        if (! class_exists(Term::class)) {
            return;
        }
        foreach ($this->terms() as $t) {
            Term::where('slug->fr_CA', $t['slug'])->delete();
        }
    }
};
