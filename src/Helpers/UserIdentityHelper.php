<?php

namespace S2low\Helpers;

class UserIdentityHelper
{
    public static function getInitials(?string $givenname, ?string $name): string
    {
        return self::firstLetter($givenname) . self::firstLetter($name);
    }

    private static function firstLetter(?string $value): string
    {
        return mb_strtoupper(mb_substr(trim((string) $value), 0, 1));
    }
}
