<?php

declare(strict_types=1);

namespace S2low\Enum;

enum GroupContactField: string
{
    case PHONE = 'phone';
    case EMAIL = 'email';
    case HOURS = 'hours';
    case MESSAGE = 'message';

    public function column(): string
    {
        return 'contact_' . $this->value;
    }

    public function shownColumn(): string
    {
        return 'contact_' . $this->value . '_shown';
    }

    public function label(): string
    {
        return match ($this) {
            self::PHONE => 'Téléphone',
            self::EMAIL => 'E-mail',
            self::HOURS => 'Horaires',
            self::MESSAGE => 'Message',
        };
    }
}
