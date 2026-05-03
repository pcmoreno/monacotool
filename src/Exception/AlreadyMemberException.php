<?php

declare(strict_types=1);

namespace App\Exception;

class AlreadyMemberException extends \RuntimeException
{
    public function __construct(string $message = 'This user is already a member of this team.')
    {
        parent::__construct($message);
    }
}
