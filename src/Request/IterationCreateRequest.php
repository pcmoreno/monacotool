<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class IterationCreateRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Date]
        public readonly string $endDate,

        #[Assert\NotNull]
        #[Assert\PositiveOrZero]
        public readonly int $output,
    ) {
    }
}
