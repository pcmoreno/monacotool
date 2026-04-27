<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class ForecastRequest
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Positive]
        public readonly int $targetOutput,

        #[Assert\NotNull]
        #[Assert\Positive]
        public readonly int $targetIterations,
    ) {
    }
}
