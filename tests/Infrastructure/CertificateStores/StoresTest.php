<?php

namespace Infrastructure\CertificateStores;

use PHPUnit\Framework\TestCase;
use S2low\Exceptions\UndefinedCertificateStore;
use S2low\Infrastructure\CertificateStores\Store;
use S2low\Infrastructure\CertificateStores\Stores;
use S2low\Infrastructure\CertificateStores\Type;

class StoresTest extends TestCase
{
    private const RGS = '/rgs/';
    private const EXTENDED = '/Etendu/';

    /**
     * @dataProvider data
     * @throws \S2low\Exceptions\UndefinedCertificateStore
     */
    public function testGet(bool $only_use_validcargs, string $expectedPath): void
    {
        $storeRGSPath = new Store(self::RGS, Type::RGS);
        $storeExtendedPath = new Store(self::EXTENDED, Type::EXTENDED);

        $stores = new Stores(
            $only_use_validcargs,
            $storeRGSPath,
            $storeExtendedPath
        );

        self::assertSame(
            $stores->getStorePath(),
            $expectedPath
        );
    }

    public function data()
    {
        return [
            [true, self::RGS],
            [false, self::EXTENDED]
        ];
    }

    /**
     * @dataProvider emptyException
     */
    public function testEmpty(bool $only_use_validcargs, string $expectedExceptionMessage): void
    {
        self::expectException(UndefinedCertificateStore::class);
        self::expectExceptionMessage($expectedExceptionMessage);
        $stores = new Stores($only_use_validcargs);
        $stores->getStorePath();
    }

    public function emptyException()
    {
        return [
            [true, 'Magasin de certificats de type RGS non trouvé'],
            [false,'Magasin de certificats de type Etendu non trouvé']
        ];
    }
}
