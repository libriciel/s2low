<?php

declare(strict_types=1);

namespace S2low\Tests\Helpers;

use PHPUnit\Framework\TestCase;
use S2low\Helpers\UserIdentityHelper;

class UserIdentityHelperTest extends TestCase
{
    /**
     * @return array<string, array{0: string|null, 1: string|null, 2: string}>
     */
    public static function initialsProvider(): array
    {
        return [
            'prénom et nom' => ['Jean', 'Dupont', 'JD'],
            'déjà en majuscules' => ['JEAN', 'DUPONT', 'JD'],
            'prénom accentué' => ['Élodie', 'Martin', 'ÉM'],
            'prénom composé' => ['Jean-Luc', 'Da Silva', 'JD'],
            'espaces superflus' => ['  Jean ', ' Dupont ', 'JD'],
            'nom seul' => [null, 'Dupont', 'D'],
            'prénom seul' => ['Jean', null, 'J'],
            'chaînes vides' => ['', '', ''],
        ];
    }

    /**
     * @dataProvider initialsProvider
     */
    public function testGetInitials(?string $givenname, ?string $name, string $expected): void
    {
        static::assertSame($expected, UserIdentityHelper::getInitials($givenname, $name));
    }
}
