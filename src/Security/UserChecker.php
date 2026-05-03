<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Security\EmailNotVerifiedException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isVerified()) {
            throw new EmailNotVerifiedException();
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
