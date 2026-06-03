<?php

declare(strict_types=1);

namespace S2low\Tests\Twig\Extensions;

use PHPUnit\Framework\TestCase;
use S2low\Twig\Extensions\LegacyErrorExtension;
use Twig\TwigFunction;

class LegacyErrorExtensionTest extends TestCase
{
    private LegacyErrorExtension $extension;
    private ?array $backupSession = null;

    protected function setUp(): void
    {
        $this->extension = new LegacyErrorExtension();
        // Backup $_SESSION if it is set
        if (isset($_SESSION)) {
            $this->backupSession = $_SESSION;
        } else {
            $_SESSION = [];
        }
    }

    protected function tearDown(): void
    {
        // Restore $_SESSION
        if ($this->backupSession !== null) {
            $_SESSION = $this->backupSession;
        } else {
            unset($_SESSION);
        }
    }

    public function testGetFunctions(): void
    {
        $functions = $this->extension->getFunctions();
        $this->assertCount(1, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertSame('get_legacy_error', $functions[0]->getName());
    }

    public function testGetLegacyErrorReturnsNullWhenNotSet(): void
    {
        unset($_SESSION['error']);
        $this->assertNull($this->extension->getLegacyError());
    }

    public function testGetLegacyErrorRetrievesAndClearsError(): void
    {
        $_SESSION['error'] = 'Test Error Message';

        $error = $this->extension->getLegacyError();

        $this->assertSame('Test Error Message', $error);
        $this->assertArrayNotHasKey('error', $_SESSION);
    }
}
