<?php

namespace S2lowLegacy\Lib;

use Exception;
use Monolog\Logger;
use OpenStack\Identity\v3\Models\Token;
use OpenStack\ObjectStore\v1\Models\Container;
use OpenStack\ObjectStore\v1\Models\StorageObject;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class OpenStackContainerWrapper
{
    /** @var Token */
    private $token;
    /** @var Container */
    private $container;
    /** @var OpenStackContainerFetcher */
    private $openStackContainerFetcher;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var OpenStackStateManager
     */
    private $openStackStateManager;


    public function __construct(
        OpenStackContainerFetcher $openStackContainerFetcher,
        LoggerInterface $logger,
        OpenStackStateManager $openStackStateManager
    ) {
        $this->openStackStateManager = $openStackStateManager;
        $this->logger = $logger;
        $this->openStackContainerFetcher = $openStackContainerFetcher;
    }

    /**
     * @return Container
     * @throws Exception
     */

    private function getContainer()
    {
        if ($this->openStackStateManager->isResetNeeded()) {
            $this->resetConnection();
        }
        if ((!isset($this->container)) || (!$this->hasValidToken())) {
            $array = $this->openStackContainerFetcher->getNewTokenAndContainer();
            list($this->token,$this->container) = $array;
        }
        return $this->container;
    }

    /**
     * @return bool
     */

    private function hasValidToken()
    {
        $hasValidToken = isset($this->token) && !$this->token->hasExpired();
        if (!$hasValidToken) {
            $this->logger->info("[Openstack] Token expiré");
        }
        return ($hasValidToken);
    }


    public function resetConnection()
    {
        $this->logger->info("[Openstack] Reset Connection");
        list($this->token,$this->container) = [null,null];
    }

    /**
     * @param $options
     * @return StorageObject
     * @throws PausingQueueException
     */

    public function createObject($options)
    {
        return $this->executeCommand(
            function (Container $container, $options) {
                return $container->createObject($options);
            },
            $options
        );
    }

    /**
     * @param $options
     * @return StreamInterface
     * @throws PausingQueueException
     */

    public function download($options)
    {
        return $this->executeCommand(
            function (Container $container, $options) {
                return $container->getObject($options)->download();
            },
            $options
        );
    }

    /**
     * @param $options
     * @return StreamInterface
     * @throws PausingQueueException
     */

    public function getFileSize($options)
    {
        throw new Exception("getFileSize non encore implémenté");
        return $this->executeCommand(
            function (Container $container, $options) {
                return $container->getObject($options)->download()->getSize();
            },
            $options
        );
    }

    /**
     * @param $options
     * @return mixed
     * @throws PausingQueueException
     */

    public function delete($options)
    {
        return $this->executeCommand(
            function (Container $container, $options) {
                $container->getObject($options)->delete();
                return true;
            },
            $options
        );
    }

    /**
     * @param $options
     * @return bool
     * @throws PausingQueueException
     */

    public function objectExists($options)
    {
        return $this->executeCommand(
            function (Container $container, $options) {
                return $container->objectExists($options);
            },
            $options
        );
    }

    /**
     * @param $function
     * @param $options
     * @return mixed
     * @throws PausingQueueException
     */

    private function executeCommand(callable $function, $options)
    {
        try {
            $result = $function($this->getContainer(), $options);
            $this->openStackStateManager->declareSuccess();
            return $result;
        } catch (Exception $e) {
            $this->openStackStateManager->declareException($e);
        }
        return false;
    }
}
