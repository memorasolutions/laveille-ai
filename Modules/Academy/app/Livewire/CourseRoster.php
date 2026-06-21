<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Gestion front-end des INSCRIPTIONS (roster) et des RÔLES DE COURS (équipe) -
 * PHASE 4 (FE-4). Composant Livewire rendu sous l'éditeur de cours.
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01, autorisation SERVEUR - NON NÉGOCIABLE) :
 *  - L'identifiant du cours est figé au montage ($courseId, propriété privée).
 *  - À CHAQUE mutation, le cours est RE-RÉSOLU côté serveur via resolveCourse()
 *    puis RÉ-AUTORISÉ par $this->authorize(...) sur la Policy posée en FE-1 :
 *      • inscriptions (roster) → authorize('manageEnrollments', $course)
 *        (admin OU owner/instructor du cours)
 *      • rôles de cours        → authorize('manageRoles', $course)
 *        (admin OU owner du cours UNIQUEMENT)
 *  - On ne fait JAMAIS confiance à un ID/état venant du navigateur. Chaque
 *    Enrollment / CourseRole ciblé est RE-RÉSOLU et SCOPÉ à CE cours (anti-IDOR)
 *    avant toute écriture : un objet d'un AUTRE cours → ModelNotFound, rien écrit.
 *  - Le rôle ajouté est validé contre une LISTE BLANCHE {instructor,assistant,
 *    editor} ; 'owner' n'est JAMAIS attribuable ni retirable par ce composant.
 *  - @can(...) en Blade ne sert qu'à CACHER des boutons (jamais l'unique garde).
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Academy\Models\Cohort;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;

class CourseRoster extends Component
{
    use WithFileUploads;

    /** Rôles de cours attribuables (liste blanche, alignée sur l'admin Backoffice). 'owner' EXCLU. */
    private const ASSIGNABLE_ROLES = ['instructor', 'assistant', 'editor'];

    /**
     * Rôle d'inscription accepté dans le CSV (liste blanche). Pour l'instant, le
     * roster ne gère qu'un statut d'inscription « étudiant » ; toute autre valeur
     * (ou valeur absente) retombe sur 'student'. La colonne « rôle » du CSV est
     * tolérée pour la lisibilité humaine ; elle ne sert PAS à attribuer un rôle
     * d'équipe (instructor/assistant/editor) - ça reste un acte manuel gâté
     * 'manageRoles'. Importer en masse ne crée donc QUE des inscriptions.
     */
    private const CSV_ENROLL_ROLES = ['student'];

    /** Nombre maximum de lignes traitées par import (au-delà : refus, pas de troncature silencieuse). */
    private const CSV_MAX_ROWS = 1000;

    /** Identifiant du cours géré (figé au montage, source de vérité serveur). */
    public int $courseId;

    // ── Saisie : inscrire un étudiant par courriel ───────────────────────────────
    public string $enrollEmail = '';

    // ── Saisie : ajouter un rôle d'équipe par courriel ───────────────────────────
    public string $roleEmail = '';
    public string $roleName = 'instructor';

    // ── Import CSV d'inscriptions en masse (WithFileUploads) ──────────────────────
    /** Fichier CSV en attente de traitement (TemporaryUploadedFile). */
    public $csvFile = null;

    /** Rapport du dernier import, rendu en role=status (null = aucun import). */
    public ?array $importReport = null;

    // ── Cohortes / groupes d'apprenants (Phase C) ───────────────────────────────
    /** Saisie : nom d'une nouvelle cohorte. */
    public string $cohortName = '';

    /** Cohorte en cours de renommage (id) + nouveau nom saisi. */
    public ?int $renamingCohort = null;
    public string $renameCohortName = '';

    /** Cohorte sélectionnée pour affecter un inscrit + l'inscrit choisi. */
    public ?int $assignCohortId = null;
    public ?int $assignEnrollmentUserId = null;

    /** Filtre d'affichage des inscrits par cohorte (null = « Tous »). */
    public ?int $cohortFilter = null;

    // ── Confirmations inline à 2 temps (jamais confirm() natif) ──────────────────
    public ?int $confirmingEnrollmentRemoval = null;
    public ?int $confirmingRoleRemoval = null;
    public ?int $confirmingCohortRemoval = null;

