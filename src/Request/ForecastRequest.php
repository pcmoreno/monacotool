<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class ForecastRequest
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Positive]
        #[Assert\LessThanOrEqual(100000)]
        public readonly int $targetOutput,

        #[Assert\NotNull]
        #[Assert\Positive]
        #[Assert\LessThanOrEqual(100)]
        public readonly int $targetIterations,
    ) {
    }
}
