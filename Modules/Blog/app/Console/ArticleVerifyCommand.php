<?php

declare(strict_types=1);

namespace Modules\Blog\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\ArticleVerification;
use Modules\News\Models\NewsArticle;
use Throwable;

/**
 * Porte d'écriture bornée pour les vérifications factuelles sur les articles de blogue
 * (2026-08-31, demande fondateur : « aussi avoir des tags qui disent si on contredit une
 * nouvelle qui circule sur internet »).
 *
 * Jumeau du mécanisme déjà en place sur les actualités (Modules\News\Console\
 * NewsApplyCommand, clé `fact_check` du payload), mais adapté à une LISTE plutôt qu'à une
 * colonne unique : un article de blogue peut examiner PLUSIEURS affirmations, contrairement à
 * une actualité qui n'en examine qu'une seule. Décision de structure du 2026-08-31, tranchée
 * en panel, unanime : jamais un verdict global sur l'article entier - un verdict global
 * écraserait des conclusions hétérogènes.
 *
 * Le vocabulaire des verdicts vit À UN SEUL ENDROIT, NewsArticle::FACT_CHECK_VERDICTS, jamais
 * copié ici. Le statut orthogonal « vérification non concluante » est disponible dès la
 * création (contrairement aux actualités, où il a été ajouté après coup) - même mécanisme,
 * même exclusivité avec le verdict.
 *
 * Pas de restriction de statut publié/brouillon sur l'article (contrairement à news:apply) :
 * une vérification ne touche jamais au contenu éditorial de l'article lui-même, elle peut donc
 * être posée, corrigée ou retirée quel que soit l'état de l'article.
 *
 * Trois modes, distingués par la forme du payload (clé unique `verification`) :
 *   - création  : objet sans `id`.
 *   - mise à jour : objet avec `id` (doit appartenir à CET article).
 *   - retrait (suppression douce) : objet avec `id` et `remove: true`, aucune autre clé.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */
class ArticleVerifyCommand extends Command
{
    protected $signature = 'blog:verify {article : id de l\'article de blogue (table articles)} {--payload= : chemin d\'un fichier JSON portant la clé verification}';

    protected $description = 'Porte d\'écriture bornée pour attacher, corriger ou retirer une vérification sur un article de blogue - jamais d\'Eloquent/SQL direct par l\'agent.';

    public function handle(): int
    {
        $articleId = (int) $this->argument('article');
        $article = Article::find($articleId);

        if (! $article) {
            $this->error("Article de blogue introuvable : {$articleId}.");

            return self::FAILURE;
        }

        $payloadPath = $this->option('payload');

        if (! $payloadPath) {
            $this->error('Fournis --payload=<fichier.json> portant la clé verification.');

            return self::FAILURE;
        }

        if (! file_exists((string) $payloadPath)) {
            $this->error("Fichier introuvable : {$payloadPath}.");

            return self::FAILURE;
        }

        $decoded = json_decode((string) file_get_contents((string) $payloadPath), true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            $this->error('Payload JSON invalide.');

            return self::FAILURE;
        }

        if (! isset($decoded['verification']) || ! is_array($decoded['verification'])) {
            $this->error("Le payload doit porter une clé 'verification' (objet).");

            return self::FAILURE;
        }

        $data = $decoded['verification'];
        $allowed = ['id', 'remove', 'claim', 'verdict', 'inconclusive', 'motif', 'sources', 'source_url', 'verified_at'];
        $inconnues = array_diff(array_keys($data), $allowed);

        if ($inconnues !== []) {
            $this->error('verification : sous-clé(s) inconnue(s) refusée(s) : '.implode(', ', $inconnues).' (attendu : '.implode(', ', $allowed).').');

            return self::FAILURE;
        }

        $isRemoval = array_key_exists('remove', $data) && $data['remove'] === true;

        if ($isRemoval) {
            return $this->removeVerification($article, $data);
        }

        return $this->upsertVerification($article, $data);
    }

