<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Academy\Models\CertificateIssued;
use Modules\Academy\Models\Course;

final class AcademyCertificateIssued
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Course $course,
        public readonly CertificateIssued $certificate,
    ) {}
}
