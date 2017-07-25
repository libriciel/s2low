<?php

class ActesEnvelopeSQLTest extends S2lowTestCase {


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

}