<?php

namespace S2low\Twig;

use S2lowLegacy\Class\Helpers;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PrepareForDisplayExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('makeLabelDisplayable', [$this, 'makeLabelDisplayable']),
        ];
    }

    public function makeLabelDisplayable(?string $label): string
    {
        return isset($label) ? Helpers :: truncateString($label) : '';
    }
}
