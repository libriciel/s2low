<?php

namespace S2low\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ArrayFilterExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('arrayUnique', [$this, 'arrayUnique']),
        ];
    }

    public function arrayUnique(array $array): array
    {
        return array_unique($array);
    }
}
