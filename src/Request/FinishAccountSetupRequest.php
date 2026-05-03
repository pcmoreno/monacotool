<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class FinishAccountSetupRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 60)]
        public readonly string $name,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        public readonly string $password,
    ) {
    }
}
