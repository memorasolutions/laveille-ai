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

            // GROUPE 'api'
            'api.blog_articles_per_page' => ['value' => 15, 'type' => 'integer', 'group' => 'api'],
            'api.product_reviews_per_page' => ['value' => 10, 'type' => 'integer', 'group' => 'api'],

            // GROUPE 'social'
            'social.facebook_page_url' => ['value' => 'https://www.facebook.com/LaVeilleDeStef', 'type' => 'string', 'group' => 'social'],
            'social.messenger_url' => ['value' => 'https://m.me/LaVeilleDeStef', 'type' => 'string', 'group' => 'social'],

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
