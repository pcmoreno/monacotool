<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Team;
use App\Entity\User;
use App\Enum\TeamRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** @extends Voter<string, Team> */
final class TeamVoter extends Voter
{
    public const VIEW = 'team.view';
    public const EDIT = 'team.edit';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT], true)
            && $subject instanceof Team;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        foreach ($subject->getMemberships() as $membership) {
            if ($membership->getUser() !== $user) {
                continue;
            }

            return match ($attribute) {
                self::VIEW => true,
                self::EDIT => $membership->getRole() === TeamRole::Admin,
                default => false,
            };
        }

        return false;
    }
}
