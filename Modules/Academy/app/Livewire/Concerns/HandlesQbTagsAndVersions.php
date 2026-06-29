<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait extrait du God-component QuestionBankManager — TAGS (F17) et VERSIONS :
 * synchronisation owner-scopée des étiquettes (création à la volée), archivage
 * de version avant écriture (uniquement si le contenu change), affichage et
 * restauration de l'historique.
 *
 * SÉCURITÉ : les tags sont toujours créés / résolus pour owner_id = auth (un tag
 * d'un autre owner n'est jamais attaché). La restauration de version re-résout la
 * question et borne la version à owner_id = auth (anti-IDOR en profondeur). Aucun
 * comportement modifié.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionTag;
use Modules\Academy\Models\QuestionVersion;

trait HandlesQbTagsAndVersions
{
    // ─────────────────────────────────────────────────────────────────────────
    // TAGS — synchronisation owner-scopée
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Synchronise les étiquettes de la question depuis la saisie $qTags (libellés
     * séparés par des virgules). Owner-scopé STRICT : chaque tag est résolu/créé pour
     * l'OWNER de la question. Un tag d'un AUTRE owner n'est jamais attaché.
     */
    private function syncTags(Question $question): void
    {
        $ownerId = (int) ($question->owner_id ?: Auth::id());

        $tagIds    = [];
        $seenSlugs = [];

        foreach (explode(',', $this->qTags) as $raw) {
            if (count($tagIds) >= self::MAX_TAGS) {
                break;
            }

            $name = trim((string) $raw);
            if ($name === '') {
                continue;
            }
            $name = mb_substr($name, 0, self::MAX_TAG_LENGTH);
            $slug = QuestionTag::slugify($name);
            if ($slug === '' || isset($seenSlugs[$slug])) {
                continue;
            }
            $seenSlugs[$slug] = true;

            $tag = QuestionTag::firstOrCreate(
                ['owner_id' => $ownerId, 'slug' => $slug],
                ['name' => $name]
            );
            $tagIds[] = $tag->id;
        }

        $question->tags()->sync($tagIds);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // VERSIONS — archivage automatique avant écriture
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * F17 (VERSIONS) : archive l'état PRÉCÉDENT de la question AVANT écriture, mais
     * SEULEMENT si le contenu noté change (prompt / type / explication / payload). Une
     * édition purement « cosmétique » (difficulté, points, activité, tags) ne génère
     * pas de version.
     *
     * @param  array<string, mixed>  $newAttributes
     */
    private function maybeSnapshotVersion(Question $question, array $newAttributes): void
    {
        $oldPayload = is_array($question->payload) ? $question->payload : [];
        $newPayload = is_array($newAttributes['payload'] ?? null) ? $newAttributes['payload'] : [];

        $contentChanged = (string) $question->prompt !== (string) ($newAttributes['prompt'] ?? '')
            || (string) $question->type !== (string) ($newAttributes['type'] ?? '')
            || (string) ($question->explanation ?? '') !== (string) ($newAttributes['explanation'] ?? '')
            || json_encode($oldPayload) !== json_encode($newPayload);

        if (! $contentChanged) {
            return;
        }

        // Transaction : isole le max()+1 et le INSERT pour éviter une collision de numéro
        // de version en cas d'édition simultanée.
        DB::transaction(function () use ($question, $oldPayload): void {
            $nextVersion = (int) QuestionVersion::where('question_id', $question->id)->max('version') + 1;

            QuestionVersion::create([
                'question_id' => $question->id,
                'owner_id'    => (int) ($question->owner_id ?: Auth::id()),
                'version'     => $nextVersion,
                'prompt'      => (string) $question->prompt,
                'payload'     => $oldPayload,
                'explanation' => $question->explanation,
                'type'        => (string) $question->type,
                'snapshot_at' => now(),
            ]);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Panneau d'historique (lecture seule)
    // ─────────────────────────────────────────────────────────────────────────

    /** Ouvre le panneau d'historique d'une question (anti-IDOR via resolveQuestion). */
    public function showHistory(int $questionId): void
    {
        $question = $this->resolveQuestion($questionId);
        $this->historyQuestionId = $question->id;
    }

    public function closeHistory(): void
    {
        $this->historyQuestionId = null;
    }

    /**
     * Recharge une version archivée DANS le formulaire d'édition (l'utilisateur
     * ré-enregistre ensuite pour restaurer). Anti-IDOR : la version est bornée à une
     * question résolue owner-scopée et à owner_id = auth.
     */
    public function restoreVersion(int $versionId): void
    {
        if ($this->historyQuestionId === null) {
            return;
        }

        $question = $this->resolveQuestion((int) $this->historyQuestionId);

        // Anti-IDOR en profondeur : on borne aussi à owner_id = auth.
        $version = QuestionVersion::where('id', $versionId)
            ->where('question_id', $question->id)
            ->where('owner_id', Auth::id())
            ->firstOrFail();

        $this->editingQuestionId  = $question->id;
        $this->selectedCategoryId = (int) $question->category_id;

        $this->qType        = in_array($version->type, Question::TYPES, true) ? $version->type : 'mcq';
        $this->qPrompt      = (string) $version->prompt;
        $this->qExplanation = $version->explanation;
        // Difficulté / points / activité / tags ne sont pas versionnés → conservés tels quels.
        $this->qDifficulty  = in_array($question->difficulty, self::DIFFICULTIES, true) ? (string) $question->difficulty : 'moyen';
        $this->qPoints      = max(1, min(100, (int) ($question->points ?? 1)));
        $this->qIsActive    = (bool) $question->is_active;
        $this->qTags        = $question->tags()->orderBy('name')->pluck('name')->implode(', ');

        $this->hydratePayloadForm($this->qType, is_array($version->payload) ? $version->payload : []);

        $this->historyQuestionId = null;
        $this->resetErrorBag();
        session()->flash('academy_bank_status', 'Version '.$version->version.' rechargée dans le formulaire. Enregistrez pour la restaurer.');
    }
}
