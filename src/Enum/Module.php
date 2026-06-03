<?php

namespace S2low\Enum;

enum Module : int
{
    case ACTES = 1;
    case HELIOS = 2;
    case MAIL = 3;

    public function id(): int
    {
        return $this->value;
    }

    public function name(): string
    {
        return match ($this) {
            self::ACTES => 'actes',
            self::HELIOS => 'helios',
            self::MAIL => 'mail',
        };
    }
    public static function fromId(int $id): ?self
    {
        return self::tryFrom($id);
    }

    public static function fromName(string $name): ?self
    {
        foreach (self::cases() as $case) {
            if (strcasecmp($case->name(), $name) === 0) {
                return $case;
            }
        }
        return null;
    }
}
