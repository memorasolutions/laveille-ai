<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Création de cours front-end (« mode édition » façon Moodle) - PHASE 5 (FE-5).
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01, autorisation SERVEUR - NON NÉGOCIABLE) :
 *  - Au montage : $this->authorize('create', Course::class) (admin OU formateur).
 *  - À la création : on RÉ-AUTORISE create() côté serveur AVANT toute écriture
 *    (on ne fait jamais confiance à l'état du navigateur).
 *  - Création atomique (transaction) : le Course (status='draft', created_by=auth)
 *    PUIS le CourseRole 'owner' pour le créateur. Un admin reste admin ; un
 *    formateur devient owner de SON cours et n'aura accès qu'à CE cours (Policies FE-1).
 *  - Slug auto-unique dérivé du titre (ne réutilise jamais un slug existant).
 *  - Redirection vers l'éditeur (academy.courses.manage) du nouveau cours.
 *
 * Formulaire ULTRA SIMPLE : titre (requis) + niveau, langue, visibilité, accès
 * (valeurs par défaut sûres). Les détails fins se règlent ensuite dans l'éditeur.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;

class CourseCreate extends Component
{
    // ── Formulaire minimal ───────────────────────────────────────────────────────
    public string $title = '';
    public string $level = 'intro';
    public string $language = 'fr-CA';
    public string $visibility = 'private';
    public string $access_type = 'free';

    /**
     * Entrée dans le formulaire de création. Autorisation SERVEUR obligatoire :
     * seul un admin (academy.manage) ou un formateur (rôle instructor) peut créer.
     */
    public function mount(): void
    {
        $this->authorize('create', Course::class);
    }

    /**
     * Crée le cours puis le rôle « owner » du créateur, dans une transaction.
     * On RÉ-AUTORISE create() ici (jamais de confiance à l'état navigateur).
     */
    public function create()
    {
        $this->authorize('create', Course::class);

        $validated = $this->validate([
            'title'       => 'required|string|max:255',
            'level'       => ['required', Rule::in(['intro', 'inter', 'avance'])],
            'language'    => 'required|string|max:10',
            'visibility'  => ['required', Rule::in(['public', 'unlisted', 'private'])],
            'access_type' => ['required', Rule::in(['free', 'paid_one_time', 'paid_subscription'])],
        ]);

        $course = DB::transaction(function () use ($validated): Course {
            $course = Course::create([
                'slug'        => $this->uniqueSlugFrom($validated['title']),
                'title'       => $validated['title'],
                'level'       => $validated['level'],
                'language'    => $validated['language'],
                'visibility'  => $validated['visibility'],
                'access_type' => $validated['access_type'],
                'price_cents' => null,
                'currency'    => 'CAD',
                'status'      => 'draft',
                'created_by'  => Auth::id(),
                'updated_by'  => Auth::id(),
            ]);

            // Le créateur devient OWNER de SON cours. Un admin reste admin (sa
            // permission globale suffit), mais lui poser owner ne nuit pas et
            // garantit qu'il figure dans course_roles si la permission disparaît.
            CourseRole::create([
                'course_id' => $course->id,
                'user_id'   => Auth::id(),
                'role'      => 'owner',
            ]);

            return $course;
        });

        session()->flash('academy_editor_status', 'Cours créé. Ajoutez maintenant vos chapitres et leçons.');

        return redirect()->route('academy.courses.manage', $course->slug);
    }

    /**
     * Slug unique dérivé du titre : base = Str::slug($title) (repli « cours » si
     * vide après translittération), puis suffixe -2, -3… tant qu'il existe déjà.
     * Inclut les cours soft-deleted (withTrashed) pour ne jamais entrer en
     * collision avec un slug réutilisable.
     */
    private function uniqueSlugFrom(string $title): string
    {
        $base = Str::slug($title) ?: 'cours';
        $slug = $base;
        $i = 2;

        while (Course::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    public function render()
    {
        return view('academy::livewire.course-create');
    }
}
