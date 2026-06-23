<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F15 - IMPORT / RESTAURATION d'un cours à partir d'un fichier de sauvegarde (.json).
 *
 * Parcours en 3 temps, SANS popup natif (aperçu + confirmation inline) :
 *   1. l'utilisateur téléverse un .json ;
 *   2. on l'analyse et on affiche un APERÇU (titre + nb chapitres/leçons/items/devoirs) ;
 *   3. il confirme -> on crée un NOUVEAU cours (brouillon, owner = lui) puis on redirige
 *      vers l'éditeur du cours importé.
 *
 * SÉCURITÉ (OWASP A01, autorisation SERVEUR) :
 *  - mount() : authorize('create', Course::class) (admin OU formateur) ;
 *  - import() : on RÉ-AUTORISE create() AVANT toute écriture ;
 *  - fichier borné (taille + extension/MIME .json) ; JSON décodé STRICTEMENT puis
 *    validé par le service (version, types, listes blanches). Un fichier malformé/
 *    hostile lève InvalidCourseBackupException, attrapée ici -> message FR, pas de 500.
 *  - L'import ne porte AUCUNE donnée personnelle d'étudiant (structure uniquement).
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Academy\Exceptions\InvalidCourseBackupException;
use Modules\Academy\Models\Course;
use Modules\Academy\Services\CourseBackupService;

class CourseImport extends Component
{
    use WithFileUploads;

    /** Fichier de sauvegarde téléversé (TemporaryUploadedFile). */
    public $backupFile = null;

    /**
     * Aperçu calculé du fichier valide (null tant qu'aucun fichier valide n'est chargé).
     *
     * @var array<string, mixed>|null
     */
    public ?array $preview = null;

    /** Message d'erreur lisible (FR) en cas de fichier invalide. */
    public ?string $importError = null;

    /**
     * Données décodées du fichier valide, conservées entre l'aperçu et la confirmation
     * (le composant Livewire persiste l'état entre requêtes ; on revalide quand même au
     * moment de l'import via le service - défense en profondeur).
     *
     * @var array<string, mixed>|null
     */
    public ?array $payload = null;

    public function mount(): void
    {
        $this->authorize('create', Course::class);
    }

    /**
     * Déclenché à chaque mise à jour du champ fichier : valide le téléversement, décode
     * le JSON et prépare l'aperçu. Aucune écriture en base à cette étape.
     */
    public function updatedBackupFile(): void
    {
        $this->reset(['preview', 'payload', 'importError']);

        $this->validate([
            'backupFile' => ['required', 'file', 'mimes:json,txt', 'max:5120'], // 5 Mo max
        ], [
            'backupFile.mimes' => 'Le fichier doit être une sauvegarde .json.',
            'backupFile.max'   => 'Le fichier dépasse la taille maximale (5 Mo).',
        ]);

        try {
            $raw = file_get_contents($this->backupFile->getRealPath());
            if ($raw === false || trim((string) $raw) === '') {
                throw new InvalidCourseBackupException('Le fichier est vide ou illisible.');
            }

            $data = json_decode((string) $raw, true, 64, JSON_THROW_ON_ERROR);
            if (! is_array($data)) {
                throw new InvalidCourseBackupException('Le fichier ne contient pas une sauvegarde valide.');
            }
        } catch (\JsonException) {
            $this->importError = 'Le fichier n\'est pas un JSON valide.';

            return;
        } catch (InvalidCourseBackupException $e) {
            $this->importError = $e->getMessage();

            return;
        }

        $this->payload = $data;
        $this->preview = $this->buildPreview($data);
    }

    /**
     * Construit un aperçu LECTURE SEULE du contenu (compteurs), sans rien créer.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildPreview(array $data): array
    {
        $chapters = is_array($data['chapters'] ?? null) ? $data['chapters'] : [];

        $lessonCount = 0;
        $itemCount   = 0;
        foreach ($chapters as $chapter) {
            $lessons = is_array($chapter['lessons'] ?? null) ? $chapter['lessons'] : [];
            $lessonCount += count($lessons);
            foreach ($lessons as $lesson) {
                $itemCount += count(is_array($lesson['items'] ?? null) ? $lesson['items'] : []);
            }
        }

        $bank = is_array($data['question_bank'] ?? null) ? $data['question_bank'] : [];

        return [
            'title'           => (string) ($data['course']['title'] ?? 'Cours sans titre'),
            'format_version'  => (string) ($data['format_version'] ?? '?'),
            'exported_at'     => (string) ($data['exported_at'] ?? ''),
            'chapters'        => count($chapters),
            'lessons'         => $lessonCount,
            'items'           => $itemCount,
            'assignments'     => count(is_array($data['assignments'] ?? null) ? $data['assignments'] : []),
            'grade_items'     => count(is_array($data['grade_items'] ?? null) ? $data['grade_items'] : []),
            'bank_questions'  => count(is_array($bank['questions'] ?? null) ? $bank['questions'] : []),
        ];
    }

    /**
     * Confirme l'import : crée le cours puis redirige vers son éditeur. RÉ-AUTORISÉ
     * côté serveur ; revalidation par le service (jamais de confiance à l'état client).
     */
    public function import(CourseBackupService $service)
    {
        $this->authorize('create', Course::class);

        if (! is_array($this->payload)) {
            $this->importError = 'Aucun fichier valide à importer. Téléversez d\'abord une sauvegarde.';

            return null;
        }

        try {
            $course = $service->import($this->payload, Auth::user());
        } catch (InvalidCourseBackupException $e) {
            $this->importError = $e->getMessage();

            return null;
        }

        session()->flash('academy_editor_status', 'Cours importé en brouillon. Vérifiez le contenu puis publiez-le.');

        return redirect()->route('academy.courses.manage', $course->slug);
    }

    /** Réinitialise le formulaire (annuler l'aperçu pour choisir un autre fichier). */
    public function cancelPreview(): void
    {
        $this->reset(['backupFile', 'preview', 'payload', 'importError']);
    }

    public function render()
    {
        return view('academy::livewire.course-import');
    }
}
