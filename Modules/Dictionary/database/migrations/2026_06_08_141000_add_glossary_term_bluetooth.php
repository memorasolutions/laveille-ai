<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme « Bluetooth » au glossaire (cat 2 « Concepts fondamentaux »).
 * Dérivés en aliases (Bluetooth Low Energy / BLE / Bluetooth LE) — auto-link.
 * Image via le compte Gemini de l'utilisateur (Playwright). Sources vérifiées 200.
 * Anti-doublon par slug. RÉVERSIBLE (down()).
 */
return new class extends Migration
{
    private function terms(): array
    {
        return [
            [
                'slug' => 'bluetooth',
                'name' => 'Bluetooth',
                'cat' => 2, 'type' => 'concept', 'difficulty' => 'beginner', 'icon' => '🔵',
                'aliases' => ['Bluetooth Low Energy', 'BLE', 'Bluetooth LE', 'bluetooth'],
                'definition' => "Le Bluetooth est une technologie de communication sans fil à courte portée qui permet à des appareils électroniques d'échanger des données et de l'audio sans câble. Elle fonctionne dans la bande radio des 2,4 GHz dite ISM (industrielle, scientifique et médicale), libre d'utilisation partout dans le monde, et emploie un saut de fréquence rapide (frequency-hopping) pour limiter les interférences avec les autres appareils qui partagent cette bande (Wi-Fi, fours à micro-ondes, etc.). Sa portée typique est d'une dizaine de mètres pour les appareils grand public, et peut atteindre plusieurs dizaines de mètres selon la puissance d'émission et l'environnement. Conçu par l'équipementier suédois Ericsson dans les années 1990, le Bluetooth est aujourd'hui géré par le Bluetooth SIG (Special Interest Group), un consortium qui réunit des milliers d'entreprises et publie les versions successives de la norme. Pour relier deux appareils, on procède à un appairage (pairing) : une première mise en relation, parfois confirmée par un code, après laquelle ils se reconnaissent automatiquement. On distingue deux grandes familles : le Bluetooth « classique » (BR/EDR), adapté aux flux continus comme l'audio des écouteurs et des enceintes, et le Bluetooth à basse consommation (BLE, Bluetooth Low Energy), introduit avec la version 4.0 en 2010, optimisé pour les petits objets qui envoient peu de données mais doivent durer des mois sur une pile — capteurs, montres, balises, objets connectés (IoT). On retrouve le Bluetooth partout : écouteurs et casques sans fil, claviers et souris, enceintes, montres connectées, automobiles, balises de localisation et la grande majorité des objets connectés domestiques.",
                'analogy' => "Le Bluetooth, c'est comme une poignée de main discrète entre deux appareils proches : une fois qu'ils se sont présentés (l'appairage), ils peuvent se parler à voix basse, sans fil, tant qu'ils restent à portée — environ la taille d'une pièce — sans déranger les autres conversations autour.",
                'example' => "Quand vous sortez vos écouteurs sans fil de leur boîtier et qu'ils se connectent tout seuls à votre téléphone, c'est le Bluetooth : la première fois, vous les avez « appairés » (parfois en confirmant un code) ; ensuite, dès qu'ils sont à portée — une dizaine de mètres — la connexion se rétablit automatiquement.",
                'did_you_know' => "Le nom « Bluetooth » rend hommage au roi viking Harald Iᵉʳ de Danemark, surnommé Blåtand (« à la dent bleue »), qui unifia au Xᵉ siècle les tribus danoises et norvégiennes — une métaphore de la technologie censée « unir » les appareils entre eux. Le logo lui-même combine ses initiales en alphabet runique : ᚼ (Hagall, H) et ᛒ (Bjarkan, B).",
                'one_sentence_answer' => "Le Bluetooth est une technologie sans fil à courte portée (bande 2,4 GHz) qui relie des appareils proches — écouteurs, claviers, objets connectés — après un appairage, avec une variante basse consommation (BLE) pour l'IoT.",
                'faq' => [
                    ['question' => "Quelle différence entre Bluetooth classique et Bluetooth Low Energy (BLE) ?", 'answer' => "Le Bluetooth classique (BR/EDR) est conçu pour des flux continus et gourmands comme l'audio des écouteurs ou des enceintes. Le Bluetooth Low Energy (BLE), introduit avec la version 4.0 en 2010, est optimisé pour les petits objets qui envoient peu de données mais doivent fonctionner très longtemps sur une pile — capteurs, montres, balises et objets connectés."],
                    ['question' => "Quelle est la portée du Bluetooth ?", 'answer' => "Pour la plupart des appareils grand public (classe 2), la portée est d'environ 10 mètres. Certains appareils plus puissants (classe 1) atteignent plusieurs dizaines de mètres, mais la portée réelle dépend des obstacles, des murs et des interférences radio environnantes."],
                ],
                'sources' => [
                    ['label' => "Wikipédia — Bluetooth", 'url' => "https://fr.wikipedia.org/wiki/Bluetooth"],
                    ['label' => "Wikipédia — Bluetooth Low Energy (BLE)", 'url' => "https://fr.wikipedia.org/wiki/Bluetooth_Low_Energy"],
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
