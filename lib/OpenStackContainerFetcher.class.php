<?php

use Monolog\Logger;
use OpenStack\OpenStack;
use Psr\Log\LoggerInterface;

class OpenStackContainerFetcher
{
    /** @var string  */
    private $containerFullName;
    /** @var array  */
    private $generate_token_options;
    /** @var OpenStack */
    private $openStack;
    /** @var Logger   */
    private $logger;

    public function __construct(
        string $containerFullName,
        array $generate_token_options,
        OpenStack $openStack,
        LoggerInterface $logger
    ) {
        $this->containerFullName = $containerFullName;
        $this->generate_token_options = $generate_token_options;
        $this->openStack = $openStack;
        $this->logger = $logger;
    }

    /**
     * @return array
     * @throws Exception
     */

    public function getNewTokenAndContainer()
    {
        try {
            $token = $this->openStack->identityV3()->generateToken($this->generate_token_options);
            $parametresWithToken = $this->generate_token_options;
            $parametresWithToken["cachedToken"] = $token->export();

            $container = $this->openStack
                ->objectStoreV1($parametresWithToken)
                ->getContainer($this->containerFullName);
        } catch (Exception$e) {
            $this->logger->error("Openstack : erreur à la génération du token / recherche du container : " . $e->getMessage());
            throw $e;
        }
        $this->logger->info("Openstack : génération du token / recherche du container " . $this->containerFullName);
        return [$token, $container];
    }
}
