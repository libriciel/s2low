<?php

class ActesEnvelopeSQLTest extends S2lowTestCase {


	/**
	 * @return ActesEnvelopeSQL
	 */
    public function getActesEnvelopeSQL(){
        return $this->getObjectInstancier()->get("ActesEnvelopeSQL");
    }

    public function testGetInfo(){
        $id  = $this->getActesEnvelopeSQL()->create(
            1,
            "000000000/abc-EACT--210703385--20170612-2.tar.gz"
        );
        $info = $this->getActesEnvelopeSQL()->getInfo($id);
        $this->assertEquals(1,$info['user_id']);
        $this->assertEquals("000000000/abc-EACT--210703385--20170612-2.tar.gz",$info['file_path']);
    }

    public function testFindByAnomalieEnveloppeName(){
        $id_expected  = $this->getActesEnvelopeSQL()->create(
            1,
            "000000000/abc-EACT--210703385--20170612-2.tar.gz"
        );
        $id = $this->getActesEnvelopeSQL()->findByAnomalieEnveloppeName("ANO_EACT--210703385--20170612-2.xml");
        $this->assertEquals($id_expected,$id);
    }

    public function testCreateRelatedEnvelope(){
        $id_envelope  = $this->getActesEnvelopeSQL()->create(
            1,
            "000000000/20170721D/abc-EACT--210703385--20170612-2.tar.gz"
        );
        $info_envelope = $this->getActesEnvelopeSQL()->getInfo($id_envelope);

        $related_envelope_id = $this->getActesEnvelopeSQL()->createRelatedEnveloppe(
            $id_envelope,
            "000000000/20170721D/SLO-EACT--SPREF0011-200036473-20170711-53.tar.gz",
            42
        );

        $info = $this->getActesEnvelopeSQL()->getInfo($related_envelope_id);
        $this->assertEquals($info['user_id'],$info_envelope['user_id']);
    }

    public function testgetLastEnvelope(){
        $id_envelope  = $this->getActesEnvelopeSQL()->create(
            1,
            "000000000/20170721D/abc-EACT--210703385--20170612-2.tar.gz"
        );
        $info = $this->getActesEnvelopeSQL()->getLastEnvelope();
        $this->assertEquals($id_envelope,$info['id']);
    }

    public function createAndList($authority_id,$authorit_group_id){
        $id_envelope  = $this->getActesEnvelopeSQL()->create(
            1,
            "000000000/20170721D/abc-EACT--210703385--20170612-2.tar.gz"
        );
        $info = $this->getActesEnvelopeSQL()->listEnveloppe(
            date("Y-m-d",strtotime("-1 month")),
            date("Y-m-d 23:59:59"),
            $authority_id,
            $authorit_group_id
        );
        $this->assertEquals($id_envelope,$info[0]['id']);
    }

    public function testlistEnveloppe(){
        $this->createAndList(false,false);
    }

    public function testlistEnveloppeAuthority(){
        $this->createAndList(1,false);
    }

    public function testlistEnveloppeAuthorityGroup(){
        $this->createAndList(false,1);
    }

    public function testGetAllTransactionToSendInCloud(){
    	$filename = "000000000/20170721D/abc-EACT--210703385--20170612-2.tar.gz";
		$id_envelope  = $this->getActesEnvelopeSQL()->create(
			1,$filename
		);

		$sqlQuery = $this->getActesEnvelopeSQL()->getAllTransactionToSendInCloudHandle();
		$all = $sqlQuery->fetch();
		$this->assertEquals($id_envelope,$all['id']);
		$this->assertEquals($filename,$all['file_path']);
	}

	public function testGetNextTransactionToSendInCloud(){
		$filename = "000000000/20170721D/abc-EACT--210703385--20170612-2.tar.gz";
		$id_envelope  = $this->getActesEnvelopeSQL()->create(
			1,$filename
		);
		$info = $this->getActesEnvelopeSQL()->getNextTransactionToSendInCloud();
		$this->assertEquals($id_envelope,$info['id']);
		$this->assertEquals($filename,$info['file_path']);
	}

	public function testSetTransactionInCloud(){
		$filename = "000000000/20170721D/abc-EACT--210703385--20170612-2.tar.gz";
		$id_envelope  = $this->getActesEnvelopeSQL()->create(
			1,$filename
		);
		$all = $this->getActesEnvelopeSQL()->getAllTransactionToSendInCloudHandle()->fetch();

		$this->assertEquals($id_envelope,$all['id']);
		$this->assertEquals($filename,$all['file_path']);
		$this->getActesEnvelopeSQL()->setTransactionInCloud($id_envelope);
		$this->assertFalse(
			$this->getActesEnvelopeSQL()->getAllTransactionToSendInCloudHandle()->hasMoreResult()
		);

		$this->getActesEnvelopeSQL()->setTransactionInCloudRemove($id_envelope);
		$all = $this->getActesEnvelopeSQL()->getAllTransactionToSendInCloudHandle()->fetch();
		$this->assertEquals($id_envelope,$all['id']);
		$this->assertEquals($filename,$all['file_path']);
	}


	public function testgetOlderTransactionHandle(){
    	$sqlQuery = $this->getActesEnvelopeSQL()->getOlderTransactionHandle('2001-01-01','2001-12-31');
    	$this->assertInstanceOf(SQLQuery::class,$sqlQuery);
	}

	public function testGetAllEnvelopepIdToSendInCloud(){
		$filename = "000000000/20170721D/abc-EACT--210703385--20170612-2.tar.gz";
		$id_envelope  = $this->getActesEnvelopeSQL()->create(
			1,$filename
		);
		$this->assertEquals([$id_envelope],$this->getActesEnvelopeSQL()->getAllEnvelopepIdToSendInCloud());
		$this->getActesEnvelopeSQL()->setEnveloppeNotAvailable($id_envelope);
		$this->assertEmpty($this->getActesEnvelopeSQL()->getAllEnvelopepIdToSendInCloud());
	}

}

