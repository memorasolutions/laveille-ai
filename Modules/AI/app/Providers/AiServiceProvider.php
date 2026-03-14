<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\AI\Events\HumanTakeoverRequested;
use Modules\AI\Listeners\NotifyAgentsOfTakeover;
use Modules\AI\Console\CheckSlaCommand;
use Modules\AI\Console\ScrapeUrlsCommand;
use Modules\AI\Console\SyncKnowledgeBaseCommand;
use Modules\AI\Livewire\AiArticleGenerator;
use Modules\AI\Livewire\AiContentAssistant;
use Modules\AI\Livewire\AiSeoAssistant;
use Modules\AI\Livewire\ChatBot;
use Modules\AI\Observers\ArticleSeoObserver;
use Modules\AI\Observers\CommentModerationObserver;
use Modules\AI\Observers\KnowledgeSourceObserver;
use Modules\AI\Observers\CsatObserver;
use Modules\AI\Observers\TicketObserver;
use Modules\AI\Models\Ticket;
use Modules\AI\Adapters\EmailChannelAdapter;
use Modules\AI\Services\AiService;
use Modules\AI\Services\ChannelRegistry;
use Modules\AI\Services\EmbeddingService;
use Modules\AI\Services\KnowledgeBaseService;
use Modules\AI\Services\RagService;
use Modules\AI\Services\SentimentService;
use Modules\AI\Services\SmartReplyService;
use Modules\AI\Services\WebScraperService;
use Nwidart\Modules\Traits\PathNamespace;

class AiServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'AI';

    protected string $nameLower = 'ai';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        Livewire::component('ai-chatbot', ChatBot::class);
        Livewire::component('ai-article-generator', AiArticleGenerator::class);
        Livewire::component('ai-content-assistant', AiContentAssistant::class);
        Livewire::component('ai-seo-assistant', AiSeoAssistant::class);

        if (class_exists('Modules\Blog\Models\Comment')) {
            \Modules\Blog\Models\Comment::observe(CommentModerationObserver::class);
        }
        if (class_exists('Modules\Blog\Models\Article')) {
            \Modules\Blog\Models\Article::observe(ArticleSeoObserver::class);
            \Modules\Blog\Models\Article::observe(KnowledgeSourceObserver::class);
        }

        // KB auto-sync observers
        if (class_exists(\Modules\Pages\Models\StaticPage::class)) {
            \Modules\Pages\Models\StaticPage::observe(KnowledgeSourceObserver::class);
        }
        if (class_exists(\Modules\Faq\Models\Faq::class)) {
            \Modules\Faq\Models\Faq::observe(KnowledgeSourceObserver::class);
        }

        Ticket::observe(TicketObserver::class);
        Ticket::observe(CsatObserver::class);

        $this->commands([SyncKnowledgeBaseCommand::class, ScrapeUrlsCommand::class, CheckSlaCommand::class]);

        Event::listen(HumanTakeoverRequested::class, NotifyAgentsOfTakeover::class);
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(AiService::class);
        $this->app->singleton(EmbeddingService::class);
        $this->app->singleton(KnowledgeBaseService::class);
        $this->app->singleton(WebScraperService::class);
        $this->app->singleton(RagService::class);
        $this->app->singleton(SmartReplyService::class);
        $this->app->singleton(SentimentService::class);

        $this->app->singleton(ChannelRegistry::class, function () {
            $registry = new ChannelRegistry();
            $registry->register('email', EmailChannelAdapter::class);

            return $registry;
        });
    }

    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->name, 'config/config.php') => config_path($this->nameLower.'.php'),
        ], 'config');

        $this->mergeConfigFrom(module_path($this->name, 'config/config.php'), $this->nameLower);
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);
        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);
    }

    public function provides(): array
    {
        return [AiService::class];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
