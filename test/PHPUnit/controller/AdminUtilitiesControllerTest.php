<?php

class AdminUtilitiesControllerTest extends S2lowTestCase {

	public function testIndex(){
		$this->setSuperAdminAuthentication();
		$this->getObjectInstancier()->get(AdminUtilitiesController::class)->indexAction();
	}

	/**
	 * @throws RedirectException
	 */
	public function testdoSendAction(){

		$mailer = $this->getMockBuilder("Mailer")->getMock();
		$mailer->expects($this->any())->method('sendMail')->willReturn(true);

		$mailerFactory = $this->getMockBuilder("MailerFactory")->getMock();
		$mailerFactory->expects($this->any())->method("getInstance")->willReturn($mailer);
		$this->getObjectInstancier()->set("MailerFactory",$mailerFactory);

		$this->setSuperAdminAuthentication();

		$post = $this->getObjectInstancier()->get(Environnement::class)->post();
		$post->set('module',1);
		$post->set('subject','test');
		$post->set('body','message');

		$this->setExpectedException(RedirectException::class,"eric@sigmalis.com");
		$this->getObjectInstancier()->get(AdminUtilitiesController::class)->doSendAction();
	}

}