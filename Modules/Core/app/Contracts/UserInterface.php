<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

interface UserInterface
{
    public function getKey();

    public function getName(): string;

    public function getEmail(): string;

    public function hasRole(array|string $roles): bool;
}
