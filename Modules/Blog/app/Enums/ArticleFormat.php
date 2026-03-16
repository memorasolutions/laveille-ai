<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Enums;

enum ArticleFormat: string
{
    case Standard = 'standard';
    case Video = 'video';
    case Gallery = 'gallery';
    case Audio = 'audio';
    case Quote = 'quote';
    case Link = 'link';

    public function label(): string
    {
        return match ($this) {
            self::Standard => __('Standard'),
            self::Video => __('Vidéo'),
            self::Gallery => __('Galerie'),
            self::Audio => __('Audio'),
            self::Quote => __('Citation'),
            self::Link => __('Lien'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Standard => 'file-text',
            self::Video => 'video',
            self::Gallery => 'images',
            self::Audio => 'music',
            self::Quote => 'quote',
            self::Link => 'link',
        };
    }
}
