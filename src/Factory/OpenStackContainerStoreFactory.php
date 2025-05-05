<?php

namespace S2low\Factory;

use S2lowLegacy\Class\actes\ActesCloudStorable;
use S2lowLegacy\Class\helios\PESAcquitCloudStorable;
use S2lowLegacy\Class\helios\PESAllerCloudStorable;
use S2lowLegacy\Class\helios\PESRetourCloudStorable;
use S2lowLegacy\Class\mailsec\MailIncludedFilesCloudStorable;
use S2lowLegacy\Lib\OpenStackContainerStore;
use S2lowLegacy\Lib\OpenStackContainerWrapperFactory;

class OpenStackContainerStoreFactory
{
    private const ACTES = 'actes';
    private const PES_ALLER = 'helios_aller';
    private const PES_ACQUIT = 'helios_acquit';
    private const PES_RETOUR = 'helios_retour';
    private const MAILSEC = 'mailsec';

    public function __construct(
        private readonly OpenStackConfigFactory $openStackConfigFactory,
        private readonly OpenStackContainerWrapperFactory $openStackContainerWrapperFactory
    ) {
    }

    public function create(): OpenStackContainerStore
    {
        $openStackContainerStore = new OpenStackContainerStore(
            $this->openStackContainerWrapperFactory
        );
        $openStackContainerStore->addConfiguration(
            ActesCloudStorable::CONTAINER_NAME,
            $this->openStackConfigFactory->create(self::ACTES)
        );
        $openStackContainerStore->addConfiguration(
            PESAllerCloudStorable::CONTAINER_NAME,
            $this->openStackConfigFactory->create(self::PES_ALLER)
        );
        $openStackContainerStore->addConfiguration(
            PESAcquitCloudStorable::CONTAINER_NAME,
            $this->openStackConfigFactory->create(self::PES_ACQUIT)
        );
        $openStackContainerStore->addConfiguration(
            PESRetourCloudStorable::CONTAINER_NAME,
            $this->openStackConfigFactory->create(self::PES_RETOUR)
        );
        $openStackContainerStore->addConfiguration(
            MailIncludedFilesCloudStorable::CONTAINER_NAME,
            $this->openStackConfigFactory->create(self::MAILSEC)
        );
        return $openStackContainerStore;
    }
}
