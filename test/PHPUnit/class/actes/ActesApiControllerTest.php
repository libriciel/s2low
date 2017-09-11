<?php

class ActesApiControllerTest extends S2lowTestCase {

    public function testActesStatus(){
        $this->setUserAuthentification();
        $actesAPIController = $this->getObjectInstancier()->get("ActesAPIController");
        $this->expectOutputRegex("#En attente de transmission#");
        $actesAPIController->listStatusAction();
    }

    public function testNbActes(){
        $this->setUserAuthentification();
        $actesAPIController = $this->getObjectInstancier()->get("ActesAPIController");
        $this->expectOutputString("{\"status_id\":0,\"authority_id\":1,\"nb_transactions\":0}");
        $actesAPIController->nbActesAction();
    }

    public function testListActes(){
        $this->setUserAuthentification();
        $actesAPIController = $this->getObjectInstancier()->get("ActesAPIController");
        $this->expectOutputString("{\"status_id\":\"0\",\"authority_id\":\"1\",\"offset\":\"0\",\"limit\":\"100\",\"transactions\":[]}");
        $actesAPIController->listActesAction();
    }

    public function testActionAfter(){
        $this->setUserAuthentification();
        $actesAPIController = $this->getObjectInstancier()->get("ActesAPIController");
        $this->expectOutputString("");
        $actesAPIController->_actionAfter();
    }

}