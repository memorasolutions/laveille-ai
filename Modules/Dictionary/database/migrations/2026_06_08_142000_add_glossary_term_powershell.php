<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Dictionary\Models\Term;

/**
 * Ajout du terme « PowerShell » au glossaire (cat 5 « Outils », type outil).
 * Dérivés en aliases (pwsh, PowerShell Core, PowerShell 7, Windows PowerShell) — auto-link.
 * Image via le compte Gemini de l'utilisateur (Playwright). Sources vérifiées 200.
 * Anti-doublon par slug. RÉVERSIBLE (down()).
 */
return new class extends Migration
{
    private function terms(): array
    {
        return [
            [
                'slug' => 'powershell',
                'name' => 'PowerShell',
                'cat' => 5, 'type' => 'outil', 'difficulty' => 'intermediate', 'icon' => '🖥️',
                'aliases' => ['pwsh', 'PowerShell Core', 'PowerShell 7', 'Windows PowerShell', 'powershell'],
                'definition' => "PowerShell est à la fois un interpréteur de commandes (shell) et un langage de script créé par Microsoft pour automatiser l'administration des systèmes. Sa première version, Windows PowerShell 1.0, est sortie en novembre 2006 et reposait sur le .NET Framework. Sa grande originalité, par rapport aux shells classiques (comme l'invite de commandes Windows ou bash sous Linux), est d'être orienté objet : là où les shells traditionnels font circuler du texte brut d'une commande à l'autre, le pipeline de PowerShell transmet de véritables objets .NET, c'est-à-dire des données structurées dotées de propriétés et de méthodes. On peut ainsi enchaîner des commandes qui se passent des objets riches, plutôt que de devoir « découper » du texte à chaque étape. Les commandes natives s'appellent des cmdlets et suivent une convention de nommage très lisible, Verbe-Nom : Get-Process (lister les processus), Get-ChildItem (lister le contenu d'un dossier), Stop-Service, Set-Item… Ce vocabulaire régulier rend les commandes faciles à deviner et à mémoriser. En 2016 (à partir du 18 août), Microsoft a ouvert le code et lancé PowerShell Core, une version open source et multiplateforme reposant sur .NET Core, capable de tourner non seulement sous Windows, mais aussi sous macOS et Linux. Cette lignée est aujourd'hui simplement appelée PowerShell 7 ; son exécutable est pwsh (la version historique restant powershell.exe sous Windows). Les scripts PowerShell portent l'extension .ps1. Très utilisé par les administrateurs systèmes et les équipes DevOps, PowerShell sert à automatiser des tâches répétitives, à configurer des serveurs, à piloter des services infonuagiques (Azure, Microsoft 365) et à orchestrer des déploiements.",
                'analogy' => "Si l'invite de commandes classique est comme un employé à qui l'on transmet des notes sur papier (du texte qu'il faut relire et redécouper à chaque étape), PowerShell est comme une chaîne de montage où chaque poste se passe des pièces déjà assemblées (des objets) : l'étape suivante reçoit directement des données structurées, sans avoir à les réanalyser.",
                'example' => "Pour lister les dix processus qui consomment le plus de mémoire sur un PC Windows, une seule ligne suffit : « Get-Process | Sort-Object WorkingSet -Descending | Select-Object -First 10 ». Chaque cmdlet passe à la suivante des objets « processus » complets (avec leur nom, leur mémoire, leur identifiant), et non du texte à analyser.",
                'did_you_know' => "Contrairement à la plupart des shells, PowerShell ne fait pas circuler du texte mais des objets : quand vous écrivez « Get-Process | Where-Object CPU -gt 100 », le second cmdlet reçoit de vrais objets « processus » et lit directement leur propriété CPU — il n'a aucun texte à analyser ni à découper, ce qui élimine une source d'erreurs typique des scripts shell traditionnels.",
                'one_sentence_answer' => "PowerShell est le shell et langage de script orienté objet de Microsoft (depuis 2006), dont le pipeline fait circuler des objets .NET ; sa version moderne PowerShell 7 (exécutable pwsh) est open source et multiplateforme.",
                'faq' => [
                    ['question' => "En quoi PowerShell diffère-t-il de l'invite de commandes Windows (cmd) ?", 'answer' => "L'invite de commandes (cmd) manipule du texte : chaque commande produit et reçoit des chaînes de caractères qu'il faut souvent analyser à la main. PowerShell, lui, est orienté objet : son pipeline transmet des objets .NET structurés (avec propriétés et méthodes), ce qui rend les scripts plus puissants, plus lisibles et moins sujets aux erreurs. Il offre aussi un langage de script complet et des milliers de cmdlets."],
                    ['question' => "PowerShell fonctionne-t-il ailleurs que sous Windows ?", 'answer' => "Oui. Depuis 2016, PowerShell Core — aujourd'hui PowerShell 7 — est open source, basé sur .NET Core et multiplateforme : il s'exécute sous Windows, macOS et Linux via l'exécutable pwsh. La version historique « Windows PowerShell » (powershell.exe) reste, elle, propre à Windows."],
                ],
                'sources' => [
                    ['label' => "Wikipédia — PowerShell", 'url' => "https://fr.wikipedia.org/wiki/PowerShell"],
                    ['label' => "Microsoft Learn — Présentation de PowerShell", 'url' => "https://learn.microsoft.com/fr-fr/powershell/scripting/overview"],
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
