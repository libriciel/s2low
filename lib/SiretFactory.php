<?php

namespace S2lowLegacy\Lib;

class SiretFactory
{
    /**
     * @var \S2lowLegacy\Lib\LuhnKey
     */
    private LuhnKey $luhnKey;
    /**
     * @var \S2lowLegacy\Lib\SirenFactory
     */
    private SirenFactory $sirenFactory;

    public function __construct(LuhnKey $luhnKey, SirenFactory $sirenFactory)
    {
        $this->luhnKey = $luhnKey;
        $this->sirenFactory = $sirenFactory;
    }

    public function get($value): Siret
    {
        return new Siret(
            $this->luhnKey,
            $value,
            $this->sirenFactory->get(mb_substr($value, 0, Siren::LENGTH))
        );
    }

    public function generate()
    {
        $siren = $this->luhnKey->generateValidNumber(Siren::LENGTH);
        $siret = $siren;
        for ($i = 0; $i < 4; $i++) {
            $siret .= mt_rand(0, 9);
        }
        return new Siret(
            $this->luhnKey,
            $this->luhnKey->getValidNumberWithBegin($siret),
            $this->sirenFactory->get($siren)
        );
    }
}
