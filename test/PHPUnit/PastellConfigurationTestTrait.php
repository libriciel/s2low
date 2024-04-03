<?php

use S2lowLegacy\Class\PastellWrapper;
use S2lowLegacy\Class\PastellWrapperFactory;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\PastellProperties;
use S2lowLegacy\Model\PastellPropertiesSQL;

trait PastellConfigurationTestTrait
{
    protected function configurePastell($authority_id = 1)
    {
        $authoritySQL = new AuthoritySQL($this->getSQLQuery());
        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "FakeURL";
        $pastellProperties->id_e = 12;
        $pastellProperties->actes_send_auto = true;
        $pastellProperties->helios_send_auto = true;
        $authoritySQL->updateSAE($authority_id, $pastellProperties);
        $pastellPropertiesSQL  = new PastellPropertiesSQL($this->getSQLQuery());
        $pastellPropertiesSQL->editProperties($authority_id, $pastellProperties);
    }

    protected function mockPastellFactory($id_d = "xyzt", $getLastErrorReturn = false, $sendSAEThrowError = false, $deleteThrowError = false)
    {
        /** @var PHPUnit\Framework\MockObject\MockObject $pastell */
        $pastell = $this->getMockBuilder(PastellWrapper::class)->disableOriginalConstructor()->getMock();
        $pastell->method('createActes')->willReturn($id_d);
        $pastell->method('createHelios')->willReturn($id_d);
        $pastell->method('getLastError')->willReturn($getLastErrorReturn);
        if ($sendSAEThrowError) {
            $pastell->method('sendSAE')->willThrowException(new Exception('Error Send SAE'));
        } else {
            $pastell->method('sendSAE')->willReturn(true);
        }
        if ($deleteThrowError) {
            $pastell->method('delete')->willThrowException(new Exception('Error on delete'));
        }
        $pastellFactory = $this->getMockBuilder(PastellWrapperFactory::class)->disableOriginalConstructor()->getMock();
        $pastellFactory->method('getNewInstance')->willReturn($pastell);
        $this->getObjectInstancier()->set(PastellWrapperFactory::class, $pastellFactory);
    }

    /**
     * @return SQLQuery
     */
    abstract public function getSQLQuery();

    /**
     * @param $classname
     */
    abstract public function getMockBuilder(string $classname);

    /**
     * @return ObjectInstancier
     */
    abstract public function getObjectInstancier();
}
