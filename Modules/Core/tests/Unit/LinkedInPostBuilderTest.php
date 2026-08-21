<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

/**
 * 2026-08-21 (demande fondateur : « je veux des posts viraux chaque fois ») - garde-fous du post
 * LinkedIn généré. Le défaut corrigé, constaté sur un post réel : trois fragments TRONQUÉS au
 * milieu d'une phrase (« compte 67 leç… ») collés bout à bout, avec les libellés de section
 * internes recopiés (« Le chiffre à retenir : », « Pourquoi ça compte : »).
 *
 * Ces tests exercent le trait RÉEL via une classe anonyme, pas une copie de sa logique.
 */
use Modules\Core\Concerns\HasAdminShareContents;

function lkBuilder(): object
{
    return new class
    {
        use HasAdminShareContents;

        public function build(string $hook, string $plain, string $interest, array $tags = ['#IA']): string
        {
            return $this->buildLinkedInPost($hook, $plain, $interest, "Toi, t'en penses quoi ? 👇", $tags);
        }

        public function sentences(string $text, int $max): string
        {
            return $this->firstCompleteSentences($text, $max);
        }

        public function stripLabel(string $text): string
        {
            return $this->stripSectionLabel($text);
        }
    };
}

it('ne coupe jamais une phrase au milieu dans le post LinkedIn', function () {
    $post = lkBuilder()->build(
        "Le 20 août 2026, Anthropic a lancé Claude Academy, une plateforme de formation gratuite et ouverte à tous pour apprendre à utiliser l'intelligence artificielle et son assistant Claude. Accessible à academy.claude.com, elle propose des cours structurés et des tutoriels courts.",
        "Le 20 août 2026, Anthropic a lancé Claude Academy (academy.claude.com), une plateforme gratuite et ouverte à tous.",
        "Le chiffre à retenir : « Building with the Claude API » compte 67 leçons et 8 quiz pour environ 9 heures de formation."
    );

    expect($post)->not->toContain('…')
        ->and($post)->not->toContain('...');
});

it('ne recopie jamais un libellé de section interne dans le post LinkedIn', function () {
    $post = lkBuilder()->build(
        'Anthropic lance Claude Academy, gratuite pour tous.',
        'Formation gratuite sur Claude.',
        "Pourquoi ça compte : la formation devient un terrain de compétition entre fournisseurs d'IA."
    );

    expect($post)->not->toContain('Pourquoi ça compte :')
        ->and($post)->not->toContain('Le chiffre à retenir :')
        ->and($post)->toContain('La formation devient un terrain de compétition'); // recapitalisé
});

it('garde une première ligne autonome sous la limite d\'affichage de LinkedIn', function () {
    $post = lkBuilder()->build(
        "Anthropic lance Claude Academy, gratuite pour tous. La plateforme réunit quatre parcours, du débutant au développeur.",
        'Formation gratuite sur Claude et l\'IA.',
        'Le parcours développeur couvre l\'API et le protocole MCP.'
    );

    $firstLine = explode("\n", $post)[0];
    expect(mb_strlen($firstLine))->toBeLessThanOrEqual(150)
        ->and($firstLine)->toEndWith('.'); // phrase complète, jamais un fragment
});

it('place les mots-clics à la fin, 5 au maximum', function () {
    $post = lkBuilder()->build('Une actualité.', 'Un résumé.', 'Un détail.', ['#A', '#B', '#C', '#D', '#E', '#F', '#G']);
    $lines = array_values(array_filter(explode("\n", $post)));
    $last = end($lines);

    expect($last)->toStartWith('#')
        ->and(substr_count($last, '#'))->toBe(5);
});

it('firstCompleteSentences retourne une chaîne vide plutôt qu\'une phrase mutilée', function () {
    $b = lkBuilder();
    // La première phrase (60 caractères) dépasse la limite de 20 : rien ne peut être retenu entier.
    expect($b->sentences('Une phrase beaucoup trop longue pour la limite demandée ici.', 20))->toBe('');
    // Deux phrases : seule la première tient.
    expect($b->sentences('Courte phrase. Une deuxième phrase nettement plus longue que la limite.', 20))
        ->toBe('Courte phrase.');
});

it('stripSectionLabel retire le libellé et recapitalise', function () {
    $b = lkBuilder();
    expect($b->stripLabel('Le chiffre à retenir : 67 leçons.'))->toBe('67 leçons.');
    expect($b->stripLabel("Pourquoi ça compte : la formation change."))->toBe('La formation change.');
    expect($b->stripLabel('Une phrase sans libellé.'))->toBe('Une phrase sans libellé.');
});
