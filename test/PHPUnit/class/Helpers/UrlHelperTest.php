<?php

namespace S2lowLegacy\Test\Class\Helpers;

use PHPUnit\class\Helpers\LegacyHelpers;
use PHPUnit\Framework\TestCase;
use S2lowLegacy\Class\Helpers\UrlHelper;

class UrlHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!defined('WEBSITE_SSL')) {
            define('WEBSITE_SSL', 'https://example.com');
        }
        $_SERVER['PHP_SELF'] = '/script.php';
        $_SERVER['QUERY_STRING'] = 'a=b&c=d';
    }

    public function testGetURLWithParam()
    {
        $params = ['c' => 'e', 'new' => 'val'];
        $this->assertEquals(LegacyHelpers::getURLWithParam($params), UrlHelper::getURLWithParam($params));
    }

    public function testGetLink()
    {
        $path = "/some/path.php";
        $this->assertEquals(LegacyHelpers::getLink($path), UrlHelper::getLink($path));
    }
}
