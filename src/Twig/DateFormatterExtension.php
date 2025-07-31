<?php

namespace S2low\Twig;

use S2lowLegacy\Class\Helpers;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DateFormatterExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('dbFormatDate', [$this, 'dbFormatDate']),
        ];
    }

    public function dbFormatDate(string $date): string
    {
        return Helpers::getDateFromBDDDate($date);
    }
}
