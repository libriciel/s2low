<?php

namespace S2low\Twig\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CssClassResolver extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('toCssClass', [$this, 'toCssClass']),
        ];
    }

    public function toCssClass(int $int): string
    {
        $classes = [
            0 => 'info',
            1 => 'info',
            2 => 'warning',
            3 => 'danger'
        ];

        return $classes[$int];
    }
}
