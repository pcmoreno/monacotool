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
        $user->setEmail($email);
        $user->setName($name);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $this->userRepository->save($user);

        return $user;
    }

    public function emailExists(string $email): bool
    {
        return $this->userRepository->findOneBy(['email' => $email]) !== null;
    }
}
