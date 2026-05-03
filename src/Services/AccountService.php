<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Membership;
use App\Entity\User;
use App\Exception\EmailAlreadyExistsException;
use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AccountService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly UserRepository $userRepository,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $mailerFrom,
    ) {
    }

    public function register(string $email, string $name, string $plainPassword): void
    {
        if ($this->userService->emailExists($email)) {
            throw new EmailAlreadyExistsException();
        }

        $plainToken = $this->generateToken();
        $user = $this->userService->create($email, $name, $plainPassword);
        $user->setEmailVerificationToken(hash('sha256', $plainToken));
        $this->userRepository->save($user);
        $this->userRepository->flush(); // single flush: persists user + token together

        $this->sendVerificationEmail($user, $plainToken);
    }

    public function verifyEmailByToken(string $plainToken): ?User
    {
        $user = $this->userRepository->findByVerificationToken(hash('sha256', $plainToken));
        if (!$user) {
            return null;
        }

        $user->setIsVerified(true);
        $user->setEmailVerificationToken(null);
        $this->userRepository->save($user);
        $this->userRepository->flush();

        return $user;
    }

    public function resendVerification(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => mb_strtolower($email)]);
        if (!$user || $user->isVerified()) {
            return;
        }

        $plainToken = $this->generateToken();
        $user->setEmailVerificationToken(hash('sha256', $plainToken));
        $this->userRepository->save($user);
        $this->userRepository->flush();

        $this->sendVerificationEmail($user, $plainToken);
    }

    public function sendPasswordResetEmail(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => mb_strtolower($email)]);
        if (!$user || !$user->isVerified()) {
            return;
        }

        $plainToken = $this->generateToken();
        $user->setPasswordResetToken(hash('sha256', $plainToken));
        $user->setPasswordResetExpiresAt(new \DateTimeImmutable('+1 hour'));
        $this->userRepository->save($user);
        $this->userRepository->flush();

        $url = $this->urlGenerator->generate(
            'app_login',
            ['reset-token' => $plainToken],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $this->mailer->send(
            (new TemplatedEmail())
                ->from($this->mailerFrom)
                ->to($user->getEmail())
                ->subject('Reset your MonacoTool password')
                ->htmlTemplate('email/password-reset.html.twig')
                ->context(['url' => $url, 'user' => $user]),
        );
    }

    public function resetPassword(string $plainToken, string $newPassword): bool
    {
        $user = $this->userRepository->findByPasswordResetToken(hash('sha256', $plainToken));
        if (!$user || $user->getPasswordResetExpiresAt() < new \DateTimeImmutable()) {
            return false;
        }

        $user->setPasswordResetToken(null);
        $user->setPasswordResetExpiresAt(null);
        $this->userService->updatePassword($user, $newPassword);

        return true;
    }

    public function finishAccountSetup(Membership $membership, string $name, string $plainPassword): void
    {
        $user = $membership->getUser();
        $user->setName($name);
        $user->setIsVerified(true);
        $this->userService->updatePassword($user, $plainPassword);
    }

    private function sendVerificationEmail(User $user, string $plainToken): void
    {
        $url = $this->urlGenerator->generate(
            'app_verify_email',
            ['token' => $plainToken],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $this->mailer->send(
            (new TemplatedEmail())
                ->from($this->mailerFrom)
                ->to($user->getEmail())
                ->subject('Verify your MonacoTool account')
                ->htmlTemplate('email/verification.html.twig')
                ->context(['url' => $url, 'user' => $user]),
        );
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
