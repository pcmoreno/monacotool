<?php

declare(strict_types=1);

namespace App\Enum;

enum MembershipStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Rejected = 'rejected';
}
