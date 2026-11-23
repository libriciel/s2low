<?php

declare(strict_types=1);

namespace S2low\Tests\DTO\TransactionList;

use PHPUnit\Framework\TestCase;
use S2low\DTO\TransactionList\StatusBadge;
use S2low\Enum\StatusTone;

class StatusBadgeTest extends TestCase
{
    public function testKnownActesStatusGetsItsToneAndBadgeLabel(): void
    {
        $badge = StatusBadge::fromActes(2, 'En attente de transmission');

        static::assertSame(StatusTone::InProgress, $badge->tone);
        static::assertSame('Attente transmission', $badge->text);
        static::assertSame('En attente de transmission', $badge->title);
        static::assertTrue($badge->isRecognized());
    }

    public function testUnknownActesStatusIsDisplayedAsRawText(): void
    {
        $badge = StatusBadge::fromActes(99, 'Nouvel état inconnu');

        static::assertNull($badge->tone);
        static::assertSame('Nouvel état inconnu', $badge->text);
        static::assertSame('Nouvel état inconnu', $badge->title);
        static::assertFalse($badge->isRecognized());
    }

    public function testKnownHeliosStatusShowsTheLastMessageInTheTitle(): void
    {
        $badge = StatusBadge::fromHelios(8, 'Information disponible', 'Transaction 32 : information disponible');

        static::assertSame(StatusTone::Done, $badge->tone);
        static::assertSame('Information disponible', $badge->text);
        static::assertSame('Information disponible — Transaction 32 : information disponible', $badge->title);
    }

    public function testKnownHeliosStatusWithoutMessageKeepsTheLabelAsTitle(): void
    {
        $badge = StatusBadge::fromHelios(2, 'En attente de transmission. Fichier valide.', '');

        static::assertSame('Attente transmission', $badge->text);
        static::assertSame('En attente de transmission. Fichier valide.', $badge->title);
    }

    public function testUnknownHeliosStatusIsDisplayedAsRawText(): void
    {
        $badge = StatusBadge::fromHelios(99, 'État ajouté en base', 'Un message');

        static::assertFalse($badge->isRecognized());
        static::assertSame('État ajouté en base', $badge->text);
        static::assertSame('État ajouté en base — Un message', $badge->title);
    }

    public function testUnknownHeliosStatusWithoutLabelFallsBackToTheMessage(): void
    {
        $badge = StatusBadge::fromHelios(99, '', 'Transaction 4 : information disponible');

        static::assertSame('Transaction 4 : information disponible', $badge->text);
    }

    public function testUnknownStatusWithoutAnyTextShowsItsIdentifier(): void
    {
        static::assertSame('État 99', StatusBadge::fromActes(99, '')->text);
        static::assertSame('État 99', StatusBadge::fromHelios(99, '', '')->text);
    }
}
