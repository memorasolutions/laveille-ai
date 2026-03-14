<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Media\Traits\HasMediaAttachments;
use Spatie\MediaLibrary\HasMedia;

class MediaUpload extends Model implements HasMedia
{
    use HasMediaAttachments;

    protected $fillable = ['name'];
}
