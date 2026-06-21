<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Service de duplication de cours (Phase C / C3 - duplication + modèles).
 *
 * Duplique EN PROFONDEUR un cours source vers un cours NEUF appartenant à un
 * nouveau propriétaire : métadonnées → chapitres → leçons → items (payload inclus),
 * médias Spatie (couverture du cours, affiche/pièces jointes des items) copiés de
 * façon DÉFENSIVE. Tout se fait dans UNE transaction (atomicité : un échec ne
 * laisse jamais un cours à moitié dupliqué).
 *
 * Ce qui N'EST PAS copié (cours NEUF, pas un clone d'historique) :
 *  - inscriptions (enrollments) ;
 *  - complétions / progression / certificats ;
 *  - rôles de cours du source (course_roles) — le nouveau propriétaire reçoit un
 *    rôle 'owner' tout neuf.
 *
 * Le nouveau cours est toujours créé en 'draft' (jamais publié automatiquement),
 * avec un slug UNIQUE dérivé du titre, is_template = false.
 *
 * SÉCURITÉ : ce service NE fait PAS d'autorisation (séparation des responsabilités).
 * L'appelant (Livewire) DOIT re-résoudre le cours source côté serveur et appeler
 * authorize('update'|'view', $source) + authorize('create', Course::class) AVANT
 * d'invoquer duplicate(). Le service se contente de copier la structure.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class CourseDuplicator
{
    /**
     * Duplique le cours $source vers un NOUVEAU cours possédé par $newOwner.
     *
     * @return Course le nouveau cours (brouillon, slug unique, owner = $newOwner)
     */
    public function duplicate(Course $source, User $newOwner): Course
    {
        return DB::transaction(function () use ($source, $newOwner): Course {
            $copy = $this->copyCourse($source, $newOwner);

            // Rôle 'owner' tout neuf pour le duplicateur (course_roles du source NON copiés).
            CourseRole::create([
                'course_id' => $copy->id,
                'user_id'   => $newOwner->id,
                'role'      => 'owner',
            ]);

            // Couverture du cours (collection « cover »).
            $this->copyMediaCollection($source, $copy, 'cover');

            // Structure profonde : chapitres → leçons → items (+ médias des items).
            $this->copyStructure($source, $copy);

            return $copy->refresh();
        });
    }

    /**
     * Crée le cours-copie : métadonnées du source + titre « (copie) » + slug UNIQUE,
     * status forcé 'draft', is_template false, created_by/updated_by = nouvel owner.
     */
    private function copyCourse(Course $source, User $newOwner): Course
    {
        $title = trim((string) $source->title).' (copie)';

        return Course::create([
            'slug'                => $this->uniqueCourseSlug($title),
            'title'               => $title,
            'subtitle'            => $source->subtitle,
            'summary'             => $source->summary,
            'description'         => $source->description,
            'language'            => $source->language,
            'level'               => $source->level,
            'duration_minutes'    => $source->duration_minutes,
            'visibility'          => $source->visibility,
            // Un cours neuf est toujours gratuit + brouillon (le duplicateur ajuste ensuite).
            'access_type'         => 'free',
            'price_cents'         => null,
            'currency'            => $source->currency,
            'stripe_price_id'     => null,
            'status'              => 'draft',
            'is_template'         => false,
            'published_at'        => null,
            'seo_jsonld'          => $source->seo_jsonld,
            'faq_dictionary_ids'  => $source->faq_dictionary_ids,
            'tools_collection_id' => $source->tools_collection_id,
            'created_by'          => $newOwner->id,
            'updated_by'          => $newOwner->id,
        ]);
    }

    /**
     * Copie chapitres → leçons → items du cours source vers la copie, en préservant
     * l'ordre (position) et tous les champs des items (payload, is_required, etc.).
     * Les slugs de leçons sont régénérés et rendus uniques.
     */
    private function copyStructure(Course $source, Course $copy): void
    {
        $chapters = Chapter::where('course_id', $source->id)
            ->orderBy('position')
            ->get();

        foreach ($chapters as $chapter) {
            $newChapter = Chapter::create([
                'course_id' => $copy->id,
                'title'     => $chapter->title,
                'position'  => $chapter->position,
                'summary'   => $chapter->summary,
            ]);

            $lessons = Lesson::where('chapter_id', $chapter->id)
                ->orderBy('position')
                ->get();

            foreach ($lessons as $lesson) {
                $newLesson = Lesson::create([
                    'chapter_id'        => $newChapter->id,
                    'title'             => $lesson->title,
                    'slug'              => $this->uniqueLessonSlug($lesson->title),
                    'position'          => $lesson->position,
                    'summary'           => $lesson->summary,
                    'estimated_minutes' => $lesson->estimated_minutes,
                ]);

                $items = LessonItem::where('lesson_id', $lesson->id)
                    ->orderBy('position')
                    ->get();

                foreach ($items as $item) {
                    $this->copyItem($item, $newLesson);
                }
            }
        }
    }

    /**
     * Copie un item de leçon (payload inclus) puis ses médias Spatie (affiche
     * « poster » + pièces jointes « attachments »). Si des médias sont copiés, on
     * resynchronise les URL/identifiants correspondants dans le payload et la colonne
     * poster_media_id vers les NOUVEAUX médias.
     */
    private function copyItem(LessonItem $item, Lesson $newLesson): void
    {
        $newItem = LessonItem::create([
            'lesson_id'         => $newLesson->id,
            'type'              => $item->type,
            'title'             => $item->title,
            'position'          => $item->position,
            'payload'           => $item->payload,
            'estimated_minutes' => $item->estimated_minutes,
            'is_required'       => $item->is_required,
            'external_ref'      => $item->external_ref,
        ]);

        // Affiche vidéo (collection « poster », singleFile).
        $newPoster = $this->copyMediaCollection($item, $newItem, 'poster');

        // Pièces jointes document (collection « attachments », plusieurs fichiers).
        // On garde l'association ancien média → nouveau média pour resynchroniser le payload.
        $attachmentMap = $this->copyAttachments($item, $newItem);

        $this->resyncItemPayloadMedia($newItem, $newPoster, $attachmentMap);
    }

    /**
     * Copie les médias d'une collection d'un modèle source vers un modèle cible, de
     * façon DÉFENSIVE : tout échec (média manquant sur le disque, etc.) est loggé et
     * NE bloque JAMAIS la duplication de la structure. Retourne le PREMIER nouveau
     * média copié (utile pour les collections singleFile comme « cover »/« poster »),
     * ou null.
     *
     * @param  \Spatie\MediaLibrary\HasMedia  $sourceModel
     * @param  \Spatie\MediaLibrary\HasMedia  $targetModel
     */
    private function copyMediaCollection($sourceModel, $targetModel, string $collection): ?Media
    {
        $first = null;

        try {
            foreach ($sourceModel->getMedia($collection) as $media) {
                $new = $media->copy($targetModel, $collection);
                $first ??= $new;
            }
        } catch (Throwable $e) {
            Log::warning('Academy CourseDuplicator: échec copie média ('.$collection.') — '.$e->getMessage(), [
                'source_id'  => $sourceModel->id ?? null,
                'collection' => $collection,
            ]);
        }

        return $first;
    }

    /**
     * Copie les pièces jointes « attachments » d'un item vers la copie et retourne
     * la table d'association [ancien media_id => nouveau Media] pour resynchroniser
     * les URL stockées dans payload['attachments']. Défensif (try/catch global).
     *
     * @return array<int, Media>
     */
    private function copyAttachments(LessonItem $item, LessonItem $newItem): array
    {
        $map = [];

        try {
            foreach ($item->getMedia('attachments') as $media) {
                $new                = $media->copy($newItem, 'attachments');
                $map[(int) $media->id] = $new;
            }
        } catch (Throwable $e) {
            Log::warning('Academy CourseDuplicator: échec copie pièces jointes — '.$e->getMessage(), [
                'item_id' => $item->id,
            ]);
        }

        return $map;
    }

    /**
     * Resynchronise les références média dans le payload/colonnes du NOUVEL item vers
     * les médias fraîchement copiés (sinon le payload pointerait encore vers les URL
     * du source). Sans média copié, on laisse le payload tel quel (acceptable :
     * d'éventuelles URL externes restent valides).
     *
     * @param  array<int, Media>  $attachmentMap  ancien media_id => nouveau Media
     */
    private function resyncItemPayloadMedia(LessonItem $newItem, ?Media $newPoster, array $attachmentMap): void
    {
        if ($newPoster === null && $attachmentMap === []) {
            return; // rien copié → on garde le payload d'origine.
        }

        $payload = $newItem->payload ?? [];

        if ($newPoster !== null) {
            $payload['poster'] = $newPoster->getUrl();
            $newItem->poster_media_id = $newPoster->id;
        }

        if ($attachmentMap !== [] && ! empty($payload['attachments']) && is_array($payload['attachments'])) {
            $payload['attachments'] = array_values(array_map(
                function ($attachment) use ($attachmentMap) {
                    $oldId = (int) ($attachment['media_id'] ?? 0);
                    if ($oldId > 0 && isset($attachmentMap[$oldId])) {
                        $new                  = $attachmentMap[$oldId];
                        $attachment['url']      = $new->getUrl();
                        $attachment['media_id'] = $new->id;
                    }

                    return $attachment;
                },
                $payload['attachments']
            ));
        }

        $newItem->forceFill(['payload' => $payload])->save();
    }

    /**
     * Slug de cours UNIQUE dérivé d'un titre. Suffixe -2, -3, … en cas de collision
     * (y compris avec des cours soft-deleted, car la colonne slug est unique en base).
     */
    private function uniqueCourseSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'cours';
        $slug = $base;
        $i    = 2;

        while (Course::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    /**
     * Slug de leçon UNIQUE dérivé d'un titre (les slugs de leçons ne sont pas
     * contraints unique en base, mais on les garde distincts par hygiène).
     */
    private function uniqueLessonSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'lecon';
        $slug = $base;
        $i    = 2;

        while (Lesson::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
