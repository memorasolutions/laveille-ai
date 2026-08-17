<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Correction de données réversible - mise en ligne publique de /livres (2026-08-17).
 *
 * Contexte (LPC art. 219 - représentation trompeuse) : un prix affiché sur le site, figé au
 * moment de la rédaction, devient périmé dès qu'Amazon change le sien - contrairement à l'index
 * et à la fiche livre (gabarits Blade, corrigés dans le même lot), les FAQ ci-dessous sont du
 * texte rédactionnel stocké en base (colonne JSON `faq`) : elles ne peuvent pas être corrigées
 * par un simple changement de gabarit, d'où cette migration de données.
 *
 * Portée : reformule les questions/réponses de FAQ qui citent un prix en dollars, pour les 5
 * livres du catalogue. Ne touche à AUCUNE autre donnée (colonnes price_paperback/price_kindle
 * conservées telles quelles - ce sont des données internes, seul l'affichage/texte public change).
 *
 * Réversible : down() restaure le texte original mot pour mot (rollback uniquement - ne jamais
 * relancer le prix en production après un rollback sans revalider le prix courant sur Amazon).
 */
return new class extends Migration
{
    /**
     * @return array<int, array{slug: string, question_before: string, question_after: string, answer_before: string, answer_after: string}>
     */
    private function corrections(): array
    {
        return [
            [
                'slug' => 'ia-sans-se-faire-poursuivre',
                'question_before' => 'Quels sont les formats disponibles pour ce livre ?',
                'question_after' => 'Quels sont les formats disponibles pour ce livre ?',
                'answer_before' => "Le livre est disponible en version brochée à 44,99 \$ CAD et en version Kindle à 29,99 \$ CAD, éligible à Kindle Unlimited.",
                'answer_after' => "Le livre est disponible en version brochée et en version Kindle (éligible à Kindle Unlimited) sur Amazon. Le prix à jour est affiché directement sur la fiche produit.",
            ],
            [
                'slug' => 'ia-pour-les-parents',
                'question_before' => 'Quels sont les formats et prix disponibles ?',
                'question_after' => 'Quels sont les formats disponibles ?',
                'answer_before' => "Le livre est disponible en version brochée à 24,99 \$ CAD et en version Kindle à 9,99 \$ CAD. Publié le 2 juillet 2026.",
                'answer_after' => "Le livre est disponible en version brochée et en version Kindle sur Amazon (prix à jour affiché sur la fiche produit). Publié le 2 juillet 2026.",
            ],
            [
                'slug' => 'nexus-neural-tome-1',
                'question_before' => 'Quel est le format et le prix du tome 1 ?',
                'question_after' => 'Quel est le format du tome 1 ?',
                'answer_before' => "Broché de 190 pages à 13,69 \$ CAD, disponible en format Kindle à 1,35 \$ CAD. Publié le 20 février 2026.",
                'answer_after' => "Broché de 190 pages, aussi disponible en format Kindle, sur Amazon (prix à jour affiché sur la fiche produit). Publié le 20 février 2026.",
            ],
            [
                'slug' => 'nexus-neural-tome-2',
                'question_before' => 'Quel est le format et le prix du tome 2 ?',
                'question_after' => 'Quel est le format du tome 2 ?',
                'answer_before' => "Broché de 190 pages à 13,69 \$ CAD, disponible en format Kindle à 6,82 \$ CAD.",
                'answer_after' => "Broché de 190 pages, aussi disponible en format Kindle, sur Amazon (prix à jour affiché sur la fiche produit).",
            ],
            [
                'slug' => 'nexus-neural-tome-3',
                'question_before' => 'Quel est le format et le prix du tome 3 ?',
                'question_after' => 'Quel est le format du tome 3 ?',
                'answer_before' => "Broché de 190 pages à 13,69 \$ CAD, disponible en format Kindle à 6,82 \$ CAD.",
                'answer_after' => "Broché de 190 pages, aussi disponible en format Kindle, sur Amazon (prix à jour affiché sur la fiche produit).",
            ],
        ];
    }

    public function up(): void
    {
        $this->apply(matchKey: 'question_before', questionKey: 'question_after', answerKey: 'answer_after');
    }

    public function down(): void
    {
        $this->apply(matchKey: 'question_after', questionKey: 'question_before', answerKey: 'answer_before');
    }

    private function apply(string $matchKey, string $questionKey, string $answerKey): void
    {
        foreach ($this->corrections() as $row) {
            $book = DB::table('books')->where('slug', $row['slug'])->first();

            if (! $book || blank($book->faq)) {
                continue;
            }

            $faq = json_decode((string) $book->faq, true);

            if (! is_array($faq)) {
                continue;
            }

            $changed = false;

            foreach ($faq as $index => $qa) {
                if (! is_array($qa) || ($qa['question'] ?? null) !== $row[$matchKey]) {
                    continue;
                }

                $faq[$index]['question'] = $row[$questionKey];
                $faq[$index]['answer'] = $row[$answerKey];
                $changed = true;
            }

            if (! $changed) {
                continue;
            }

            DB::table('books')->where('slug', $row['slug'])->update([
                'faq' => json_encode($faq, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
        }
    }
};
