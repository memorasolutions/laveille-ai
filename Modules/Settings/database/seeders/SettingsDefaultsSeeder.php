<?php

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Settings\Models\Setting;

class SettingsDefaultsSeeder extends Seeder
{
    /**
     * Seed les paramètres par défaut.
     * Ne remplace PAS les valeurs existantes en prod.
     */
    public function run(): void
    {
        $defaults = [
            // GROUPE 'fronttheme'
            'fronttheme.home_articles_limit' => ['value' => 12, 'type' => 'integer', 'group' => 'fronttheme'],
            'fronttheme.home_popular_tools_limit' => ['value' => 4, 'type' => 'integer', 'group' => 'fronttheme'],
            'fronttheme.home_featured_terms_limit' => ['value' => 5, 'type' => 'integer', 'group' => 'fronttheme'],
            'fronttheme.home_featured_acronyms_limit' => ['value' => 5, 'type' => 'integer', 'group' => 'fronttheme'],
            'fronttheme.home_interactive_tools_limit' => ['value' => 4, 'type' => 'integer', 'group' => 'fronttheme'],
            'fronttheme.home_breaking_news_limit' => ['value' => 9, 'type' => 'integer', 'group' => 'fronttheme'],
            'fronttheme.home_highlights_limit' => ['value' => 6, 'type' => 'integer', 'group' => 'fronttheme'],
            'fronttheme.home_sponsored_skip' => ['value' => 6, 'type' => 'integer', 'group' => 'fronttheme'],
            'fronttheme.home_sponsored_limit' => ['value' => 4, 'type' => 'integer', 'group' => 'fronttheme'],
            'fronttheme.sidebar_latest_articles_limit' => ['value' => 5, 'type' => 'integer', 'group' => 'fronttheme'],
            'fronttheme.sidebar_recent_articles_limit' => ['value' => 4, 'type' => 'integer', 'group' => 'fronttheme'],
            'fronttheme.sidebar_popular_tags_limit' => ['value' => 10, 'type' => 'integer', 'group' => 'fronttheme'],

            // GROUPE 'blog'
            'blog.articles_per_page' => ['value' => 10, 'type' => 'integer', 'group' => 'blog'],

            // GROUPE 'directory'
            'directory.recent_tools_limit' => ['value' => 6, 'type' => 'integer', 'group' => 'directory'],
            'directory.popular_tools_limit' => ['value' => 6, 'type' => 'integer', 'group' => 'directory'],
            'directory.top_voted_tools_limit' => ['value' => 6, 'type' => 'integer', 'group' => 'directory'],
            'directory.similar_tools_limit' => ['value' => 4, 'type' => 'integer', 'group' => 'directory'],
            'directory.leaderboard_all_time_limit' => ['value' => 10, 'type' => 'integer', 'group' => 'directory'],
            'directory.leaderboard_monthly_limit' => ['value' => 10, 'type' => 'integer', 'group' => 'directory'],

            // GROUPE 'news'
            'news.articles_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'news'],

            // GROUPE 'media'
            'media.items_per_page' => ['value' => 24, 'type' => 'integer', 'group' => 'media'],

            // GROUPE 'acronyms'
            'acronyms.related_acronyms_limit' => ['value' => 6, 'type' => 'integer', 'group' => 'acronyms'],

            // GROUPE 'roadmap'
            'roadmap.ideas_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'roadmap'],
            'roadmap.admin_ideas_per_page' => ['value' => 30, 'type' => 'integer', 'group' => 'roadmap'],
            'roadmap.boards_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'roadmap'],

            // GROUPE 'blog' (admin)
            'blog.submissions_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'blog'],
            'blog.tags_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'blog'],

            // GROUPE 'backoffice'
            'backoffice.mail_logs_per_page' => ['value' => 25, 'type' => 'integer', 'group' => 'backoffice'],
            'backoffice.login_history_per_page' => ['value' => 30, 'type' => 'integer', 'group' => 'backoffice'],
            'backoffice.contact_messages_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'backoffice'],
            'backoffice.announcements_per_page' => ['value' => 25, 'type' => 'integer', 'group' => 'backoffice'],
            'backoffice.blocked_ips_per_page' => ['value' => 25, 'type' => 'integer', 'group' => 'backoffice'],
            'backoffice.notifications_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'backoffice'],
            'backoffice.url_redirects_per_page' => ['value' => 25, 'type' => 'integer', 'group' => 'backoffice'],
            'backoffice.settings_per_page' => ['value' => 25, 'type' => 'integer', 'group' => 'backoffice'],
            'backoffice.activity_logs_per_page' => ['value' => 30, 'type' => 'integer', 'group' => 'backoffice'],
            'backoffice.subscribers_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'backoffice'],
            'backoffice.plans_per_page' => ['value' => 15, 'type' => 'integer', 'group' => 'backoffice'],
            'backoffice.users_per_page' => ['value' => 15, 'type' => 'integer', 'group' => 'backoffice'],
            'backoffice.settings_table_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'backoffice'],
            'backoffice.media_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'backoffice'],
            'backoffice.campaigns_per_page' => ['value' => 15, 'type' => 'integer', 'group' => 'backoffice'],
            'backoffice.categories_per_page' => ['value' => 15, 'type' => 'integer', 'group' => 'backoffice'],
            'backoffice.comments_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'backoffice'],
            'backoffice.meta_tags_per_page' => ['value' => 15, 'type' => 'integer', 'group' => 'backoffice'],
            'backoffice.shortcodes_per_page' => ['value' => 15, 'type' => 'integer', 'group' => 'backoffice'],
            'backoffice.feature_flags_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'backoffice'],
            'backoffice.articles_per_page' => ['value' => 15, 'type' => 'integer', 'group' => 'backoffice'],
            'backoffice.roles_per_page' => ['value' => 15, 'type' => 'integer', 'group' => 'backoffice'],

            // GROUPE 'auth'
            'auth.user_notifications_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'auth'],
            'auth.user_articles_per_page' => ['value' => 10, 'type' => 'integer', 'group' => 'auth'],
            'auth.user_activity_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'auth'],

            // GROUPE 'directory' (admin)
            'directory.moderation_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'directory'],
            'directory.admin_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'directory'],

            // GROUPE 'acronyms'
            'acronyms.admin_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'acronyms'],

            // GROUPE 'dictionary'
            'dictionary.terms_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'dictionary'],

            // GROUPE 'news' (admin)
            'news.admin_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'news'],

            // GROUPE 'shorturl'
            'shorturl.user_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'shorturl'],
            'shorturl.admin_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'shorturl'],

            // GROUPE 'health'
            'health.incidents_per_page' => ['value' => 15, 'type' => 'integer', 'group' => 'health'],

            // GROUPE 'newsletter'
            'newsletter.marketing_templates_per_page' => ['value' => 15, 'type' => 'integer', 'group' => 'newsletter'],
            'newsletter.workflows_per_page' => ['value' => 15, 'type' => 'integer', 'group' => 'newsletter'],

            // GROUPE 'pages'
            'pages.static_pages_per_page' => ['value' => 15, 'type' => 'integer', 'group' => 'pages'],

            // GROUPE 'ai'
            'ai.knowledge_base_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'ai'],
            'ai.knowledge_urls_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'ai'],
            'ai.csat_surveys_per_page' => ['value' => 30, 'type' => 'integer', 'group' => 'ai'],
            'ai.conversations_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'ai'],
            'ai.unified_inbox_per_page' => ['value' => 30, 'type' => 'integer', 'group' => 'ai'],
            'ai.tickets_per_page' => ['value' => 20, 'type' => 'integer', 'group' => 'ai'],

            // GROUPE 'api'
            'api.blog_articles_per_page' => ['value' => 15, 'type' => 'integer', 'group' => 'api'],
            'api.product_reviews_per_page' => ['value' => 10, 'type' => 'integer', 'group' => 'api'],

            // GROUPE 'voting'
            'voting.threshold_noticed' => ['value' => 2, 'type' => 'integer', 'group' => 'voting'],
            'voting.threshold_approved' => ['value' => 5, 'type' => 'integer', 'group' => 'voting'],
            'voting.threshold_favorite' => ['value' => 10, 'type' => 'integer', 'group' => 'voting'],
            'voting.rate_limit' => ['value' => 50, 'type' => 'integer', 'group' => 'voting'],
            'voting.reputation_vote_cast' => ['value' => 1, 'type' => 'integer', 'group' => 'voting'],
            'voting.reputation_community_approved' => ['value' => 15, 'type' => 'integer', 'group' => 'voting'],

            // GROUPE 'reputation'
            'reputation.threshold_contributeur' => ['value' => 15, 'type' => 'integer', 'group' => 'reputation'],
            'reputation.threshold_verifie' => ['value' => 50, 'type' => 'integer', 'group' => 'reputation'],
            'reputation.threshold_expert' => ['value' => 150, 'type' => 'integer', 'group' => 'reputation'],
            'reputation.multiplier_contributeur' => ['value' => 1.25, 'type' => 'double', 'group' => 'reputation'],
            'reputation.multiplier_verifie' => ['value' => 1.5, 'type' => 'double', 'group' => 'reputation'],
            'reputation.multiplier_expert' => ['value' => 2.0, 'type' => 'double', 'group' => 'reputation'],
            'reputation.ban_duration_days' => ['value' => 7, 'type' => 'integer', 'group' => 'reputation'],

            // GROUPE 'seo'
            'seo.meta_description' => ['value' => 'Votre source d\'information sur l\'intelligence artificielle et les technologies au Québec.', 'type' => 'string', 'group' => 'seo'],

            // GROUPE 'social'
            'social.facebook_page_url' => ['value' => 'https://www.facebook.com/LaVeilleDeStef', 'type' => 'string', 'group' => 'social'],
            'social.messenger_url' => ['value' => 'https://m.me/LaVeilleDeStef', 'type' => 'string', 'group' => 'social'],

            // GROUPE 'contact'
            'contact.address' => ['value' => "L'Ancienne-Lorette, QC, Canada", 'type' => 'string', 'group' => 'contact'],

            // GROUPE 'cache'
            'cache.frontend_composer_duration' => ['value' => 600, 'type' => 'integer', 'group' => 'cache'],
            'cache.settings_duration' => ['value' => 3600, 'type' => 'integer', 'group' => 'cache'],
        ];

        foreach ($defaults as $key => $config) {
            if (! Setting::where('key', $key)->exists()) {
                Setting::set($key, $config['value'], $config['type'], $config['group']);
            }
        }
    }
}
