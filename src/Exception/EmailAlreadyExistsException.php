<?php

declare(strict_types=1);

namespace App\Exception;

class EmailAlreadyExistsException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('An account with this email already exists.');
    }
}