    /**
     * Mode retrait (suppression douce) : la clé `remove: true` n'accepte AUCUNE autre clé que
     * `id` - jamais combinée avec une correction, pour qu'un retrait reste un geste explicite et
     * sans ambiguïté sur ce qu'il fait.
     */
    private function removeVerification(Article $article, array $data): int
    {
        if (array_diff(array_keys($data), ['id', 'remove']) !== []) {
            $this->error('remove n\'accepte aucune autre clé que id.');

            return self::FAILURE;
        }

        if (! isset($data['id']) || ! is_int($data['id']) || $data['id'] <= 0) {
            $this->error('remove nécessite un id valide (entier).');

            return self::FAILURE;
        }

        $verification = ArticleVerification::where('article_id', $article->id)->find((int) $data['id']);

        if (! $verification) {
            $this->error("Vérification {$data['id']} introuvable pour l'article {$article->id}.");

            return self::FAILURE;
        }

        $verification->delete();

        Log::channel('composition')->info('blog:verify - vérification retirée', [
            'article_id' => $article->id,
            'verification_id' => $verification->id,
        ]);

        $this->info("Article {$article->id} : vérification {$verification->id} retirée.");

        return self::SUCCESS;
    }

    /**
     * Mode création ou mise à jour. `claim` et le couple verdict/inconclusive sont TOUJOURS
     * exigés, création comme mise à jour (jamais une omission qui laisserait un état ambigu sur
     * ce qui est tranché). Les champs optionnels (motif, sources, source_url) suivent la
     * doctrine « absence ne veut jamais dire effacement » déjà en vigueur côté actualités : une
     * clé absente laisse la valeur existante intacte, une clé explicitement `null` l'efface.
     */
    private function upsertVerification(Article $article, array $data): int
    {
        $isUpdate = array_key_exists('id', $data);

        if ($isUpdate) {
            if (! isset($data['id']) || ! is_int($data['id']) || $data['id'] <= 0) {
                $this->error('id doit être un entier positif.');

                return self::FAILURE;
            }

            $verification = ArticleVerification::where('article_id', $article->id)->find((int) $data['id']);

            if (! $verification) {
                $this->error("Vérification {$data['id']} introuvable pour l'article {$article->id}.");

                return self::FAILURE;
            }
        } else {
            $verification = new ArticleVerification(['article_id' => $article->id]);
        }

        $allowedVerdicts = array_keys(NewsArticle::FACT_CHECK_VERDICTS);
        $inconclusif = array_key_exists('inconclusive', $data) && $data['inconclusive'] === true;

        if ($inconclusif && array_key_exists('verdict', $data) && $data['verdict'] !== null) {
            $this->error('verification.inconclusive et verification.verdict sont exclusifs : une entrée est soit tranchée, soit non concluante, jamais les deux.');

            return self::FAILURE;
        }

        if (! $inconclusif
            && (! isset($data['verdict']) || ! is_string($data['verdict']) || ! in_array($data['verdict'], $allowedVerdicts, true))) {
            $this->error('verification.verdict attendu parmi '.implode(', ', $allowedVerdicts).' (ou "inconclusive": true à la place).');

            return self::FAILURE;
        }

        if (! isset($data['claim']) || ! is_string($data['claim']) || trim($data['claim']) === '' || mb_strlen($data['claim']) > 300) {
            $this->error('verification.claim doit être une chaîne non vide de 300 caractères maximum (affirmation examinée, en une phrase).');

            return self::FAILURE;
        }

        $motifResult = $this->applyOptionalText($verification, $data, 'motif', 2000);
        if ($motifResult !== self::SUCCESS) {
            return $motifResult;
        }

        $sourceUrlResult = $this->applyOptionalUrl($verification, $data, 'source_url');
        if ($sourceUrlResult !== self::SUCCESS) {
            return $sourceUrlResult;
        }

        $sourcesResult = $this->applyOptionalSourcesList($verification, $data);
        if ($sourcesResult !== self::SUCCESS) {
            return $sourcesResult;
        }

        $verifiedAtResult = $this->applyVerifiedAt($verification, $data, $isUpdate);
        if ($verifiedAtResult !== self::SUCCESS) {
            return $verifiedAtResult;
        }

        $verification->claim = trim($data['claim']);
        $verification->verdict = $inconclusif ? null : $data['verdict'];
        $verification->inconclusive_at = $inconclusif ? now('America/Toronto') : null;

        if (! $isUpdate) {
            $maxPosition = ArticleVerification::where('article_id', $article->id)->max('position') ?? -1;
            $verification->position = $maxPosition + 1;
        }

        $verification->save();

        Log::channel('composition')->info('blog:verify - vérification appliquée', [
            'article_id' => $article->id,
            'verification_id' => $verification->id,
            'verdict' => $verification->verdict,
            'inconclusive' => $verification->inconclusive_at !== null,
        ]);

        $factCheckMessage = $verification->verdict ?? 'non concluante';
        $this->info("Article {$article->id} : vérification {$verification->id} - {$factCheckMessage}.");

        return self::SUCCESS;
    }

