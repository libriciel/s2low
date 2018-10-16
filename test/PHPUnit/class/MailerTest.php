<?php

class MailerTest extends PHPUnit_Framework_TestCase {



	public function testIsValidMail(){

		$mailer = new Mailer();
		$this->assertTrue($mailer->isValidMail("noreply@libriciel.coop"));
		$this->assertFalse($mailer->isValidMail("aaa"));
		$this->assertFalse($mailer->isValidMail("a>>aa&&@@<<<<"));

	}
}