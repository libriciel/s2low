<?php
use OpenStack\OpenStack;

class OpenStackContainerFetcher{
    /** @var string  */
    private $containerFullName;
    /** @var array  */
    private $generate_token_options;
    /** @var OpenStack */
    private $openStack;

    public function __construct(string $containerFullName,array $generate_token_options,OpenStack $openStack)
    {
        $this->containerFullName = $containerFullName;
        $this->generate_token_options = $generate_token_options;
        $this->openStack = $openStack;
    }

    public function getNewTokenAndContainer(){
        $token =$this->openStack->identityV3()->generateToken($this->generate_token_options);

        $parametresWithToken = $this->generate_token_options;
        $parametresWithToken["cachedToken"]=$token->export();

        $container = $this->openStack
            ->objectStoreV1($parametresWithToken)
            ->getContainer($this->containerFullName);

        return [$token, $container];
    }
}