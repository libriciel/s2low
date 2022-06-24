<?php

class PostgreSQLSchemaInfoTest extends S2lowTestCase
{
    public function testGetDefinition()
    {
        /** @var PostgreSQLSchemaInfo $postreSQLSchemaInfo */
        $postreSQLSchemaInfo = $this->getObjectInstancier()->get("PostgreSQLSchemaInfo");
        $definition = $postreSQLSchemaInfo->getDatabaseDefinition();
        $this->assertContains('authorities_id_seq', $definition['sequence']);
        $this->assertEquals('integer', $definition['table']['authorities']['id']['data_type']);
        $this->assertEquals("id", $definition['constraint']['authorities']['authorities_pkey']['conkey'][0]);
        //print_r($definition);
        $this->assertMatchesRegularExpression('#CREATE INDEX xml_nomfic_index ON (public\.)?helios_transactions USING btree \(xml_nomfic\)#', $definition['index']['xml_nomfic_index']['indexdef']);
    }
}
