<?php

namespace S2low\Twig\Extensions;

use S2low\Enum\UserRole;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class LabeliseRoleExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('labeliseRole', [$this, 'roleToLabel']),
        ];
    }

    public function roleToLabel(string $userRole): string
    {
        return UserRole::from($userRole)->getLabel();
    }
}
