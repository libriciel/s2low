<?php

use App\CloudAccess\OldS3;
use App\DatabaseAccess\SelfDB;
use App\DTO\MigrationItem;
use App\Enum\Status;
use App\Enum\Type;
use App\Service\BucketResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BucketResolverTest extends TestCase
{
    private SelfDB|MockObject $selfDBMock;
    private OldS3|MockObject $oldS3Mock;
    private BucketResolver $bucketResolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->selfDBMock = $this->createMock(SelfDB::class);
        $this->oldS3Mock = $this->createMock(OldS3::class);
        $this->bucketResolver = new BucketResolver($this->selfDBMock, $this->oldS3Mock);
    }

    // -----------------------------------------------------------------------
    // Run()
    // -----------------------------------------------------------------------

    public function testRunWithNoTransactions(): void
    {
        $this->selfDBMock->expects($this->once())
            ->method('getTransactionsByStatus')
            ->with(Status::HANDLE, 50)
            ->willReturn([]);

        $this->oldS3Mock->expects($this->never())->method('exists');

        ob_start();
        $this->bucketResolver->run(50);
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testRunWithTransactionsProcessesThem(): void
    {
        $item = new MigrationItem(1, 'siren1/file.tar.gz', Type::ACTE->value, '2023-01-01', 'siren1');

        $this->selfDBMock->expects($this->once())
            ->method('getTransactionsByStatus')
            ->with(Status::HANDLE, 10)
            ->willReturn([$item]);

        $this->oldS3Mock->expects($this->once())
            ->method('exists')
            ->with('sl-adullact-actes-2023', 'siren1/file.tar.gz')
            ->willReturn(true);

        $this->selfDBMock->expects($this->once())->method('updateBucket')->with($item, 'sl-adullact-actes-2023');
        $this->selfDBMock->expects($this->once())->method('updateStatus')->with($item, Status::BUCKET_FOUND);

        ob_start();
        $this->bucketResolver->run(10);
        $output = ob_get_clean();

        $this->assertStringContainsString('FOUND in sl-adullact-actes-2023', $output);
    }

    // -----------------------------------------------------------------------
    // ACTES
    // -----------------------------------------------------------------------

    public function testActesBucketFoundInYearBucket(): void
    {
        $item = new MigrationItem(1, 'siren/file.tar.gz', Type::ACTE->value, '2019-06-15', 'siren');

        $this->oldS3Mock->expects($this->once())
            ->method('exists')
            ->with('sl-adullact-actes-2019', 'siren/file.tar.gz')
            ->willReturn(true);

        $this->selfDBMock->expects($this->once())->method('updateBucket')->with($item, 'sl-adullact-actes-2019');
        $this->selfDBMock->expects($this->once())->method('updateStatus')->with($item, Status::BUCKET_FOUND);

        ob_start();
        $result = $this->bucketResolver->processBucketResolve($item);
        ob_end_clean();

        $this->assertTrue($result);
    }

    public function testActes2007FallbackToNoDashBucket(): void
    {
        // Cas spécial 2007 : sl-adullact-actes-2007 n'existe pas, sl-adullact-actes2007 oui
        $item = new MigrationItem(1, 'siren/file.tar.gz', Type::ACTE->value, '2007-03-01', 'siren');

        // La priorité 1 est sl-adullact-actes2007, puis sladullact-actes, puis les années
        // Si ça existe dans le premier, 1 seul appel.
        $this->oldS3Mock->expects($this->once())
            ->method('exists')
            ->with('sl-adullact-actes2007', 'siren/file.tar.gz')
            ->willReturn(true);

        $this->selfDBMock->expects($this->once())->method('updateBucket')->with($item, 'sl-adullact-actes2007');
        $this->selfDBMock->expects($this->once())->method('updateStatus')->with($item, Status::BUCKET_FOUND);

        ob_start();
        $result = $this->bucketResolver->processBucketResolve($item);
        ob_end_clean();

        $this->assertTrue($result);
    }

    public function testActesFallbackToGlobalBucket(): void
    {
        $item = new MigrationItem(1, 'siren/file.tar.gz', Type::ACTE->value, '2005-01-01', 'siren');

        // 1. sl-adullact-actes-2005 (ko) -> 2. sladullact-actes (ok)
        $this->oldS3Mock->expects($this->exactly(2))
            ->method('exists')
            ->willReturnMap([
                ['sl-adullact-actes-2005', 'siren/file.tar.gz', false],
                ['sladullact-actes', 'siren/file.tar.gz', true],
            ]);

        $this->selfDBMock->expects($this->once())->method('updateBucket')->with($item, 'sladullact-actes');

        ob_start();
        $result = $this->bucketResolver->processBucketResolve($item);
        ob_end_clean();

        $this->assertTrue($result);
    }

    public function testActesFallbackToDifferentYearBucket(): void
    {
        $item = new MigrationItem(1, 'siren/file.tar.gz', Type::ACTE->value, '2020-01-01', 'siren');

        // Disons que c'est un acte de 2019 rattaché à 2020 administrativement.
        // 1. sl-adullact-actes-2020 (ko) -> 2. sladullact-actes (ko) -> ... fallback années ... -> sl-adullact-actes-2019 (ok)
        $this->oldS3Mock->expects($this->any())
            ->method('exists')
            ->willReturnCallback(function($bucket, $key) {
                return $bucket === 'sl-adullact-actes-2019'; // Ne renvoie true que pour 2019
            });

        $this->selfDBMock->expects($this->once())->method('updateBucket')->with($item, 'sl-adullact-actes-2019');

        ob_start();
        $result = $this->bucketResolver->processBucketResolve($item);
        ob_end_clean();

        $this->assertTrue($result);
    }

    public function testActesNotFoundAnywhere(): void
    {
        $item = new MigrationItem(1, 'siren/file.tar.gz', Type::ACTE->value, '2005-01-01', 'siren');

        // Va tester l'année 2005, le global, plus toutes les années 2026 à 2007 (soit ~22 appels)
        $this->oldS3Mock->expects($this->atLeast(20))
            ->method('exists')
            ->willReturn(false);

        $this->selfDBMock->expects($this->never())->method('updateBucket');
        $this->selfDBMock->expects($this->once())
            ->method('updateStatus')
            ->with($item, Status::ERROR, "File not found in any S3 bucket array tests.");

        ob_start();
        $result = $this->bucketResolver->processBucketResolve($item);
        ob_end_clean();

        $this->assertFalse($result);
    }

    // -----------------------------------------------------------------------
    // PES ALLER (Sharding hex)
    // -----------------------------------------------------------------------

    public function testPesAllerBucketFoundInFirstPriorityBySha1Prefix(): void
    {
        // Key = "siren/abc123def" -> sha1 commence par 'a' -> bucket: sladullact-helios-aller-filea
        $item = new MigrationItem(10, 'siren/abc123def', Type::PES_ALLER->value, '2020-01-01', 'siren');

        $this->oldS3Mock->expects($this->once())
            ->method('exists')
            ->with('sladullact-helios-aller-filea', 'siren/abc123def')
            ->willReturn(true);

        $this->selfDBMock->expects($this->once())->method('updateBucket')->with($item, 'sladullact-helios-aller-filea');
        $this->selfDBMock->expects($this->once())->method('updateStatus')->with($item, Status::BUCKET_FOUND);

        ob_start();
        $result = $this->bucketResolver->processBucketResolve($item);
        ob_end_clean();

        $this->assertTrue($result);
    }

    public function testPesAllerSha1StartsWith0(): void
    {
        // Key = "siren/0deadbeef" -> sha1 commence par '0' -> bucket: sladullact-helios-aller-file0
        $item = new MigrationItem(10, 'siren/0deadbeef', Type::PES_ALLER->value, '2020-01-01', 'siren');

        $this->oldS3Mock->expects($this->once())
            ->method('exists')
            ->with('sladullact-helios-aller-file0', 'siren/0deadbeef')
            ->willReturn(true);

        $this->selfDBMock->expects($this->once())->method('updateBucket')->with($item, 'sladullact-helios-aller-file0');

        ob_start();
        $result = $this->bucketResolver->processBucketResolve($item);
        ob_end_clean();

        $this->assertTrue($result);
    }

    public function testPesAllerFallbackToOtherHexBucket(): void
    {
        // Key = "siren/fab123" -> sha1 commence par 'f' -> priorité 1 = sladullact-helios-aller-filef
        // Mais pas trouvé dans filef, trouvé dans file3 (fallback)
        $item = new MigrationItem(10, 'siren/fab123', Type::PES_ALLER->value, '2020-01-01', 'siren');

        // filef -> false, file0 -> false, file1 -> false, file2 -> false, file3 -> true
        $this->oldS3Mock->expects($this->exactly(5))
            ->method('exists')
            ->willReturnMap([
                ['sladullact-helios-aller-filef', 'siren/fab123', false],
                ['sladullact-helios-aller-file0', 'siren/fab123', false],
                ['sladullact-helios-aller-file1', 'siren/fab123', false],
                ['sladullact-helios-aller-file2', 'siren/fab123', false],
                ['sladullact-helios-aller-file3', 'siren/fab123', true],
            ]);

        $this->selfDBMock->expects($this->once())->method('updateBucket')->with($item, 'sladullact-helios-aller-file3');

        ob_start();
        $result = $this->bucketResolver->processBucketResolve($item);
        ob_end_clean();

        $this->assertTrue($result);
    }

    // -----------------------------------------------------------------------
    // PES ACQUIT
    // -----------------------------------------------------------------------

    public function testPesAcquitSingleBucket(): void
    {
        $item = new MigrationItem(5, 'siren/acquit.xml', Type::PES_ACQUIT->value, '2019-12-01', 'siren');

        $this->oldS3Mock->expects($this->once())
            ->method('exists')
            ->with('sladullact-helios-pesacquitprefix', 'siren/acquit.xml')
            ->willReturn(true);

        $this->selfDBMock->expects($this->once())->method('updateBucket')->with($item, 'sladullact-helios-pesacquitprefix');

        ob_start();
        $result = $this->bucketResolver->processBucketResolve($item);
        ob_end_clean();

        $this->assertTrue($result);
    }

    public function testPesAcquitNotFound(): void
    {
        $item = new MigrationItem(5, 'siren/acquit.xml', Type::PES_ACQUIT->value, '2019-12-01', 'siren');

        $this->oldS3Mock->expects($this->once())
            ->method('exists')
            ->with('sladullact-helios-pesacquitprefix', 'siren/acquit.xml')
            ->willReturn(false);

        $this->selfDBMock->expects($this->once())
            ->method('updateStatus')
            ->with($item, Status::ERROR, "File not found in any S3 bucket array tests.");

        ob_start();
        $result = $this->bucketResolver->processBucketResolve($item);
        ob_end_clean();

        $this->assertFalse($result);
    }

    // -----------------------------------------------------------------------
    // MAIL
    // -----------------------------------------------------------------------

    public function testMailSingleBucket(): void
    {
        $item = new MigrationItem(7, 'siren/dl_folder/mail.zip', Type::MAIL->value, '2022-03-01', 'siren');

        $this->oldS3Mock->expects($this->once())
            ->method('exists')
            ->with('sladullact-mail', 'siren/dl_folder/mail.zip')
            ->willReturn(true);

        $this->selfDBMock->expects($this->once())->method('updateBucket')->with($item, 'sladullact-mail');

        ob_start();
        $result = $this->bucketResolver->processBucketResolve($item);
        ob_end_clean();

        $this->assertTrue($result);
    }
}
