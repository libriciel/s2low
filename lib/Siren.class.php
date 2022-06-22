<?php

//http://xml.insee.fr/schema/siret.html#controles
class Siren
{
    const LENGTH = 9;

    private $luhnKey;

    public function __construct(LuhnKey $luhnKey)
    {
        $this->luhnKey = $luhnKey;
    }

    public function isValid($siren)
    {
        if (mb_strlen($siren) != self::LENGTH) {
            return false;
        }
        return $this->luhnKey->isValid($siren);
    }

    public function generate()
    {
        return $this->luhnKey->generateValidNumber(self::LENGTH);
    }
}
