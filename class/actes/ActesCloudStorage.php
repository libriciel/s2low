<?php

namespace S2lowLegacy\Class\actes;

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\CloudStorage;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;

class ActesCloudStorage extends CloudStorage
{
    public function __construct(
        ActesCloudStorable $iCloudStorable,
        OpenStackSwiftWrapper $openStackSwiftWrapper,
        LoggerInterface $logger,
        $openstack_enable
    ) {
        parent::__construct($iCloudStorable, $openStackSwiftWrapper, $logger, $openstack_enable);
    }
}
