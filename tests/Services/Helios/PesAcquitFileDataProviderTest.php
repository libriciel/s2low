<?php

declare(strict_types=1);

namespace S2low\Tests\Services\Helios;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use S2low\Exceptions\TransactionNotFoundException;
use S2low\Services\Helios\PesAcquitFileDataProvider;
use S2lowLegacy\Model\HeliosTransactionsSQL;

/**
 * @covers \S2low\Services\Helios\PesAcquitFileDataProvider
 */
final class PesAcquitFileDataProviderTest extends TestCase
{
    private HeliosTransactionsSQL&MockObject $heliosTransactionsSQL;
    private PesAcquitFileDataProvider $provider;

    protected function setUp(): void
    {
        $this->heliosTransactionsSQL = $this->createMock(HeliosTransactionsSQL::class);
        $this->provider = new PesAcquitFileDataProvider($this->heliosTransactionsSQL);
    }

    /**
     * Vérifie que getRelativePath retourne le chemin du fichier acquit depuis la transaction
     */
    public function testGetRelativePathReturnsAcquitFilename(): void
    {
        $transactionId = 'tx-123';
        $expectedPath = 'path/to/acquit.xml';

        $this->heliosTransactionsSQL
            ->expects(self::once())
            ->method('getInfo')
            ->with($transactionId)
            ->willReturn([
                'acquit_filename' => $expectedPath,
                'siren' => '123456789',
            ]);

        $result = $this->provider->getRelativePath($transactionId);

        $this->assertSame($expectedPath, $result);
    }

    /**
     * Vérifie qu'une exception est levée quand la transaction n'existe pas
     */
    public function testGetRelativePathThrowsExceptionWhenTransactionNotFound(): void
    {
        $transactionId = 'tx-invalid';

        $this->heliosTransactionsSQL
            ->method('getInfo')
            ->willReturn(false);

        $this->expectException(TransactionNotFoundException::class);
        $this->expectExceptionMessage($transactionId);

        $this->provider->getRelativePath($transactionId);
    }

    /**
     * Vérifie que getCloudId retourne le siren suivi du nom de fichier acquit
     */
    public function testGetCloudIdReturnsSirenSlashAcquitFilename(): void
    {
        $transactionId = 'tx-123';
        $siren = '123456789';
        $acquitFilename = 'acquit.xml';

        $this->heliosTransactionsSQL
            ->expects(self::once())
            ->method('getInfo')
            ->with($transactionId)
            ->willReturn([
                'acquit_filename' => $acquitFilename,
                'siren' => $siren,
            ]);

        $result = $this->provider->getCloudId($transactionId);

        $this->assertSame($siren . '/' . $acquitFilename, $result);
    }

    /**
     * Vérifie qu'une exception est levée quand la transaction n'existe pas pour getCloudId
     */
    public function testGetCloudIdThrowsExceptionWhenTransactionNotFound(): void
    {
        $transactionId = 'tx-invalid';

        $this->heliosTransactionsSQL
            ->method('getInfo')
            ->willReturn(false);

        $this->expectException(TransactionNotFoundException::class);
        $this->expectExceptionMessage($transactionId);

        $this->provider->getCloudId($transactionId);
    }

    /**
     * Vérifie que getTransactionIdFromFileName retourne l'ID de transaction à partir du nom de fichier
     */
    public function testGetTransactionIdFromFileNameReturnsTransactionId(): void
    {
        $filePath = '/path/to/acquit_file.xml';
        $expectedTransactionId = 'tx-123';

        $this->heliosTransactionsSQL
            ->expects(self::once())
            ->method('getByPesAcquitName')
            ->with('acquit_file.xml')
            ->willReturn($expectedTransactionId);

        $result = $this->provider->getTransactionIdFromFileName($filePath);

        $this->assertSame($expectedTransactionId, $result);
    }

    /**
     * Vérifie que getTransactionIdFromFileName retourne null quand le fichier n'est pas trouvé
     */
    public function testGetTransactionIdFromFileNameReturnsNullWhenNotFound(): void
    {
        $filePath = '/path/to/unknown.xml';

        $this->heliosTransactionsSQL
            ->method('getByPesAcquitName')
            ->willReturn(false);

        $result = $this->provider->getTransactionIdFromFileName($filePath);

        $this->assertNull($result);
    }

    /**
     * Vérifie que setTransactionIsInCloud appelle correctement la méthode de mise à jour du statut cloud
     */
    public function testSetTransactionIsInCloudCallsHeliosTransactionsSQL(): void
    {
        $transactionId = 'tx-123';

        $this->heliosTransactionsSQL
            ->expects(self::once())
            ->method('setPesAcquitInCloud')
            ->with($transactionId, true);

        $this->provider->setTransactionIsInCloud($transactionId);
    }
}
