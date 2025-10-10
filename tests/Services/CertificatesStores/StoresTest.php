<?php

namespace S2low\Tests\Services\CertificatesStores;

use PHPUnit\Framework\TestCase;
use S2low\Exceptions\CertificateStoreNotFoundException;
use S2low\Services\CertificateStores\Store;
use S2low\Services\CertificateStores\Stores;
use S2low\Services\CertificateStores\Type;

class StoresTest extends TestCase
{
    private const RGS = '/rgs/';
    private const EXTENDED = '/extended/';

    /**
     * @dataProvider data
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
            $stores->getDefaultStorePath(),
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
        self::expectException(CertificateStoreNotFoundException::class);
        self::expectExceptionMessage($expectedExceptionMessage);
        $stores = new Stores($only_use_validcargs);
        $stores->getDefaultStorePath();
    }

    public function emptyException()
    {
        return [
            [true, Type::RGS->name . ' Certificate Store not found'],
            [false,Type::EXTENDED->name . ' Certificate Store not found']
        ];
    }
}
