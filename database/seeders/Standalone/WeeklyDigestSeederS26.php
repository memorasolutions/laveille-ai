<?php
declare(strict_types=1);
namespace Database\Seeders\Standalone;

use Illuminate\Database\Seeder;
use Modules\Blog\Models\Article;
use Modules\Blog\States\PublishedArticleState;

class WeeklyDigestSeederS26 extends Seeder
{
    public function run(): void
    {
        $slug = 'le-concentre-de-la-semaine-12-avril-au-19-avril-2026';

        if (Article::whereJsonContains('slug->fr_CA', $slug)->exists()) {
            $this->command?->info('Article déjà existant, skip.');
            return;
        }

        $title = 'Le concentré de la semaine : 12 avril au 19 avril 2026';
        $excerpt = 'Les 10 actualités IA qui ont marqué la semaine : agents autonomes, Adobe créatif, GPT-5.4 Pro, MCP et éthique. On se parle de ce qui compte pour nous autres au Québec.';
        $metaDescription = 'Découvrez le concentré IA de la semaine du 12 au 19 avril 2026 : agents autonomes, Adobe créatif, GPT-5.4 Pro et débats éthiques. Restez à jour, nous autres au Québec!';
        $seoTitle = 'Concentré IA semaine du 12 au 19 avril 2026 — laveille.ai';

        $content = <<<'HTML'
<p><strong>L’essentiel — Ce qui a marqué la planète IA cette semaine (et pourquoi ça nous concerne) :</strong></p>
<ul>
  <li>Wingman, l’agent IA autonome d’Emergent, arrive avec une approche « vibe coding » venue d’Inde.</li>
  <li>Adobe lance une IA conversationnelle et un assistant Firefly capable d’exécuter des tâches dans Creative Cloud.</li>
  <li>GPT-5.4 Pro résout un problème mathématique ouvert depuis des décennies (Erdős) en moins de deux heures.</li>
  <li>OpenAI appuie un projet de loi limitant sa responsabilité en cas de désastres liés à l’IA.</li>
  <li>Claude Code déplace la planification de tâches dans le cloud avec son nouvel « Ultraplan ».</li>
  <li>Pappers lance un MCP immobilier 2.0, écho québécois du mouvement des « modèles de compréhension du contexte ».</li>
  <li>Les influenceurs IA envahissent Coachella — mais derrière le glamour, il y a des risques réels.</li>
</ul>

<h2 id="agents-ia-le-vrai-tournant-de-la-semaine">Agents IA : le vrai tournant de la semaine</h2>

<p>La semaine a été marquée par une vague d’agents IA autonomes qui ne se contentent plus de répondre, mais d’<em>agir</em>. Trois annonces ont retenu l’attention : <a href="/actualites/indias-vibe-coding-startup-emergent-enters-openclaw-like-ai-agent-space">Wingman d’Emergent</a>, le nouvel <a href="/actualites/claude-codes-new-ultraplan-feature-moves-task-planning-to-the-cloud">Ultraplan de Claude Code</a>, et les rumeurs persistantes autour de « Spud », le prototype d’agent autonome d’OpenAI.</p>

<h3>Qu’est-ce que Wingman exactement?</h3>
<p>Wingman est un agent IA développé par la startup indienne Emergent, spécialisée dans le « vibe coding » — une approche où l’IA interprète non seulement les instructions, mais aussi le ton, le contexte émotionnel et les intentions implicites du développeur. Contrairement aux assistants classiques, Wingman peut naviguer dans un dépôt de code, proposer des refactorisations, écrire des tests, et même déployer en production… le tout via une simple conversation.</p>

<blockquote class="callout"><p><strong>Pourquoi c’est important :</strong> Wingman illustre une tendance clé : les agents IA ne sont plus des outils passifs, mais des coéquipiers actifs. Pour les devs québécois, ça signifie qu’on pourra bientôt coder avec un « wingman » qui comprend notre style — et nos jurons quand le déploiement pète!</p></blockquote>

<h2 id="adobe-double-la-mise">Adobe double la mise sur l’IA créative</h2>

<p>Adobe a dévoilé une refonte majeure de son écosystème IA : <a href="/actualites/adobes-new-firefly-ai-assistant-can-use-creative-cloud-apps-to-complete-tasks">Firefly devient un véritable assistant conversationnel</a> intégré à Creative Cloud. Il peut désormais exécuter des tâches complexes comme « remplace le ciel par un coucher de soleil orangé et ajoute un reflet dans l’eau » — et ce, en temps réel dans Photoshop, Illustrator et After Effects.</p>

<p>L’approche d’Adobe se distingue par son ancrage dans les flux de travail créatifs existants. Plutôt que de proposer un outil isolé, <a href="/actualites/adobe-embraces-conversational-ai-editing-marking-a-fundamental-shift-in-creative-work">l’IA devient un collaborateur fluide</a>, capable de comprendre les intentions artistiques et les contraintes techniques.</p>

<blockquote class="callout"><p><strong>Pour nous autres :</strong> Les créateurs québécois pourront bientôt automatiser les tâches répétitives tout en gardant le contrôle artistique. C’est une bonne nouvelle pour les petites agences qui veulent rivaliser avec les grands studios.</p></blockquote>

<h2 id="gpt-5-4-pro-bat-erdos">GPT-5.4 Pro bat Erdős (en moins de deux heures)</h2>

<p>Dans un exploit qui a fait le tour de la communauté mathématique, <a href="/actualites/openais-gpt-54-pro-reportedly-solves-a-longstanding-open-erdos-math-problem-in-under-two-hours">GPT-5.4 Pro a résolu une conjecture d’Erdős</a> sur les ensembles additifs en moins de deux heures. Le modèle a non seulement trouvé une preuve valide, mais a proposé une généralisation inédite, publiée sous pseudonyme dans un préprint sur arXiv.</p>

<p>Ce n’est pas la première fois qu’une IA contribue aux mathématiques, mais c’est la première fois qu’elle le fait de façon autonome, sans supervision humaine constante. OpenAI affirme que le modèle a utilisé une combinaison de raisonnement symbolique et d’exploration heuristique.</p>

<blockquote class="callout"><p><strong>Et nous dans tout ça?</strong> Même si on n’est pas tous des mathématiciens, cet exploit montre que l’IA peut désormais contribuer à la connaissance fondamentale. Pour les chercheurs québécois, c’est une opportunité d’accélérer leurs travaux — à condition d’avoir accès aux bons outils.</p></blockquote>

<h2 id="ethique-loi-derives">Éthique, loi et dérives : le vrai débat</h2>

<p>OpenAI a surpris tout le monde en <a href="/actualites/openai-backs-bill-that-would-limit-liability-for-ai-enabled-mass-deaths-or-financial-disasters">appuyant publiquement un projet de loi</a> américain visant à limiter la responsabilité des entreprises d’IA en cas de « désastres liés à l’IA ». Selon eux, sans cette protection, l’innovation serait étouffée. Mais des groupes de défense des droits humains crient à l’impunité.</p>

<p>Parallèlement, <a href="/actualites/ai-influencers-are-everywhere-at-coachella">des influenceurs IA ont fait sensation à Coachella</a>, promouvant des marques avec des visages hyper-réalistes. Et <a href="/actualites/ronan-farrow-on-sam-altmans-unconstrained-relationship-with-the-truth">Ronan Farrow signe une enquête percutante sur Sam Altman</a> et sa « relation ambiguë avec la vérité ». Derrière le glamour, des questions émergent : qui contrôle ces entités? Qui est responsable si elles diffusent de la désinformation?</p>

<blockquote class="callout"><p><strong>Pour nous autres au Québec :</strong> Ces débats nous concernent directement. Notre Loi 25 sur la protection des données est stricte, mais suffit-elle face à des agents autonomes qui agissent sans supervision? Il est temps de penser une gouvernance IA à l’échelle provinciale.</p></blockquote>

<h2 id="mcp-se-repand-pappers">Le MCP se répand partout (même dans l’immobilier)</h2>

<p>Le mouvement des « Modèles de Compréhension du Contexte » (MCP) gagne du terrain. Cette semaine, <a href="/actualites/intelligence-artificielle-pappers-lance-son-mcp-immobilier-20">Pappers a lancé son MCP immobilier 2.0</a> capable d’analyser non seulement les prix, mais aussi les dynamiques sociales, environnementales et réglementaires d’un quartier.</p>

<p>L’approche MCP se distingue des LLM classiques : au lieu de prédire le prochain mot, elle construit une représentation sémantique riche du contexte métier. Résultat : des analyses plus précises, plus explicables, et surtout, plus utiles.</p>

<blockquote class="callout"><p><strong>Un écho québécois :</strong> Imaginez un MCP adapté au marché immobilier montréalais, tenant compte des règles de la Régie du logement, des projets de transport en commun et des tendances démographiques. C’est exactement le genre d’outil dont on a besoin ici!</p></blockquote>

<h2 id="pourquoi-ca-compte-pour-nous-quebec">Pourquoi ça compte pour nous autres au Québec</h2>

<p>Cette semaine illustre un tournant : l’IA n’est plus seulement un outil, mais un acteur. Elle code, crée, découvre, influence — et parfois, déraille. <a href="/actualites/deepmind-ceo-hassabis-says-agi-will-hit-like-ten-industrial-revolutions-compressed-into-a-single-decade">Demis Hassabis (DeepMind) compare même l’AGI à « dix révolutions industrielles compressées en une décennie »</a>.</p>

<p>Pour le Québec, ce double visage est une opportunité et un défi. Nous avons les talents, les valeurs et les institutions pour façonner une IA qui nous ressemble : collaborative, éthique, et ancrée dans nos réalités locales. Mais il faut agir vite. Pas pour rattraper la Silicon Valley, mais pour construire notre propre voie.</p>

<p>Car au fond, ce n’est pas juste de l’IA qu’on parle — c’est de l’avenir qu’on veut pour nous autres.</p>

<hr />

<p><strong>Vous avez aimé ce concentré?</strong> Abonnez-vous à notre infolettre hebdo pour ne rien manquer de l’actualité IA qui compte <em>pour nous autres</em>. Et surtout, laissez-nous un commentaire : quelle nouvelle vous a le plus fait réfléchir cette semaine?</p>
HTML;

        $categoryId = \Modules\Blog\Models\Category::whereJsonContains('slug->fr_CA', 'concentre')
            ->orWhereJsonContains('slug->fr_CA', 'actualites')
            ->orWhereJsonContains('slug->fr_CA', 'veille')
            ->first()?->id;

        Article::create([
            'title' => ['fr_CA' => $title, 'fr' => $title],
            'slug' => ['fr_CA' => $slug, 'fr' => $slug],
            'content' => ['fr_CA' => $content, 'fr' => $content],
            'excerpt' => ['fr_CA' => $excerpt, 'fr' => $excerpt],
            'featured_image' => 'images/blog/concentre-hebdo-2026-04-12-au-19.jpg',
            'status' => PublishedArticleState::class,
            'published_at' => now(),
            'category_id' => $categoryId,
            'is_featured' => true,
            'user_id' => 1,
            'meta' => [
                'seo_title' => $seoTitle,
                'meta_description' => $metaDescription,
            ],
            'tags' => ['concentré', 'hebdo', 'veille-ia', 'actualités', 'quebec', 'ia-2026'],
        ]);

        $this->command?->info('Article Concentré hebdo S26 créé.');
    }
}