    /**
     * Entrée. Autorisation SERVEUR d'affichage : pour voir CET espace il faut au
     * minimum pouvoir gérer les inscriptions (admin OU owner/instructor du cours).
     * La gestion des rôles est en plus gâtée par 'manageRoles' (owner/admin).
     */
    public function mount(Course $course): void
    {
        $this->authorize('manageEnrollments', $course);

        $this->courseId = $course->id;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Résolution + autorisation serveur (cœur anti-escalade / anti-IDOR)
    // ─────────────────────────────────────────────────────────────────────────────

    /** Re-résout TOUJOURS le cours depuis la base (jamais depuis le navigateur). */
    private function resolveCourse(): Course
    {
        return Course::findOrFail($this->courseId);
    }

    /**
     * Re-résout un Enrollment ET vérifie qu'il appartient bien à CE cours (anti-IDOR).
     * Un Enrollment d'un autre cours → ModelNotFound, aucune écriture possible.
     */
    private function resolveEnrollmentFor(Course $course, int $enrollmentId): Enrollment
    {
        return Enrollment::where('id', $enrollmentId)
            ->where('course_id', $course->id)
            ->firstOrFail();
    }

    /**
     * Re-résout un CourseRole ET vérifie qu'il appartient bien à CE cours (anti-IDOR).
     * Un rôle d'un autre cours → ModelNotFound, aucune écriture possible.
     */
    private function resolveRoleFor(Course $course, int $roleId): CourseRole
    {
        return CourseRole::where('id', $roleId)
            ->where('course_id', $course->id)
            ->firstOrFail();
    }

    /**
     * Re-résout une Cohorte ET vérifie qu'elle appartient bien à CE cours (anti-IDOR).
     * Une cohorte d'un autre cours → ModelNotFound, aucune écriture possible.
     */
    private function resolveCohortFor(Course $course, int $cohortId): Cohort
    {
        return Cohort::where('id', $cohortId)
            ->where('course_id', $course->id)
            ->firstOrFail();
    }

    /**
     * Re-résout un inscrit ACTIF de CE cours par son user_id (anti-IDOR).
     * Un utilisateur non inscrit (ou inscription annulée) → ModelNotFound :
     * impossible d'affecter quelqu'un d'étranger au cours à une cohorte.
     */
    private function resolveActiveEnrolleeFor(Course $course, int $userId): Enrollment
    {
        return Enrollment::where('course_id', $course->id)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->firstOrFail();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // ROSTER (inscriptions) - gâté manageEnrollments
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Inscrit un utilisateur EXISTANT par courriel (status='active', source='admin').
     * Si aucun compte n'existe pour ce courriel : message d'erreur clair, AUCUN
     * compte n'est créé (on n'invite pas, on ne provisionne pas d'utilisateur ici).
     */
    public function enrollByEmail(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        $data = $this->validate([
            'enrollEmail' => 'required|email|max:255',
        ]);

        $user = User::where('email', $data['enrollEmail'])->first();

        if (! $user) {
            $this->addError('enrollEmail', "Aucun compte n'existe pour ce courriel. L'utilisateur doit d'abord se créer un compte.");

            return;
        }

        // Idempotence : un Enrollment annulé est réactivé ; un actif reste actif.
        $enrollment = Enrollment::firstOrNew([
            'course_id' => $course->id,
            'user_id'   => $user->id,
        ]);

        if ($enrollment->exists && $enrollment->status === 'active') {
            $this->addError('enrollEmail', 'Cet utilisateur est déjà inscrit à ce cours.');

            return;
        }

        $enrollment->fill([
            'status'       => 'active',
            'source'       => 'admin',
            'enrolled_at'  => now(),
            'cancelled_at' => null,
        ])->save();

        $this->reset('enrollEmail');
        session()->flash('academy_roster_status', 'Utilisateur inscrit au cours.');
    }

    /**
     * Désinscrit (status='cancelled' - choix auditable, on ne supprime pas la ligne).
     * L'Enrollment est re-résolu et SCOPÉ à CE cours (anti-IDOR).
     */
    public function cancelEnrollment(int $enrollmentId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        $enrollment = $this->resolveEnrollmentFor($course, $enrollmentId);

        $enrollment->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $this->confirmingEnrollmentRemoval = null;
        session()->flash('academy_roster_status', 'Inscription annulée.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // IMPORT CSV EN MASSE (roster) - gâté manageEnrollments (même gate que l'ajout manuel)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Importe des inscriptions en masse depuis un fichier CSV (une colonne courriel
     * obligatoire, en-tête email/courriel toléré + colonne rôle optionnelle).
     *
     * SÉCURITÉ : le cours est RE-RÉSOLU côté serveur puis RÉ-AUTORISÉ par la MÊME
     * gate que l'ajout manuel ('manageEnrollments' = admin OU owner/instructor du
     * cours). Toutes les écritures sont enveloppées dans UNE transaction.
     *
     * CONFORMITÉ LCAP / Loi 25 : un courriel SANS compte n'est JAMAIS provisionné -
     * aucun compte n'est créé. Il est listé dans le rapport « inconnus » et ignoré.
     */
    public function importCsv(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        $this->validate([
            'csvFile' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $rows = $this->parseCsvRows($this->csvFile->getRealPath());

        if (count($rows) > self::CSV_MAX_ROWS) {
            $this->reset('csvFile');
            $this->addError('csvFile', 'Le fichier dépasse '.self::CSV_MAX_ROWS.' lignes. Divisez-le en plusieurs fichiers (aucune ligne n\'a été traitée).');

            return;
        }

        $report = [
            'enrolled'         => 0,
            'already'          => 0,
            'invalid'          => 0,
            'unknown_emails'   => [],
            'enrolled_emails'  => [],
        ];

        DB::transaction(function () use ($course, $rows, &$report): void {
            foreach ($rows as $row) {
                $email = $row['email'];

                if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $report['invalid']++;

                    continue;
                }

                $user = User::where('email', $email)->first();

                if (! $user) {
                    // Conformité LCAP/Loi 25 : aucun compte n'est créé pour un courriel inconnu.
                    if (! in_array($email, $report['unknown_emails'], true)) {
                        $report['unknown_emails'][] = $email;
                    }

                    continue;
                }

                // Même logique idempotente que l'inscription manuelle (enrollByEmail),
                // scopée à CE cours (anti-IDOR).
                $enrollment = Enrollment::firstOrNew([
                    'course_id' => $course->id,
                    'user_id'   => $user->id,
                ]);

                if ($enrollment->exists && $enrollment->status === 'active') {
                    $report['already']++;

                    continue;
                }

                $enrollment->fill([
                    'status'       => 'active',
                    'source'       => 'admin',
                    'enrolled_at'  => now(),
                    'cancelled_at' => null,
                ])->save();

                $report['enrolled']++;
                $report['enrolled_emails'][] = $email;
            }
        });

        $this->reset('csvFile');
        $this->importReport = $report;
        session()->flash('academy_roster_status', "Import terminé : {$report['enrolled']} inscrit(s), {$report['already']} déjà inscrit(s), ".count($report['unknown_emails']).' inconnu(s), '.$report['invalid'].' ligne(s) invalide(s).');
    }

    /**
     * Lit et normalise les lignes d'un CSV. Robuste : BOM UTF-8, séparateur ';' ou
     * ',', espaces, casse e-mail en minuscule. Détecte un en-tête optionnel
     * (email/courriel) pour ne pas le traiter comme une donnée. Renvoie un tableau
     * de ['email' => string] (le rôle CSV éventuel est lu mais non utilisé pour
     * attribuer un rôle d'équipe - import = inscriptions seulement).
     *
     * @return array<int, array{email: string}>
     */
    private function parseCsvRows(string $path): array
    {
        $raw = file_get_contents($path);

        if ($raw === false || $raw === '') {
            return [];
        }

        // Retire le BOM UTF-8 s'il est présent.
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);

        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];

        $rows         = [];
        $headerParsed = false;

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            // Détecte le séparateur ligne par ligne (',' ou ';').
            $delimiter = (substr_count($line, ';') > substr_count($line, ',')) ? ';' : ',';
            $cells     = str_getcsv($line, $delimiter);

            $email = strtolower(trim((string) ($cells[0] ?? '')));

            // En-tête optionnel (1re ligne « email » / « courriel ») : on l'ignore.
            if (! $headerParsed) {
                $headerParsed = true;
                if (in_array($email, ['email', 'courriel', 'e-mail'], true)) {
                    continue;
                }
            }

            $rows[] = ['email' => $email];
        }

        return $rows;
    }

