<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\Blog\Models\Comment;
use Modules\Newsletter\Models\Subscriber;
use Modules\Pages\Models\StaticPage;

class DemoSeederCommand extends Command
{
    protected $signature = 'app:demo {--fresh : Clear existing demo data first}';

    protected $description = 'Generate realistic demo data to showcase all modules';

    public function handle(): int
    {
        if ($this->demoDataExists() && ! $this->option('fresh')) {
            $this->components->warn('Demo data already exists. Use --fresh to recreate.');

            return self::SUCCESS;
        }

        if ($this->option('fresh')) {
            $this->clearDemoData();
        }

        $this->components->info('Generating demo data...');

        DB::transaction(function () {
            $this->createUsers();
            $this->createArticles();
            $this->createPages();
            $this->createActivityLogs();
            $this->createSubscribers();
        });

        $this->showSummary();

        return self::SUCCESS;
    }

    private function demoDataExists(): bool
    {
        return User::where('email', 'like', '%@demo.test')->exists();
    }

    private function clearDemoData(): void
    {
        $this->components->task('Clearing existing demo data', function () {
            $demoUserIds = User::where('email', 'like', '%@demo.test')->pluck('id');
            Comment::whereIn('user_id', $demoUserIds)->delete();
            Article::whereIn('user_id', $demoUserIds)->delete();
            Subscriber::where('email', 'like', '%@demo.test')->delete();
            User::where('email', 'like', '%@demo.test')->delete();

            return true;
        });
    }

    private function createUsers(): void
    {
        $this->components->task('Creating demo users', function () {
            $users = [
                ['email' => 'editor@demo.test', 'name' => 'Demo Editor', 'role' => 'editor'],
                ['email' => 'user@demo.test', 'name' => 'Demo User', 'role' => 'user'],
                ['email' => 'premium@demo.test', 'name' => 'Premium User', 'role' => 'user'],
            ];

            foreach ($users as $data) {
                $role = $data['role'];
                unset($data['role']);
                $user = User::factory()->create(array_merge($data, [
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]));
                $user->assignRole($role);
            }

            return true;
        });
    }

    private function createArticles(): void
    {
        $this->components->task('Creating blog articles', function () {
            $editor = User::where('email', 'editor@demo.test')->first();
            if (! $editor) {
                return false;
            }

            $category = Category::first();

            $articles = [
                ['title' => 'Building Scalable SaaS Applications with Laravel', 'published_at' => now()->subDays(5), 'tags' => ['Laravel', 'SaaS']],
                ['title' => 'Microservices Architecture for Tech Startups', 'published_at' => now()->subDays(12), 'tags' => ['Architecture', 'Microservices']],
                ['title' => 'AI Integration in Modern Web Applications', 'published_at' => now()->subDays(18), 'tags' => ['AI', 'Web']],
                ['title' => 'DevOps Best Practices for SaaS Companies', 'published_at' => now()->subDays(25), 'tags' => ['DevOps', 'SaaS']],
                ['title' => 'Progressive Web Apps: The Future of Mobile', 'published_at' => now()->subDays(2), 'tags' => ['PWA', 'Mobile']],
                ['title' => 'Cloud Security in the SaaS Ecosystem', 'published_at' => null, 'tags' => ['Security', 'Cloud']],
            ];

            foreach ($articles as $data) {
                $article = Article::factory()->create(array_merge($data, [
                    'user_id' => $editor->id,
                    'category_id' => $category?->id,
                    'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                ]));

                // Add 2 comments on published articles
                if ($article->published_at) {
                    $commenter = User::where('email', 'user@demo.test')->first();
                    if ($commenter) {
                        Comment::factory()->count(2)->create([
                            'article_id' => $article->id,
                            'user_id' => $commenter->id,
                        ]);
                    }
                }
            }

            return true;
        });
    }

    private function createPages(): void
    {
        $this->components->task('Creating static pages', function () {
            $editor = User::where('email', 'editor@demo.test')->first();

            $pages = [
                ['title' => 'About Us', 'slug' => 'about-demo', 'content' => 'We are a technology company focused on building innovative SaaS solutions that empower businesses worldwide.'],
                ['title' => 'Terms of Service', 'slug' => 'terms-demo', 'content' => 'Please read these terms of service carefully before using our platform and services.'],
                ['title' => 'Privacy Policy', 'slug' => 'privacy-demo', 'content' => 'We are committed to protecting your personal information and your right to privacy.'],
            ];

            foreach ($pages as $page) {
                StaticPage::create(array_merge($page, [
                    'status' => 'published',
                    'user_id' => $editor?->id,
                ]));
            }

            return true;
        });
    }

    private function createActivityLogs(): void
    {
        $this->components->task('Creating activity logs', function () {
            $users = User::where('email', 'like', '%@demo.test')->get();

            $activities = [
                'Logged in to the application',
                'Updated profile information',
                'Created a new article',
                'Changed account password',
                'Exported user data',
            ];

            foreach ($users as $user) {
                foreach (fake()->randomElements($activities, 2) as $message) {
                    activity()
                        ->performedOn($user)
                        ->causedBy($user)
                        ->log($message);
                }
            }

            return true;
        });
    }

    private function createSubscribers(): void
    {
        $this->components->task('Creating newsletter subscribers', function () {
            $subscribers = [
                ['email' => 'john@demo.test', 'name' => 'John Doe', 'confirmed_at' => now()->subDays(10)],
                ['email' => 'jane@demo.test', 'name' => 'Jane Smith', 'confirmed_at' => now()->subDays(7)],
                ['email' => 'bob@demo.test', 'name' => 'Bob Wilson', 'confirmed_at' => now()->subDays(3)],
                ['email' => 'alice@demo.test', 'name' => 'Alice Brown', 'confirmed_at' => now()->subDays(15), 'unsubscribed_at' => now()->subDays(2)],
                ['email' => 'charlie@demo.test', 'name' => 'Charlie Davis', 'confirmed_at' => now()->subDays(20), 'unsubscribed_at' => now()->subDays(5)],
            ];

            foreach ($subscribers as $data) {
                Subscriber::create($data);
            }

            return true;
        });
    }

    private function showSummary(): void
    {
        $this->newLine();
        $this->components->info('Demo data generated successfully!');

        $this->table(['Data', 'Count'], [
            ['Users', User::where('email', 'like', '%@demo.test')->count()],
            ['Articles', Article::whereIn('user_id', User::where('email', 'like', '%@demo.test')->pluck('id'))->count()],
            ['Comments', Comment::whereIn('user_id', User::where('email', 'like', '%@demo.test')->pluck('id'))->count()],
            ['Pages', StaticPage::whereIn('slug', ['about-demo', 'terms-demo', 'privacy-demo'])->count()],
            ['Subscribers', Subscriber::where('email', 'like', '%@demo.test')->count()],
        ]);

        $this->components->bulletList([
            'Login as editor: editor@demo.test / password',
            'Login as user: user@demo.test / password',
            'Use --fresh to recreate demo data',
        ]);
    }
}
