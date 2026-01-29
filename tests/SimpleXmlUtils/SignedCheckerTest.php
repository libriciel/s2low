<?php

namespace S2low\Tests\SimpleXmlUtils;

use PHPUnit\Framework\TestCase;
use S2low\Services\SimpleXmlUtils\SignatureDeleter;
use S2low\Services\SimpleXmlUtils\SignedChecker;

class SignedCheckerTest extends TestCase
{
    public function testDeleteSignature()
    {
        $file = \XadesSignatureTest::TEST_FILE;

        $result = "/tmp/result.xml";

        $signedChecker = new SignedChecker();
        $signatureDeleter = new SignatureDeleter();

        $this->assertTrue($signedChecker->isSigned($file));
        $signatureDeleter->deleteSignature($file, $result);
        $this->assertFalse($signedChecker->isSigned($result));
    }
}
