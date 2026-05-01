<?php

declare(strict_types=1);

namespace App\Exception;

final class TooManyTeamsException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('You have reached the maximum of 5 teams.');
    }
}
