<?php

class ActesUpdateClassificationSQLTest extends S2lowTestCase {

    /** @var  ActesUpdateClassificationSQL */
    private $actesUpdateClassificationSQL;
    private $classification_xml;

    protected function setUp() {
        parent::setUp();
        $this->actesUpdateClassificationSQL = $this->getObjectInstancier()->get("ActesUpdateClassificationSQL");
        $this->classification_xml = file_get_contents(__DIR__."/../fixtures/classification.xml");

    }

    public function testUpdateClassification(){
        $sql = "INSERT into actes_classification_requests(request_date, requested_by, version_date, xml_data) VALUES (now(),?,NULL,NULL)";
        $this->getSQLQuery()->query($sql,1);

        $this->actesUpdateClassificationSQL->updateClassification("123456789",$this->classification_xml);

        $this->assertEquals(
            $this->classification_xml,
            $this->actesUpdateClassificationSQL->getClassification("123456789")
        );

        $this->assertEquals(
            'Actes réglementaires',
            $this->actesUpdateClassificationSQL->getActeNature()[1]['descr']
        );
        $authority_id = $this->getObjectInstancier()->get("AuthoritySQL")->getIdBySIREN("123456789");
        $this->assertEquals(
            "Commande Publique",
            $this->actesUpdateClassificationSQL->getClassificationCode($authority_id)[0]['description']
        );

        $actesTypePJSQL = $this->getObjectInstancier()->get('ActesTypePJSQL');
        $this->assertEquals(13,count($actesTypePJSQL->getAll()));
    }

    public function testUpdateClassificationBadSiren(){
        $this->setExpectedException("Exception","Aucune collectivité ne correspond au SIREN 42");
        $this->actesUpdateClassificationSQL->updateClassification("42",$this->classification_xml);
    }

    public function testUpdateClassificationNoXML(){
        $this->setExpectedException("Exception","Le message n'est pas un retour de classification: EnveloppeMISILLCL trouvé.");
        $this->actesUpdateClassificationSQL->updateClassification("123456789",file_get_contents(__DIR__."/../fixtures/test-archive-MISILCL/TACT--SPREF0011-000000000-20170721-4.xml"));
    }


}