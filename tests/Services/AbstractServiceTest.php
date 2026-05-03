<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Entity\Team;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

abstract class AbstractServiceTest extends TestCase
{
    protected function makeUser(
        string $email = 'user@example.com',
        bool $isVerified = true,
        array $roles = [],
    ): User {
        $user = new User();
        $user->setEmail($email);
        $user->setName('Test User');
        $user->setIsVerified($isVerified);
        $user->setPassword('hashed');
        if ($roles !== []) {
            $user->setRoles($roles);
        }

        return $user;
    }

    protected function makeSuperAdmin(string $email = 'super@example.com'): User
    {
        return $this->makeUser($email, true, ['ROLE_SUPER_ADMIN']);
    }

    protected function makeTeam(string $name = 'Test Team'): Team
    {
        $team = new Team();
        $team->setName($name);

        return $team;
    }
}
