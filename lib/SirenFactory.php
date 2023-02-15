<?php

namespace S2lowLegacy\Lib;

/**
 *
 */
class SirenFactory
{
    /**
     * @var \S2lowLegacy\Lib\LuhnKey
     */
    private LuhnKey $luhnKey;

    /**
     * @param \S2lowLegacy\Lib\LuhnKey $luhnKey
     */
    public function __construct(LuhnKey $luhnKey)
    {
        $this->luhnKey = $luhnKey;
    }

    /**
     * @return \S2lowLegacy\Lib\Siren
     */
    public function get(string $value): Siren
    {
        return new Siren(
            $this->luhnKey,
            $value
        );
    }

    public function generate()
    {
        return new Siren(
            $this->luhnKey,
            $this->luhnKey->generateValidNumber(Siren::LENGTH)
        );
    }
}
