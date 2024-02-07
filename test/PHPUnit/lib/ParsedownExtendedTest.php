<?php

declare(strict_types=1);

namespace PHPUnit\lib;

use PHPUnit\Framework\TestCase;
use S2lowLegacy\Lib\ParsedownExtended;

class ParsedownExtendedTest extends TestCase
{
    public function testParsedown()
    {
        $parsedown = new ParsedownExtended(2);
        $html = $parsedown->text(file_get_contents(__DIR__ . "/fixtures/test.md"));
        $this->assertEquals(
            "<h2>h2</h2>
<h3>h3</h3>
<h4>h4</h4>
<h5>h5</h5>
<h6>h6</h6>",
            $html
        );
    }
}
