<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Editor\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Editor\Models\Shortcode;

class ShortcodeSeeder extends Seeder
{
    public function run(): void
    {
        Shortcode::firstOrCreate(
            ['tag' => 'button'],
            [
                'name' => 'Bouton',
                'description' => 'Bouton avec lien',
                'html_template' => '<a href="{{ $url }}" class="btn btn-{{ $color }} btn-sm">{{ $content }}</a>',
                'parameters' => ['url', 'color'],
                'has_content' => true,
                'is_active' => true,
            ]
        );

        Shortcode::firstOrCreate(
            ['tag' => 'alert'],
            [
                'name' => 'Alerte',
                'description' => "Message d'alerte",
                'html_template' => '<div class="alert alert-{{ $type }}" role="alert">{{ $content }}</div>',
                'parameters' => ['type'],
                'has_content' => true,
                'is_active' => true,
            ]
        );

        Shortcode::firstOrCreate(
            ['tag' => 'youtube'],
            [
                'name' => 'Vidéo YouTube',
                'description' => 'Intégrer une vidéo YouTube',
                'html_template' => '<div class="ratio ratio-16x9"><iframe src="https://www.youtube.com/embed/{{ $id }}" allowfullscreen></iframe></div>',
                'parameters' => ['id'],
                'has_content' => false,
                'is_active' => true,
            ]
        );

        Shortcode::firstOrCreate(
            ['tag' => 'badge'],
            [
                'name' => 'Badge',
                'description' => 'Badge coloré',
                'html_template' => '<span class="badge bg-{{ $color }}">{{ $content }}</span>',
                'parameters' => ['color'],
                'has_content' => true,
                'is_active' => true,
            ]
        );

        Shortcode::firstOrCreate(
            ['tag' => 'card'],
            [
                'name' => 'Carte',
                'description' => 'Carte avec titre',
                'html_template' => '<div class="card"><div class="card-header">{{ $title }}</div><div class="card-body">{{ $content }}</div></div>',
                'parameters' => ['title'],
                'has_content' => true,
                'is_active' => true,
            ]
        );
    }
}