    /**
     * motif : chaîne libre facultative, bornée à 2000 caractères (propre au cas précis, distincte
     * de la phrase générique du verdict). Absence = ne pas toucher ; null explicite = effacer.
     */
    private function applyOptionalText(ArticleVerification $verification, array $data, string $key, int $maxLength): int
    {
        if (! array_key_exists($key, $data)) {
            return self::SUCCESS;
        }

        if ($data[$key] === null) {
            $verification->{$key} = null;

            return self::SUCCESS;
        }

        if (! is_string($data[$key]) || mb_strlen($data[$key]) > $maxLength) {
            $this->error("verification.{$key} dépasse {$maxLength} caractères.");

            return self::FAILURE;
        }

        $verification->{$key} = $data[$key];

        return self::SUCCESS;
    }

    /**
     * source_url : même garde-fou de schéma que fact_check.source côté actualités (défense en
     * profondeur contre un href exécutable dans le badge public - http(s) uniquement, jamais
     * javascript:/data:). Absence = ne pas toucher ; null explicite = effacer.
     */
    private function applyOptionalUrl(ArticleVerification $verification, array $data, string $key): int
    {
        if (! array_key_exists($key, $data)) {
            return self::SUCCESS;
        }

        if ($data[$key] === null) {
            $verification->{$key} = null;

            return self::SUCCESS;
        }

        if (! $this->isValidHttpUrl($data[$key])) {
            $this->error("verification.{$key} doit être une URL http(s) valide (où circule l'affirmation), ou être absente.");

            return self::FAILURE;
        }

        $verification->{$key} = $data[$key];

        return self::SUCCESS;
    }

    /**
     * sources : tableau de 5 URL probantes maximum, même garde-fou de schéma que source_url,
     * appliqué à chaque élément. Absence = ne pas toucher ; null explicite = effacer.
     */
    private function applyOptionalSourcesList(ArticleVerification $verification, array $data): int
    {
        if (! array_key_exists('sources', $data)) {
            return self::SUCCESS;
        }

        if ($data['sources'] === null) {
            $verification->sources = null;

            return self::SUCCESS;
        }

        if (! is_array($data['sources']) || count($data['sources']) > 5) {
            $this->error('verification.sources : 5 URL maximum.');

            return self::FAILURE;
        }

        foreach ($data['sources'] as $source) {
            if (! $this->isValidHttpUrl($source)) {
                $this->error('verification.sources contient une URL invalide (http(s) uniquement).');

                return self::FAILURE;
            }
        }

        $verification->sources = $data['sources'];

        return self::SUCCESS;
    }

    /**
     * verified_at : absente et création -> date du jour (America/Toronto). Absente et mise à
     * jour -> ne pas toucher. Fournie -> doit être une date parsable. Explicitement `null` ->
     * refusée : une vérification tranchée ou non concluante garde toujours une date.
     */
    private function applyVerifiedAt(ArticleVerification $verification, array $data, bool $isUpdate): int
    {
        if (! array_key_exists('verified_at', $data)) {
            if (! $isUpdate) {
                $verification->verified_at = now('America/Toronto');
            }

            return self::SUCCESS;
        }

        if ($data['verified_at'] === null) {
            $this->error('verification.verified_at ne peut pas être effacée - fournis une date, ou omets la clé pour ne pas y toucher.');

            return self::FAILURE;
        }

        try {
            $verification->verified_at = \Carbon\Carbon::parse((string) $data['verified_at']);
        } catch (Throwable) {
            $this->error('verification.verified_at n\'est pas une date valide.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Même garde-fou qu'ailleurs dans le module : filter_var(FILTER_VALIDATE_URL) accepte à lui
     * seul des schémas comme javascript:, qui deviendraient exécutables dans le badge public au
     * premier clic. Seuls http et https passent, vérifiés séparément.
     */
    private function isValidHttpUrl(mixed $value): bool
    {
        if (! is_string($value) || mb_strlen($value) > 2048 || ! filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }

        $schema = mb_strtolower((string) parse_url($value, PHP_URL_SCHEME));

        return in_array($schema, ['http', 'https'], true);
    }
}
