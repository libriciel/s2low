<?php

declare(strict_types=1);

namespace PHPUnit\lib;

use PHPUnit\Framework\TestCase;
use S2lowLegacy\Class\ExtendPdf;

class ExtendPDFTest extends TestCase
{
    /**
     * @var ExtendPDF
     */
    private $extendPDF;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extendPDF = new ExtendPdf();
    }

    public function testAll()
    {
        $this->extendPDF->addPage();
        $this->extendPDF->Header();

        $this->extendPDF->RoundedRect(10, 10, 100, 100, 3, 'DF', '13');
        $this->extendPDF->SetMyWidths([100,100,100,100]);   // Quickfix php8
        $this->extendPDF->myRow(array("","Etat","Date", "Message"));

        $this->extendPDF->SetMyWidths([100,100,100]);
        $this->extendPDF->SetMyAligns(array(1,2,3));
        $this->extendPDF->setMyBorder("TBRL");
        $this->extendPDF->Footer();
        $this->assertTrue(true);
    }
}
