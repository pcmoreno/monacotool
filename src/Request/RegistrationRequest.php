<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class RegistrationRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email,

        #[Assert\NotBlank]
        public readonly string $name,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters.')]
        public readonly string $password,

        #[Assert\NotBlank]
        public readonly string $confirmPassword,
    ) {
    }
}
