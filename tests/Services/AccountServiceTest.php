<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Exception\EmailAlreadyExistsException;
use App\Repository\UserRepository;
use App\Services\AccountService;
use App\Services\UserService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AccountServiceTest extends AbstractServiceTest
{
    private AccountService $service;
    private UserService $userService;
    private UserRepository $userRepository;
    private MailerInterface $mailer;
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        $this->userService = $this->createMock(UserService::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->urlGenerator->method('generate')->willReturn('http://localhost/link');

        $this->service = new AccountService(
            $this->userService,
            $this->userRepository,
            $this->mailer,
            $this->urlGenerator,
            'noreply@example.com',
        );
    }

    // --- register ---

    public function test_register_throws_if_email_already_exists(): void
    {
        $this->userService->method('emailExists')->willReturn(true);

        $this->expectException(EmailAlreadyExistsException::class);
        $this->service->register('existing@example.com', 'Test', 'password123');
    }

    public function test_register_persists_user_with_hashed_verification_token(): void
    {
        $user = $this->makeUser();
        $this->userService->method('emailExists')->willReturn(false);
        $this->userService->method('create')->willReturn($user);
        $this->mailer->method('send');

        $savedUser = null;
        $this->userRepository->expects($this->once())->method('save')
            ->with($this->callback(function (mixed $u) use (&$savedUser): bool {
                $savedUser = $u;
                return true;
            }));
        $this->userRepository->expects($this->once())->method('flush');

        $this->service->register('new@example.com', 'New', 'password123');

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $savedUser->getEmailVerificationToken());
    }

    public function test_register_sends_verification_email(): void
    {
        $this->userService->method('emailExists')->willReturn(false);
        $this->userService->method('create')->willReturn($this->makeUser());
        $this->userRepository->method('save');
        $this->userRepository->method('flush');

        $this->mailer->expects($this->once())->method('send');

        $this->service->register('new@example.com', 'New', 'password123');
    }

    // --- verifyEmailByToken ---

    public function test_verify_email_returns_null_for_unknown_token(): void
    {
        $this->userRepository->method('findByVerificationToken')->willReturn(null);

        $this->assertNull($this->service->verifyEmailByToken('invalidtoken'));
    }

    public function test_verify_email_marks_user_verified_and_clears_token(): void
    {
        $user = $this->makeUser(isVerified: false);
        $user->setEmailVerificationToken(hash('sha256', 'sometoken'));
        $this->userRepository->method('findByVerificationToken')->willReturn($user);
        $this->userRepository->method('save');
        $this->userRepository->method('flush');

        $result = $this->service->verifyEmailByToken('sometoken');

        $this->assertSame($user, $result);
        $this->assertTrue($user->isVerified());
        $this->assertNull($user->getEmailVerificationToken());
    }

    // --- resendVerification ---

    public function test_resend_verification_does_nothing_for_unknown_email(): void
    {
        $this->userRepository->method('findOneBy')->willReturn(null);

        $this->mailer->expects($this->never())->method('send');
        $this->userRepository->expects($this->never())->method('save');

        $this->service->resendVerification('nobody@example.com');
    }

    public function test_resend_verification_does_nothing_for_already_verified_user(): void
    {
        $this->userRepository->method('findOneBy')->willReturn($this->makeUser(isVerified: true));

        $this->mailer->expects($this->never())->method('send');
        $this->userRepository->expects($this->never())->method('save');

        $this->service->resendVerification('verified@example.com');
    }

    public function test_resend_verification_sends_email_for_unverified_user(): void
    {
        $this->userRepository->method('findOneBy')->willReturn($this->makeUser(isVerified: false));
        $this->userRepository->method('save');
        $this->userRepository->method('flush');

        $this->mailer->expects($this->once())->method('send');

        $this->service->resendVerification('unverified@example.com');
    }

    // --- sendPasswordResetEmail ---

    public function test_send_password_reset_does_nothing_for_unknown_email(): void
    {
        $this->userRepository->method('findOneBy')->willReturn(null);

        $this->mailer->expects($this->never())->method('send');
        $this->userRepository->expects($this->never())->method('save');

        $this->service->sendPasswordResetEmail('nobody@example.com');
    }

    public function test_send_password_reset_does_nothing_for_unverified_user(): void
    {
        $this->userRepository->method('findOneBy')->willReturn($this->makeUser(isVerified: false));

        $this->mailer->expects($this->never())->method('send');
        $this->userRepository->expects($this->never())->method('save');

        $this->service->sendPasswordResetEmail('unverified@example.com');
    }

    public function test_send_password_reset_stores_hashed_token_and_sends_email(): void
    {
        $user = $this->makeUser(isVerified: true);
        $this->userRepository->method('findOneBy')->willReturn($user);
        $this->userRepository->method('save');
        $this->userRepository->method('flush');

        $this->mailer->expects($this->once())->method('send');

        $this->service->sendPasswordResetEmail('user@example.com');

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $user->getPasswordResetToken());
        $this->assertGreaterThan(new \DateTimeImmutable(), $user->getPasswordResetExpiresAt());
    }

    // --- resetPassword ---

    public function test_reset_password_returns_false_for_unknown_token(): void
    {
        $this->userRepository->method('findByPasswordResetToken')->willReturn(null);

        $this->assertFalse($this->service->resetPassword('badtoken', 'newpassword'));
    }

    public function test_reset_password_returns_false_for_expired_token(): void
    {
        $user = $this->makeUser();
        $user->setPasswordResetExpiresAt(new \DateTimeImmutable('-1 hour'));
        $this->userRepository->method('findByPasswordResetToken')->willReturn($user);

        $this->assertFalse($this->service->resetPassword('expiredtoken', 'newpassword'));
    }

    public function test_reset_password_clears_token_and_updates_password(): void
    {
        $user = $this->makeUser();
        $user->setPasswordResetToken(hash('sha256', 'validtoken'));
        $user->setPasswordResetExpiresAt(new \DateTimeImmutable('+1 hour'));
        $this->userRepository->method('findByPasswordResetToken')->willReturn($user);
        $this->userService->expects($this->once())->method('updatePassword')->with($user, 'newpassword');

        $result = $this->service->resetPassword('validtoken', 'newpassword');

        $this->assertTrue($result);
        $this->assertNull($user->getPasswordResetToken());
        $this->assertNull($user->getPasswordResetExpiresAt());
    }
}
