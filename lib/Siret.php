<?php

namespace S2lowLegacy\Lib;

class Siret
{
    private const LENGTH = 14;

    public function __construct(
        private readonly LuhnKey $luhnKey,
        private readonly string $value,
        private readonly Siren $siren,
    ) {
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
