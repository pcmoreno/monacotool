<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\User;
use App\Exception\EmailAlreadyExistsException;
use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AccountService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly UserRepository $userRepository,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function register(string $email, string $name, string $plainPassword): void
    {
        if ($this->userService->emailExists($email)) {
            throw new EmailAlreadyExistsException();
        }

        $user = $this->userService->create($email, $name, $plainPassword);
        $user->setEmailVerificationToken($this->generateToken());
        $this->userRepository->save($user);

        $this->sendVerificationEmail($user);
    }

    public function verifyEmailByToken(string $token): ?User
    {
        $user = $this->userRepository->findByVerificationToken($token);
        if (!$user) {
            return null;
        }

        $user->setIsVerified(true);
        $user->setEmailVerificationToken(null);
        $this->userRepository->save($user);

        return $user;
    }

    public function resendVerification(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => mb_strtolower($email)]);
        if (!$user || $user->isVerified()) {
            return;
        }

        $user->setEmailVerificationToken($this->generateToken());
        $this->userRepository->save($user);

        $this->sendVerificationEmail($user);
    }

    public function sendPasswordResetEmail(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => mb_strtolower($email)]);
        if (!$user) {
            return;
        }

        $user->setPasswordResetToken($this->generateToken());
        $user->setPasswordResetExpiresAt(new \DateTimeImmutable('+1 hour'));
        $this->userRepository->save($user);

        $url = $this->urlGenerator->generate(
            'app_login',
            ['reset-token' => $user->getPasswordResetToken()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $this->mailer->send(
            (new TemplatedEmail())
                ->from('noreply@monacotool.local')
                ->to($user->getEmail())
                ->subject('Reset your MonacoTool password')
                ->htmlTemplate('email/password-reset.html.twig')
                ->context(['url' => $url, 'user' => $user]),
        );
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $user = $this->userRepository->findByPasswordResetToken($token);
        if (!$user || $user->getPasswordResetExpiresAt() < new \DateTimeImmutable()) {
            return false;
        }

        $user->setPasswordResetToken(null);
        $user->setPasswordResetExpiresAt(null);
        $this->userService->updatePassword($user, $newPassword);

        return true;
    }

    private function sendVerificationEmail(User $user): void
    {
        $url = $this->urlGenerator->generate(
            'app_verify_email',
            ['token' => $user->getEmailVerificationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $this->mailer->send(
            (new TemplatedEmail())
                ->from('noreply@monacotool.local')
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
