<?php

namespace S2lowLegacy\Class\mailsec;

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\CloudStorage;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;

class MailIncludedFilesCloudStorage extends CloudStorage
{
    public function __construct(
        MailIncludedFilesCloudStorable $iCloudStorable,
        OpenStackSwiftWrapper $openStackSwiftWrapper,
        LoggerInterface $logger,
        $openstack_enable
    ) {
        parent::__construct($iCloudStorable, $openStackSwiftWrapper, $logger, $openstack_enable);
    }
}
