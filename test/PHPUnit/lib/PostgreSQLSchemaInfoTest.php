<?php

class PostgreSQLSchemaInfoTest extends S2lowTestCase {

	public function testGetDefinition(){
		/** @var PostgreSQLSchemaInfo $postreSQLSchemaInfo */
		$postreSQLSchemaInfo = $this->getObjectInstancier()->get("PostgreSQLSchemaInfo");
		$definition = $postreSQLSchemaInfo->getDatabaseDefinition();
		$this->assertContains('authorities_id_seq',$definition['sequence']);
		$this->assertEquals('integer',$definition['table']['authorities']['id']['data_type']);
		$this->assertEquals("id",$definition['constraint'][0]['conkey'][0]);
		$this->assertEquals('CREATE INDEX toto ON actes_included_files USING btree (transaction_id)',$definition['index']['toto']['indexdef']);
	}

}