<?php

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\Blog\Models\Comment;
use Modules\Newsletter\Models\Subscriber;
use Modules\Pages\Models\StaticPage;
use Modules\SaaS\Models\Plan;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportController extends Controller
{
    public function showForm(): View
    {
        return view('backoffice::import.users');
    }

    public function importUsers(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $handle = fopen($request->file('file')->getPathname(), 'r');
        fgetcsv($handle); // skip headers
        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) {
                continue;
            }

            [, $name, $email] = $row;

            $user = User::firstOrCreate(
                ['email' => trim($email)],
                [
                    'name' => trim($name),
                    'password' => Hash::make(Str::random(12)),
                    'is_active' => true,
                ]
            );

            $user->wasRecentlyCreated ? $imported++ : $skipped++;
        }

        fclose($handle);

        return back()->with('success', "$imported utilisateur(s) importé(s), $skipped ignoré(s).");
    }

    public function showFormArticles(): View
    {
        return view('backoffice::import.articles');
    }

    public function importArticles(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $handle = fopen($request->file('file')->getPathname(), 'r');
        fgetcsv($handle); // skip headers: title,content,status,category_name
        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                continue;
            }

            $title = trim($row[0]);
            $content = trim($row[1]);
            $status = isset($row[2]) ? trim($row[2]) : 'draft';
            $categoryName = isset($row[3]) ? trim($row[3]) : null;

            if (! in_array($status, ['draft', 'published', 'archived'], true)) {
                $status = 'draft';
            }

            $categoryId = null;
            if ($categoryName !== null && $categoryName !== '') {
                $category = Category::where('name->'.app()->getLocale(), $categoryName)->first();
                $categoryId = $category?->id;
            }

            $article = Article::firstOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'title' => $title,
                    'content' => $content,
                    'status' => $status,
                    'category_id' => $categoryId,
                    'user_id' => auth()->id(),
                ]
            );

            $article->wasRecentlyCreated ? $imported++ : $skipped++;
        }

        fclose($handle);

        return back()->with('success', "$imported article(s) importé(s), $skipped ignoré(s).");
    }

    public function showFormCategories(): View
    {
        return view('backoffice::import.categories');
    }

    public function importCategories(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $handle = fopen($request->file('file')->getPathname(), 'r');
        fgetcsv($handle); // skip headers: name,description,color
        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (trim((string) $row[0]) === '') {
                continue;
            }

            $name = trim($row[0]);
            $description = isset($row[1]) ? trim($row[1]) : null;
            $color = isset($row[2]) ? trim($row[2]) : null;

            $category = Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $description,
                    'color' => $color,
                    'is_active' => true,
                ]
            );

            $category->wasRecentlyCreated ? $imported++ : $skipped++;
        }

        fclose($handle);

        return back()->with('success', "$imported catégorie(s) importée(s), $skipped ignorée(s).");
    }

    public function showFormSubscribers(): View
    {
        return view('backoffice::import.subscribers');
    }

    public function importSubscribers(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $handle = fopen($request->file('file')->getPathname(), 'r');
        fgetcsv($handle); // skip headers: email,name
        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (trim((string) $row[0]) === '') {
                continue;
            }

            $email = trim($row[0]);
            $name = isset($row[1]) ? trim($row[1]) : null;

            $subscriber = Subscriber::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'confirmed_at' => now(),
                ]
            );

            $subscriber->wasRecentlyCreated ? $imported++ : $skipped++;
        }

        fclose($handle);

        return back()->with('success', "$imported abonné(s) importé(s), $skipped ignoré(s).");
    }

    public function showFormPlans(): View
    {
        return view('backoffice::import.plans');
    }

    public function importPlans(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $handle = fopen($request->file('file')->getPathname(), 'r');
        fgetcsv($handle); // skip headers: name,price,interval,features
        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                continue;
            }

            $name = trim($row[0]);
            $price = isset($row[1]) ? (float) trim($row[1]) : 0;
            $interval = isset($row[2]) ? trim($row[2]) : 'monthly';
            $features = isset($row[3]) ? trim($row[3]) : null;

            if (! in_array($interval, ['monthly', 'yearly'], true)) {
                $interval = 'monthly';
            }

            $plan = Plan::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'price' => $price,
                    'interval' => $interval,
                    'features' => $features,
                    'is_active' => true,
                ]
            );

            $plan->wasRecentlyCreated ? $imported++ : $skipped++;
        }

        fclose($handle);

        return back()->with('success', "$imported plan(s) importé(s), $skipped ignoré(s).");
    }

    public function showFormPages(): View
    {
        return view('backoffice::import.pages');
    }

    public function importPages(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $handle = fopen($request->file('file')->getPathname(), 'r');
        fgetcsv($handle); // skip headers: title,content,status
        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                continue;
            }

            $title = trim($row[0]);
            $content = trim($row[1]);
            $status = isset($row[2]) ? trim($row[2]) : 'draft';

            if (! in_array($status, ['draft', 'published'], true)) {
                $status = 'draft';
            }

            $slug = Str::slug($title);
            $locale = app()->getLocale();
            $existing = StaticPage::where("slug->{$locale}", $slug)->first();

            if ($existing) {
                $skipped++;
            } else {
                StaticPage::create([
                    'title' => $title,
                    'slug' => $slug,
                    'content' => $content,
                    'status' => $status,
                    'user_id' => auth()->id(),
                ]);
                $imported++;
            }
        }

        fclose($handle);

        return back()->with('success', "$imported page(s) importée(s), $skipped ignorée(s).");
    }

    public function showFormComments(): View
    {
        return view('backoffice::import.comments');
    }

    public function importComments(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $handle = fopen($request->file('file')->getPathname(), 'r');
        fgetcsv($handle); // skip headers: article_id,guest_name,guest_email,content
        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 4) {
                continue;
            }

            $articleId = (int) trim($row[0]);
            $guestName = trim($row[1]);
            $guestEmail = trim($row[2]);
            $content = trim($row[3]);

            if (! Article::where('id', $articleId)->exists()) {
                $skipped++;

                continue;
            }

            Comment::create([
                'article_id' => $articleId,
                'guest_name' => $guestName,
                'guest_email' => $guestEmail,
                'content' => $content,
                'status' => 'pending',
            ]);

            $imported++;
        }

        fclose($handle);

        return back()->with('success', "$imported commentaire(s) importé(s), $skipped ignoré(s).");
    }

    /**
     * @return BinaryFileResponse|RedirectResponse
     */
    public function template(string $type)
    {
        $templates = [
            'users' => ['id', 'name', 'email', 'created_at'],
            'articles' => ['title', 'content', 'status', 'category_name'],
            'categories' => ['name', 'description', 'color'],
            'subscribers' => ['email', 'name'],
            'plans' => ['name', 'price', 'interval', 'features'],
            'pages' => ['title', 'content', 'status'],
            'comments' => ['article_id', 'guest_name', 'guest_email', 'content'],
        ];

        if (! isset($templates[$type])) {
            return back()->with('error', 'Type de template invalide.');
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
        $handle = fopen($tempFile, 'w');
        fputcsv($handle, $templates[$type]);
        fclose($handle);

        return response()->download($tempFile, "{$type}_template.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ])->deleteFileAfterSend();
    }
}
