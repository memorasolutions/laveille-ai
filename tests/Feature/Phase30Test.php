<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Phase 30 : make:crud complet et fonctionnel
 * Teste la génération de fichiers CRUD pour un vrai module (Blog/Post).
 */
function cleanupBlogPost(): void
{
    $files = [
        base_path('Modules/Blog/app/Models/Post.php'),
        base_path('Modules/Blog/app/Policies/PostPolicy.php'),
        base_path('Modules/Blog/app/Http/Controllers/PostController.php'),
        base_path('Modules/Blog/app/Services/PostCrudService.php'),
        base_path('Modules/Blog/database/factories/PostFactory.php'),
        base_path('Modules/Blog/tests/Feature/PostCrudTest.php'),
    ];
    foreach ($files as $f) {
        if (file_exists($f)) {
            unlink($f);
        }
    }

    foreach (glob(base_path('Modules/Blog/database/migrations/*create_posts*')) ?: [] as $f) {
        @unlink($f);
    }

    $viewDir = base_path('Modules/Blog/resources/views/posts');
    if (is_dir($viewDir)) {
        foreach (glob("{$viewDir}/*") ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($viewDir);
    }
}

afterEach(fn () => cleanupBlogPost());

it('make:crud generates Post model with correct content', function () {
    $this->artisan('make:crud', [
        'module' => 'Blog',
        'model' => 'Post',
        '--fields' => 'title:string,content:text,price:decimal,published:boolean',
    ])->assertSuccessful();

    $path = base_path('Modules/Blog/app/Models/Post.php');
    expect(file_exists($path))->toBeTrue();

    $content = file_get_contents($path);
    expect($content)
        ->toContain('class Post extends Model')
        ->toContain('HasFactory')
        ->toContain('SoftDeletes')
        ->toContain("'title'")
        ->toContain('newFactory');
});

it('make:crud generates migration with correct columns', function () {
    $this->artisan('make:crud', [
        'module' => 'Blog',
        'model' => 'Post',
        '--fields' => 'title:string,content:text,price:decimal,published:boolean',
    ])->assertSuccessful();

    $migrations = glob(base_path('Modules/Blog/database/migrations/*create_posts*'));
    expect($migrations)->not->toBeEmpty();

    $content = file_get_contents($migrations[0]);
    expect($content)
        ->toContain('string')
        ->toContain('text')
        ->toContain('decimal')
        ->toContain('boolean')
        ->toContain('softDeletes');
});

it('make:crud generates PostPolicy with authorization methods', function () {
    $this->artisan('make:crud', [
        'module' => 'Blog',
        'model' => 'Post',
        '--fields' => 'title:string,content:text,price:decimal,published:boolean',
    ])->assertSuccessful();

    $path = base_path('Modules/Blog/app/Policies/PostPolicy.php');
    expect(file_exists($path))->toBeTrue();

    $content = file_get_contents($path);
    expect($content)
        ->toContain('PostPolicy')
        ->toContain('HandlesAuthorization')
        ->toContain('viewAny')
        ->toContain('delete');
});

it('make:crud generates PostCrudService extending CrudService', function () {
    $this->artisan('make:crud', [
        'module' => 'Blog',
        'model' => 'Post',
        '--fields' => 'title:string,content:text,price:decimal,published:boolean',
    ])->assertSuccessful();

    $path = base_path('Modules/Blog/app/Services/PostCrudService.php');
    expect(file_exists($path))->toBeTrue();

    $content = file_get_contents($path);
    expect($content)
        ->toContain('PostCrudService extends CrudService')
        ->toContain('Post::class');
});

it('make:crud generates PostController with correct view and route references', function () {
    $this->artisan('make:crud', [
        'module' => 'Blog',
        'model' => 'Post',
        '--fields' => 'title:string,content:text,price:decimal,published:boolean',
    ])->assertSuccessful();

    $path = base_path('Modules/Blog/app/Http/Controllers/PostController.php');
    expect(file_exists($path))->toBeTrue();

    $content = file_get_contents($path);
    expect($content)
        ->toContain('PostController')
        ->toContain('PostCrudService')
        ->toContain('blog::posts')
        ->toContain('blog.posts.index');
});

it('make:crud generates PostFactory with fake data definitions', function () {
    $this->artisan('make:crud', [
        'module' => 'Blog',
        'model' => 'Post',
        '--fields' => 'title:string,content:text,price:decimal,published:boolean',
    ])->assertSuccessful();

    $path = base_path('Modules/Blog/database/factories/PostFactory.php');
    expect(file_exists($path))->toBeTrue();

    $content = file_get_contents($path);
    expect($content)
        ->toContain('PostFactory extends Factory')
        ->toContain('fake()->words')
        ->toContain('fake()->paragraph');
});

it('make:crud generates Blade views with correct layout and content', function () {
    $this->artisan('make:crud', [
        'module' => 'Blog',
        'model' => 'Post',
        '--fields' => 'title:string,content:text,price:decimal,published:boolean',
    ])->assertSuccessful();

    $base = base_path('Modules/Blog/resources/views/posts');

    expect(file_exists("{$base}/index.blade.php"))->toBeTrue();
    expect(file_exists("{$base}/edit.blade.php"))->toBeTrue();
    expect(file_exists("{$base}/_fields.blade.php"))->toBeTrue();
    expect(file_exists("{$base}/create.blade.php"))->toBeTrue();

    $indexContent = file_get_contents("{$base}/index.blade.php");
    expect($indexContent)->toContain('backoffice::layouts.admin');

    $editContent = file_get_contents("{$base}/edit.blade.php");
    expect($editContent)
        ->toContain('backoffice::layouts.admin')
        ->toContain('$post->id');
});

it('make:crud generates Pest test file with correct structure', function () {
    $this->artisan('make:crud', [
        'module' => 'Blog',
        'model' => 'Post',
        '--fields' => 'title:string,content:text,price:decimal,published:boolean',
    ])->assertSuccessful();

    $path = base_path('Modules/Blog/tests/Feature/PostCrudTest.php');
    expect(file_exists($path))->toBeTrue();

    $content = file_get_contents($path);
    expect($content)
        ->toContain('RefreshDatabase')
        ->toContain('can list posts')
        ->toContain('can store a new Post');
});

it('make:crud --force allows overwriting existing files', function () {
    $this->artisan('make:crud', [
        'module' => 'Blog',
        'model' => 'Post',
        '--fields' => 'title:string,content:text,price:decimal,published:boolean',
        '--force' => true,
    ])->assertSuccessful();

    $this->artisan('make:crud', [
        'module' => 'Blog',
        'model' => 'Post',
        '--fields' => 'title:string,content:text,price:decimal,published:boolean',
        '--force' => true,
    ])->assertSuccessful();
});
