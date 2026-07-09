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
 * Seed des 5 fiches livres réelles (2 essais + trilogie Nexus Neural) pour /livres.
 *
 * RÈGLE ANTI-HALLUCINATION : tous les faits (prix, ISBN, ASIN, pages, dates) proviennent
 * exclusivement des données vérifiées Amazon fournies par l'utilisateur (2026-07-08). Le
 * contenu rédactionnel (subtitle/benefits/excerpt/toc_summary/faq) a été généré par
 * mcp__hermes__model_invoke (task_type=synthesis) à partir du contenu réel des dossiers
 * sources — jamais inventé par l'agent. Deux exclusions volontaires sur la trilogie
 * (contradictions internes aux sources, cf. rapport de recherche 2026-07-08) :
 *   - aucun lieu géographique précis pour l'installation « Omega » ;
 *   - « Sigma » n'est jamais présentée comme antagoniste distincte de Maya Torres.
 * Aucune date de publication n'est renseignée pour les tomes 2 et 3 : seul le tome 1 a une
 * date confirmée par l'utilisateur ; les dates trouvées dans metadata.yaml (sources internes,
 * 2025-12-26) sont des dates de rédaction, pas des dates de publication Amazon confirmées,
 * et ne sont donc pas utilisées (defensive : mieux vaut un champ vide qu'une donnée fausse).
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $books = [
            [
                'title' => "L'IA sans se faire poursuivre",
                'subtitle' => 'Le guide pratique pour PME et professionnels',
                'slug' => 'ia-sans-se-faire-poursuivre',
                'series_slug' => null,
                'series_position' => null,
                'genre' => 'Essai / conformité IA',
                'target_audience' => "Dirigeants et responsables de PME de 10 à 200 employés, travailleurs autonomes, consultants, responsables RH/marketing/TI/juridique, OBNL, enseignants, étudiants avancés et journalistes.",
                'author_bio_short' => "Stéphane Lapointe est le fondateur de MEMORA solutions, une agence spécialisée en gouvernance de l'intelligence artificielle et en conformité. Il accompagne les PME et professionnels dans l'intégration éthique et légale de l'IA. Ce guide pratique est le fruit de son expertise terrain.",
                'one_sentence_answer' => "Un guide pratique de 444 pages pour les PME et professionnels, couvrant l'éthique, la conformité (Loi 25, RGPD, AI Act) et l'intégration opérationnelle de l'IA, avec 18 annexes et une roadmap en 9 phases.",
                'benefits' => [
                    "Comprendre le cadre légal complet (Loi 25, RGPD, AI Act) et les sanctions potentielles.",
                    "Suivre une roadmap en 9 phases pour intégrer l'IA en entreprise de manière conforme.",
                    "Accéder à 18 annexes pratiques incluant templates, checklists et une FAQ de 30 questions.",
                    "Apprendre à choisir un consultant ou un fournisseur IA avec des questions clés.",
                    "Savoir gérer un incident de conformité avec un runbook de 72 heures.",
                ],
                'excerpt' => "Au printemps 2023, un ingénieur de Samsung Semiconductor avait un problème. [...] Il a copié-collé son bout de code dans l'IA, demandé qu'on lui trouve le bug, et reçu une réponse claire en quelques secondes. [...] Samsung a découvert tout ça en mai 2023, dans un audit de routine. L'interdiction de ChatGPT est tombée la semaine suivante. [...] Le code propriétaire, lui, avait déjà quitté le bâtiment. (Chapitre 1)",
                'toc_summary' => [
                    ['part' => 'Mise en contexte et éthique', 'chapters' => "Chapitres 1 à 3 : introduction, enjeux éthiques de l'IA"],
                    ['part' => 'Cadre légal', 'chapters' => "Chapitres 4 à 7 : Loi 25 Québec, RGPD européen, AI Act européen, triple verrou"],
                    ['part' => 'Normes et roadmap', 'chapters' => "Chapitres 8 à 14 : normes ISO/NIST, roadmap en 9 phases d'intégration IA"],
                    ['part' => 'Outils et mise en œuvre', 'chapters' => "Chapitres 15 à 18 : site web, outils IA, vibe coding, choix d'un consultant"],
                    ['part' => 'IA par département', 'chapters' => "Chapitres 19 à 22 : RH, marketing/ventes, service client, finance/juridique"],
                    ['part' => 'Conclusion et bonus', 'chapters' => "Chapitres 23 à 25 : conclusion, bonus école/enseignants, droit à l'image et IA"],
                    ['part' => 'Annexes', 'chapters' => "18 annexes (A à R) : glossaire, calendrier de conformité, templates, checklists, FAQ, etc."],
                ],
                'faq' => [
                    ['question' => 'La Loi 25 s\'applique-t-elle à ma PME de huit employés ?', 'answer' => "Oui. La Loi 25 s'applique à toute entreprise au sens du Code civil du Québec qui recueille des renseignements personnels, indépendamment de sa taille. Même un travailleur autonome y est assujetti."],
                    ['question' => 'Combien coûte la mise en conformité Loi 25 pour une PME de 50 personnes ?', 'answer' => "Entre 25 000 et 50 000 dollars en année 1, tout compris (consultant, formation, outils), si la démarche est bien structurée."],
                    ['question' => 'ChatGPT gratuit est-il acceptable en contexte professionnel ?', 'answer' => "Non, pour les traitements qui incluent des données d'entreprise. Il faut migrer vers une offre Team ou Enterprise."],
                    ['question' => 'Puis-je utiliser l\'IA pour trier des CV ?', 'answer' => "Oui, avec encadrement. Le système est à haut risque selon l'AI Act : supervision humaine significative obligatoire, information des candidats, procédure de révision."],
                    ['question' => 'En cas d\'incident, qui dois-je notifier et dans quel délai ?', 'answer' => "Trois horloges : sans délai à la CAI (Loi 25), 72 heures à l'autorité compétente (RGPD), 15 jours aux autorités de surveillance (AI Act haut risque)."],
                    ['question' => 'Quels sont les formats disponibles pour ce livre ?', 'answer' => "Le livre est disponible en version brochée à 44,99 \$ CAD et en version Kindle à 29,99 \$ CAD, éligible à Kindle Unlimited."],
                    ['question' => 'Quelle est la structure du livre ?', 'answer' => "L'ouvrage compte 25 chapitres numérotés, des pages liminaires, une clôture « Et maintenant ? » et 18 annexes (A à R) incluant glossaire, templates et FAQ."],
                ],
                'isbn_paperback' => '979-8195277505',
                'asin_kindle' => 'B0GXMTLXQC',
                'price_paperback' => 44.99,
                'price_kindle' => 29.99,
                'amazon_url_paperback' => 'https://amazon.ca/dp/B0H11T6XZL',
                'amazon_url_kindle' => 'https://amazon.ca/dp/B0GXMTLXQC',
                'cover_image' => '/images/livres/ia-sans-se-faire-poursuivre-cover-600.jpg',
                'date_published' => '2026-05-08',
                'sort_order' => 1,
            ],
            [
                'title' => "L'IA pour les parents",
                'subtitle' => 'Protéger tes enfants, encadrer les écrans, apprivoiser l\'intelligence artificielle à la maison',
                'slug' => 'ia-pour-les-parents',
                'series_slug' => null,
                'series_position' => null,
                'genre' => 'Essai / parentalité numérique',
                'target_audience' => "Parents québécois et francophones souhaitant accompagner leurs enfants de 0 à 20 ans et plus dans un usage sécuritaire et éclairé de l'IA, avec des réponses différenciées par tranche d'âge.",
                'author_bio_short' => "Stéphane Lapointe est le fondateur de MEMORA solutions et consultant en gouvernance de l'IA et en conformité à la Loi 25. Fort de son expertise en protection des données et en éthique numérique, il signe ce guide pratique pour aider les parents à encadrer l'intelligence artificielle à la maison.",
                'one_sentence_answer' => "Ce guide pratique de 353 pages aide les parents québécois à comprendre l'IA, protéger leurs enfants et encadrer les écrans, avec des conseils adaptés à chaque âge et des exercices concrets.",
                'benefits' => [
                    "Des réponses concrètes adaptées à 4 tranches d'âge (0-3, 4-6, 7-9, 10-12 ans en annexe, primaire à université dans chaque chapitre).",
                    "19 chapitres avec un exercice de 15 minutes chacun, couvrant les risques, les outils utiles et le cadre familial.",
                    "11 annexes incluant une charte imprimable, des scripts de dialogue parent-enfant, un journal de bord familial et un répertoire d'outils sûrs par âge.",
                    "Le cadre légal expliqué simplement : Loi 25 (Québec), RGPD, AI Act, COPPA, avec une checklist vie privée en 5 minutes.",
                ],
                'excerpt' => "L'IA n'est plus un gadget, c'est l'infrastructure de la vie de tes enfants. [...] 72 % des 12-17 ans connaissent déjà ces outils (OTM Junior 2024). [...] Ton rôle : devenir le garde-fou, pas l'expert en code. (Chapitre 1)",
                'toc_summary' => [
                    ['part' => 'Cadrer', 'chapters' => "Chapitres 1 à 3 : l'IA comme infrastructure, fonctionnement sans jargon, permis de prompter"],
                    ['part' => 'Risques', 'chapters' => "Chapitres 4 à 9 : vie privée et Loi 25, identité, hallucinations, amitiés toxiques avec l'IA, santé mentale et compagnons IA, deepfakes et sextorsion"],
                    ['part' => 'Outils utiles', 'chapters' => "Chapitres 10 à 13 : IA copilote scolaire, tutorat socratique, créativité augmentée, agents IA autonomes"],
                    ['part' => 'Cadrer la maison', 'chapters' => "Chapitres 14 à 16 : dialogue familial, charte familiale IA, verrouillage technique et parental"],
                    ['part' => "Préparer l'avenir", 'chapters' => "Chapitres 17 à 19 : métiers de demain, IA citoyenne, l'humain seul pilote"],
                ],
                'faq' => [
                    ['question' => 'À quel âge un enfant peut-il utiliser l\'IA seul ?', 'answer' => "Le livre précise que ChatGPT est réservé aux 13 ans et plus, avec consentement parental de 13 à 17 ans. Snapchat, Character.AI, Replika, Discord, TikTok et Instagram sont aussi à partir de 13 ans. Avant 13 ans, seule une co-utilisation avec un parent est recommandée."],
                    ['question' => 'Quels sont les risques des compagnons IA comme Character.AI ou Replika pour la santé mentale ?', 'answer' => "Le livre consacre un chapitre aux amitiés toxiques avec des IA et à leur impact sur la santé mentale. Il met en garde contre la confusion entre relation humaine et relation artificielle, et propose des scripts de dialogue pour en parler avec son enfant."],
                    ['question' => 'Comment la Loi 25 protège-t-elle les données de mes enfants ?', 'answer' => "Le chapitre 4 explique qu'une donnée numérique est comme du dentifrice : une fois sortie du tube, elle ne rentre jamais. La Loi 25 du Québec oblige les entreprises à protéger les renseignements personnels des enfants. Le livre couvre aussi le RGPD, l'AI Act et la COPPA."],
                    ['question' => 'Que faire face aux deepfakes et à la sextorsion ?', 'answer' => "Un chapitre entier alerte sur ces dangers et propose des scripts de conversation parent-enfant par âge ainsi que des contrats individuels enfant pour établir des règles claires."],
                    ['question' => 'Le livre contient-il des exercices pratiques ?', 'answer' => "Oui, chaque chapitre inclut un mini-exercice de 15 minutes. De plus, 11 annexes offrent une charte familiale IA imprimable, un journal de bord familial de 12 mois et un répertoire d'outils sûrs par âge."],
                    ['question' => 'Quels sont les formats et prix disponibles ?', 'answer' => "Le livre est disponible en version brochée à 24,99 \$ CAD et en version Kindle à 9,99 \$ CAD. Publié le 2 juillet 2026."],
                    ['question' => "Y a-t-il des conseils pour les tout-petits (0 à 3 ans) ?", 'answer' => "Oui, une annexe est dédiée aux jeunes enfants de 0 à 12 ans avec des sous-sections par tranche d'âge. Chaque chapitre principal comporte aussi des sections adaptées à chaque âge, jusqu'à l'université."],
                ],
                'isbn_paperback' => '979-8185285084',
                'asin_kindle' => 'B0H68BZHN5',
                'price_paperback' => 24.99,
                'price_kindle' => 9.99,
                'amazon_url_paperback' => 'https://amazon.ca/dp/B0H7CN9QG5',
                'amazon_url_kindle' => 'https://amazon.ca/dp/B0H68BZHN5',
                'cover_image' => '/images/livres/ia-pour-les-parents-cover-600.jpg',
                'date_published' => '2026-07-02',
                'sort_order' => 2,
            ],
            [
                'title' => 'Nexus Neural : Tome 1 - L\'Éveil',
                'subtitle' => 'Nexus décuple son intelligence. Mais il éteint son humanité.',
                'slug' => 'nexus-neural-tome-1',
                'series_slug' => 'nexus-neural',
                'series_position' => 1,
                'genre' => 'Science-fiction / thriller technologique',
                'target_audience' => "Lectorat adulte (18+) de thrillers technologiques et de science-fiction spéculative, sensible aux questions d'éthique et de transhumanisme, ancré à Montréal.",
                'author_bio_short' => "Stéphane Lapointe est un auteur québécois de techno-thrillers de science-fiction. Il explore dans ses romans les dérives du transhumanisme et de l'intelligence artificielle. La trilogie Nexus Neural est ancrée dans l'univers montréalais.",
                'one_sentence_answer' => "Après la mort de sa sœur, Étienne Marchant s'implante clandestinement le Nexus, un prototype d'interface cerveau-machine qui décuple ses capacités mais érode son humanité, le transformant en justicier traqué.",
                'benefits' => [
                    "Un thriller haletant où chaque chapitre creuse la perte d'empathie du héros, entre action et réflexion philosophique.",
                    "Une immersion dans le Montréal contemporain, avec son écosystème technologique et ses zones d'ombre.",
                    "Le motif poétique de la murmuration des étourneaux, fil rouge symbolique de la trilogie, présent dès les premières pages.",
                    "Un prologue choc qui plante le décor : un manifeste vidéo, quatorze exécutions, un homme derrière un masque de compression.",
                ],
                'excerpt' => "La caméra ne mentait jamais. L'homme assis face à l'objectif portait un masque de compression noir qui ne laissait voir que ses yeux. [...] Mon nom est Étienne Marchant. [...] Pendant les six derniers mois, j'ai exécuté quatorze personnes. (Prologue)",
                'toc_summary' => [
                    ['part' => 'Acte I - La tentation', 'chapters' => 'Chapitres 1 à 5'],
                    ['part' => "Acte II - L'ascension", 'chapters' => 'Chapitres 6 à 10'],
                    ['part' => 'Acte III - La chute', 'chapters' => 'Chapitres 11 à 15'],
                ],
                'faq' => [
                    ['question' => 'De quoi parle le tome 1 de Nexus Neural ?', 'answer' => "Étienne Marchant, chercheur montréalais, s'implante le Nexus après la mort de sa sœur Camille. L'interface lui confère mémoire eidétique et réflexes surhumains, mais efface son empathie. Il devient justicier de l'ombre, traqué par la journaliste Léa Chen et l'agent Sarah Frost. Le tome se conclut sur un manifeste vidéo viral."],
                    ['question' => 'Qui est Étienne Marchant ?', 'answer' => "Le protagoniste de la trilogie, chercheur en neurosciences computationnelles à Montréal. Après le décès de sa sœur Camille, il s'implante le Nexus, un prototype d'interface cerveau-machine. Sa quête de vengeance le transforme en justicier, mais l'implant lui vole progressivement son humanité."],
                    ['question' => 'Quel est le lien entre les trois tomes ?', 'answer' => "Chaque tome suit l'évolution d'Étienne Marchant et du mouvement transhumaniste qu'il déclenche. Le tome 1 pose les bases (l'implant, la perte d'empathie, le manifeste), le tome 2 voit l'expansion du mouvement et l'arrivée de nouveaux alliés, le tome 3 confronte Étienne aux derniers vestiges du Consortium."],
                    ['question' => 'Quelle est l\'ambiance du premier tome ?', 'answer' => "Un techno-thriller sombre et urbain, mêlant action, suspense et questionnements éthiques. L'écriture est nerveuse, le ton mature (18+). L'univers montréalais sert de toile de fond à une réflexion sur l'identité, la mémoire et le coût de la surhumanité."],
                    ['question' => 'Quel est le format et le prix du tome 1 ?', 'answer' => "Broché de 190 pages à 13,69 \$ CAD, disponible en format Kindle à 1,35 \$ CAD. Publié le 20 février 2026."],
                    ['question' => 'Quel âge et quel contenu mature ?', 'answer' => "Œuvre destinée aux adultes (18+). Elle contient des scènes de violence explicite ainsi que des thèmes de deuil, de perte d'empathie et de transhumanisme radical."],
                    ['question' => 'Que signifie le titre « Nexus Neural » ?', 'answer' => "« Nexus » évoque la connexion, le lien entre le cerveau et la machine. « Neural » renvoie aux neurosciences. L'implant porte le nom de Nexus, et la trilogie explore les conséquences de cette interface sur l'humanité d'Étienne."],
                    ['question' => 'Quel est le rôle du motif des étourneaux ?', 'answer' => "Camille, la sœur décédée d'Étienne, expliquait : « On appelle ça une murmuration [...] Chaque oiseau ne réagit qu'à ses sept voisins les plus proches. » Ce motif symbolise la connexion et l'identité, et traverse les trois tomes comme un fil rouge poétique."],
                ],
                'isbn_paperback' => '979-8249205256',
                'asin_kindle' => 'B0GPQ6WFGN',
                'price_paperback' => 13.69,
                'price_kindle' => 1.35,
                'amazon_url_paperback' => 'https://amazon.ca/dp/B0GPPBCMSZ',
                'amazon_url_kindle' => 'https://amazon.ca/dp/B0GPQ6WFGN',
                'cover_image' => '/images/livres/nexus-neural-tome-1-cover-600.jpg',
                'date_published' => '2026-02-20',
                'sort_order' => 3,
            ],
            [
                'title' => 'Nexus Neural : Tome 2 - L\'Expansion',
                'subtitle' => "Le manifeste a tout changé. Étienne n'est plus seul. Mais être nombreux ne signifie pas être en sécurité.",
                'slug' => 'nexus-neural-tome-2',
                'series_slug' => 'nexus-neural',
                'series_position' => 2,
                'genre' => 'Science-fiction / thriller technologique',
                'target_audience' => "Lectorat adulte (18+) de thrillers technologiques et de science-fiction spéculative, intéressé par les dynamiques de groupe, les trahisons et l'expansion d'un mouvement clandestin.",
                'author_bio_short' => "Stéphane Lapointe est un auteur québécois de techno-thrillers de science-fiction. Il explore dans ses romans les dérives du transhumanisme et de l'intelligence artificielle. La trilogie Nexus Neural est ancrée dans l'univers montréalais.",
                'one_sentence_answer' => "Trois mois après le manifeste, le mouvement Nexus grandit, mais le Consortium riposte dans l'ombre. Étienne recrute Maya Torres, chirurgienne des implants, tandis que des trahisons internes fragilisent la révolution transhumaniste.",
                'benefits' => [
                    "Une intrigue élargie où le collectif remplace l'action solitaire, avec des alliances et des trahisons inattendues.",
                    "L'arrivée de Maya Torres, alliée clé et chirurgienne des implants, qui enrichit l'univers et les enjeux.",
                    "La journaliste Léa Chen publie un article dévoilant le Consortium, ajoutant une dimension médiatique au conflit.",
                    "Un rythme qui s'accélère, porté par l'enquête de l'agent Sarah Frost trois mois après le manifeste.",
                ],
                'excerpt' => "Le café avait refroidi depuis longtemps. Sarah Frost fixait la tasse posée sur son bureau [...] Trois mois. Quatre-vingt-onze jours depuis le manifeste. Sur l'écran devant elle, le visage d'Étienne Marchant la regardait. (Chapitre 1)",
                'toc_summary' => [
                    ['part' => 'Roman complet', 'chapters' => '17 chapitres'],
                ],
                'faq' => [
                    ['question' => 'De quoi parle le tome 2 de Nexus Neural ?', 'answer' => "Trois mois après le manifeste, le mouvement Nexus a gagné des adeptes. Étienne Marchant recrute Maya Torres, chirurgienne des implants et ex-militaire, pour renforcer ses rangs. Mais le Consortium contre-attaque dans l'ombre, et des trahisons internes menacent la révolution transhumaniste."],
                    ['question' => 'Qui est Maya Torres dans ce tome ?', 'answer' => "Maya Torres est une chirurgienne des implants et une ancienne militaire. Elle devient une alliée clé d'Étienne Marchant, l'aidant à étendre le réseau Nexus."],
                    ['question' => 'Quel est le lien entre le tome 2 et le tome 1 ?', 'answer' => "Le tome 2 est la suite directe du manifeste viral du tome 1. Étienne n'est plus un justicier solitaire : il dirige un mouvement. La journaliste Léa Chen et l'agent Sarah Frost reviennent, tandis que le Consortium, responsable de la mort de Camille, riposte."],
                    ['question' => 'Quelle est l\'ambiance du deuxième tome ?', 'answer' => "Un techno-thriller collectif, où les enjeux passent de la vengeance personnelle à la lutte clandestine. L'ambiance est plus politique, avec des trahisons et des manipulations. Le ton reste mature (18+), avec des scènes de violence et de tension."],
                    ['question' => 'Quel est le format et le prix du tome 2 ?', 'answer' => "Broché de 190 pages à 13,69 \$ CAD, disponible en format Kindle à 6,82 \$ CAD."],
                    ['question' => 'Quel âge et quel contenu mature ?', 'answer' => "Œuvre destinée aux adultes (18+). Le tome 2 contient des scènes de violence ainsi que des thèmes de conspiration et de perte d'humanité."],
                    ['question' => 'Que signifie le titre « L\'Expansion » ?', 'answer' => "« L'Expansion » fait référence à la croissance du mouvement Nexus après le manifeste. Les adeptes se multiplient et les ramifications du Consortium s'étendent. Le titre évoque à la fois la propagation technologique et les risques d'un réseau trop vaste."],
                ],
                'isbn_paperback' => '979-8249213978',
                'asin_kindle' => 'B0GPPGK36T',
                'price_paperback' => 13.69,
                'price_kindle' => 6.82,
                'amazon_url_paperback' => 'https://amazon.ca/dp/B0GPP4ZYJG',
                'amazon_url_kindle' => 'https://amazon.ca/dp/B0GPPGK36T',
                'cover_image' => '/images/livres/nexus-neural-tome-2-cover-600.jpg',
                'date_published' => null,
                'sort_order' => 4,
            ],
            [
                'title' => 'Nexus Neural : Tome 3 - La Singularité',
                'subtitle' => "La fin approche, pour tout le monde. Étienne est le seul à pouvoir sauver l'humanité. Mais il ne reste presque plus rien d'humain en lui.",
                'slug' => 'nexus-neural-tome-3',
                'series_slug' => 'nexus-neural',
                'series_position' => 3,
                'genre' => 'Science-fiction / thriller technologique',
                'target_audience' => "Lectorat adulte (18+) de thrillers technologiques et de science-fiction spéculative, prêt pour un dénouement où l'humanité d'Étienne est mise à l'épreuve ultime.",
                'author_bio_short' => "Stéphane Lapointe est un auteur québécois de techno-thrillers de science-fiction. Il explore dans ses romans les dérives du transhumanisme et de l'intelligence artificielle. La trilogie Nexus Neural est ancrée dans l'univers montréalais.",
                'one_sentence_answer' => "Étienne Marchant, presque totalement déshumanisé, traque les dirigeants du Consortium à travers l'Europe, notamment à Prague, pour contrer le projet Omega et ce qu'il reste de son humanité.",
                'benefits' => [
                    "Un final intense où Étienne doit allier ses derniers réflexes humains à une puissance surhumaine pour arrêter Elena Vance et le projet Omega.",
                    "Le retour des alliés Maya Torres, Léa Chen et Alpha pour un affrontement qui mêle action et dilemmes éthiques.",
                    "La murmuration des étourneaux devient omniprésente, offrant une clôture poétique à la trilogie.",
                    "Une intrigue qui se déploie en Europe (dont Prague), élargissant la portée géographique de la saga.",
                ],
                'excerpt' => "L'odeur du sang était différente à Prague. [...] Étienne essuya la lame sur le manteau de l'homme à ses pieds. Karl Brenner, 54 ans, directeur régional du Consortium pour l'Europe de l'Est, ne respirait plus. (Chapitre 1)",
                'toc_summary' => [
                    ['part' => 'Roman complet', 'chapters' => '24 chapitres et un épilogue'],
                ],
                'faq' => [
                    ['question' => 'De quoi parle le tome 3 de Nexus Neural ?', 'answer' => "Étienne Marchant, ayant presque perdu toute humanité, poursuit sa traque des dirigeants du Consortium à travers l'Europe, dont Prague. Il doit affronter Elena Vance et le projet Omega, une menace qui vise à transformer l'humanité. Il s'allie à Maya Torres, Léa Chen et un allié nommé Alpha."],
                    ['question' => 'Qui est Étienne Marchant dans ce dernier tome ?', 'answer' => "Le même personnage que dans les tomes précédents, mais son empathie est presque totalement érodée par le Nexus. Il agit avec une efficacité glaciale, guidé par sa mission, dans un affrontement final où son humanité résiduelle est mise en jeu."],
                    ['question' => 'Quel est le lien avec les tomes 1 et 2 ?', 'answer' => "Le tome 3 conclut l'arc narratif commencé dans les deux premiers volumes. Les personnages Léa Chen, Sarah Frost et Maya Torres réapparaissent, et le motif des étourneaux atteint son apogée."],
                    ['question' => 'Quelle est l\'ambiance du troisième tome ?', 'answer' => "Un techno-thriller crépusculaire, où Étienne oscille entre machine et homme. L'action se déplace en Europe, ajoutant une dimension géopolitique. Le ton reste mature (18+), avec une violence assumée et une réflexion sur la singularité technologique."],
                    ['question' => 'Quel est le format et le prix du tome 3 ?', 'answer' => "Broché de 190 pages à 13,69 \$ CAD, disponible en format Kindle à 6,82 \$ CAD."],
                    ['question' => 'Quel âge et quel contenu mature ?', 'answer' => "Œuvre destinée aux adultes (18+). Le tome 3 contient des scènes de violence explicite ainsi que des thèmes de perte d'identité et de sacrifice."],
                    ['question' => 'Que signifie le titre « La Singularité » ?', 'answer' => "« La Singularité » fait référence au point de bascule où une intelligence non humaine dépasse l'humain. Étienne, porté par le Nexus, incarne cette singularité. Le titre annonce un affrontement entre l'humanité résiduelle et la déshumanisation technologique."],
                    ['question' => 'Le motif des étourneaux est-il important dans ce tome ?', 'answer' => "Oui, c'est dans le tome 3 qu'il est le plus développé. Il devient omniprésent jusqu'à la scène finale, reliant la métaphore de la murmuration à la connexion entre les personnages et à l'identité collective."],
                ],
                'isbn_paperback' => '979-8249215606',
                'asin_kindle' => 'B0GPPNWV6X',
                'price_paperback' => 13.69,
                'price_kindle' => 6.82,
                'amazon_url_paperback' => 'https://amazon.ca/dp/B0GPPHGB7V',
                'amazon_url_kindle' => 'https://amazon.ca/dp/B0GPPNWV6X',
                'cover_image' => '/images/livres/nexus-neural-tome-3-cover-600.jpg',
                'date_published' => null,
                'sort_order' => 5,
            ],
        ];

        foreach ($books as $book) {
            $benefits = $book['benefits'];
            $tocSummary = $book['toc_summary'];
            $faq = $book['faq'];
            unset($book['benefits'], $book['toc_summary'], $book['faq']);

            DB::table('books')->updateOrInsert(
                ['slug' => $book['slug']],
                array_merge($book, [
                    'benefits' => json_encode($benefits, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'toc_summary' => json_encode($tocSummary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'faq' => json_encode($faq, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'is_under_construction' => false,
                    'is_published' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ])
            );
        }
    }

    public function down(): void
    {
        DB::table('books')->whereIn('slug', [
            'ia-sans-se-faire-poursuivre',
            'ia-pour-les-parents',
            'nexus-neural-tome-1',
            'nexus-neural-tome-2',
            'nexus-neural-tome-3',
        ])->delete();
    }
};
