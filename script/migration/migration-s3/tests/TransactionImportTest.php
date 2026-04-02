<?php

use App\DatabaseAccess\SelfDB;
use App\DTO\MigrationItem;
use App\Enum\Status;
use App\Enum\Type;
use App\Migration\ActesSource;
use App\Migration\MailSource;
use App\Migration\PesAcquitSource;
use App\Migration\PesSource;
use App\Repository\ActesRepository;
use App\Repository\MailSecRepository;
use App\Repository\PesAcquitRepository;
use App\Repository\PesRepository;
use App\Service\TransactionImportFromS2low;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TransactionImportTest extends TestCase
{
    private SelfDB|MockObject $selfDBMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->selfDBMock = $this->createMock(SelfDB::class);
    }

    // -----------------------------------------------------------------------
    // ActesSource Tests
    // -----------------------------------------------------------------------

    public function testActesSourceIdentifier(): void
    {
        $repoMock = $this->createMock(ActesRepository::class);
        $source = new ActesSource($repoMock);
        $this->assertEquals(Type::ACTE->value, $source->getType());
    }

    public function testActesSourceGetItemsYieldsCorrectMigrationItems(): void
    {
        $repoMock = $this->createMock(ActesRepository::class);
        $repoMock->expects($this->exactly(2))
            ->method('getBatch')
            ->willReturnOnConsecutiveCalls(
                [
                    ['id' => 1, 'file_path' => 'path/to/file1', 'siren' => 'siren1', 'submission_date' => '2023-01-01'],
                    ['id' => 2, 'file_path' => 'path/to/file2', 'siren' => 'siren2', 'submission_date' => '2023-02-01'],
                ],
                [] // Empty batch -> stops loop
            );

        $source = new ActesSource($repoMock);
        $items = iterator_to_array($source->getItems(0));

        $this->assertCount(2, $items);
        $this->assertEquals(1, $items[0]->id);
        $this->assertEquals('path/to/file1', $items[0]->key);
        $this->assertEquals(Type::ACTE->value, $items[0]->type);
        $this->assertEquals('siren1', $items[0]->siren);
    }

    public function testActesSourceGetItemsWithMinDate(): void
    {
        $repoMock = $this->createMock(ActesRepository::class);
        $repoMock->expects($this->once())
            ->method('getBatch')
            ->with(0, TransactionImportFromS2low::LIMIT, '2023-06-01')
            ->willReturn([]);

        $source = new ActesSource($repoMock);
        $items = iterator_to_array($source->getItems(0, '2023-06-01'));
        $this->assertEmpty($items);
    }

    // -----------------------------------------------------------------------
    // PesSource Tests
    // -----------------------------------------------------------------------

    public function testPesSourceIdentifier(): void
    {
        $repoMock = $this->createMock(PesRepository::class);
        $source = new PesSource($repoMock);
        $this->assertEquals(Type::PES_ALLER->value, $source->getType());
    }

    public function testPesSourceGetItemsYieldsCorrectKey(): void
    {
        $repoMock = $this->createMock(PesRepository::class);
        $repoMock->expects($this->exactly(2))
            ->method('getBatch')
            ->willReturnOnConsecutiveCalls(
                [['id' => 10, 'sha1' => 'abc123', 'siren' => 'siren10', 'submission_date' => '2020-06-01']],
                []
            );

        $source = new PesSource($repoMock);
        $items = iterator_to_array($source->getItems(0));

        $this->assertCount(1, $items);
        $this->assertEquals('siren10/abc123', $items[0]->key);
        $this->assertEquals(Type::PES_ALLER->value, $items[0]->type);
    }

    // -----------------------------------------------------------------------
    // PesAcquitSource Tests
    // -----------------------------------------------------------------------

    public function testPesAcquitSourceIdentifier(): void
    {
        $repoMock = $this->createMock(PesAcquitRepository::class);
        $source = new PesAcquitSource($repoMock);
        $this->assertEquals(Type::PES_ACQUIT->value, $source->getType());
    }

    public function testPesAcquitSourceGetItemsYieldsCorrectKey(): void
    {
        $repoMock = $this->createMock(PesAcquitRepository::class);
        $repoMock->expects($this->exactly(2))
            ->method('getBatch')
            ->willReturnOnConsecutiveCalls(
                [['id' => 5, 'acquit_filename' => 'acquit.xml', 'siren' => 'siren5', 'submission_date' => '2019-12-01']],
                []
            );

        $source = new PesAcquitSource($repoMock);
        $items = iterator_to_array($source->getItems(0));

        $this->assertCount(1, $items);
        $this->assertEquals('siren5/acquit.xml', $items[0]->key);
        $this->assertEquals(Type::PES_ACQUIT->value, $items[0]->type);
    }

    // -----------------------------------------------------------------------
    // MailSource Tests
    // -----------------------------------------------------------------------

    public function testMailSourceIdentifier(): void
    {
        $repoMock = $this->createMock(MailSecRepository::class);
        $source = new MailSource($repoMock);
        $this->assertEquals(Type::MAIL->value, $source->getType());
    }

    public function testMailSourceGetItemsYieldsCorrectKey(): void
    {
        $repoMock = $this->createMock(MailSecRepository::class);
        $repoMock->expects($this->exactly(2))
            ->method('getBatch')
            ->willReturnOnConsecutiveCalls(
                [['id' => 7, 'fn_download' => 'dl_folder', 'siren' => 'siren7', 'date_envoi' => '2022-03-01']],
                []
            );

        $source = new MailSource($repoMock);
        $items = iterator_to_array($source->getItems(0));

        $this->assertCount(1, $items);
        $this->assertEquals('siren7/dl_folder/mail.zip', $items[0]->key);
        $this->assertEquals(Type::MAIL->value, $items[0]->type);
        $this->assertEquals('2022-03-01', $items[0]->date);
    }

    // -----------------------------------------------------------------------
    // TransactionImportFromS2low Tests
    // -----------------------------------------------------------------------

    public function testImportRunCreatesEntriesInSelfDB(): void
    {
        $repoMock = $this->createMock(ActesRepository::class);
        $repoMock->expects($this->exactly(2))
            ->method('getBatch')
            ->willReturnOnConsecutiveCalls(
                [
                    ['id' => 1, 'file_path' => 'path1', 'siren' => 's1', 'submission_date' => '2023-01-01'],
                    ['id' => 2, 'file_path' => 'path2', 'siren' => 's2', 'submission_date' => '2023-02-01'],
                ],
                []
            );

        $source = new ActesSource($repoMock);

        // SelfDB should get 2 calls to create()
        $this->selfDBMock->expects($this->once())
            ->method('getLastIdAtTypeAndStatus')
            ->with(Type::ACTE->value, Status::HANDLE)
            ->willReturn(0);

        $this->selfDBMock->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                [$this->callback(fn (MigrationItem $item) => $item->id === 1 && $item->oldkey === 'path1')],
                [$this->callback(fn (MigrationItem $item) => $item->id === 2 && $item->oldkey === 'path2')]
            );

        $importer = new TransactionImportFromS2low($source, $this->selfDBMock);
        $importer->run();
    }

    public function testImportRunWithMinDatePassesDateToSource(): void
    {
        $repoMock = $this->createMock(ActesRepository::class);
        $repoMock->expects($this->once())
            ->method('getBatch')
            ->with(0, TransactionImportFromS2low::LIMIT, '2024-01-01')
            ->willReturn([]);

        $source = new ActesSource($repoMock);

        $this->selfDBMock->expects($this->once())
            ->method('getLastIdAtTypeAndStatus')
            ->willReturn(0);

        $this->selfDBMock->expects($this->never())
            ->method('create');

        $importer = new TransactionImportFromS2low($source, $this->selfDBMock);
        $importer->run(null, '2024-01-01');
    }

    public function testImportRunResumesFromLastProcessedId(): void
    {
        $repoMock = $this->createMock(ActesRepository::class);
        $repoMock->expects($this->once())
            ->method('getBatch')
            ->with(500, $this->anything(), null) // Should start from 500
            ->willReturn([]);

        $source = new ActesSource($repoMock);

        $this->selfDBMock->expects($this->once())
            ->method('getLastIdAtTypeAndStatus')
            ->with(Type::ACTE->value, Status::HANDLE)
            ->willReturn(500);

        $importer = new TransactionImportFromS2low($source, $this->selfDBMock);
        $importer->run();
    }
}
