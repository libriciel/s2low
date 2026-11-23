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

    public function iconPath(): string
    {
        return match ($this) {
            self::Done => 'M5 8.2l2 2 4-4.2',
            self::InProgress => 'M5 8h6M8.5 5.5 11 8l-2.5 2.5',
            self::Waiting => 'M8 4.5V8l2.3 1.5',
            self::Failed => 'M8 4.5v4M8 11.2v.01',
            self::Inactive => 'M5.2 8h5.6',
        };
    }
}
