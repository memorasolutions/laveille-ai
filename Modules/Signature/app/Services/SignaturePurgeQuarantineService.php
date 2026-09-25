<?php

declare(strict_types=1);

namespace Modules\Signature\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Modules\Signature\Models\Signature;

/**
 * Filet de rollback (B2) exigé par la règle absolue du projet : « jamais supprimer de données
 * utilisateurs sans filet ». Avant TOUTE suppression réelle (purge automatique OU suppression
 * explicite d'un membre, M3.4), ce service :
 *   (a) écrit une ligne JSONL (contenu complet + métadonnées des fichiers) dans une archive
 *       PRIVÉE datée (config signature.purge_archive_directory) ;
 *   (b) DÉPLACE (jamais ne copie ni ne supprime directement) chaque fichier image vers un dossier
 *       de quarantaine PRIVÉ (config signature.quarantine_directory), sur un disque distinct du
 *       disque servi publiquement.
 * La donnée reste ainsi reconstructible pendant 'quarantine_retention_days' jours (voir
 * Modules\Signature\Console\RestoreSignatureFromQuarantineCommand), avant que
 * Modules\Signature\Console\PurgeSignatureQuarantineCommand ne la vide définitivement.
 */
final class SignaturePurgeQuarantineService
{
    public function quarantine(Signature $signature, string $sourceDisk, string $reason): void
    {
        if (! $signature->relationLoaded('images')) {
            $signature->load('images');
        }

        $disk = (string) config('signature.quarantine_disk', 'local');
        $quarantineDir = trim((string) config('signature.quarantine_directory', 'signature-quarantaine'), '/');
        $archiveDir = trim((string) config('signature.purge_archive_directory', 'signature-purge-archive'), '/');

        $imagesArchive = [];
        foreach ($signature->images as $image) {
            $quarantinePath = null;

            if ($image->path !== null && Storage::disk($sourceDisk)->exists($image->path)) {
                $quarantinePath = $quarantineDir.'/'.$signature->id.'/'.$image->id.'-'.basename($image->path);
                Storage::disk($disk)->put($quarantinePath, Storage::disk($sourceDisk)->get($image->path));
                Storage::disk($sourceDisk)->delete($image->path);
            }

            $imagesArchive[] = [
                'image_id' => $image->id,
                'role' => $image->role,
                'restore_path' => $image->path,
                'quarantine_path' => $quarantinePath,
                'width' => $image->width,
                'height' => $image->height,
                'display_width' => $image->display_width,
                'display_height' => $image->display_height,
            ];
        }

        $entry = [
            'signature_id' => $signature->id,
            'user_id' => $signature->user_id,
            'template' => $signature->template,
            'content' => $signature->content,
            'reminder_email' => $signature->reminder_email,
            'images' => $imagesArchive,
            'source_disk' => $sourceDisk,
            'reason' => $reason,
            'quarantined_at' => now()->toIso8601String(),
        ];

        $archiveFile = $archiveDir.'/'.now()->toDateString().'.jsonl';
        Storage::disk($disk)->append($archiveFile, json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Restaure une signature depuis la quarantaine (opérateur uniquement, fenêtre de
     * 'quarantine_retention_days' jours) : recherche la DERNIÈRE entrée d'archive correspondant à
     * cette signature, redépose le contenu et déplace les fichiers image de la quarantaine vers
     * leur emplacement d'origine, puis vide l'entrée utilisée de la quarantaine.
     */
    public function restore(Signature $signature): bool
    {
        $disk = (string) config('signature.quarantine_disk', 'local');
        $archiveDir = trim((string) config('signature.purge_archive_directory', 'signature-purge-archive'), '/');

        $entry = null;
        $entryFile = null;

        // Les fichiers d'archive sont nommés AAAA-MM-JJ.jsonl - triés du plus récent au plus
        // ancien pour retenir la DERNIÈRE purge si une même signature avait été purgée plus d'une
        // fois (cas rare, mais jamais supposé impossible).
        $files = collect(Storage::disk($disk)->files($archiveDir))->sortDesc();

        foreach ($files as $file) {
            $lines = explode("\n", (string) Storage::disk($disk)->get($file));
            foreach (array_reverse($lines) as $line) {
                if (trim($line) === '') {
                    continue;
                }
                $decoded = json_decode($line, true);
                if (is_array($decoded) && (int) ($decoded['signature_id'] ?? 0) === $signature->id) {
                    $entry = $decoded;
                    $entryFile = $file;
                    break 2;
                }
            }
        }

        if ($entry === null) {
            return false;
        }

        $sourceDisk = (string) ($entry['source_disk'] ?? config('signature.disk', 'public'));

        foreach ((array) ($entry['images'] ?? []) as $imageArchive) {
            $imageId = (int) ($imageArchive['image_id'] ?? 0);
            $restorePath = $imageArchive['restore_path'] ?? null;
            $quarantinePath = $imageArchive['quarantine_path'] ?? null;

            $image = $signature->images->firstWhere('id', $imageId) ?? $signature->images()->find($imageId);
            if (! $image) {
                continue;
            }

            if ($quarantinePath !== null && $restorePath !== null && Storage::disk($disk)->exists($quarantinePath)) {
                Storage::disk($sourceDisk)->put($restorePath, Storage::disk($disk)->get($quarantinePath));
                Storage::disk($disk)->delete($quarantinePath);
            }

            $image->update([
                'path' => $restorePath,
                'purged_at' => null,
            ]);
        }

        $signature->update([
            'content' => $entry['content'] ?? [],
            'reminder_email' => $entry['reminder_email'] ?? null,
            'status' => Signature::STATUS_ACTIVE,
            'purged_at' => null,
        ]);

        // L'entrée utilisée est retirée du fichier d'archive (restauration = sortie de quarantaine)
        // - les AUTRES entrées du même jour sont conservées intactes.
        if ($entryFile !== null) {
            $this->removeEntryFromArchive($disk, $entryFile, $signature->id);
        }

        return true;
    }

    private function removeEntryFromArchive(string $disk, string $file, int $signatureId): void
    {
        $lines = explode("\n", (string) Storage::disk($disk)->get($file));
        $kept = array_filter($lines, function (string $line) use ($signatureId): bool {
            if (trim($line) === '') {
                return false;
            }
            $decoded = json_decode($line, true);

            return ! (is_array($decoded) && (int) ($decoded['signature_id'] ?? 0) === $signatureId);
        });

        if ($kept === []) {
            Storage::disk($disk)->delete($file);

            return;
        }

        Storage::disk($disk)->put($file, implode("\n", $kept));
    }

    /**
     * Vide définitivement l'archive et la quarantaine pour un fichier d'archive dont la date (dans
     * son nom) dépasse la fenêtre de rétention - utilisé par
     * Modules\Signature\Console\PurgeSignatureQuarantineCommand.
     *
     * @return array{files: int, folders: int}
     */
    public function purgeOlderThan(Carbon $cutoff): array
    {
        $disk = (string) config('signature.quarantine_disk', 'local');
        $archiveDir = trim((string) config('signature.purge_archive_directory', 'signature-purge-archive'), '/');
        $quarantineDir = trim((string) config('signature.quarantine_directory', 'signature-quarantaine'), '/');

        $emptiedFiles = 0;
        $emptiedFolders = 0;

        foreach (Storage::disk($disk)->files($archiveDir) as $file) {
            $basename = pathinfo($file, PATHINFO_FILENAME);
            $fileDate = Carbon::createFromFormat('Y-m-d', $basename);
            if ($fileDate === false || $fileDate->greaterThanOrEqualTo($cutoff)) {
                continue;
            }

            $lines = explode("\n", (string) Storage::disk($disk)->get($file));
            foreach ($lines as $line) {
                if (trim($line) === '') {
                    continue;
                }
                $decoded = json_decode($line, true);
                $signatureId = $decoded['signature_id'] ?? null;
                if ($signatureId === null) {
                    continue;
                }

                $folder = $quarantineDir.'/'.$signatureId;
                if (Storage::disk($disk)->exists($folder)) {
                    Storage::disk($disk)->deleteDirectory($folder);
                    $emptiedFolders++;
                }
            }

            Storage::disk($disk)->delete($file);
            $emptiedFiles++;
        }

        return ['files' => $emptiedFiles, 'folders' => $emptiedFolders];
    }
}
