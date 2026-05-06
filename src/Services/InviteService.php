<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Membership;
use App\Entity\Team;
use App\Entity\User;
use App\Enum\MembershipStatus;
use App\Enum\TeamRole;
use App\Exception\AlreadyMemberException;
use App\Repository\MembershipRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class InviteService
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
        private readonly MembershipRepository $membershipRepository,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $mailerFrom,
        private readonly int $inviteTokenExpiryDays,
    ) {
    }

    public function invite(Team $team, string $name, string $email): void
    {
        $email = mb_strtolower($email);

        $this->guardNotAlreadyPendingOrActiveMember($team, $email);

        $user = $this->userRepository->findOneBy(['email' => $email]);
        $isNewUser = $user === null;

        if ($isNewUser) {
            $user = new User();
            $user->setEmail($email);
            $user->setName($name);
            $user->setPassword(bin2hex(random_bytes(32))); // unhashed sentinel — never valid for login
            $user->setIsVerified(false);
        }

        $plainToken = $this->generateToken();
        $hashedToken = hash('sha256', $plainToken);

        // Reuse an existing rejected row to avoid hitting the unique (user, team) constraint
        $membership = $this->findRejectedMembership($team, $email) ?? new Membership();
        $membership->setUser($user);
        $membership->setTeam($team);
        $membership->setRole(TeamRole::User);
        $membership->setStatus(MembershipStatus::Pending);
        $membership->setInviteToken($hashedToken);
        $membership->setInviteExpiresAt(new \DateTimeImmutable(sprintf('+%d days', $this->inviteTokenExpiryDays)));

        if ($isNewUser) {
            $this->userRepository->save($user);
        }
        $this->membershipRepository->save($membership);
        $this->membershipRepository->flush();

        if ($isNewUser) {
            $this->sendNewUserInviteEmail($user, $team, $plainToken);
        } else {
            $this->sendExistingUserInviteEmail($user, $team, $plainToken);
        }
    }

    public function completeSetup(Membership $membership, string $name, string $plainPassword): void
    {
        $user = $membership->getUser();
        $user->setName($name);
        $user->setIsVerified(true);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $membership->setStatus(MembershipStatus::Active);
        $membership->setInviteToken(null);
        $membership->setInviteExpiresAt(null);

        $this->userRepository->save($user);
        $this->membershipRepository->save($membership);
        $this->membershipRepository->flush();
    }

    public function findPendingByToken(string $plainToken): ?Membership
    {
        $membership = $this->membershipRepository->findByInviteToken(hash('sha256', $plainToken));

        if (!$membership || $membership->getStatus() !== MembershipStatus::Pending) {
            return null;
        }

        return $membership;
    }

    public function accept(Membership $membership): void
    {
        $membership->setStatus(MembershipStatus::Active);
        $membership->setInviteToken(null);
        $membership->setInviteExpiresAt(null);
        $this->membershipRepository->save($membership);
        $this->membershipRepository->flush();
    }

    public function reject(Membership $membership): void
    {
        $membership->setStatus(MembershipStatus::Rejected);
        $membership->setInviteToken(null);
        $membership->setInviteExpiresAt(null);
        $this->membershipRepository->save($membership);
        $this->membershipRepository->flush();
    }

    private function guardNotAlreadyPendingOrActiveMember(Team $team, string $email): void
    {
        foreach ($team->getMemberships() as $membership) {
            if ($membership->getUser()->getEmail() !== $email) {
                continue;
            }

            match ($membership->getStatus()) {
                MembershipStatus::Active => throw new AlreadyMemberException('This user is already a member of this team.'),
                MembershipStatus::Pending => throw new AlreadyMemberException('This user has already been invited.'),
                default => null,
            };
        }
    }

    private function findRejectedMembership(Team $team, string $email): ?Membership
    {
        foreach ($team->getMemberships() as $membership) {
            if (
                $membership->getUser()->getEmail() === $email
                && $membership->getStatus() === MembershipStatus::Rejected
            ) {
                return $membership;
            }
        }

        return null;
    }

    private function sendNewUserInviteEmail(User $user, Team $team, string $plainToken): void
    {
        $url = $this->urlGenerator->generate(
            'app_invite_setup',
            ['token' => $plainToken],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $this->mailer->send(
            (new TemplatedEmail())
                ->from($this->mailerFrom)
                ->to($user->getEmail())
                ->subject('You\'ve been invited to join ' . str_replace(["\r", "\n"], '', $team->getName()) . ' on MonacoTool')
                ->htmlTemplate('email/invite-new-user.html.twig')
                ->context(['user' => $user, 'team' => $team, 'url' => $url]),
        );
    }

    private function sendExistingUserInviteEmail(User $user, Team $team, string $plainToken): void
    {
        $acceptUrl = $this->urlGenerator->generate(
            'app_invite_accept',
            ['token' => $plainToken],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $rejectUrl = $this->urlGenerator->generate(
            'app_invite_reject',
            ['token' => $plainToken],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $this->mailer->send(
            (new TemplatedEmail())
                ->from($this->mailerFrom)
                ->to($user->getEmail())
                ->subject('You\'ve been invited to join ' . str_replace(["\r", "\n"], '', $team->getName()) . ' on MonacoTool')
                ->htmlTemplate('email/invite-existing-user.html.twig')
                ->context(['user' => $user, 'team' => $team, 'acceptUrl' => $acceptUrl, 'rejectUrl' => $rejectUrl]),
        );
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
