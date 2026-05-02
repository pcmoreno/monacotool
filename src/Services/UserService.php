<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function create(string $email, string $name, string $plainPassword): User
    {
        $user = new User();
        $user->setEmail(mb_strtolower($email));
        $user->setName($name);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        return $user;
    }

    public function emailExists(string $email): bool
    {
        return $this->userRepository->findOneBy(['email' => mb_strtolower($email)]) !== null;
    }

    public function updatePassword(User $user, string $plainPassword): void
    {
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $this->userRepository->save($user);
    }
}
