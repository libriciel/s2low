<?php

declare(strict_types=1);

namespace S2low\Tests\Enum;

use PHPUnit\Framework\TestCase;
use S2low\Enum\HeliosStatus;
use S2low\Enum\StatusTone;

class HeliosStatusTest extends TestCase
{
    private const int BADGE_MAX_LENGTH = 24;

    /**
     * @dataProvider toneProvider
     */
    public function testToneOfEachStatus(HeliosStatus $status, StatusTone $expectedTone): void
    {
        static::assertSame($expectedTone, $status->tone());
    }

    public static function toneProvider(): iterable
    {
        yield [HeliosStatus::ERREUR, StatusTone::Failed];
        yield [HeliosStatus::ANNULE, StatusTone::Inactive];
        yield [HeliosStatus::POSTE, StatusTone::InProgress];
        yield [HeliosStatus::EN_ATTENTE, StatusTone::InProgress];
        yield [HeliosStatus::TRANSMIS, StatusTone::InProgress];
        yield [HeliosStatus::ACQUITTE, StatusTone::Done];
        yield [HeliosStatus::VALIDE, StatusTone::Done];
        yield [HeliosStatus::REFUSE, StatusTone::Failed];
        yield [HeliosStatus::EN_TRAITEMENT, StatusTone::InProgress];
        yield [HeliosStatus::INFORMATION_DISPONIBLE, StatusTone::Done];
        yield [HeliosStatus::ENVOYE_AU_SAE, StatusTone::InProgress];
        yield [HeliosStatus::ACCEPTE_PAR_SAE, StatusTone::Done];
        yield [HeliosStatus::REFUSE_PAR_SAE, StatusTone::Failed];
        yield [HeliosStatus::ATTENTE_SIGNEE, StatusTone::Waiting];
        yield [HeliosStatus::ATTENTE_POSTEE, StatusTone::Waiting];
        yield [HeliosStatus::EN_ATTENTE_TRANSMISSION_SAE, StatusTone::InProgress];
        yield [HeliosStatus::ERREUR_LORS_DE_L_ENVOI_SAE, StatusTone::Failed];
        yield [HeliosStatus::A_DETRUIRE, StatusTone::Inactive];
        yield [HeliosStatus::DETRUITE, StatusTone::Inactive];
        yield [HeliosStatus::ERREUR_SAE_DOCUMENTS_INDISPONIBLES, StatusTone::Failed];
        yield [HeliosStatus::TRANSMIS_SANS_ACK, StatusTone::Done];
    }

    public function testEveryStatusHasATone(): void
    {
        static::assertCount(count(HeliosStatus::cases()), iterator_to_array(self::toneProvider(), false));
    }

    /**
     * @dataProvider shortenedBadgeLabelProvider
     */
    public function testLongLabelsAreShortenedOnTheBadge(HeliosStatus $status, string $expectedBadgeLabel): void
    {
        static::assertSame($expectedBadgeLabel, $status->badgeLabel());
    }

    public static function shortenedBadgeLabelProvider(): iterable
    {
        yield [HeliosStatus::EN_ATTENTE, 'Attente transmission'];
        yield [HeliosStatus::VALIDE, 'Validé'];
        yield [HeliosStatus::ACCEPTE_PAR_SAE, 'Archivé par le SAE'];
        yield [HeliosStatus::REFUSE_PAR_SAE, 'Refusé par le SAE'];
        yield [HeliosStatus::EN_ATTENTE_TRANSMISSION_SAE, 'Attente envoi SAE'];
        yield [HeliosStatus::ERREUR_LORS_DE_L_ENVOI_SAE, 'Erreur envoi SAE'];
        yield [HeliosStatus::ERREUR_SAE_DOCUMENTS_INDISPONIBLES, 'Envoi SAE impossible'];
        yield [HeliosStatus::TRANSMIS_SANS_ACK, 'Transmis sans acquit'];
    }

    public function testBadgeLabelsFitOnOneLine(): void
    {
        foreach (HeliosStatus::cases() as $status) {
            static::assertLessThanOrEqual(self::BADGE_MAX_LENGTH, mb_strlen($status->badgeLabel()), $status->name);
        }
    }

    /**
     * @dataProvider saeArchivingProvider
     */
    public function testOnlySomeStatusesAllowSaeArchiving(HeliosStatus $status, bool $expected): void
    {
        static::assertSame($expected, $status->allowsSaeArchiving());
    }

    public static function saeArchivingProvider(): iterable
    {
        foreach (HeliosStatus::cases() as $status) {
            yield $status->name => [$status, in_array($status->value, [4, 8, 11, 20], true)];
        }
    }
}
