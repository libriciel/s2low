<?php

namespace S2lowLegacy\Lib;

//http://xml.insee.fr/schema/siret.html#controles
class Siren
{
    public const LENGTH = 9;

    private $luhnKey;

    public function __construct(LuhnKey $luhnKey, $value)
    {
        $this->luhnKey = $luhnKey;
        $this->value = preg_replace('/\s+/', '', $value);
    }

    public function isValid()
    {
        if (mb_strlen($this->value) != self::LENGTH) {
            return false;
        }
        return $this->luhnKey->isValid($this->value);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
