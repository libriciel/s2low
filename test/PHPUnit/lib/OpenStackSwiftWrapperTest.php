<?php

declare(strict_types=1);

namespace PHPUnit\lib;

use Exception;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\CloudStorageException;
use S2lowLegacy\Lib\OpenStackContainerStore;
use S2lowLegacy\Lib\OpenStackContainerWrapper;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowLegacy\Lib\UnrecoverableException;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class OpenStackSwiftWrapperTest extends TestCase
{
    private const PATH = '/fixtures/';
    private const EXISTING_FILE_NAME = 'test.xml';
    private const ABSENT_FILE_NAME = 'testQuiNexistePas.xml';
    private const EXISTING_FILE_PATH = __DIR__ . self::PATH . self::EXISTING_FILE_NAME;
    private const ABSENT_FILE_PATH = __DIR__ . self::PATH . self::ABSENT_FILE_NAME;
    private const CONTAINER_TEST = 'container_test';

    private LoggerInterface $logger;

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {

        if (file_exists(self::ABSENT_FILE_PATH)) {
            unlink(self::ABSENT_FILE_PATH);
        }

        $this->logger = new Logger('PHPUNIT');
        $this->logger->pushHandler(new NullHandler());
        parent::setUp();
    }

    /**
     * This method is called after each test.
     */
    protected function tearDown(): void
    {
        if (file_exists(self::ABSENT_FILE_PATH)) {
            unlink(self::ABSENT_FILE_PATH);
        }
        parent::tearDown();
    }

    /**
     * @throws Exception
     */
    public function testSendFile()
    {

        /** @var  OpenStackSwiftWrapper | MockObject $openStackSwiftWrapper */
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->expects(static::once())
            ->method('createObject')
            ->with(static::callback(
                function ($args) {
                    return $args['stream']->getMetadata('uri') ==  OpenStackSwiftWrapperTest::EXISTING_FILE_PATH
                        && $args['name'] = OpenStackSwiftWrapperTest::EXISTING_FILE_NAME;
                }
            ))
            ->willReturn(true);

        /** @var  OpenStackContainerStore | MockObject $openStackContainersStore */
        $openStackContainersStore = $this->getMockBuilder(OpenStackContainerStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects(static::once())
            ->method('getContainerWrapper')
            ->with(static::equalTo(self::CONTAINER_TEST))
        ->willReturn($openStackSwiftWrapper);

        /** @var OpenStackContainerStore $openStackFactory */
        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $openStackContainersStore,
            $this->logger,
            true
        );

        static::assertTrue($openStackSwiftWrapper->sendFile(
            self::CONTAINER_TEST,
            self::EXISTING_FILE_PATH
        ));
    }

    /**
     * @throws Exception
     */
    public function testSendFileWithDifferentName()
    {
        /** @var  OpenStackSwiftWrapper | MockObject $openStackSwiftWrapper */
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->expects(static::once())
            ->method('createObject')
            ->with(static::callback(
                function ($args) {
                    return $args['stream']->getMetadata('uri') ==  OpenStackSwiftWrapperTest::EXISTING_FILE_PATH
                        && $args['name'] = OpenStackSwiftWrapperTest::ABSENT_FILE_NAME;
                }
            ));

        /** @var  OpenStackContainerStore | MockObject $openStackContainersStore */
        $openStackContainersStore = $this->getMockBuilder(OpenStackContainerStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects(static::once())
            ->method('getContainerWrapper')
            ->with(static::equalTo(self::CONTAINER_TEST))
            ->willReturn($openStackSwiftWrapper);

        /** @var OpenStackContainerStore $openStackFactory */
        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $openStackContainersStore,
            $this->logger,
            true
        );

        $openStackSwiftWrapper->sendFile(
            self::CONTAINER_TEST,
            self::EXISTING_FILE_PATH,
            self::ABSENT_FILE_NAME
        );
    }

    /**
     * @throws Exception
     */
    public function testSendNonexistentFile()
    {
        /** @var  OpenStackContainerStore | MockObject $openStackContainersStore */
        $openStackContainersStore = $this->getMockBuilder(OpenStackContainerStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects(static::never())
            ->method('getContainerWrapper');

        $this->expectException(CloudStorageException::class);

        /** @var OpenStackContainerStore $openStackFactory */
        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $openStackContainersStore,
            $this->logger,
            true
        );

        $openStackSwiftWrapper->sendFile(
            self::CONTAINER_TEST,
            self::ABSENT_FILE_PATH
        );
    }

    /**
     * @throws UnrecoverableException|\S2lowLegacy\Lib\PausingQueueException
     */

    public function testDeleteFile()
    {
        /** @var  OpenStackSwiftWrapper | MockObject $openStackSwiftWrapper */
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->expects(static::once())
            ->method('delete')
            ->with(self::ABSENT_FILE_NAME);

        /** @var  OpenStackContainerStore | MockObject $openStackContainersStore */
        $openStackContainersStore = $this->getMockBuilder(OpenStackContainerStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects(static::once())
            ->method('getContainerWrapper')
            ->with(static::equalTo(self::CONTAINER_TEST))
            ->willReturn($openStackSwiftWrapper);


        /** @var OpenStackContainerStore $openStackFactory */
        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $openStackContainersStore,
            $this->logger,
            true
        );

        $openStackSwiftWrapper->deleteFile(
            self::CONTAINER_TEST,
            self::ABSENT_FILE_PATH
        );
    }

    /**
     * @throws Exception
     */

    public function testRetrieveFileLocal()
    {
        /** @var  OpenStackContainerStore | MockObject $openStackContainersStore */
        $openStackContainersStore = $this->getMockBuilder(OpenStackContainerStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects(static::never())
            ->method('getContainerWrapper');

        /** @var OpenStackContainerStore $openStackFactory */
        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $openStackContainersStore,
            $this->logger,
            true
        );

        $filepath = self::EXISTING_FILE_PATH;

        static::assertEquals(
            $filepath,
            $openStackSwiftWrapper->retrieveFile(
                self::CONTAINER_TEST,
                $filepath
            )
        );
    }

    /**
     * @throws Exception
     */

    public function testRetrieveAbsentFileLocalNoCloud()
    {
        /** @var  OpenStackContainerStore | MockObject $openStackContainersStore */
        $openStackContainersStore = $this->getMockBuilder(OpenStackContainerStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects(static::never())
            ->method('getContainerWrapper');

        /** @var OpenStackContainerStore $openStackFactory */
        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $openStackContainersStore,
            $this->logger,
            false
        );

        $filepath = self::ABSENT_FILE_PATH;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Le fichier ' . self::ABSENT_FILE_PATH . " n'est pas présent localement, aucun cloud configuré");

        static::assertEquals(
            $filepath,
            $openStackSwiftWrapper->retrieveFile(
                self::CONTAINER_TEST,
                $filepath
            )
        );
    }
    /**
     * @throws Exception
     */

    public function testRetrieveFile()
    {

        /** @var  OpenStackSwiftWrapper | MockObject $openStackSwiftWrapper */
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, 'fileContent');
        rewind($stream);

        $openStackSwiftWrapper->expects(static::once())
            ->method('download')
            ->with(self::ABSENT_FILE_NAME)
            ->willReturn($stream);

        /** @var  OpenStackContainerStore | MockObject $openStackContainersStore */
        $openStackContainersStore = $this->getMockBuilder(OpenStackContainerStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects(static::once())
            ->method('getContainerWrapper')
            ->with(static::equalTo(self::CONTAINER_TEST))
            ->willReturn($openStackSwiftWrapper);

        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $openStackContainersStore,
            $this->logger,
            true
        );

        $openStackSwiftWrapper->retrieveFile(
            self::CONTAINER_TEST,
            self::ABSENT_FILE_PATH
        );
        static::assertTrue(file_exists(self::ABSENT_FILE_PATH));
    }

    /**
     * @return void
     */
    public function testRetrieveFileReturnsEmptyFile()
    {

        /** @var  OpenStackSwiftWrapper | MockObject $openStackSwiftWrapper */
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, '');
        rewind($stream);

        $openStackSwiftWrapper->expects(static::once())
            ->method('download')
            ->with(self::ABSENT_FILE_NAME)
            ->willReturn($stream);

        /** @var  OpenStackContainerStore | MockObject $openStackContainersStore */
        $openStackContainersStore = $this->getMockBuilder(OpenStackContainerStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects(static::once())
            ->method('getContainerWrapper')
            ->with(static::equalTo(self::CONTAINER_TEST))
            ->willReturn($openStackSwiftWrapper);

        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $openStackContainersStore,
            $this->logger,
            true
        );

        $exceptionThrown = false;
        try {
            $openStackSwiftWrapper->retrieveFile(
                self::CONTAINER_TEST,
                self::ABSENT_FILE_PATH
            );
        } catch (Exception $exception) {
            $exceptionThrown = true;
            $exceptionMessage = $exception->getMessage();
            $exceptionClass = get_class($exception);
            static::assertEquals('Erreur lors du téléchargement', $exceptionMessage);
            static::assertEquals(CloudStorageException::class, $exceptionClass);
        }
        static::assertTrue($exceptionThrown);
        static::assertFalse(file_exists(self::ABSENT_FILE_PATH));
    }

    /**
     * @return void
     * @throws \S2lowLegacy\Class\CloudStorageException
     * @throws \S2lowLegacy\Lib\PausingQueueException
     * @throws \S2lowLegacy\Lib\UnrecoverableException
     */
    public function testRetrieveFileWithDoubleSlash()
    {

        /** @var  OpenStackSwiftWrapper | MockObject $openStackSwiftWrapper */
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->expects(static::once())
            ->method('objectExists')
            ->with('//trop///de////double//////slash')
            ->willReturn(true);

        $openStackSwiftWrapper->expects(static::once())
            ->method('download')
            ->with('//trop///de////double//////slash')
            ->willReturn('fileContent');

        /** @var  OpenStackContainerStore | MockObject $openStackContainersStore */
        $openStackContainersStore = $this->getMockBuilder(OpenStackContainerStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects(static::once())
            ->method('getContainerWrapper')
            ->with(static::equalTo(self::CONTAINER_TEST))
            ->willReturn($openStackSwiftWrapper);


        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $openStackContainersStore,
            $this->logger,
            true
        );

        $openStackSwiftWrapper->retrieveFile(
            self::CONTAINER_TEST,
            'slash',
            '//trop///de////double//////slash'
        );

        if (file_exists('slash')) {
            unlink('slash');
        }
    }

    /**
     * @return void
     * @throws \S2lowLegacy\Class\CloudStorageException
     * @throws \S2lowLegacy\Lib\PausingQueueException
     * @throws \S2lowLegacy\Lib\UnrecoverableException
     */
    public function testRetrieveFileWithDoubleSlashStoredWithoutDoubleSlash()
    {

        /** @var  OpenStackSwiftWrapper | MockObject $openStackSwiftWrapper */
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->expects(static::exactly(2))
            ->method('objectExists')
            ->withConsecutive(['//trop///de////double//////slash'], ['/trop/de/double/slash'])
            ->will(static::onConsecutiveCalls(false, true));

        $openStackSwiftWrapper->expects(static::once())
            ->method('download')
            ->with('/trop/de/double/slash')
            ->willReturn('fileContent');

        /** @var  OpenStackContainerStore | MockObject $openStackContainersStore */
        $openStackContainersStore = $this->getMockBuilder(OpenStackContainerStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects(static::once())
            ->method('getContainerWrapper')
            ->with(static::equalTo(self::CONTAINER_TEST))
            ->willReturn($openStackSwiftWrapper);


        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $openStackContainersStore,
            $this->logger,
            true
        );

        $openStackSwiftWrapper->retrieveFile(
            self::CONTAINER_TEST,
            'slash',
            '//trop///de////double//////slash'
        );

        if (file_exists('slash')) {
            unlink('slash');
        }
    }

    /**
     * @return void
     * @throws \S2lowLegacy\Class\CloudStorageException
     * @throws \S2lowLegacy\Lib\PausingQueueException
     * @throws \S2lowLegacy\Lib\UnrecoverableException
     */
    public function testRetrieveAbsentFileWithDoubleSlash()
    {

        /** @var  OpenStackSwiftWrapper | MockObject $openStackSwiftWrapper */
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->expects(static::exactly(2))
            ->method('objectExists')
            ->withConsecutive(['//trop///de////double//////slash'], ['/trop/de/double/slash'])
            ->will(static::onConsecutiveCalls(false, false));

        $openStackSwiftWrapper->expects(static::never())
            ->method('download');

        /** @var OpenStackContainerStore | MockObject $openStackContainersStore */
        $openStackContainersStore = $this->getMockBuilder(OpenStackContainerStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects(static::once())
            ->method('getContainerWrapper')
            ->with(static::equalTo(self::CONTAINER_TEST))
            ->willReturn($openStackSwiftWrapper);


        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $openStackContainersStore,
            $this->logger,
            true
        );

        $this->expectException(CloudStorageException::class);
        $this->expectExceptionMessage('/trop/de/double/slash non trouvé dans container_test');

        $openStackSwiftWrapper->retrieveFile(
            self::CONTAINER_TEST,
            'slash',
            '//trop///de////double//////slash'
        );

        if (file_exists('slash')) {
            unlink('slash');
        }
    }

    /**
     * @return void
     * @throws \S2lowLegacy\Class\CloudStorageException
     * @throws \S2lowLegacy\Lib\PausingQueueException
     * @throws \S2lowLegacy\Lib\UnrecoverableException
     */
    public function testErrorCreateObject()
    {

        /** @var  OpenStackSwiftWrapper | MockObject $openStackSwiftWrapper */
        $openStackSwiftWrapper = $this->getMockBuilder(OpenStackContainerWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $openStackSwiftWrapper->expects(static::once())
            ->method('createObject')
            ->willReturn(false);

        /** @var  OpenStackContainerStore | MockObject $openStackContainersStore */
        $openStackContainersStore = $this->getMockBuilder(OpenStackContainerStore::class)
            ->disableOriginalConstructor()
            ->getMock();


        $openStackContainersStore
            ->expects(static::once())
            ->method('getContainerWrapper')
            ->with(static::equalTo(self::CONTAINER_TEST))
            ->willReturn($openStackSwiftWrapper);

        /** @var OpenStackContainerStore $openStackFactory */
        $openStackSwiftWrapper = new OpenStackSwiftWrapper(
            $openStackContainersStore,
            $this->logger,
            true
        );

        static::assertFalse($openStackSwiftWrapper->sendFile(
            self::CONTAINER_TEST,
            self::EXISTING_FILE_PATH
        ));
    }
}
