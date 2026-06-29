<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait extrait du God-component CourseEditor — contenu interactif H5P (F16) :
 * ajout d'un nouvel item h5p depuis un paquet téléversé, remplacement du paquet
 * d'un item existant, utilitaires de validation et de nommage de fichiers.
 *
 * SÉCURITÉ : resolveCourse() → authorize('manageStructure') → canUploadH5p()
 * (restreint aux admins : le JS embarqué dans .h5p tourne côté navigateur) →
 * resolveItemFor() (anti-IDOR) → validation (extension/taille) → extraction
 * via H5pPackageService (valide ZIP, anti zip-slip, liste noire exécutables).
 * Le rendu final est isolé dans un iframe sandbox. Aucun comportement modifié.
 *
 * safeFileName() : helper de nommage non devinable, partagé avec saveCover()
 * et HandlesItemMedia (accessibles via $this après composition du trait).
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Academy\Models\LessonItem;

trait HandlesH5p
{
    /** Taille maximale (Ko) d'un paquet .h5p (~30 Mo). */
    private const H5P_MAX_KB = 30720;

    // ─────────────────────────────────────────────────────────────────────────
    // GARDE ADMIN (JS tiers : cf. commentaire sécurité ci-dessus)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * F16 - SÉCURITÉ : un paquet .h5p embarque du JavaScript TIERS rendu dans
     * un iframe « allow-same-origin allow-scripts ». On restreint le téléversement
     * aux comptes ADMIN (permission « academy.manage ») : un formateur peut gérer
     * la structure de SES cours, mais ne peut pas publier de JS tiers.
     *
     * @return bool true si l'utilisateur courant peut téléverser un paquet H5P.
     */
    private function canUploadH5p(): bool
    {
        return (bool) Auth::user()?->can('academy.manage');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RÈGLES DE VALIDATION (DRY, partagées entre addH5pItem et replaceH5pPackage)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Règles de validation d'un téléversement H5P. On accepte .h5p ET .zip ;
     * pour un fichier nommé « .zip » on exige aussi le mime zip (défense en
     * profondeur, en plus de la validation ZIP stricte du service).
     *
     * @return array<int, string>
     */
    private function h5pFileRules(mixed $file): array
    {
        $rules = ['required', 'file', 'extensions:h5p,zip', 'max:'.self::H5P_MAX_KB];

        $ext = is_object($file) && method_exists($file, 'getClientOriginalExtension')
            ? strtolower((string) $file->getClientOriginalExtension())
            : '';

        if ($ext === 'zip') {
            $rules[] = 'mimes:zip';
        }

        return $rules;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJOUT D'UN NOUVEL ITEM H5P
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Crée un NOUVEL item « h5p » à partir d'un paquet .h5p téléversé pour la
     * leçon. Titre : celui saisi (newItem.{lesson}.title) sinon celui lu dans
     * h5p.json. Un paquet invalide (zip corrompu, structure manquante, zip-slip)
     * est rejeté proprement en erreur de champ (jamais de 500).
     */
    public function addH5pItem(int $lessonId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        // RESTRICTION ADMIN (JS tiers) : refus propre en erreur de champ.
        if (! $this->canUploadH5p()) {
            $this->addError("newH5p.$lessonId", 'Seul un administrateur peut téléverser du contenu interactif H5P (sécurité : code tiers).');

            return;
        }

        // Anti-IDOR : la leçon doit appartenir à un chapitre de CE cours.
        $lesson = $this->resolveLessonFor($course, $lessonId);

        $this->validate(
            ["newH5p.$lessonId" => $this->h5pFileRules($this->newH5p[$lessonId] ?? null)],
            [],
            ["newH5p.$lessonId" => 'paquet H5P']
        );

        try {
            $result = (new \Modules\Academy\Services\H5pPackageService())->extract($this->newH5p[$lessonId]);
        } catch (\Throwable $e) {
            $this->addError("newH5p.$lessonId", $e->getMessage());

            return;
        }

        $title    = trim((string) ($this->newItem[$lessonId]['title'] ?? '')) ?: $result['title'];
        $position = (int) LessonItem::where('lesson_id', $lesson->id)->max('position') + 1;

        LessonItem::create([
            'lesson_id'   => $lesson->id,
            'type'        => 'h5p',
            'title'       => mb_substr($title, 0, 255),
            'position'    => $position,
            'payload'     => ['h5p_path' => $result['path'], 'title' => $result['title']],
            'is_required' => (bool) ($this->newItem[$lessonId]['is_required'] ?? false),
        ]);

        unset($this->newH5p[$lessonId], $this->newItem[$lessonId]);
        $this->flashSaved('Contenu interactif H5P ajouté.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REMPLACEMENT D'UN PAQUET H5P EXISTANT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Remplace le paquet d'un item « h5p » existant (anti-IDOR : item de CE
     * cours). L'ancien dossier extrait est supprimé APRÈS extraction réussie du
     * nouveau (pas de fenêtre où l'item pointe vers un dossier supprimé).
     */
    public function replaceH5pPackage(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        // RESTRICTION ADMIN (JS tiers) : même garde que l'ajout. Refus propre.
        if (! $this->canUploadH5p()) {
            $this->addError("itemH5p.$itemId", 'Seul un administrateur peut téléverser du contenu interactif H5P (sécurité : code tiers).');

            return;
        }

        $item = $this->resolveItemFor($course, $itemId);
        if ($item->type !== 'h5p') {
            return;
        }

        $this->validate(
            ["itemH5p.$itemId" => $this->h5pFileRules($this->itemH5p[$itemId] ?? null)],
            [],
            ["itemH5p.$itemId" => 'paquet H5P']
        );

        $service = new \Modules\Academy\Services\H5pPackageService();

        try {
            $result = $service->extract($this->itemH5p[$itemId]);
        } catch (\Throwable $e) {
            $this->addError("itemH5p.$itemId", $e->getMessage());

            return;
        }

        $oldPath             = $item->payload['h5p_path'] ?? null;
        $payload             = $item->payload ?? [];
        $payload['h5p_path'] = $result['path'];
        $payload['title']    = $result['title'];
        $item->forceFill(['payload' => $payload])->save();

        if (is_string($oldPath) && $oldPath !== $result['path']) {
            $service->delete($oldPath);
        }

        unset($this->itemH5p[$itemId]);
        $this->flashSaved('Contenu interactif H5P remplacé.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // UTILITAIRE DE NOMMAGE (partagé avec saveCover et HandlesItemMedia)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Nom de fichier non devinable et sûr : slug du préfixe + identifiant unique
     * + extension d'origine en liste blanche (jamais .php/.phtml, etc.). On ne se
     * fie jamais au nom client pour le stockage (le nom client n'est conservé que
     * comme libellé d'affichage des pièces jointes).
     */
    private function safeFileName(mixed $file, string $prefix): string
    {
        $ext  = strtolower((string) $file->getClientOriginalExtension());
        $safe = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx'], true) ? $ext : 'bin';

        return Str::slug($prefix).'-'.Str::random(16).'.'.$safe;
    }
}
