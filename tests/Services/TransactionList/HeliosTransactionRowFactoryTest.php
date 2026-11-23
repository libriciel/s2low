<?php

declare(strict_types=1);

namespace S2low\Tests\Services\TransactionList;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use S2low\Enum\StatusTone;
use S2low\Helpers\RequeteHelper;
use S2low\Services\TransactionList\HeliosTransactionRowFactory;
use UnexpectedValueException;

class HeliosTransactionRowFactoryTest extends TestCase
{
    private const array STATUS_LABELS = [
        4 => 'Acquittement reçu',
        8 => 'Information disponible',
        11 => 'Refuser par le SAE',
        3 => 'Transmis',
    ];

    private HeliosTransactionRowFactory $factory;

    protected function setUp(): void
    {
        $requeteHelper = $this->createMock(RequeteHelper::class);
        $requeteHelper->method('getLink')->willReturnCallback(static fn (string $path): string => 'https://s2low.test' . $path);
        $this->factory = new HeliosTransactionRowFactory($requeteHelper);
    }

    public function testMapsAListLineToARow(): void
    {
        $row = $this->factory->fromListLine($this->aListLine(), self::STATUS_LABELS);

        static::assertSame(32, $row->id);
        static::assertSame('https://s2low.test/modules/helios/helios_transac_show.php?id=32', $row->showUrl);
        static::assertSame('PESALR2_376882429_20261121_0028.xml', $row->filename);
        static::assertSame('Paie novembre CCAS 11/2026', $row->nomFic);
        static::assertSame('Commune de Test', $row->authorityName);
        static::assertEquals(new DateTimeImmutable('2026-11-21 14:33:00+01'), $row->submittedAt);
        static::assertSame('Utilisateur Test', $row->followerName);
    }

    public function testTheStatusComesFromTheStatusIdAndTheMessageGoesToTheTitle(): void
    {
        $row = $this->factory->fromListLine($this->aListLine(), self::STATUS_LABELS);

        static::assertSame(StatusTone::Done, $row->status->tone);
        static::assertSame('Information disponible', $row->status->text);
        static::assertSame('Information disponible — Transaction 32 : information disponible', $row->status->title);
    }

    public function testAnUnknownStatusShowsTheRawText(): void
    {
        $row = $this->factory->fromListLine($this->aListLine(['last_status_id' => '99']), self::STATUS_LABELS);

        static::assertFalse($row->status->isRecognized());
        static::assertSame('Transaction 32 : information disponible', $row->status->text);
    }

    /**
     * @dataProvider selectableProvider
     */
    public function testOnlyStatusesThatCanBeArchivedAreSelectable(string $status, bool $expected): void
    {
        $row = $this->factory->fromListLine($this->aListLine(['last_status_id' => $status]), self::STATUS_LABELS);

        static::assertSame($expected, $row->isSelectable);
    }

    public static function selectableProvider(): iterable
    {
        yield 'acquitté' => ['4', true];
        yield 'information disponible' => ['8', true];
        yield 'refusé par le SAE' => ['11', true];
        yield 'transmis' => ['3', false];
        yield 'inconnu' => ['99', false];
    }

    public function testAMissingNomFicIsEmpty(): void
    {
        $row = $this->factory->fromListLine($this->aListLine(['xml_nomfic' => null]), self::STATUS_LABELS);

        static::assertSame('', $row->nomFic);
    }

    public function testALineWithoutSubmissionDateHasNoDate(): void
    {
        $row = $this->factory->fromListLine($this->aListLine(['submission_date' => null]), self::STATUS_LABELS);

        static::assertNull($row->submittedAt);
    }

    public function testRefusesALineWithAnInvalidSubmissionDate(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Transaction 32');

        $this->factory->fromListLine($this->aListLine(['submission_date' => 'pas une date']), self::STATUS_LABELS);
    }

    private function aListLine(array $overrides = []): array
    {
        return array_merge([
            'id' => '32',
            'last_status_id' => '8',
            'user_id' => '2',
            'xml_nomfic' => 'Paie novembre CCAS 11/2026',
            'filename' => 'PESALR2_376882429_20261121_0028.xml',
            'submission_date' => '2026-11-21 14:33:00+01',
            'authority_name' => 'Commune de Test',
            'name' => 'Test',
            'givenname' => 'Utilisateur',
            'message' => 'Transaction 32 : information disponible',
        ], $overrides);
    }
}