    /** Réinitialise le rapport et le fichier (bouton « fermer le rapport »). */
    public function clearImportReport(): void
    {
        $this->importReport = null;
        $this->reset('csvFile');
        $this->resetErrorBag('csvFile');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // RÔLES DE COURS (équipe) - gâté manageRoles (owner/admin UNIQUEMENT)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Ajoute un rôle d'équipe à un utilisateur EXISTANT par courriel.
     * Le rôle est validé contre la liste blanche {instructor,assistant,editor}
     * ('owner' jamais attribuable). Unicité user+course (un cours créé par un
     * formateur a déjà son créateur en 'owner' : on ne duplique pas).
     */
    public function addRoleByEmail(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageRoles', $course);

        $data = $this->validate([
            'roleEmail' => 'required|email|max:255',
            'roleName'  => ['required', Rule::in(self::ASSIGNABLE_ROLES)],
        ]);

        $user = User::where('email', $data['roleEmail'])->first();

        if (! $user) {
            $this->addError('roleEmail', "Aucun compte n'existe pour ce courriel. L'utilisateur doit d'abord se créer un compte.");

            return;
        }

        $exists = CourseRole::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            $this->addError('roleEmail', 'Cet utilisateur a déjà un rôle dans ce cours.');

            return;
        }

        CourseRole::create([
            'course_id' => $course->id,
            'user_id'   => $user->id,
            'role'      => $data['roleName'],
        ]);

        $this->reset('roleEmail');
        session()->flash('academy_roster_status', 'Rôle attribué.');
    }

