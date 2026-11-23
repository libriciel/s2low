<?php

declare(strict_types=1);

namespace S2low\Tests\Enum;

use PHPUnit\Framework\TestCase;
use S2low\Enum\ActesStatus;
use S2low\Enum\StatusTone;

class ActesStatusTest extends TestCase
{
    private const int BADGE_MAX_LENGTH = 24;

    /**
     * @dataProvider toneProvider
     */
    public function testToneOfEachStatus(ActesStatus $status, StatusTone $expectedTone): void
    {
        static::assertSame($expectedTone, $status->tone());
    }

    public static function toneProvider(): iterable
    {
        yield [ActesStatus::ERREUR, StatusTone::Failed];
        yield [ActesStatus::ANNULE, StatusTone::Inactive];
        yield [ActesStatus::POSTE, StatusTone::InProgress];
        yield [ActesStatus::EN_ATTENTE_DE_TRANSMISSION, StatusTone::InProgress];
        yield [ActesStatus::TRANSMIS, StatusTone::InProgress];
        yield [ActesStatus::ACQUITTEMENT_RECU, StatusTone::Done];
        yield [ActesStatus::VALIDE, StatusTone::Done];
        yield [ActesStatus::REFUSE, StatusTone::Failed];
        yield [ActesStatus::DOCUMENT_RECU, StatusTone::Waiting];
        yield [ActesStatus::ACQUITTEMENT_ENVOYE, StatusTone::Done];
        yield [ActesStatus::DOCUMENT_ENVOYE, StatusTone::Done];
        yield [ActesStatus::REFUS_D_ENVOI, StatusTone::Failed];
        yield [ActesStatus::ACQUITTEMENT_DE_DOCUMENT_RECU, StatusTone::Done];
        yield [ActesStatus::ENVOYE_AU_SAE, StatusTone::InProgress];
        yield [ActesStatus::ARCHIVE_PAR_LE_SAE, StatusTone::Done];
        yield [ActesStatus::ERREUR_LORS_DE_L_ARCHIVAGE, StatusTone::Failed];
        yield [ActesStatus::RECU_PAR_LE_SAE, StatusTone::Done];
        yield [ActesStatus::DETRUITE, StatusTone::Inactive];
        yield [ActesStatus::EN_ATTENTE_D_ETRE_POSTEE, StatusTone::Waiting];
        yield [ActesStatus::EN_ATTENTE_D_ETRE_SIGNEE, StatusTone::Waiting];
        yield [ActesStatus::EN_ATTENTE_DE_TRANSMISSION_AU_SAE, StatusTone::InProgress];
        yield [ActesStatus::ERREUR_LORS_DE_L_ENVOI_AU_SAE, StatusTone::Failed];
        yield [ActesStatus::DOCUMENT_RECU_PAS_D_AR, StatusTone::Waiting];
        yield [ActesStatus::ERREUR_SAE_DOCUMENTS_INDISPONIBLES, StatusTone::Failed];
    }

    public function testEveryStatusHasATone(): void
    {
        static::assertCount(count(ActesStatus::cases()), iterator_to_array(self::toneProvider(), false));
    }

    /**
     * @dataProvider shortenedBadgeLabelProvider
     */
    public function testLongLabelsAreShortenedOnTheBadge(ActesStatus $status, string $expectedBadgeLabel): void
    {
        static::assertSame($expectedBadgeLabel, $status->badgeLabel());
    }

    public static function shortenedBadgeLabelProvider(): iterable
    {
        yield [ActesStatus::EN_ATTENTE_DE_TRANSMISSION, 'Attente transmission'];
        yield [ActesStatus::ACQUITTEMENT_DE_DOCUMENT_RECU, 'Acq. document reçu'];
        yield [ActesStatus::EN_ATTENTE_DE_TRANSMISSION_AU_SAE, 'Attente envoi SAE'];
        yield [ActesStatus::ERREUR_LORS_DE_L_ENVOI_AU_SAE, 'Erreur envoi SAE'];
        yield [ActesStatus::ERREUR_LORS_DE_L_ARCHIVAGE, 'Erreur archivage'];
        yield [ActesStatus::ERREUR_SAE_DOCUMENTS_INDISPONIBLES, 'Envoi SAE impossible'];
        yield [ActesStatus::DOCUMENT_RECU_PAS_D_AR, 'Document reçu sans AR'];
    }

    public function testBadgeLabelsFitOnOneLine(): void
    {
        foreach (ActesStatus::cases() as $status) {
            static::assertLessThanOrEqual(self::BADGE_MAX_LENGTH, mb_strlen($status->badgeLabel()), $status->name);
        }
    }

    /**
     * @dataProvider bulkClosingProvider
     */
    public function testOnlySomeStatusesAllowBulkClosing(ActesStatus $status, bool $expected): void
    {
        static::assertSame($expected, $status->allowsBulkClosing());
    }

    public static function bulkClosingProvider(): iterable
    {
        foreach (ActesStatus::cases() as $status) {
            yield $status->name => [$status, in_array($status->value, [4, 5, 14, 18, 20], true)];
        }
    }
}
