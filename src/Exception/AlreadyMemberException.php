<?php

declare(strict_types=1);

namespace App\Exception;

final class AlreadyMemberException extends \DomainException
{
    public function __construct(string $message = 'This user is already a member of this team.')
    {
        parent::__construct($message);
    }
}
