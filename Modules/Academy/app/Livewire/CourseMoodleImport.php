<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Import d'un cours à partir d'une sauvegarde MOODLE (.mbz). Miroir DÉLIBÉRÉ de
 * CourseImport (F15, sauvegarde .json Academy) : même parcours (téléversement ->
 * confirmation -> nouveau cours en BROUILLON, propriétaire = importateur ->
 * redirection vers l'éditeur), même posture de sécurité. Contrairement à
 * CourseImport, l'import est fait en UN SEUL clic (pas d'aperçu préalable détaillé) :
 * le fichier .mbz est trop volumineux/complexe pour un aperçu léger côté client -
 * le RÉSUMÉ (sections/activités importées/ignorées) est affiché APRÈS coup, via le
 * flash « academy_editor_status » déjà utilisé par tout le reste de l'éditeur.
 *
 * SÉCURITÉ (OWASP A01, autorisation SERVEUR) :
 *  - drapeau academy.moodle_import_enabled vérifié en tête de CHAQUE action (404
 *    si désactivé, même convention que academy.scorm_enabled) ;
 *  - mount() ET import() : authorize('create', Course::class) (admin OU formateur) ;
 *  - fichier borné (taille + extension .mbz/.zip) ; le contenu est ENTIÈREMENT
 *    revalidé par MoodleBackupImportService (zip valide, manifeste reconnu,
 *    anti zip-slip, anti zip-bomb, anti-XXE) - un fichier malformé/hostile lève
 *    InvalidCourseBackupException, attrapée ici -> message FR, jamais de 500.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Academy\Exceptions\InvalidCourseBackupException;
use Modules\Academy\Models\Course;
use Modules\Academy\Services\MoodleBackupImportService;

class CourseMoodleImport extends Component
{
    use WithFileUploads;

    /** Fichier .mbz téléversé en attente (TemporaryUploadedFile). */
    public $mbzFile = null;

    /** Message d'erreur lisible (FR) en cas de fichier invalide. */
    public ?string $importError = null;

    public function mount(): void
    {
        abort_unless((bool) config('academy.moodle_import_enabled', false), 404);
        $this->authorize('create', Course::class);
    }

    /** Taille maximale (Ko) du fichier .mbz (~200 Mo par défaut, cf. config). */
    private function maxKb(): int
    {
        return (int) config('academy.moodle_import.max_kb', 204800);
    }

    /**
     * Analyse + importe le fichier .mbz téléversé : crée un NOUVEAU cours (brouillon,
     * owner = l'utilisateur courant) puis redirige vers son éditeur, avec un résumé
     * détaillé (sections/activités importées + activités ignorées par type - jamais
     * de perte silencieuse) dans le flash de statut.
     */
    public function import(MoodleBackupImportService $service)
    {
        abort_unless((bool) config('academy.moodle_import_enabled', false), 404);
        $this->authorize('create', Course::class);

        $this->reset('importError');

        $this->validate([
            'mbzFile' => ['required', 'file', 'extensions:mbz,zip', 'max:'.$this->maxKb()],
        ], [], ['mbzFile' => 'sauvegarde Moodle (.mbz)']);

        try {
            $result = $service->import($this->mbzFile, Auth::user());
        } catch (InvalidCourseBackupException $e) {
            $this->importError = $e->getMessage();

            return null;
        }

        $this->reset('mbzFile');

        session()->flash('academy_editor_status', $this->buildSummaryMessage($result));

        return redirect()->route('academy.courses.manage', $result['course']->slug);
    }

    /**
     * Construit le message de résumé (FR) affiché après import : compteurs +
     * détail des activités ignorées par type, pour ne JAMAIS laisser croire que
     * tout le contenu Moodle a été repris silencieusement.
     *
     * @param  array{course: Course, sections_imported: int, items_imported: int, items_ignored: array<string, int>}  $result
     */
    private function buildSummaryMessage(array $result): string
    {
        $message = sprintf(
            'Cours Moodle importé en brouillon : %d section(s), %d contenu(s) importé(s).',
            $result['sections_imported'],
            $result['items_imported']
        );

        $ignoredTotal = array_sum($result['items_ignored']);
        if ($ignoredTotal > 0) {
            $detail = [];
            foreach ($result['items_ignored'] as $type => $count) {
                $detail[] = "{$type} ({$count})";
            }

            $message .= sprintf(
                ' %d activité(s) NON importée(s) (type non pris en charge) : %s.',
                $ignoredTotal,
                implode(', ', $detail)
            );
        }

        $message .= ' Vérifiez le contenu puis publiez-le.';

        return $message;
    }

    public function render()
    {
        return view('academy::livewire.course-moodle-import');
    }
}
