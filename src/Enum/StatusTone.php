<?php

declare(strict_types=1);

namespace S2low\Enum;

enum StatusTone: string
{
    case Done = 'done';
    case InProgress = 'in-progress';
    case Waiting = 'waiting';
    case Failed = 'failed';
    case Inactive = 'inactive';

    public function symbol(): string
    {
        return match ($this) {
            self::Done => '✓',
            self::InProgress => '→',
            self::Waiting => '…',
            self::Failed => '!',
            self::Inactive => '–',
        };
    }
}
