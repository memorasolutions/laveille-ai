<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait extrait du God-component CourseEditor — médias des items de leçon :
 * téléversement et retrait de l'affiche vidéo (poster) et des pièces jointes
 * document, via Spatie MediaLibrary.
 *
 * SÉCURITÉ : chaque mutation re-résout le cours via $this->resolveCourse() et
 * ré-autorise 'manageStructure' (anti-IDOR), puis resolveItemFor() vérifie
 * l'appartenance de l'item à CE cours (anti-IDOR remontant item→leçon→cours)
 * avant toute écriture. Mimes et tailles validés côté SERVEUR. Les noms de
 * fichiers stockés sont non devinables (safeFileName, défini dans HandlesH5p).
 * Aucun comportement modifié par rapport au composant d'origine.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

trait HandlesItemMedia
{
    /** Taille maximale (Ko) de l'affiche vidéo (~4 Mo). */
    private const POSTER_MAX_KB = 4096;

    /** Taille maximale (Ko) de la pièce jointe document (~10 Mo). */
    private const ATTACHMENT_MAX_KB = 10240;

    // ─────────────────────────────────────────────────────────────────────────
    // AFFICHE VIDÉO (collection « poster »)
    // ─────────────────────────────────────────────────────────────────────────

    public function uploadItemPoster(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $item = $this->resolveItemFor($course, $itemId);

        $this->validate([
            "itemPoster.$itemId" => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::POSTER_MAX_KB],
        ]);

        $file  = $this->itemPoster[$itemId];
        $media = $item->addMedia($file)
            ->usingFileName($this->safeFileName($file, 'affiche'))
            ->toMediaCollection('poster');

        // Le lecteur lit posterUrl() (média en priorité) ; on garde aussi payload['poster']
        // synchronisé pour la rétrocompatibilité de l'affichage existant.
        $payload           = $item->payload ?? [];
        $payload['poster'] = $media->getUrl();
        $item->forceFill(['poster_media_id' => $media->id, 'payload' => $payload])->save();

        unset($this->itemPoster[$itemId]);
        $this->flashSaved('Affiche de la vidéo mise à jour.');
    }

    public function removeItemPoster(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $item = $this->resolveItemFor($course, $itemId);
        $item->clearMediaCollection('poster');

        $payload = $item->payload ?? [];
        unset($payload['poster']);
        $item->forceFill(['poster_media_id' => null, 'payload' => $payload])->save();

        unset($this->itemPoster[$itemId]);
        $this->flashSaved('Affiche de la vidéo retirée.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PIÈCES JOINTES DOCUMENT (collection « attachments »)
    // ─────────────────────────────────────────────────────────────────────────

    public function uploadItemAttachment(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $item = $this->resolveItemFor($course, $itemId);

        $this->validate([
            "itemAttachment.$itemId" => [
                'required', 'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png,webp',
                'max:'.self::ATTACHMENT_MAX_KB,
            ],
        ]);

        $file = $this->itemAttachment[$itemId];

        // Lire le nom client AVANT addMedia() (qui déplace le fichier temporaire).
        // Ce nom ne sert que de LIBELLÉ d'affichage, jamais de nom de stockage.
        $displayName = $file->getClientOriginalName();

        $media = $item->addMedia($file)
            ->usingFileName($this->safeFileName($file, 'document'))
            ->toMediaCollection('attachments');

        // Le lecteur (lesson.blade) lit payload['attachments'] = [{name, url}].
        $payload                = $item->payload ?? [];
        $attachments            = $payload['attachments'] ?? [];
        $attachments[]          = [
            'name'     => $displayName,
            'url'      => $media->getUrl(),
            'media_id' => $media->id,
        ];
        $payload['attachments'] = array_values($attachments);
        $item->forceFill(['payload' => $payload])->save();

        unset($this->itemAttachment[$itemId]);
        $this->flashSaved('Pièce jointe ajoutée.');
    }

    public function removeItemAttachment(int $itemId, int $mediaId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $item = $this->resolveItemFor($course, $itemId);

        // Le média doit appartenir à CET item (anti-IDOR) avant suppression.
        $media = $item->getMedia('attachments')->firstWhere('id', $mediaId);
        if ($media) {
            $media->delete();
        }

        $payload                = $item->payload ?? [];
        $payload['attachments'] = array_values(array_filter(
            $payload['attachments'] ?? [],
            fn ($a) => ($a['media_id'] ?? null) !== $mediaId
        ));
        $item->forceFill(['payload' => $payload])->save();

        $this->flashSaved('Pièce jointe retirée.');
    }
}
