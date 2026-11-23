<?php

declare(strict_types=1);

use S2lowLegacy\Class\actes\ListeActesHTML;

class ListeActesHTMLTest extends S2lowTestCase
{
    private const string TABLE = '<table id="transactions-list"></table>';

    public function testAnEmptyListShowsAMessageInsteadOfTheForm(): void
    {
        $html = $this->display('');

        static::assertStringContainsString('Pas de transaction trouvée correspondant aux critères de filtrage', $html);
        static::assertStringNotContainsString('id="div_chck"', $html);
    }

    public function testTheTableIsShownInsideTheBulkActionForm(): void
    {
        $html = $this->display(self::TABLE);

        $formStart = strpos($html, '<form id="div_chck"');
        static::assertNotFalse($formStart);
        static::assertGreaterThan($formStart, strpos($html, self::TABLE));
        static::assertLessThan(strpos($html, '</form>', $formStart), strpos($html, self::TABLE));
    }

    public function testTheEnvelopeToggleButtonsAreGone(): void
    {
        $html = $this->display(self::TABLE);

        static::assertStringContainsString('<h2>Liste des transactions</h2>', $html);
        static::assertStringNotContainsString('show_all()', $html);
    }

    private function display(string $transactionTable): string
    {
        ob_start();
        try {
            (new ListeActesHTML())->display($transactionTable);
        } finally {
            $html = ob_get_clean();
        }

        return $html;
    }
}
