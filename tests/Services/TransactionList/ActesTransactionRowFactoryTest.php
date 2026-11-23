<?php

declare(strict_types=1);

namespace S2low\Tests\Services\TransactionList;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use S2low\Enum\StatusTone;
use S2low\Helpers\RequeteHelper;
use S2low\Services\TransactionList\ActesTransactionRowFactory;
use S2lowLegacy\Class\actes\TypeTransaction;
use UnexpectedValueException;

class ActesTransactionRowFactoryTest extends TestCase
{
    private ActesTransactionRowFactory $factory;

    protected function setUp(): void
    {
        $requeteHelper = $this->createMock(RequeteHelper::class);
        $requeteHelper->method('getLink')->willReturnCallback(static fn (string $path): string => 'https://s2low.test' . $path);
        $this->factory = new ActesTransactionRowFactory($requeteHelper);
    }

    public function testMapsAListLineToARow(): void
    {
        $row = $this->factory->fromListLine($this->aListLine());

        static::assertSame(37, $row->id);
        static::assertSame('https://s2low.test/modules/actes/actes_transac_show.php?id=37', $row->showUrl);
        static::assertSame('2026-DEL-030', $row->internalNumber);
        static::assertSame(TypeTransaction::TransmissionActe, $row->type);
        static::assertSame("Rapport sur le prix et la qualité du service public de l'eau", $row->subject);
        static::assertSame('Délibérations', $row->nature);
        static::assertSame('Commune de Test', $row->authorityName);
        static::assertEquals(new DateTimeImmutable('2026-11-21 17:37:00+01'), $row->submittedAt);
        static::assertSame(StatusTone::Done, $row->status->tone);
        static::assertSame('Acquittement reçu', $row->status->text);
        static::assertSame('Marie Dupont', $row->followerName);
    }

    public function testTheSubjectIsNoLongerTruncated(): void
    {
        $longSubject = str_repeat('Approbation de la convention pluriannuelle ', 10);

        $row = $this->factory->fromListLine($this->aListLine(['subject' => $longSubject]));

        static::assertSame($longSubject, $row->subject);
    }

    /**
     * @dataProvider selectableProvider
     */
    public function testOnlyActTransmissionsInAClosableStatusAreSelectable(string $type, int $status, bool $expected): void
    {
        $row = $this->factory->fromListLine($this->aListLine(['type' => $type, 'last_status_id' => $status]));

        static::assertSame($expected, $row->isSelectable);
    }

    public static function selectableProvider(): iterable
    {
        yield 'transmission acquittée' => ['1', 4, true];
        yield 'transmission en attente de signature' => ['1', 18, true];
        yield 'transmission postée' => ['1', 1, false];
        yield 'courrier acquitté' => ['2', 4, false];
        yield 'transmission dans un état inconnu' => ['1', 99, false];
    }

    public function testTheTypeIsOnlyMentionedForOtherTransactionsThanActTransmissions(): void
    {
        static::assertTrue($this->factory->fromListLine($this->aListLine())->isActTransmission());

        $annulation = $this->factory->fromListLine($this->aListLine(['type' => '6', 'type_str' => 'Annulation']));
        static::assertFalse($annulation->isActTransmission());
        static::assertSame('Annulation', $annulation->typeLabel);
    }

    public function testFollowUpsLinkToTheirOwnTransaction(): void
    {
        $row = $this->factory->fromListLine($this->aListLine(['courrier_info' => [
            32 => ['type' => '6', 'type_str' => 'Annulation', 'sens' => 'reçu'],
            40 => ['type' => '3', 'type_str' => 'Demande de pièces complémentaires'],
        ]]));

        static::assertCount(2, $row->followUps);
        static::assertSame('Annulation (reçu)', $row->followUps[0]->label);
        static::assertSame('https://s2low.test/modules/actes/actes_transac_show.php?id=32', $row->followUps[0]->url);
        static::assertSame('Demande de pièces complémentaires (envoyé)', $row->followUps[1]->label);
    }

    /**
     * @dataProvider archiveUrlProvider
     */
    public function testOnlyHttpArchiveUrlsAreKept(?string $archiveUrl, ?string $expected): void
    {
        $row = $this->factory->fromListLine($this->aListLine(['archive_url' => $archiveUrl]));

        static::assertSame($expected, $row->archiveUrl);
    }

    public static function archiveUrlProvider(): iterable
    {
        yield 'https' => ['https://sae.test/archive/1', 'https://sae.test/archive/1'];
        yield 'http en majuscules' => ['HTTP://sae.test/archive/1', 'HTTP://sae.test/archive/1'];
        yield 'javascript' => ['javascript:alert(1)', null];
        yield 'absente' => [null, null];
        yield 'vide' => ['', null];
    }

    public function testAStatusMissingFromTheLabelsIsShownWithItsBadge(): void
    {
        $row = $this->factory->fromListLine($this->aListLine(['last_status_id' => 22, 'current_status_name' => null]));

        static::assertSame('Envoi SAE impossible', $row->status->text);
        static::assertSame('État 22', $row->status->title);
    }

    public function testALineWithoutSubmissionDateHasNoDate(): void
    {
        $row = $this->factory->fromListLine($this->aListLine(['submission_date' => null]));

        static::assertNull($row->submittedAt);
    }

    public function testRefusesALineWithAnInvalidSubmissionDate(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Transaction 37');

        $this->factory->fromListLine($this->aListLine(['submission_date' => 'pas une date']));
    }

    private function aListLine(array $overrides = []): array
    {
        return array_merge([
            'envelope_id' => '32',
            'submission_date' => '2026-11-21 17:37:00+01',
            'name' => 'Dupont',
            'givenname' => 'Marie',
            'authority_name' => 'Commune de Test',
            'transaction_id' => '37',
            'type' => '1',
            'number' => '2026-DEL-030',
            'subject' => "Rapport sur le prix et la qualité du service public de l'eau",
            'archive_url' => null,
            'nature_descr' => 'Délibérations',
            'last_status_id' => '4',
            'type_str' => "Transmission d'actes",
            'current_status' => '4',
            'current_status_name' => 'Acquittement reçu',
            'courrier_info' => [],
        ], $overrides);
    }
}
