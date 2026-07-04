<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Import SCORM - ajout d'un nouvel item « scorm » depuis un paquet téléversé,
 * remplacement du paquet d'un item existant. Mirroir DÉLIBÉRÉ de HandlesH5p
 * (F16) : même structure, même posture de sécurité, DRY dans l'ESPRIT (le
 * détail d'extraction diffère : disque privé + parsing manifeste XML).
 *
 * SÉCURITÉ : resolveCourse() → authorize('manageStructure') → canUploadScorm()
 * (restreint aux admins : le JS embarqué dans un SCO tourne côté navigateur,
 * même risque accepté que H5P) → resolveItemFor() (anti-IDOR) → validation
 * (extension/taille) → extraction via ScormPackageService (manifeste XML
 * anti-XXE, anti zip-slip, liste noire exécutables, disque PRIVÉ). Drapeau
 * academy.scorm_enabled vérifié en tête de chaque mutation (404 si désactivé,
 * même comportement que les routes runtime).
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\ScormPackageService;

trait HandlesScorm
{
    /** Taille maximale (Ko) d'un paquet SCORM (~200 Mo par défaut, cf. config). */
    private function scormMaxKb(): int
    {
        return (int) config('academy.scorm.max_kb', 204800);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GARDE ADMIN (JS tiers embarqué dans le SCO : même risque que H5P)
    // ─────────────────────────────────────────────────────────────────────────

    private function canUploadScorm(): bool
    {
        return (bool) Auth::user()?->can('academy.manage');
    }

    /**
     * Règles de validation d'un téléversement SCORM (extension + taille).
     */
    private function scormFileRules(mixed $file): array
    {
        $rules = ['required', 'file', 'extensions:zip', 'max:'.$this->scormMaxKb()];

        $ext = is_object($file) && method_exists($file, 'getClientOriginalExtension')
            ? strtolower((string) $file->getClientOriginalExtension())
            : '';

        if ($ext === 'zip') {
            $rules[] = 'mimes:zip';
        }

        return $rules;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJOUT D'UN NOUVEL ITEM SCORM
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Crée un NOUVEL item « scorm » à partir d'un paquet .zip téléversé pour la
     * leçon. Titre : celui saisi (newItem.{lesson}.title) sinon celui lu dans
     * imsmanifest.xml. Un paquet invalide (zip corrompu, manifeste manquant,
     * launch introuvable, zip-slip) est rejeté proprement en erreur de champ
     * (jamais de 500).
     */
    public function addScormItem(int $lessonId): void
    {
        abort_unless((bool) config('academy.scorm_enabled', false), 404);

        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        if (! $this->canUploadScorm()) {
            $this->addError("newScorm.$lessonId", 'Seul un administrateur peut téléverser un paquet SCORM (sécurité : code tiers).');

            return;
        }

        // Anti-IDOR : la leçon doit appartenir à un chapitre de CE cours.
        $lesson = $this->resolveLessonFor($course, $lessonId);

        $this->validate(
            ["newScorm.$lessonId" => $this->scormFileRules($this->newScorm[$lessonId] ?? null)],
            [],
            ["newScorm.$lessonId" => 'paquet SCORM']
        );

        try {
            $result = (new ScormPackageService())->extract($this->newScorm[$lessonId]);
        } catch (\Throwable $e) {
            $this->addError("newScorm.$lessonId", $e->getMessage());

            return;
        }

        $title    = trim((string) ($this->newItem[$lessonId]['title'] ?? '')) ?: $result['title'];
        $position = (int) LessonItem::where('lesson_id', $lesson->id)->max('position') + 1;

        LessonItem::create([
            'lesson_id'   => $lesson->id,
            'type'        => 'scorm',
            'title'       => mb_substr($title, 0, 255),
            'position'    => $position,
            'payload'     => [
                'scorm_path'       => $result['path'],
                'scorm_launch_url' => $result['launch_url'],
                'scorm_version'    => $result['version'],
                'scorm_title'      => $result['title'],
            ],
            'is_required' => (bool) ($this->newItem[$lessonId]['is_required'] ?? false),
        ]);

        unset($this->newScorm[$lessonId], $this->newItem[$lessonId]);
        $this->flashSaved('Paquet SCORM ajouté.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REMPLACEMENT D'UN PAQUET SCORM EXISTANT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Remplace le paquet d'un item « scorm » existant (anti-IDOR : item de CE
     * cours). L'ancien dossier extrait est supprimé APRÈS extraction réussie du
     * nouveau (pas de fenêtre où l'item pointe vers un dossier supprimé). La
     * progression déjà enregistrée (ScormRegistration/Completion) n'est PAS
     * réinitialisée par un remplacement (comportement H5P identique).
     */
    public function replaceScormPackage(int $itemId): void
    {
        abort_unless((bool) config('academy.scorm_enabled', false), 404);

        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        if (! $this->canUploadScorm()) {
            $this->addError("itemScorm.$itemId", 'Seul un administrateur peut téléverser un paquet SCORM (sécurité : code tiers).');

            return;
        }

        $item = $this->resolveItemFor($course, $itemId);
        if ($item->type !== 'scorm') {
            return;
        }

        $this->validate(
            ["itemScorm.$itemId" => $this->scormFileRules($this->itemScorm[$itemId] ?? null)],
            [],
            ["itemScorm.$itemId" => 'paquet SCORM']
        );

        $service = new ScormPackageService();

        try {
            $result = $service->extract($this->itemScorm[$itemId]);
        } catch (\Throwable $e) {
            $this->addError("itemScorm.$itemId", $e->getMessage());

            return;
        }

        $oldPath                        = $item->payload['scorm_path'] ?? null;
        $payload                        = $item->payload ?? [];
        $payload['scorm_path']          = $result['path'];
        $payload['scorm_launch_url']    = $result['launch_url'];
        $payload['scorm_version']       = $result['version'];
        $payload['scorm_title']         = $result['title'];
        $item->forceFill(['payload' => $payload])->save();

        if (is_string($oldPath) && $oldPath !== $result['path']) {
            $service->delete($oldPath);
        }

        unset($this->itemScorm[$itemId]);
        $this->flashSaved('Paquet SCORM remplacé.');
    }
}
