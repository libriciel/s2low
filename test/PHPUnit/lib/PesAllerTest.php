<?php

declare(strict_types=1);

namespace PHPUnit\lib;

use Exception;
use PHPUnit\Framework\TestCase;
use S2lowLegacy\Lib\PesAllerReader;

class PesAllerTest extends TestCase
{
    private PesAllerReader $pesAllerReader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pesAllerReader = new PesAllerReader();
    }

    /**
     * @throws Exception
     * @dataProvider bonsPesAllers
     */
    public function testGetPmsg(string $path, bool $isPesAcquitRetour, string $codColl, string $codBud, string $idPost): void
    {
        $pesAllerData = $this->pesAllerReader->getPesAllerData($path);
        static::assertEquals($isPesAcquitRetour, $pesAllerData->isPesAcquitRetour());
        static::assertEquals($codColl, $pesAllerData->getCodCol());
        static::assertEquals($codBud, $pesAllerData->getCodBud());
        static::assertEquals($idPost, $pesAllerData->getIdPost());
    }
    public function bonsPesAllers(): array
    {
        return [
            [ __DIR__ . '/fixtures/HELIOS_SIMU_ALR2_1444811220_681372666.xml',false,'123','12','034000'],
            [__DIR__ . '/fixtures/PES_ACQUIT_RETOUR.xml', true,'007','12','123456']
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
        $this->pesAllerReader->getPesAllerData($path);
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
