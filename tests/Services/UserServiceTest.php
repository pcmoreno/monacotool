<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\UserService;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserServiceTest extends AbstractServiceTest
{
    private UserService $service;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->service = new UserService($this->userRepository, $this->passwordHasher);
    }

    public function test_create_returns_user_without_saving(): void
    {
        $this->passwordHasher->method('hashPassword')->willReturn('hashed_password');
        $this->userRepository->expects($this->never())->method('save');

        $user = $this->service->create('Test@Example.com', 'Test', 'password123');

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertSame('Test', $user->getName());
        $this->assertSame('hashed_password', $user->getPassword());
    }

    public function test_create_lowercases_email(): void
    {
        $this->passwordHasher->method('hashPassword')->willReturn('hashed');

        $user = $this->service->create('User@EXAMPLE.COM', 'Test', 'password');

        $this->assertSame('user@example.com', $user->getEmail());
    }

    public function test_email_exists_returns_true_when_user_found(): void
    {
        $this->userRepository->method('findOneBy')->willReturn($this->makeUser());

        $this->assertTrue($this->service->emailExists('user@example.com'));
    }

    public function test_email_exists_returns_false_when_user_not_found(): void
    {
        $this->userRepository->method('findOneBy')->willReturn(null);

        $this->assertFalse($this->service->emailExists('nobody@example.com'));
    }

    public function test_update_password_hashes_and_persists(): void
    {
        $user = $this->makeUser();
        $this->passwordHasher->method('hashPassword')->with($user, 'newpassword')->willReturn('new_hashed');
        $this->userRepository->expects($this->once())->method('save')->with($user);
        $this->userRepository->expects($this->once())->method('flush');

        $this->service->updatePassword($user, 'newpassword');

        $this->assertSame('new_hashed', $user->getPassword());
    }
}
