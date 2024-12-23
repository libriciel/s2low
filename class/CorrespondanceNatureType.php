<?php

namespace S2lowLegacy\Class;

class CorrespondanceNatureType
{

    const CORRESPONDANCE = [
          '1' => '99_DE',
          '2' => '99_AR',
          '3' => '99_AI',
          '4' => '99_DC',
          '5' => '99_BU',
          '6' => '99_AU',
        ];

    public static function getCorrespondanceNature(int $correspondanceNature)
    {
        return self::CORRESPONDANCE[$correspondanceNature];
    }

}