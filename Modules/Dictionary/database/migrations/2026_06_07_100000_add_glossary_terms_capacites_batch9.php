<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Ajout de 3 termes « capacités IA 2026 » au glossaire (batch #9) :
 * Computer use (cat 1 « IA »), Deep research (cat 1 « IA »), Instruction tuning (cat 2 « Concepts fondamentaux »).
 * Images via le compte Gemini de l'utilisateur. Standard complet, sources vérifiées 200.
 * Anti-doublon par slug. RÉVERSIBLE (down()).
 */
return new class extends Migration
{
    private function terms(): array
    {
        return [
            [
                'slug' => 'computer-use',
                'name' => 'Computer use (usage de l\'ordinateur)',
                'cat' => 1, 'type' => 'technique', 'difficulty' => 'intermediate', 'icon' => '🖱️',
                'definition' => "« Computer use » (usage de l'ordinateur) désigne la capacité d'un agent IA à se servir d'un ordinateur comme le ferait un humain : voir l'écran, déplacer le curseur, cliquer, taper au clavier et passer d'une application à l'autre pour accomplir des tâches dans des interfaces graphiques existantes — sans passer par des API applicatives dédiées. On parle aussi de « Computer-Using Agent » (CUA). Concrètement, l'IA reçoit des captures d'écran ou une vue de la fenêtre, interprète visuellement ce qui est affiché (boutons, champs, menus), puis agit via une couche qui simule clavier et souris, en enchaînant les actions jusqu'à ce que la tâche soit terminée ou qu'une intervention humaine soit requise. C'est une capacité « agentique » : le système poursuit un objectif, planifie plusieurs étapes et les exécute de façon autonome, plutôt que de simplement répondre par du texte. Anthropic a introduit cette fonctionnalité avec Claude 3.5 Sonnet (« computer use ») fin 2024, et OpenAI avec « Operator ». L'intérêt : automatiser des tâches dans n'importe quel logiciel, même sans intégration technique. Les limites : fiabilité encore imparfaite, lenteur et enjeux de sécurité (un agent qui contrôle l'écran peut faire des actions non désirées).",
                'analogy' => "C'est comme confier ta souris et ton clavier à un assistant : au lieu de lui donner une « prise » spéciale (une API), il regarde ton écran et l'utilise exactement comme toi — il clique, tape et navigue à ta place.",
                'example' => "On demande à l'agent : « Réserve une salle de réunion pour demain 14 h ». Il ouvre le navigateur, voit le calendrier à l'écran, clique sur le bon créneau, remplit le formulaire et confirme — uniquement en regardant l'écran et en manipulant curseur et clavier.",
                'did_you_know' => "Le « computer use » n'a pas besoin d'API : l'agent voit littéralement l'écran et bouge la souris, ce qui lui permet d'utiliser des logiciels qui n'offrent aucune intégration — au prix d'une fiabilité encore variable et d'enjeux de sécurité.",
                'one_sentence_answer' => "Le « computer use » est la capacité d'un agent IA à contrôler un ordinateur comme un humain (voir l'écran, cliquer, taper) pour accomplir des tâches dans des logiciels existants.",
                'faq' => [
                    ['question' => "Quelle différence entre computer use et un appel d'API ?", 'answer' => "Un appel d'API passe par une interface technique prévue à cet effet ; le computer use n'en a pas besoin : l'agent voit l'écran et manipule souris et clavier comme un humain, ce qui marche même sans intégration."],
                    ['question' => "Quels sont les risques du computer use ?", 'answer' => "Fiabilité encore imparfaite, lenteur, et surtout sécurité : un agent qui contrôle l'écran peut cliquer au mauvais endroit ou réaliser des actions non désirées — d'où l'importance d'une supervision."],
                ],
                'sources' => [
                    ['label' => "Anthropic — Claude 3.5 models and computer use", 'url' => "https://www.anthropic.com/news/3-5-models-and-computer-use"],
                    ['label' => "Anthropic — Computer use (documentation)", 'url' => "https://docs.anthropic.com/en/docs/build-with-claude/computer-use"],
                ],
            ],
            [
                'slug' => 'deep-research',
                'name' => 'Deep research (recherche approfondie)',
                'cat' => 1, 'type' => 'technique', 'difficulty' => 'intermediate', 'icon' => '🔬',
                'definition' => "Le « deep research » (recherche approfondie) est un mode agentique de l'IA qui mène, de façon autonome, une recherche en plusieurs étapes : il combine le raisonnement, une exploration itérative du web (de nombreuses sources successives) et la synthèse de l'information dans une boucle dynamique, pour produire un rapport structuré et cité sur une question complexe. OpenAI l'a défini comme « un agent qui utilise le raisonnement pour synthétiser de grandes quantités d'informations en ligne et accomplir des tâches de recherche multi-étapes ». Il se distingue nettement des paradigmes précédents : un moteur de recherche classique renvoie une liste de liens en quelques secondes ; une recherche augmentée (type Perplexity) renvoie un résumé cité en quelques secondes à minutes ; le deep research, lui, peut travailler plusieurs minutes — voire davantage — en planifiant ses recherches, en lisant et recoupant des dizaines de sources, puis en rédigeant un document de synthèse référencé. OpenAI, Google (Gemini) et Perplexity proposent tous un mode « Deep Research » depuis 2025. C'est un outil puissant pour les revues de littérature, les analyses de marché ou la veille — à condition de vérifier les sources, car l'agent peut encore se tromper.",
                'analogy' => "C'est la différence entre chercher un mot dans un dictionnaire (recherche classique) et confier un mini-mémoire à un assistant de recherche : il va lire des dizaines de sources, recouper, et te rendre un rapport structuré et sourcé.",
                'example' => "À la demande « Fais-moi un état des lieux de l'adoption de l'IA par les PME québécoises », un mode deep research planifie ses recherches, consulte une trentaine de sources, recoupe les chiffres et rédige un rapport de plusieurs pages avec citations — en quelques minutes.",
                'did_you_know' => "Là où une recherche classique prend quelques secondes, un mode « deep research » peut travailler plusieurs minutes : il échange la rapidité contre la profondeur, en menant une vraie enquête multi-étapes plutôt qu'une simple requête.",
                'one_sentence_answer' => "Le deep research est un mode agentique qui mène une recherche web autonome en plusieurs étapes et produit un rapport structuré et cité sur une question complexe.",
                'faq' => [
                    ['question' => "Quelle différence avec une recherche normale ?", 'answer' => "Une recherche classique renvoie des liens en quelques secondes ; le deep research raisonne, explore et recoupe des dizaines de sources sur plusieurs minutes, puis rédige un rapport synthétisé et cité."],
                    ['question' => "Peut-on faire confiance à un rapport de deep research ?", 'answer' => "C'est un excellent point de départ, mais l'agent peut encore se tromper ou mal interpréter une source : la vérification des citations reste indispensable."],
                ],
                'sources' => [
                    ['label' => "Wikipédia — Deep research", 'url' => "https://en.wikipedia.org/wiki/Deep_research"],
                    ['label' => "Glukhov — Search vs Deep Search vs Deep Research", 'url' => "https://www.glukhov.org/rag/architecture/search-vs-deepsearch-vs-deep-research/"],
                ],
            ],
            [
                'slug' => 'instruction-tuning',
                'name' => 'Instruction tuning (ajustement par instructions)',
                'cat' => 2, 'type' => 'technique', 'difficulty' => 'advanced', 'icon' => '🎚️',
                'definition' => "L'instruction tuning (ajustement par instructions) est une forme d'apprentissage supervisé (fine-tuning) où l'on poursuit l'entraînement d'un modèle de langage déjà pré-entraîné sur un jeu de données de paires « instruction → réponse », afin qu'il apprenne à suivre des consignes en langage naturel de façon utile et cohérente. Chaque exemple montre explicitement comment répondre à une instruction (« Résume ce texte », « Traduis en français », « Explique comme à un enfant de 5 ans »), plutôt que de simplement continuer un texte. C'est cette étape qui est largement responsable du comportement « assistant de chat » moderne : comprendre des requêtes variées, rester dans le cadre de la consigne et gérer plusieurs tâches. Elle se distingue du pré-entraînement, auto-supervisé sur d'immenses corpus de texte bruts, qui apprend la langue et les connaissances générales mais sans objectif de « suivre des instructions ». Elle se distingue aussi du fine-tuning « classique » (spécialisation sur une seule tâche) : l'instruction tuning entraîne sur une grande variété de tâches formulées comme des instructions, ce qui rend le modèle polyvalent et capable de généraliser. Enfin, elle précède souvent le RLHF (apprentissage par renforcement à partir de retours humains), avec lequel elle est complémentaire dans la chaîne d'alignement.",
                'analogy' => "Le pré-entraînement apprend au modèle la langue et la culture générale (comme des années de lecture) ; l'instruction tuning lui apprend les bonnes manières de la conversation : écouter la consigne et y répondre utilement, plutôt que de continuer à parler tout seul.",
                'example' => "Un modèle de base « sait » beaucoup de choses sur le hockey, mais répond de façon verbeuse et hors format. Après instruction tuning sur des milliers d'exemples, il sait répondre à « Donne 3 points clés sur la LNH » par une liste concise de trois éléments.",
                'did_you_know' => "C'est l'instruction tuning, plus que la taille du modèle, qui a transformé les « modèles de base » bruts en assistants utilisables : sans cette étape, un LLM aurait tendance à « compléter » votre phrase plutôt qu'à répondre à votre demande.",
                'one_sentence_answer' => "L'instruction tuning est un fine-tuning supervisé sur des paires instruction-réponse qui apprend à un LLM à suivre des consignes en langage naturel.",
                'faq' => [
                    ['question' => "Quelle différence entre instruction tuning et pré-entraînement ?", 'answer' => "Le pré-entraînement apprend la langue et les connaissances générales sur du texte brut ; l'instruction tuning, sur un jeu plus petit de paires instruction-réponse, apprend au modèle à interpréter une consigne comme une tâche à exécuter."],
                    ['question' => "Instruction tuning et RLHF, est-ce la même chose ?", 'answer' => "Non : ce sont deux étapes complémentaires de l'alignement. L'instruction tuning est un apprentissage supervisé sur des réponses correctes ; le RLHF affine ensuite le comportement à partir de préférences humaines."],
                ],
                'sources' => [
                    ['label' => "IBM — Instruction tuning", 'url' => "https://www.ibm.com/think/topics/instruction-tuning"],
                    ['label' => "arXiv — Instruction Tuning for Large Language Models: A Survey (2023)", 'url' => "https://arxiv.org/abs/2308.10792"],
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
