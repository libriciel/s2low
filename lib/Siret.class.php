<?php

class Siret
{
    const LENGTH = 14;

    private $siren;
    private $luhnKey;

    public function __construct(LuhnKey $luhnKey, Siren $siren)
    {
        $this->luhnKey = $luhnKey;
        $this->siren = $siren;
    }

    public function isValid($siret)
    {
        if (mb_strlen($siret) != self::LENGTH) {
            return false;
        }

        $siren = mb_substr($siret, 0, Siren::LENGTH);
        if (! $this->siren->isValid($siren)) {
            return false;
        }

        return $this->luhnKey->isValid($siret);
    }


    public function generate()
    {
        $siren = $this->luhnKey->generateValidNumber(Siren::LENGTH);
        for ($i = 0; $i < 4; $i++) {
            $siren .= mt_rand(0, 9);
        }
        return $this->luhnKey->getValidNumberWithBegin($siren);
    }
}
