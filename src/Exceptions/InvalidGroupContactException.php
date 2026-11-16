<?php

declare(strict_types=1);

namespace S2low\Exceptions;

use InvalidArgumentException;
use S2low\Enum\GroupContactField;

final class InvalidGroupContactException extends InvalidArgumentException
{
    public function __construct(public readonly GroupContactField $field, string $message)
    {
        parent::__construct($message);
    }
}
