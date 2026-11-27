<?php

declare(strict_types=1);

namespace S2low\Tests\S2lowLegacy\Class\helios;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use S2lowLegacy\Class\helios\HeliosMenagePesRetourWorker;
use S2lowLegacy\Class\helios\PESRetourCloudStorable;
use S2lowLegacy\Class\helios\PESRetourCloudStorage;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Model\HeliosRetourSQL;

final class HeliosMenagePesRetourWorkerTest extends TestCase
{
    private const THIRTY_DAYS_IN_SECONDS = 30 * 86400;

    private TmpFolder $tmpFolder;
    private string $heliosResponsesRoot;

    protected function setUp(): void
    {
        $this->tmpFolder = new TmpFolder();
        $this->heliosResponsesRoot = $this->tmpFolder->create();
    }

    protected function tearDown(): void
    {
        $this->tmpFolder->delete($this->heliosResponsesRoot);
    }

    public static function pesRetourFilenames(): iterable
    {
        yield 'nom commençant par PES2R' => [
            'PES2R_RCE_P_250_00_078026_20261125_20261125_20261126035554.xml',
        ];
        yield 'nom préfixé par Passtrans' => [
            '7318043_PES2R_RCE_P_250_00_078026_20261125_20261125_20261126035554.xml',
        ];
        yield 'nom préfixé par Passtrans, PES Retour ASAP' => [
            '1103001_PES2R_FCT_ASAPTitresPostalisesNotifies_047_00_4300696_104001_20261105_20261105_20261105233232.xml',
        ];
    }

    /**
     * @dataProvider pesRetourFilenames
     */
    public function testDeletesOldPesRetourAlreadyInCloud(string $filename): void
    {
        $pesRetourPath = $this->createOldResponseFile($filename);

        $this->createWorkerWithEveryFileInCloud()->work(1);

        self::assertFileDoesNotExist($pesRetourPath);
    }

    public function testKeepsOldAcquitAlreadyInCloud(): void
    {
        $acquitPath = $this->createOldResponseFile('6510480_074004_261125092414-ACK-J1702013_A02818QT_OK.xml');

        $this->createWorkerWithEveryFileInCloud()->work(1);

        self::assertFileExists($acquitPath);
    }

    private function createOldResponseFile(string $filename): string
    {
        $path = $this->heliosResponsesRoot . '/' . $filename;
        self::assertNotFalse(file_put_contents($path, '<PES_Retour/>'));
        self::assertTrue(touch($path, time() - self::THIRTY_DAYS_IN_SECONDS));

        return $path;
    }

    private function createWorkerWithEveryFileInCloud(): HeliosMenagePesRetourWorker
    {
        $openStackSwiftWrapper = $this->createStub(OpenStackSwiftWrapper::class);
        $openStackSwiftWrapper->method('fileExistsOnCloud')->willReturn(true);

        return new HeliosMenagePesRetourWorker(
            new PESRetourCloudStorage(
                new PESRetourCloudStorable($this->createStub(HeliosRetourSQL::class), $this->heliosResponsesRoot),
                $openStackSwiftWrapper,
                new NullLogger(),
                true
            )
        );
    }
}
