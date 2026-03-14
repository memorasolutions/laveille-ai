<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface UserInterface
{
    public function getKey();

    public function getName(): string;

    public function getEmail(): string;

    public function hasRole(array|string $roles): bool;
}
