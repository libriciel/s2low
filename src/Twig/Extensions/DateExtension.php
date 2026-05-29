<?php

namespace S2low\Twig\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DateExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('days_ago', [$this, 'calculateDaysAgo']),
        ];
    }

    public function calculateDaysAgo(\DateTimeInterface $date): int
    {
        $today = new \DateTime();
        return (int) $today->diff($date)->format('%a');
    }
}
