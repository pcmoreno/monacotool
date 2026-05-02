<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

#[Assert\Expression(
    'this.password === this.confirmPassword',
    message: 'Passwords do not match.'
)]
final class ResetPasswordRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public readonly string $token,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 72, minMessage: 'Password must be at least 8 characters.', maxMessage: 'Password must be 72 characters or fewer.')]
        public readonly string $password,

        #[Assert\NotBlank]
        public readonly string $confirmPassword,
    ) {
    }
}
