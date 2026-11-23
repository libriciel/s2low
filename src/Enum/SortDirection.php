<?php

declare(strict_types=1);

namespace S2low\Enum;

enum SortDirection: string
{
    case Asc = 'asc';
    case Desc = 'desc';

    public static function fromQuery(mixed $sortWay): self
    {
        return $sortWay === self::Asc->value ? self::Asc : self::Desc;
    }

    public function reversed(): self
    {
        return $this === self::Asc ? self::Desc : self::Asc;
    }

    public function arrow(): string
    {
        return $this === self::Asc ? '▲' : '▼';
    }
}
