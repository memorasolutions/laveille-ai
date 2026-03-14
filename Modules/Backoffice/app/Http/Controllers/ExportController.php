<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use App\Models\User;
use Illuminate\Routing\Controller;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\Blog\Models\Comment;
use Modules\Export\Services\ExportService;
use Modules\Newsletter\Models\Campaign;
use Modules\Pages\Models\StaticPage;
use Modules\SaaS\Models\Plan;
use Modules\Settings\Models\Setting;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function __construct(private readonly ExportService $exportService) {}

    public function users(): BinaryFileResponse
    {
        $data = User::cursor()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'created_at' => (string) $u->created_at,
        ]);

        $path = $this->exportService->toCsv($data, 'users_export.csv', ['id', 'name', 'email', 'created_at']);

        return response()->download($path, 'users_export.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ])->deleteFileAfterSend();
    }

    public function roles(): BinaryFileResponse
    {
        $data = Role::all()->map(fn (Role $r) => [
            'id' => $r->id,
            'name' => $r->name,
            'guard_name' => $r->guard_name,
        ]);

        $path = $this->exportService->toCsv($data, 'roles_export.csv', ['id', 'name', 'guard_name']);

        return response()->download($path, 'roles_export.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ])->deleteFileAfterSend();
    }

    public function settings(): BinaryFileResponse
    {
        $data = Setting::all()->toArray();

        $path = $this->exportService->toCsv($data, 'settings_export.csv', ['id', 'key', 'value', 'group']);

        return response()->download($path, 'settings_export.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ])->deleteFileAfterSend();
    }

    public function articles(): BinaryFileResponse
    {
        $locale = app()->getLocale();
        $data = Article::cursor()->map(fn (Article $a) => [
            'id' => $a->id,
            'title' => $a->getTranslation('title', $locale),
            'slug' => $a->getTranslation('slug', $locale),
            'status' => (string) $a->status,
            'category_id' => $a->category_id,
            'created_at' => (string) $a->created_at,
        ]);

        $path = $this->exportService->toCsv($data, 'articles_export.csv', ['id', 'title', 'slug', 'status', 'category_id', 'created_at']);

        return response()->download($path, 'articles_export.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ])->deleteFileAfterSend();
    }

    public function categories(): BinaryFileResponse
    {
        $locale = app()->getLocale();
        $data = Category::withCount('articles')->get()->map(fn (Category $c) => [
            'id' => $c->id,
            'name' => $c->getTranslation('name', $locale),
            'slug' => $c->getTranslation('slug', $locale),
            'color' => $c->color,
            'is_active' => $c->is_active ? 'true' : 'false',
            'articles_count' => $c->articles_count,
        ]);

        $path = $this->exportService->toCsv($data, 'categories_export.csv', ['id', 'name', 'slug', 'color', 'is_active', 'articles_count']);

        return response()->download($path, 'categories_export.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ])->deleteFileAfterSend();
    }

    public function plans(): BinaryFileResponse
    {
        $data = Plan::all()->map(fn (Plan $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'slug' => $p->slug,
            'price' => $p->price,
            'interval' => $p->interval,
            'is_active' => $p->is_active ? 'true' : 'false',
        ]);

        $path = $this->exportService->toCsv($data, 'plans_export.csv', ['id', 'name', 'slug', 'price', 'interval', 'is_active']);

        return response()->download($path, 'plans_export.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ])->deleteFileAfterSend();
    }

    public function campaigns(): BinaryFileResponse
    {
        $data = Campaign::cursor()->map(fn (Campaign $c) => [
            'id' => $c->id,
            'subject' => $c->subject,
            'status' => (string) $c->status,
            'recipient_count' => $c->recipient_count,
            'sent_at' => (string) $c->sent_at,
            'created_at' => (string) $c->created_at,
        ]);

        $path = $this->exportService->toCsv($data, 'campaigns_export.csv', ['id', 'subject', 'status', 'recipient_count', 'sent_at', 'created_at']);

        return response()->download($path, 'campaigns_export.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ])->deleteFileAfterSend();
    }

    public function pages(): BinaryFileResponse
    {
        $locale = app()->getLocale();
        $data = StaticPage::cursor()->map(fn (StaticPage $p) => [
            'id' => $p->id,
            'title' => $p->getTranslation('title', $locale),
            'slug' => $p->getTranslation('slug', $locale),
            'status' => $p->status,
            'created_at' => (string) $p->created_at,
        ]);

        $path = $this->exportService->toCsv($data, 'pages_export.csv', ['id', 'title', 'slug', 'status', 'created_at']);

        return response()->download($path, 'pages_export.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ])->deleteFileAfterSend();
    }

    public function comments(): BinaryFileResponse
    {
        $data = Comment::cursor()->map(fn (Comment $c) => [
            'id' => $c->id,
            'author_name' => $c->author_name,
            'content' => $c->content,
            'status' => (string) $c->status,
            'article_id' => $c->article_id,
            'created_at' => (string) $c->created_at,
        ]);

        $path = $this->exportService->toCsv($data, 'comments_export.csv', ['id', 'author_name', 'content', 'status', 'article_id', 'created_at']);

        return response()->download($path, 'comments_export.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ])->deleteFileAfterSend();
    }
}