    /**
     * Retire un rôle d'équipe. Le CourseRole est re-résolu et SCOPÉ à CE cours
     * (anti-IDOR). Le rôle 'owner' n'est JAMAIS retirable par ce composant.
     */
    public function removeRole(int $roleId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageRoles', $course);

        $role = $this->resolveRoleFor($course, $roleId);

        if ($role->role === 'owner') {
            $this->confirmingRoleRemoval = null;
            $this->addError('roleEmail', "Le rôle « propriétaire » ne peut pas être retiré.");

            return;
        }

        $role->delete();

        $this->confirmingRoleRemoval = null;
        session()->flash('academy_roster_status', 'Rôle retiré.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // COHORTES / GROUPES D'APPRENANTS (Phase C) - gâté manageEnrollments
    // ─────────────────────────────────────────────────────────────────────────────
    //
    // Une cohorte appartient à UN cours (course_id). Chaque mutation RE-RÉSOUT le
    // cours, ré-autorise 'manageEnrollments', puis RE-RÉSOUT la cohorte/le membre
    // scopés à CE cours (anti-IDOR) : jamais de confiance à un id du navigateur.

    /** Crée une cohorte (nom requis) rattachée à CE cours. */
    public function createCohort(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        $data = $this->validate([
            'cohortName' => 'required|string|max:120',
        ]);

        Cohort::create([
            'course_id' => $course->id,
            'name'      => trim($data['cohortName']),
        ]);

        $this->reset('cohortName');
        session()->flash('academy_roster_status', 'Cohorte créée.');
    }

    /** Passe une cohorte en mode renommage (pré-remplit le champ). */
    public function startRenameCohort(int $cohortId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        $cohort = $this->resolveCohortFor($course, $cohortId);

        $this->renamingCohort   = $cohort->id;
        $this->renameCohortName = $cohort->name;
        $this->resetErrorBag('renameCohortName');
    }

    public function cancelRenameCohort(): void
    {
        $this->renamingCohort = null;
        $this->reset('renameCohortName');
        $this->resetErrorBag('renameCohortName');
    }

    /** Renomme une cohorte de CE cours (re-résolue, anti-IDOR). */
    public function renameCohort(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        if ($this->renamingCohort === null) {
            return;
        }

        $cohort = $this->resolveCohortFor($course, (int) $this->renamingCohort);

        $data = $this->validate([
            'renameCohortName' => 'required|string|max:120',
        ]);

        $cohort->update(['name' => trim($data['renameCohortName'])]);

        $this->renamingCohort = null;
        $this->reset('renameCohortName');
        session()->flash('academy_roster_status', 'Cohorte renommée.');
    }

    /**
     * Supprime une cohorte de CE cours (re-résolue, anti-IDOR). Les liens pivot
     * partent en cascade ; les INSCRIPTIONS au cours ne sont jamais touchées.
     */
    public function deleteCohort(int $cohortId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        $cohort = $this->resolveCohortFor($course, $cohortId);

        DB::transaction(function () use ($cohort): void {
            $cohort->members()->detach();
            $cohort->delete();
        });

        if ($this->cohortFilter === $cohortId) {
            $this->cohortFilter = null;
        }
        $this->confirmingCohortRemoval = null;
        session()->flash('academy_roster_status', 'Cohorte supprimée.');
    }

    /**
     * Affecte un inscrit ACTIF de CE cours à une cohorte de CE cours.
     * La cohorte ET l'inscription sont re-résolues et scopées au cours (anti-IDOR) :
     * impossible d'affecter une cohorte étrangère ou un user non inscrit.
     */
    public function assignToCohort(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        $data = $this->validate([
            'assignCohortId'         => 'required|integer',
            'assignEnrollmentUserId' => 'required|integer',
        ]);

        $cohort = $this->resolveCohortFor($course, (int) $data['assignCohortId']);
        $enrollment = $this->resolveActiveEnrolleeFor($course, (int) $data['assignEnrollmentUserId']);

        // syncWithoutDetaching = idempotent (pas de doublon, contrainte unique respectée).
        $cohort->members()->syncWithoutDetaching([$enrollment->user_id]);

        $this->reset('assignCohortId', 'assignEnrollmentUserId');
        session()->flash('academy_roster_status', 'Personne affectée à la cohorte.');
    }

    /**
     * Retire un membre d'une cohorte de CE cours (re-résolus, anti-IDOR).
     * Ne touche jamais l'inscription au cours, seulement le lien à la cohorte.
     */
    public function removeFromCohort(int $cohortId, int $userId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        $cohort = $this->resolveCohortFor($course, $cohortId);

        $cohort->members()->detach($userId);

        session()->flash('academy_roster_status', 'Personne retirée de la cohorte.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Confirmations inline à 2 temps (jamais de popup native)
    // ─────────────────────────────────────────────────────────────────────────────

    public function confirmCohortRemoval(int $cohortId): void
    {
        $this->confirmingCohortRemoval = $cohortId;
    }

    public function cancelCohortRemoval(): void
    {
        $this->confirmingCohortRemoval = null;
    }

    public function confirmEnrollmentRemoval(int $enrollmentId): void
    {
        $this->confirmingEnrollmentRemoval = $enrollmentId;
    }

    public function cancelEnrollmentRemoval(): void
    {
        $this->confirmingEnrollmentRemoval = null;
    }

    public function confirmRoleRemoval(int $roleId): void
    {
        $this->confirmingRoleRemoval = $roleId;
    }

    public function cancelRoleRemoval(): void
    {
        $this->confirmingRoleRemoval = null;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Lecture (affichage) - listes fraîches, scopées à CE cours
    // ─────────────────────────────────────────────────────────────────────────────

    /** Le cours frais (sert les directives @can dans la vue). */
    #[Computed]
    public function course(): Course
    {
        return Course::findOrFail($this->courseId);
    }

    /**
     * Inscriptions de CE cours, avec l'utilisateur, les plus récentes d'abord.
     * Si un filtre cohorte est actif (et valide pour CE cours), ne renvoie que les
     * inscrits membres de cette cohorte.
     */
    #[Computed]
    public function enrollments()
    {
        $query = Enrollment::where('course_id', $this->courseId)
            ->with('user')
            ->orderByDesc('enrolled_at')
            ->orderByDesc('id');

        if ($this->cohortFilter !== null) {
            // La cohorte du filtre est re-validée contre CE cours (anti-IDOR d'affichage).
            $cohort = Cohort::where('id', $this->cohortFilter)
                ->where('course_id', $this->courseId)
                ->first();

            $memberIds = $cohort ? $cohort->members()->pluck('users.id')->all() : [];
            $query->whereIn('user_id', $memberIds);
        }

        return $query->get();
    }

    /** Cohortes de CE cours, avec leurs membres, alphabétiques. */
    #[Computed]
    public function cohorts()
    {
        return Cohort::where('course_id', $this->courseId)
            ->with('members')
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /** Inscrits ACTIFS de CE cours (pour le sélecteur d'affectation), triés par nom. */
    #[Computed]
    public function activeEnrollees()
    {
        return Enrollment::where('course_id', $this->courseId)
            ->where('status', 'active')
            ->with('user')
            ->get()
            ->sortBy(fn ($e) => $e->user?->name ?? '')
            ->values();
    }

    /** Membres de l'équipe (rôles) de CE cours, avec l'utilisateur. */
    #[Computed]
    public function teamRoles()
    {
        return CourseRole::where('course_id', $this->courseId)
            ->with('user')
            ->orderByRaw("CASE WHEN role = 'owner' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get();
    }

    /** Libellé lisible d'un rôle (FR). */
    public function roleLabel(string $role): string
    {
        return match ($role) {
            'owner'      => 'Propriétaire',
            'instructor' => 'Formateur',
            'assistant'  => 'Assistant',
            'editor'     => 'Éditeur',
            default      => $role,
        };
    }

    /** Libellé lisible d'un statut d'inscription (FR). */
    public function statusLabel(string $status): string
    {
        return match ($status) {
            'active'    => 'Active',
            'pending'   => 'En attente',
            'cancelled' => 'Annulée',
            default     => $status,
        };
    }

    public function render()
    {
        return view('academy::livewire.course-roster');
    }
}
