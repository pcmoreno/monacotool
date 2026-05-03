<?php

declare(strict_types=1);

namespace App\Exception;

class AlreadyMemberException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('This user is already a member of this team.');
    }
}
