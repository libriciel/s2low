<?php

namespace S2lowLegacy\Lib;

class Siret
{
    private const LENGTH = 14;

    private $siren;
    private $luhnKey;

    public function __construct(LuhnKey $luhnKey, $value, Siren $siren)
    {
        $this->luhnKey = $luhnKey;
        $this->value = $value;
        $this->siren = $siren;
    }

    public function isValid()
    {
        if (mb_strlen($this->value) != self::LENGTH) {
            return false;
        }

        if (! $this->siren->isValid()) {
            return false;
        }

        return $this->luhnKey->isValid($this->value);
    }

    public function getValue()
    {
        return $this->value;
    }
}
