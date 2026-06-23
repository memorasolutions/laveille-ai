<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F23 - RAPPORTS et JOURNAUX par cours. Composant Livewire en LECTURE SEULE
 * rendu sur la route academy.courses.reports.
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01, autorisation SERVEUR - NON NÉGOCIABLE) :
 *  - L'identifiant du cours est figé au montage ($courseId, source de vérité).
 *  - L'accès est gâté par authorize('manageEnrollments', $course) (admin OU
 *    owner/instructor du cours = la même gate de pilotage que le roster et les
 *    analytics). Un étudiant / utilisateur lambda → 403.
 *  - À CHAQUE lecture, le cours est RE-RÉSOLU côté serveur (resolveCourse) puis
 *    ré-autorisé ; toutes les données sont calculées par CourseReportService
 *    SCOPÉES à CE cours. Un gérant du cours A ne peut pas voir le cours B via
 *    slug/ID forgé.
 *  - Aucune écriture : ce composant ne mute rien (zéro effet de bord).
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Academy\Models\Course;
use Modules\Academy\Services\CourseReportService;

class CourseReports extends Component
{
    /** Identifiant du cours (figé au montage, source de vérité serveur). */
    public int $courseId;

    /** Onglet actif : 'participation' ou 'journal'. */
    #[Url(as: 'onglet')]
    public string $tab = 'participation';

    /** Filtre journal : étudiant (0 = tous). */
    public int $filterUser = 0;

    /** Filtre journal : type d'évènement ('' = tous). */
    public string $filterType = '';

    /** Page courante du journal (pagination manuelle d'une collection dérivée). */
    public int $logPage = 1;

    /** Nombre d'évènements par page du journal. */
    public int $perPage = 25;

    /**
     * Entrée. Autorisation SERVEUR : voir les rapports d'un cours exige de pouvoir
     * le piloter (admin OU owner/instructor) - même gate que le roster.
     */
    public function mount(Course $course): void
    {
        $this->authorize('manageEnrollments', $course);

        $this->courseId = $course->id;
    }

    /** Re-résout TOUJOURS le cours depuis la base (jamais depuis le navigateur). */
    private function resolveCourse(): Course
    {
        $course = Course::findOrFail($this->courseId);
        $this->authorize('manageEnrollments', $course);

        return $course;
    }

    private function service(): CourseReportService
    {
        return app(CourseReportService::class);
    }

    /** Le cours frais (en-tête de la vue). */
    #[Computed]
    public function course(): Course
    {
        return Course::findOrFail($this->courseId);
    }

    /** Bascule d'onglet (réinitialise la pagination du journal). */
    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['participation', 'journal'], true) ? $tab : 'participation';
        $this->logPage = 1;
    }

    /** Toute modification de filtre repart de la 1re page. */
    public function updatedFilterUser(): void
    {
        $this->logPage = 1;
    }

    public function updatedFilterType(): void
    {
        $this->logPage = 1;
    }

    /**
     * Rapport de participation (re-résout + ré-autorise à chaque rendu).
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function participation(): \Illuminate\Support\Collection
    {
        return $this->service()->participation($this->resolveCourse());
    }

    /** Étudiants inscrits actifs (filtre du journal). */
    #[Computed]
    public function enrolledUsers(): \Illuminate\Support\Collection
    {
        return $this->service()->enrolledUsers($this->resolveCourse());
    }

    /**
     * Journal paginé. La source est une collection dérivée (UNION de timestamps) ;
     * on la pagine manuellement via un LengthAwarePaginator pour garder les liens
     * Livewire standards sans charger toute la liste dans la vue.
     */
    #[Computed]
    public function activityLog(): LengthAwarePaginator
    {
        $all = $this->service()->activityLog($this->resolveCourse(), [
            'user_id' => $this->filterUser,
            'type'    => $this->filterType,
        ]);

        $total = $all->count();
        $page = max(1, $this->logPage);
        $items = $all->forPage($page, $this->perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $total,
            $this->perPage,
            $page,
            ['pageName' => 'logPage']
        );
    }

    /** Navigation de page du journal (bornée). */
    public function gotoLogPage(int $page): void
    {
        $this->logPage = max(1, $page);
    }

    public function render()
    {
        return view('academy::livewire.course-reports');
    }
}
