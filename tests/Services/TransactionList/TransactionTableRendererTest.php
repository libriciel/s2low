<?php

declare(strict_types=1);

namespace S2low\Tests\Services\TransactionList;

use DateTimeImmutable;
use DOMDocument;
use DOMXPath;
use Masterminds\HTML5;
use S2low\DTO\TransactionList\ActesFollowUp;
use S2low\DTO\TransactionList\ActesTransactionRow;
use S2low\DTO\TransactionList\ActesTransactionTable;
use S2low\DTO\TransactionList\HeliosTransactionRow;
use S2low\DTO\TransactionList\HeliosTransactionTable;
use S2low\DTO\TransactionList\StatusBadge;
use S2low\DTO\TransactionList\TransactionTableSorting;
use S2low\Services\TransactionList\TransactionTableRenderer;
use S2lowLegacy\Class\actes\TypeTransaction;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TransactionTableRendererTest extends KernelTestCase
{
    private const string LIST_URL = 'https://s2low.test/modules/actes/index.php';

    public function testTheWholeActesRowLinksToTheTransaction(): void
    {
        $page = $this->renderActes([$this->anActesRow()]);

        $rowLink = $this->single($page, '//tbody/tr/td//a[contains(@class, "transaction-table__row-link")]');
        static::assertSame('https://s2low.test/modules/actes/actes_transac_show.php?id=37', $rowLink->getAttribute('href'));
        static::assertSame('Rapport annuel', trim($rowLink->textContent));
    }

    public function testTheDetailIconIsKept(): void
    {
        $page = $this->renderActes([$this->anActesRow()]);

        $detailIcon = $this->single($page, '//td[contains(@class, "transaction-table__actions")]/a[img[@title="Afficher le détail"]]');
        static::assertSame('https://s2low.test/modules/actes/actes_transac_show.php?id=37', $detailIcon->getAttribute('href'));
    }

    public function testOnlySelectableRowsHaveACheckbox(): void
    {
        $page = $this->renderActes([
            $this->anActesRow(id: 1, isSelectable: true),
            $this->anActesRow(id: 2, isSelectable: false),
        ]);

        $checkboxes = $page->query('//input[@type="checkbox"]');
        static::assertSame(1, $checkboxes->length);
        static::assertSame('liste_id[]', $checkboxes->item(0)->getAttribute('name'));
        static::assertSame('1', $checkboxes->item(0)->getAttribute('value'));
    }

    public function testARecognizedStatusIsABadgeWithTheFullLabelAsTitle(): void
    {
        $page = $this->renderActes([$this->anActesRow(status: StatusBadge::fromActes(2, 'En attente de transmission'))]);

        $badge = $this->single($page, '//span[contains(@class, "transaction-status ")]');
        static::assertStringContainsString('transaction-status--in-progress', $badge->getAttribute('class'));
        static::assertSame('En attente de transmission', $badge->getAttribute('title'));
        static::assertSame('Attente transmission', trim($this->single($page, '//span[@class="transaction-status__text"]')->textContent));
    }

    public function testAnUnrecognizedStatusIsOnlyText(): void
    {
        $page = $this->renderActes([$this->anActesRow(status: StatusBadge::fromActes(99, 'État inconnu'))]);

        static::assertSame(0, $page->query('//span[contains(@class, "transaction-status ")]')->length);
        static::assertSame('État inconnu', trim($this->single($page, '//span[@class="transaction-status-raw"]')->textContent));
    }

    public function testUserContentIsEscaped(): void
    {
        $page = $this->renderActes([$this->anActesRow(subject: '<script>alert(1)</script>')]);

        static::assertSame(0, $page->query('//tbody//script')->length);
        static::assertSame('<script>alert(1)</script>', trim($this->single($page, '//a[contains(@class, "transaction-table__row-link")]')->textContent));
    }

    public function testFollowUpsAreLinksToTheirTransaction(): void
    {
        $page = $this->renderActes([$this->anActesRow(followUps: [
            new ActesFollowUp('Annulation (reçu)', 'https://s2low.test/modules/actes/actes_transac_show.php?id=32'),
        ])]);

        $followUp = $this->single($page, '//a[contains(@class, "transaction-table__follow-up")]');
        static::assertSame('https://s2low.test/modules/actes/actes_transac_show.php?id=32', $followUp->getAttribute('href'));
        static::assertSame('Annulation (reçu)', trim($followUp->textContent));
    }

    public function testTheAuthorityIsOnlyShownWhenTheListCoversSeveralAuthorities(): void
    {
        $oneAuthority = $this->renderActes([$this->anActesRow()]);
        $severalAuthorities = $this->renderActes([$this->anActesRow()], showsAuthority: true);

        static::assertStringNotContainsString('Commune de Test', $oneAuthority->document->textContent);
        static::assertStringContainsString('Délibérations · Commune de Test', $severalAuthorities->document->textContent);
    }

    public function testTheTypeIsOnlyMentionedForOtherTransactionsThanActTransmissions(): void
    {
        $transmission = $this->renderActes([$this->anActesRow()]);
        $annulation = $this->renderActes([$this->anActesRow(type: TypeTransaction::Annulation)]);

        static::assertStringNotContainsString('Annulation', $transmission->document->textContent);
        static::assertStringContainsString('Annulation', $annulation->document->textContent);
    }

    public function testColumnHeadersLinkToTheSorting(): void
    {
        $page = $this->renderActes([$this->anActesRow()]);

        $sortLinks = $page->query('//thead//a[contains(@class, "transaction-table__sort")]');
        static::assertSame(2, $sortLinks->length);
        static::assertSame(self::LIST_URL . '?order=id&sortway=asc', $sortLinks->item(0)->getAttribute('href'));
        static::assertSame(self::LIST_URL . '?order=submission_date&sortway=desc', $sortLinks->item(1)->getAttribute('href'));
    }

    public function testTheSubmissionDateIsShownWithDayAndTime(): void
    {
        $page = $this->renderActes([$this->anActesRow()]);

        static::assertStringContainsString('21/11/2026', $page->document->textContent);
        static::assertStringContainsString('17:37', $page->document->textContent);
    }

    public function testTheHeliosFilenameLinksToTheTransactionAndCanWrap(): void
    {
        $page = $this->renderHelios([$this->aHeliosRow()]);

        $rowLink = $this->single($page, '//a[contains(@class, "transaction-table__row-link")]');
        static::assertSame('https://s2low.test/modules/helios/helios_transac_show.php?id=32', $rowLink->getAttribute('href'));
        static::assertSame('PES_<b>_x.xml', $rowLink->textContent);
        static::assertSame(3, $rowLink->getElementsByTagName('wbr')->length);
        static::assertSame(0, $rowLink->getElementsByTagName('b')->length);
    }

    public function testTheHeliosRowShowsNomFicAndIdentifier(): void
    {
        $html = $this->renderHelios([$this->aHeliosRow()], showsAuthority: true)->document->textContent;

        static::assertStringContainsString('NomFic : Paie novembre', $html);
        static::assertStringContainsString('n° 32 · Commune de Test', $html);
    }

    public function testATransactionWithoutSubmissionDateShowsADash(): void
    {
        $page = $this->renderHelios([$this->aHeliosRow(submittedAt: null)]);

        static::assertSame('—', trim($page->query('//tbody/tr/td')->item(2)->textContent));
    }

    private function renderActes(array $rows, bool $showsAuthority = false): DOMXPath
    {
        $table = new ActesTransactionTable($rows, TransactionTableSorting::fromQuery(self::LIST_URL, []), $showsAuthority);

        return $this->parse($this->renderer()->renderActes($table));
    }

    private function renderHelios(array $rows, bool $showsAuthority = false): DOMXPath
    {
        $table = new HeliosTransactionTable($rows, TransactionTableSorting::fromQuery(self::LIST_URL, []), $showsAuthority);

        return $this->parse($this->renderer()->renderHelios($table));
    }

    private function renderer(): TransactionTableRenderer
    {
        return self::getContainer()->get(TransactionTableRenderer::class);
    }

    private function parse(string $html): DOMXPath
    {
        $document = (new HTML5(['disable_html_ns' => true]))->loadHTML('<!DOCTYPE html><html><body>' . $html . '</body></html>');

        return new DOMXPath($document);
    }

    private function single(DOMXPath $page, string $xpath): \DOMElement
    {
        $nodes = $page->query($xpath);
        static::assertSame(1, $nodes->length, $xpath);

        return $nodes->item(0);
    }

    private function anActesRow(
        int $id = 37,
        bool $isSelectable = false,
        ?StatusBadge $status = null,
        string $subject = 'Rapport annuel',
        array $followUps = [],
        TypeTransaction $type = TypeTransaction::TransmissionActe,
    ): ActesTransactionRow {
        return new ActesTransactionRow(
            id: $id,
            showUrl: 'https://s2low.test/modules/actes/actes_transac_show.php?id=' . $id,
            internalNumber: '2026-DEL-030',
            type: $type,
            typeLabel: $type === TypeTransaction::Annulation ? 'Annulation' : "Transmission d'actes",
            subject: $subject,
            nature: 'Délibérations',
            authorityName: 'Commune de Test',
            submittedAt: new DateTimeImmutable('2026-11-21 17:37:00', new \DateTimeZone(date_default_timezone_get())),
            status: $status ?? StatusBadge::fromActes(4, 'Acquittement reçu'),
            followerName: 'Marie Dupont',
            isSelectable: $isSelectable,
            archiveUrl: null,
            followUps: $followUps,
        );
    }

    private function aHeliosRow(?DateTimeImmutable $submittedAt = new DateTimeImmutable('2026-11-21 14:33:00')): HeliosTransactionRow
    {
        return new HeliosTransactionRow(
            id: 32,
            showUrl: 'https://s2low.test/modules/helios/helios_transac_show.php?id=32',
            filename: 'PES_<b>_x.xml',
            nomFic: 'Paie novembre',
            authorityName: 'Commune de Test',
            submittedAt: $submittedAt,
            status: StatusBadge::fromHelios(8, 'Information disponible', ''),
            followerName: 'Utilisateur Test',
            isSelectable: true,
        );
    }
}
