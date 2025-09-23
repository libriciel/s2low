<?php

namespace S2low\Tests\S2lowLegacy\Class\actes;

use S2lowLegacy\Class\actes\ActesCloudStorable;
use S2lowLegacy\Lib\SQLQuery;

class ActesCloudStorableTest extends \S2lowTestCase
{
    private ActesCloudStorable $actesCloudStorable;
    public function setup(): void
    {
        $this->actesCloudStorable = self::getContainer()->get(ActesCloudStorable::class);
    }

    public function testGetFilePathOnCloud()
    {
        $filePath = 'default_file_path';
        $enveloppeId = $this->createEnveloppeWithFilePath($filePath)[0];
        $res = $this->actesCloudStorable->getFilePathOnCloud($enveloppeId);

        self::assertSame($filePath, $res);
    }

    public function testGetFilePathOnCloudWhenEmpty()
    {
        $enveloppeId = $this->createEnveloppeWithoutFilePath()[0];
        $res = $this->actesCloudStorable->getFilePathOnCloud($enveloppeId);

        self::assertSame('', $res);
    }

    private function createEnveloppeWithFilePath($filePath)
    {
        return self::getContainer()->get(SQLQuery::class)->queryOneCol("INSERT INTO actes_envelopes(user_id,file_path) VALUES(1,'$filePath') returning ID");
    }

    private function createEnveloppeWithoutFilePath()
    {
        return self::getContainer()->get(SQLQuery::class)->queryOneCol("INSERT INTO actes_envelopes(user_id) VALUES(1) returning ID");
    }

}
