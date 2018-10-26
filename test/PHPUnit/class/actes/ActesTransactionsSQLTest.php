<?php

class ActesTransactionsSQLTest extends S2lowTestCase {

	use ActesUtilitiesTestTrait;

    /**
     * @return ActesTransactionsSQL
     */
    private function getActesTransactionsSQL(){
        return $this->getObjectInstancier()->get("ActesTransactionsSQL");
    }

    public function testGetLastArchiveFromStatus(){
        $transaction_id =$this->createTransaction('14');
        $this->getActesTransactionsSQL()->updateStatus($transaction_id,12,"test");

        $result_1 = $this->getActesTransactionsSQL()->getArchiveFromStatusWithSAE(12);
        $this->assertNotEmpty($result_1);

        $result = $this->getActesTransactionsSQL()->getLastArchiveFromStatus(12,date("Y-m-d"));
        $this->assertEquals($result_1,$result);

    }

    public function testGetNbByStatus(){
        $this->createTransaction(ActesStatusSQL::STATUS_POSTE);
        $this->assertEquals(1,$this->getActesTransactionsSQL()->getNbByStatus(ActesStatusSQL::STATUS_POSTE));
    }

    public function testCreateRelatedTransaction(){
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);
        $transaction_info = $this->getActesTransactionsSQL()->getInfo($transaction_id);
        $related_transaction_id = $this->getActesTransactionsSQL()->createRelatedTransaction(
            $transaction_info['envelope_id'],
            2,
            '2017-07-25',
            $transaction_id
        );
        $this->assertNotNull($related_transaction_id);
        $transaction_info = $this->getActesTransactionsSQL()->getInfo($related_transaction_id);
        $this->assertEquals($transaction_id,$transaction_info['related_transaction_id']);

    }

    public function testGuessUniqueId(){
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

        $unique_id = $this->getActesTransactionsSQL()->guessUniqueId($transaction_id);

        $this->assertEquals("034-000000000-20170701-20170728C-AI",$unique_id);
    }

    public function testUpdateStatus(){
		$transaction_id =$this->createTransaction('14');
		$this->getActesTransactionsSQL()->updateStatus($transaction_id,1,"foo");
		$info = $this->getActesTransactionsSQL()->getStatusInfo($transaction_id,1);
		$this->assertEquals("foo",$info['message']);
	}

    public function testUpdateStatusTooLong(){
		$transaction_id =$this->createTransaction('14');
		$message = str_repeat("1234567890",53);
		$this->getActesTransactionsSQL()->updateStatus($transaction_id,1,$message);
		$info = $this->getActesTransactionsSQL()->getStatusInfo($transaction_id,1);
		$this->assertEquals(512,strlen($info['message']));
	}

	public function testgetByStatusSinceDate(){
		$transaction_id =$this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
		$this->assertEmpty(
			$this->getActesTransactionsSQL()->getByStatusSinceDate(
				ActesStatusSQL::STATUS_TRANSMIS,
				"1970-01-01")
		);
		$this->assertEquals(
			[$transaction_id],
			$this->getActesTransactionsSQL()->getByStatusSinceDate(
				ActesStatusSQL::STATUS_TRANSMIS,
				date("Y-m-d",strtotime("+2 days")))
			);
	}

}