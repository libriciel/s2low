<?php

use GuzzleHttp\Exception\ConnectException;
use Monolog\Logger;
use OpenStack\Common\Error\BadResponseError;
use Psr\Http\Message\RequestInterface;

class OpenStackStateManagerTest extends S2lowTestCase {

    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var \Monolog\Handler\StreamHandler
     */
    private $handler;

    public function setUp(): void
    {
        parent::setUp();
        $this->logger = new Logger("test");
        $this->handler = new  Monolog\Handler\TestHandler();
        $this->logger->pushHandler($this->handler);
    }

    public function testMaxConsecutiveExceptions(){


        $classe = new OpenStackStateManager($this->logger);

        $exceptionThrown = true;
        try{
            for($i = 1 ; $i <= OpenStackStateManager::MAX_CONSECUTIVE_ATTEMPTS ; $i++){
                $classe->declareException(new Exception("Test_".$i));
            }
        } catch (Exception $e){
            $exceptionThrown = true;
            $this->assertEquals(
                PausingQueueException::class,
                get_class($e)
            );
        }
        $this->assertEquals(true, $exceptionThrown);
    }

    public function testNoResetNeeded(){
        $classe = new OpenStackStateManager($this->logger);

        $this->assertFalse($classe->isResetNeeded());
    }

    /**
     * @throws PausingQueueException
     */
    public function testResetNeededAfterException(){
        $classe = new OpenStackStateManager($this->logger);

        $classe->declareException(new Exception("test"));

        $this->assertTrue($classe->isResetNeeded());
    }

    /**
     * @throws PausingQueueException
     */
    public function testNoResetNeededAfterSuccess(){
        $classe = new OpenStackStateManager($this->logger);

        $classe->declareException(new Exception("test"));
        $classe->declareSuccess();

        $this->assertFalse($classe->isResetNeeded());
    }

    /** @dataProvider exceptionsProvider */
    public function testException(Exception $exception,string $message){
        $classe = new OpenStackStateManager($this->logger);

        $classe->declareException($exception);
        
        $this->assertEquals("[Openstack][1] {$message}",
            $this->handler->getRecords()[0]["message"]);
    }

    public function exceptionsProvider(){
        return [
            $this->buildExceptionRequestInterface(),
            $this->buildBadResponseError(401),
            $this->buildBadResponseError(404,"Parceque mais parceque"),
            $this->buildArbitraryException(UnexpectedValueException::class, "Isn't it unexpected")

        ];
    }

    public function buildExceptionRequestInterface(){
        /** @var RequestInterface | PHPUnit\Framework\MockObject\ $requestInterfaceMock */
        $requestInterfaceMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $exceptionMessage = "Le message";
        $exceptionRequestInterface = new ConnectException($exceptionMessage, $requestInterfaceMock);

        return [$exceptionRequestInterface, "Erreur Guzzle : $exceptionMessage"];
    }

    public function buildBadResponseError($status,$reasonPhrase=""){
        $exceptionBadResponseError = new \OpenStack\Common\Error\BadResponseError("");

        /** @var \Psr\Http\Message\ResponseInterface | \PHPUnit\Framework\MockObject\MockObject $badResponse */
        $badResponse = $this->getMockBuilder(\GuzzleHttp\Psr7\Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $badResponse->method('getStatusCode')->willReturn($status);
        $badResponse->method('getReasonPhrase')->willReturn($reasonPhrase);
        $exceptionBadResponseError->setResponse($badResponse);

        return [$exceptionBadResponseError, $status===401?"Erreur d'authentification":"Erreur ${status} : ${reasonPhrase}"];
    }

    public function buildArbitraryException($exceptionName,$message){
        return [
            new $exceptionName($message),
            "Erreur $exceptionName : $message"
        ];
    }

}