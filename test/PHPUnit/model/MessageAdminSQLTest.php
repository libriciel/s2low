<?php

class MessageAdminSQLTest extends S2lowTestCase {

	const MESSAGE_TEST = "Ceci est un message";

	/**
	 * @var MessageAdminSQL
	 */
	private $messageAdminSQL;
	private $message_id;

	protected function setUp() : void {
		parent::setUp();
		$this->messageAdminSQL = new MessageAdminSQL($this->getSQLQuery());
		$this->message_id = $this->messageAdminSQL->edit(0,"titre",self::MESSAGE_TEST,1,MessageAdmin::NIVEAU_DANGER);
	}

	public function testEdit(){
		$result = $this->messageAdminSQL->getAll(0,10);
		$this->assertEquals(self::MESSAGE_TEST,$result[0]->message);
	}

	public function testEditExisting(){
		$message2 = "un autre message";
		$this->messageAdminSQL->edit(0,"titre2",$message2,1,MessageAdmin::NIVEAU_DANGER);
		$result = $this->messageAdminSQL->getAll(0,10);
		$this->assertEquals($message2,$result[0]->message);
	}

	public function testGetInfo(){
		$messageAdmin = $this->messageAdminSQL->getMessage($this->message_id);
		$this->assertEquals(self::MESSAGE_TEST,$messageAdmin->message);
	}

	public function testPublier(){
		$message_id_2 = $this->messageAdminSQL->edit(0,"titre2",self::MESSAGE_TEST,1,MessageAdmin::NIVEAU_DANGER);

		$this->messageAdminSQL->publier($this->message_id,1);
		$this->messageAdminSQL->publier($message_id_2,1);

		$messageAdmin1 = $this->messageAdminSQL->getMessage($this->message_id);

		$this->assertEquals(MessageAdmin::ETAT_RETIRE,$messageAdmin1->getEtat());

		$messageAdmin2 = $this->messageAdminSQL->getMessage($message_id_2);
		$this->assertEquals(MessageAdmin::ETAT_PUBLIE,$messageAdmin2->getEtat());
	}

	public function testPublierReverseOrder(){
		$message_id_2 = $this->messageAdminSQL->edit(0,"titre2",self::MESSAGE_TEST,1,MessageAdmin::NIVEAU_DANGER);

		$this->messageAdminSQL->publier($message_id_2,1);
		$this->messageAdminSQL->publier($this->message_id,1);


		$messageAdmin1 = $this->messageAdminSQL->getMessage($this->message_id);
		$this->assertEquals(MessageAdmin::ETAT_PUBLIE,$messageAdmin1->getEtat());

		$messageAdmin2 = $this->messageAdminSQL->getMessage($message_id_2);
		$this->assertEquals(MessageAdmin::ETAT_RETIRE,$messageAdmin2->getEtat());
	}

	public function testEditPulishMessage(){
		$this->messageAdminSQL->publier($this->message_id,1);
		$this->setExpectedException("Exception","Impossible de modifier ce message qui n'est pas en cours de rédaction");
		$this->messageAdminSQL->edit($this->message_id,'test','toto',1,MessageAdmin::NIVEAU_DANGER);
	}

	public function testRetirer(){
		$this->messageAdminSQL->publier($this->message_id,1);
		$this->messageAdminSQL->retirer($this->message_id,1);
		$message = $this->messageAdminSQL->getPublishedMessage();
		$this->assertEquals(0,$message->message_id);
	}

	public function testEdit2(){
		$this->messageAdminSQL->edit($this->message_id,"titre2","toto",1,MessageAdmin::NIVEAU_DANGER);
		$messageAdmin = $this->messageAdminSQL->getMessage($this->message_id);
		$this->assertEquals("toto",$messageAdmin->message);
	}

	public function testgetPublishedMessage(){
		$messageAdmin = $this->messageAdminSQL->getPublishedMessage();
		$this->assertEquals(0,$messageAdmin->message_id);
		$this->messageAdminSQL->publier($this->message_id,1);
		$messageAdmin = $this->messageAdminSQL->getPublishedMessage();
		$this->assertEquals($this->message_id,$messageAdmin->message_id);
	}
}