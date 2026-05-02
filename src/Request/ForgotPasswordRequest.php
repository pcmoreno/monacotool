<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class ForgotPasswordRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email,
    ) {
    }
}
