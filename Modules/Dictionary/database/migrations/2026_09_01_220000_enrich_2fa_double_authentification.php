<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Modules\Dictionary\Models\Term;

/**
 * Enrichit la fiche « 2FA (authentification à deux facteurs) » (slug `2fa`) pour le terme demandé
 * « double authentification » - ANTI-DOUBLON, pas de nouvelle fiche (2026-09-01).
 *
 * CONTRÔLE ANTI-DOUBLON FAIT AVANT RÉDACTION, sur le relevé RÉEL du sitemap (523 slugs publiés,
 * jamais un sondage d'URL devinée) : `grep -iE "auth|facteur|2fa|mfa|..."` fait ressortir DEUX
 * fiches déjà publiées et déjà reliées entre elles - `2fa` (broader_slugs=['mfa']) et `mfa`
 * (narrower_slugs=['2fa']). Aucune fiche « authentification » générique n'existe (grep -i "authent"
 * sur les 523 slugs : 0 résultat), donc rien à rattacher en broader plus haut.
 *
 * DÉCISION : « double authentification » est le MÊME MÉCANISME que la fiche `2fa` sous un autre
 * nom (exactement deux facteurs) - jamais un synonyme de `mfa` (deux facteurs OU PLUS). Vérifié
 * par une source primaire, pas supposé : le Grand dictionnaire terminologique (OQLF) définit
 * « authentification à deux facteurs » comme « deux éléments appartenant à deux facteurs
 * d'authentification DISTINCTS » (fiche 26557344) et « authentification multifacteur » comme
 * « AU MOINS deux facteurs » (fiche 26557505) - deux vedettes distinctes, deux définitions
 * distinctes, vérifiées par lecture directe des meta-descriptions des deux fiches GDT (200,
 * texte exact recopié, pas paraphrasé par un modèle). Le GDT n'indexe PAS « double
 * authentification » comme vedette normalisée - c'est un synonyme d'usage courant de
 * « authentification à deux facteurs » (Wikipédia et usage général), jamais du MFA. Donc :
 * AUCUNE fiche nouvelle. Enrichissement de `2fa` uniquement.
 *
 * CE QUE CETTE MIGRATION AJOUTE (additif, jamais un remplacement de champ existant) :
 *
 * 1. ALIASES sur `2fa` : 'double authentification' (le terme demandé), 'authentification à deux
 *    facteurs' (le nom retenu par l'OQLF - déjà dans le `name` affiché sous forme de qualificatif
 *    entre parenthèses, mais PAS matchable par l'auto-lien : GlossaryLinkifier::extractQualifierAliases()
 *    ne pousse le qualificatif en alias que s'il ressemble à un acronyme propre (2-8 majuscules ou
 *    CamelCase court) - une locution française minuscule comme celle-ci ne matche aucun des deux
 *    patterns et est donc silencieusement ignorée), et 'Two-Factor Authentication' (forme anglaise
 *    complète, même logique que l'alias 'Multi-Factor Authentication' déjà posé sur `mfa`). Aucune
 *    de ces trois chaînes n'est un mot générique isolé (contrairement à « clés d'accès » sur
 *    `passkey`, qui a produit 3 faux liens le 2026-08-22) - risque de collision jugé nul.
 *
 * 2. FAQ sur `2fa` (+3, jamais de retrait des 6 existantes) : la nuance 2FA/MFA (l'angle
 *    terminologique demandé - quel terme la fiche décrit, quelle est la différence avec l'autre),
 *    le choix du facteur (l'angle utile pour ce lectorat - SMS le plus faible, application
 *    meilleure, clé physique/passkey la plus robuste contre l'hameçonnage - sourcé Centre canadien
 *    pour la cybersécurité, PAS l'historique du protocole), et la distinction avec la passkey
 *    (« remplace » vs « s'ajoute », reprend le texte de la fiche `passkey` elle-même : sa
 *    définition dit littéralement « qui remplace le mot de passe » - vérifié par lecture directe
 *    avant d'écrire cette phrase).
 *
 * 3. SOURCES sur `2fa` (+2, jamais de retrait des 3 existantes) : GDT (OQLF) pour la terminologie,
 *    Centre canadien pour la cybersécurité (ITSM.30.031, daté 2025-10-24 par les meta dcterms.issued
 *    de la page réelle) pour le classement des facteurs. Les deux URL ont été vérifiées 200 avant
 *    d'être écrites ici (jamais une URL devinée) - un essai de version française du guide CCCS
 *    (motif deviné) a renvoyé 404 et a été abandonné plutôt que gardé.
 *
 * 4. RELATION `passkey` ↔ `mfa` (jamais `passkey` ↔ `2fa` directement) : la fiche `passkey` a été
 *    LUE avant de poser quoi que ce soit (règle du brief) - sa définition dit qu'elle REMPLACE le
 *    mot de passe (un seul geste), alors que le 2FA/double authentification s'AJOUTE à un mot de
 *    passe et suppose deux ÉTAPES séparées. Poser passkey en narrower/broader direct de `2fa`
 *    affirmerait donc une filiation fausse (« sous-terme » au sens strict de la page publique, qui
 *    affiche broader_slugs sous le libellé « Catégorie parente »). En revanche, la documentation
 *    NIST SP 800-63-4 et FIDO Alliance (vérifiée par recherche, cf. rapport de session) décrit
 *    explicitement la passkey comme incarnant le PRINCIPE du multifacteur en un seul geste
 *    (possession de l'appareil + biométrie/NIP) quand la vérification utilisateur est active -
 *    une affirmation qui concerne le MFA (« au moins deux facteurs »), jamais spécifiquement le
 *    2FA (qui suppose deux ÉTAPES distinctes). Direction retenue, sourcée plutôt que devinée :
 *    `mfa.narrower_slugs` gagne `passkey` (aux côtés de `2fa` déjà présent), et
 *    `passkey.broader_slugs` gagne `mfa` (aux côtés de `fido2` déjà présent - un terme peut avoir
 *    plusieurs parents, le tableau le permet). Un lecteur arrivé sur `2fa` trouve quand même la
 *    passkey directement : la FAQ « Est-ce la même chose qu'une clé d'accès ? » ci-dessus mentionne
 *    « clé d'accès », déjà alias enregistré de `passkey` - le lien se pose donc tout seul dans le
 *    corps du texte via GlossaryLinkifier, sans fausse relation hiérarchique dans le graphe.
 *
 * IDEMPOTENTE : chaque ajout est fusionné en excluant d'abord tout doublon (par valeur pour les
 * alias/slugs, par 'question'/'url' pour faq/sources) - la migration peut être rejouée sans effet
 * cumulatif.
 * RÉVERSIBLE : down() retire UNIQUEMENT ce que cette migration a posé, jamais les champs
 * pré-existants (6 FAQ, 3 sources, broader_slugs=['fido2'] et narrower_slugs=['2fa'] d'origine).
 * Un terme introuvable (base locale désynchronisée de la production, partielle par nature) est
 * journalisé, jamais fatal - un déploiement ne doit pas échouer pour ça.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */
