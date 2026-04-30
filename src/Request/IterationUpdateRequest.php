<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final class IterationUpdateRequest
{
    public function __construct(
        #[SerializedName('end_date')]
        #[Assert\Date]
        public readonly ?string $endDate = null,

        #[Assert\PositiveOrZero]
        public readonly ?int $output = null,
    ) {
    }
}
