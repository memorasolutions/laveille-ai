<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class DocumentationController extends Controller
{
    public function index(): View
    {
        $sections = $this->collectHelpSections();

        return view('backoffice::themes.backend.documentation.index', [
            'sections' => $sections,
        ]);
    }

    private function collectHelpSections(): array
    {
        $sections = [];
        $basePath = base_path('Modules');

        $categories = [
            'Contenu' => [
                'Blog/resources/views/themes/backend/admin/articles/_help.blade.php' => 'Articles',
                'Blog/resources/views/themes/backend/admin/categories/_help.blade.php' => 'Catégories',
                'Blog/resources/views/themes/backend/admin/comments/_help.blade.php' => 'Commentaires',
                'Blog/resources/views/themes/backend/admin/tags/_help.blade.php' => 'Tags',
                'Blog/resources/views/themes/backend/admin/revisions/_help.blade.php' => 'Révisions',
                'Pages/resources/views/themes/backend/admin/pages/_help.blade.php' => 'Pages',
                'Backoffice/resources/views/themes/backend/media/_help.blade.php' => 'Médias',
                'Menu/resources/views/admin/_help.blade.php' => 'Menus',
                'Faq/resources/views/admin/_help.blade.php' => 'FAQ',
                'Testimonials/resources/views/admin/_help.blade.php' => 'Témoignages',
                'Widget/resources/views/admin/_help.blade.php' => 'Widgets',
                'CustomFields/resources/views/admin/_help.blade.php' => 'Champs personnalisés',
            ],
            'Marketing' => [
                'Newsletter/resources/views/themes/backend/admin/_help.blade.php' => 'Abonnés',
                'Newsletter/resources/views/themes/backend/admin/campaigns/_help.blade.php' => 'Campagnes',
                'Newsletter/resources/views/admin/templates/_help.blade.php' => 'Templates',
                'Newsletter/resources/views/admin/workflows/_help.blade.php' => 'Workflows',
                'ABTest/resources/views/admin/experiments/_help.blade.php' => 'Tests A/B',
                'ShortUrl/resources/views/admin/_help.blade.php' => 'Liens courts',
            ],
            'Formulaires' => [
                'FormBuilder/resources/views/admin/forms/_help.blade.php' => 'Formulaires',
                'FormBuilder/resources/views/admin/submissions/_help.blade.php' => 'Soumissions',
                'Backoffice/resources/views/themes/backend/contact-messages/_help.blade.php' => 'Messages contact',
            ],
            'Utilisateurs' => [
                'Backoffice/resources/views/themes/backend/users/_help.blade.php' => 'Utilisateurs',
                'Backoffice/resources/views/themes/backend/roles/_help.blade.php' => 'Rôles',
                'Team/resources/views/teams/_help.blade.php' => 'Équipes',
                'Backoffice/resources/views/themes/backend/profile/_help.blade.php' => 'Profil',
                'Backoffice/resources/views/themes/backend/login-history/_help.blade.php' => 'Historique connexions',
            ],
            'SaaS et commerce' => [
                'Backoffice/resources/views/themes/backend/plans/_help.blade.php' => 'Plans',
                'Backoffice/resources/views/themes/backend/revenue/_help.blade.php' => 'Revenus',
                'Tenancy/resources/views/admin/tenants/_help.blade.php' => 'Tenants',
            ],
            'SEO et référencement' => [
                'Backoffice/resources/views/themes/backend/seo/_help.blade.php' => 'Méta-tags SEO',
                'SEO/resources/views/admin/redirects/_help.blade.php' => 'Redirections',
                'Backoffice/resources/views/themes/backend/shortcodes/_help.blade.php' => 'Shortcodes',
            ],
            'Système' => [
                'Backoffice/resources/views/themes/backend/dashboard/_help.blade.php' => 'Tableau de bord',
                'Backoffice/resources/views/themes/backend/settings/_help.blade.php' => 'Paramètres',
                'Backoffice/resources/views/themes/backend/branding/_help.blade.php' => 'Branding',
                'Backoffice/resources/views/themes/backend/themes/_help.blade.php' => 'Thèmes',
                'Backoffice/resources/views/themes/backend/translations/_help.blade.php' => 'Traductions',
                'Backoffice/resources/views/themes/backend/cache/_help.blade.php' => 'Cache',
                'Backoffice/resources/views/themes/backend/backups/_help.blade.php' => 'Sauvegardes',
                'Backoffice/resources/views/themes/backend/health/_help.blade.php' => 'Santé système',
                'Backoffice/resources/views/themes/backend/system-info/_help.blade.php' => 'Info système',
                'Storage/resources/views/admin/_help.blade.php' => 'Stockage',
            ],
            'Sécurité' => [
                'Backoffice/resources/views/themes/backend/security/_help.blade.php' => 'Sécurité',
                'Backoffice/resources/views/themes/backend/blocked-ips/_help.blade.php' => 'IPs bloquées',
                'Backoffice/resources/views/themes/backend/activity-logs/_help.blade.php' => 'Journaux d\'activité',
                'Backoffice/resources/views/themes/backend/logs/_help.blade.php' => 'Logs système',
            ],
            'Communication' => [
                'Backoffice/resources/views/themes/backend/notifications/_help.blade.php' => 'Notifications',
                'Backoffice/resources/views/themes/backend/push-notifications/_help.blade.php' => 'Push notifications',
                'Backoffice/resources/views/themes/backend/email-templates/_help.blade.php' => 'Templates email',
                'Backoffice/resources/views/themes/backend/mail-log/_help.blade.php' => 'Journal email',
                'Backoffice/resources/views/themes/backend/webhooks/_help.blade.php' => 'Webhooks',
            ],
            'Avancé' => [
                'AI/resources/views/admin/conversations/_help.blade.php' => 'Intelligence artificielle',
                'Backoffice/resources/views/themes/backend/feature-flags/_help.blade.php' => 'Feature flags',
                'Backoffice/resources/views/themes/backend/scheduler/_help.blade.php' => 'Planificateur',
                'Backoffice/resources/views/themes/backend/failed-jobs/_help.blade.php' => 'Tâches échouées',
                'Backoffice/resources/views/themes/backend/data-retention/_help.blade.php' => 'Rétention données',
                'Backoffice/resources/views/themes/backend/trash/_help.blade.php' => 'Corbeille',
                'Backoffice/resources/views/themes/backend/onboarding-steps/_help.blade.php' => 'Onboarding',
                'Backoffice/resources/views/themes/backend/cookie-categories/_help.blade.php' => 'Cookies',
                'Core/resources/views/admin/announcements/_help.blade.php' => 'Annonces',
                'Backoffice/resources/views/themes/backend/stats/_help.blade.php' => 'Statistiques',
                'Backoffice/resources/views/themes/backend/search/_help.blade.php' => 'Recherche',
            ],
        ];

        foreach ($categories as $category => $items) {
            $sectionItems = [];
            foreach ($items as $path => $title) {
                $fullPath = $basePath.'/'.$path;
                if (File::exists($fullPath)) {
                    $sectionItems[] = [
                        'title' => $title,
                        'view_path' => $fullPath,
                    ];
                }
            }
            if (! empty($sectionItems)) {
                $sections[$category] = $sectionItems;
            }
        }

        return $sections;
    }
}