return new class extends Migration
{
    private const SLUG_2FA = '2fa';

    private const SLUG_MFA = 'mfa';

    private const SLUG_PASSKEY = 'passkey';

    private const NOUVEAUX_ALIASES_2FA = [
        'double authentification',
        'authentification à deux facteurs',
        'Two-Factor Authentication',
    ];

    private const NOUVELLES_FAQ_2FA = [
        [
            'question' => 'Double authentification, 2FA, MFA : quelle est la différence?',
            'answer' => '« Double authentification », « 2FA » et « authentification à deux facteurs » désignent le même mécanisme, à exactement deux preuves d’identité : c’est le terme retenu par l’Office québécois de la langue française. Le MFA (authentification multifacteur), lui, couvre deux facteurs ou plus. La double authentification en est donc un cas particulier.',
        ],
        [
            'question' => 'Le code par SMS, une application ou une clé de sécurité : quel facteur choisir?',
            'answer' => 'Ce ne sont pas des choix équivalents. Le code par SMS est le plus vulnérable, un numéro de téléphone pouvant être détourné par fraude de la carte SIM. Une application d’authentification est plus robuste, et une clé de sécurité physique ou une passkey offre la meilleure protection contre l’hameçonnage, selon le Centre canadien pour la cybersécurité.',
        ],
        [
            'question' => 'Est-ce la même chose qu’une clé d’accès (passkey)?',
            'answer' => 'Non, ce sont deux approches distinctes. Une clé d’accès remplace complètement le mot de passe par une seule vérification cryptographique. La double authentification, elle, s’ajoute à un mot de passe existant comme étape de vérification supplémentaire.',
        ],
    ];

    private const NOUVELLES_SOURCES_2FA = [
        [
            'label' => 'Authentification à deux facteurs - Grand dictionnaire terminologique (OQLF)',
            'url' => 'https://vitrinelinguistique.oqlf.gouv.qc.ca/fiche-gdt/fiche/26557344/authentification-a-deux-facteurs',
            'year' => '2026',
            'author' => 'Office québécois de la langue française',
        ],
        [
            'label' => 'Defending against adversary-in-the-middle threats with phishing-resistant MFA (ITSM.30.031) - Centre canadien pour la cybersécurité',
            'url' => 'https://www.cyber.gc.ca/en/guidance/defending-against-adversary-middle-threats-phishing-resistant-multi-factor-authentication-itsm30031',
            'year' => '2025',
            'author' => 'Centre canadien pour la cybersécurité',
        ],
    ];

    public function up(): void
    {
        if ($terme2fa = $this->trouver(self::SLUG_2FA)) {
            $terme2fa->aliases = array_values(array_unique(array_merge(
                $terme2fa->aliases ?? [],
                self::NOUVEAUX_ALIASES_2FA
            )));

            $questionsExistantes = array_column(self::NOUVELLES_FAQ_2FA, 'question');
            $faqSansDoublon = array_filter($terme2fa->faq ?? [], function ($qa) use ($questionsExistantes) {
                return ! is_array($qa) || ! in_array($qa['question'] ?? null, $questionsExistantes, true);
            });
            $terme2fa->faq = array_values(array_merge($faqSansDoublon, self::NOUVELLES_FAQ_2FA));

            $urlsExistantes = array_column(self::NOUVELLES_SOURCES_2FA, 'url');
            $sourcesSansDoublon = array_filter($terme2fa->sources ?? [], function ($src) use ($urlsExistantes) {
                return ! is_array($src) || ! in_array($src['url'] ?? null, $urlsExistantes, true);
            });
            $terme2fa->sources = array_values(array_merge($sourcesSansDoublon, self::NOUVELLES_SOURCES_2FA));

            $terme2fa->save();
        }

        if ($termeMfa = $this->trouver(self::SLUG_MFA)) {
            $termeMfa->narrower_slugs = array_values(array_unique(array_merge(
                $termeMfa->narrower_slugs ?? [],
                [self::SLUG_PASSKEY]
            )));
            $termeMfa->save();
        }

        if ($termePasskey = $this->trouver(self::SLUG_PASSKEY)) {
            $termePasskey->broader_slugs = array_values(array_unique(array_merge(
                $termePasskey->broader_slugs ?? [],
                [self::SLUG_MFA]
            )));
            $termePasskey->save();
        }
    }

    public function down(): void
    {
        if ($terme2fa = $this->trouver(self::SLUG_2FA)) {
            $terme2fa->aliases = array_values(array_diff($terme2fa->aliases ?? [], self::NOUVEAUX_ALIASES_2FA));

            $questionsRetirees = array_column(self::NOUVELLES_FAQ_2FA, 'question');
            $terme2fa->faq = array_values(array_filter($terme2fa->faq ?? [], function ($qa) use ($questionsRetirees) {
                return ! is_array($qa) || ! in_array($qa['question'] ?? null, $questionsRetirees, true);
            }));

            $urlsRetirees = array_column(self::NOUVELLES_SOURCES_2FA, 'url');
            $terme2fa->sources = array_values(array_filter($terme2fa->sources ?? [], function ($src) use ($urlsRetirees) {
                return ! is_array($src) || ! in_array($src['url'] ?? null, $urlsRetirees, true);
            }));

            $terme2fa->save();
        }

        if ($termeMfa = $this->trouver(self::SLUG_MFA)) {
            $termeMfa->narrower_slugs = array_values(array_diff($termeMfa->narrower_slugs ?? [], [self::SLUG_PASSKEY]));
            $termeMfa->save();
        }

        if ($termePasskey = $this->trouver(self::SLUG_PASSKEY)) {
            $termePasskey->broader_slugs = array_values(array_diff($termePasskey->broader_slugs ?? [], [self::SLUG_MFA]));
            $termePasskey->save();
        }
    }

    private function trouver(string $slug): ?Term
    {
        // `slug` est TRADUISIBLE (Spatie) : la colonne contient un JSON, et `where('slug', ...)`
        // compare ce JSON entier à une chaîne simple - donc ne correspond JAMAIS.
        $terme = Term::where('slug->fr_CA', $slug)->first()
            ?? Term::where('slug->fr', $slug)->first();

        if (! $terme) {
            // Attendu sur une base de travail partielle (locale), ANORMAL en production : on le
            // journalise au lieu de laisser un « rien à faire » se confondre avec un succès.
            Log::warning("[glossaire] terme '{$slug}' introuvable : enrichissement double-authentification non appliqué.");
        }

        return $terme;
    }
};
