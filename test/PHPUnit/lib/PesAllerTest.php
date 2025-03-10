<?php

declare(strict_types=1);

namespace PHPUnit\lib;

use Exception;
use PHPUnit\Framework\TestCase;
use S2lowLegacy\Lib\PesAller;

class PesAllerTest extends TestCase
{
    private PesAller $pesAller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pesAller = new PesAller();
    }

    /**
     * @throws Exception
     * @dataProvider bonsPesAllers
     */
    public function testGetPmsg(string $path, string $P_MSG): void
    {
        static::assertEquals($P_MSG, $this->pesAller->getP_MSG($path));
    }
    public function bonsPesAllers(): array
    {
        return [
            [ __DIR__ . '/fixtures/HELIOS_SIMU_ALR2_1444811220_681372666.xml','PES#123#034000#12'],
            [__DIR__ . '/fixtures/PES_ACQUIT_RETOUR.xml', 'PES#007#123456#12']
        ];
    }

    /**
     * @throws Exception
     * @dataProvider mauvaisPesAllers
     */
    public function testGetPmsgBadPesAller(string $path, string $message)
    {
        self::expectException(Exception::class);
        self::expectExceptionMessage($message);
        $this->pesAller->getP_MSG($path);
    }

    public function mauvaisPesAllers(): array
    {
        return [
            [__DIR__ . '/fixtures/test.xml', 'La balise EnTetePES n\'est pas présente ou est vide'],
            [__DIR__ . '/fixtures/HELIOS_SIMU_ALR2_NoCodBud.xml','La balise EnTetePES/CodBud n\'est pas présente ou est vide' ],
            [__DIR__ . '/fixtures/HELIOS_SIMU_ALR2_NoCodColl.xml', 'La balise EnTetePES/CodCol ou EnTetePES/CodColl n\'est pas présente ou est vide'],
            [__DIR__ . '/fixtures/HELIOS_SIMU_ALR2_NoIdPost.xml', 'La balise EnTetePES/IdPost n\'est pas présente ou est vide']
        ];
    }
}
