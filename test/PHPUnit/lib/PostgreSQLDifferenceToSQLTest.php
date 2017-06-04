<?php

class PostgreSQLDifferenceToSQLTest extends PHPUnit_Framework_TestCase {

	/** @var  PostgreSQLDifferenceToSQL */
	private $postgreSQLDifferenceToSQL;

	protected function setUp() {
		parent::setUp();
		$this->postgreSQLDifferenceToSQL = new PostgreSQLDifferenceToSQL();

	}

	private function getS2lowDefinition(){
		return json_decode(file_get_contents(__DIR__."/../../../db/s2low.sql.json"),true);
	}

	public function testEmpty(){
		$this->assertEmpty($this->postgreSQLDifferenceToSQL->getSQL(array()));
	}

	public function testCreateSequence(){
		$result = $this->postgreSQLDifferenceToSQL->getSQL(array('create_sequence'=>array('a')));
		$this->assertEquals(array("CREATE SEQUENCE a;"),$result);
	}

	public function testDropSequence(){
		$result = $this->postgreSQLDifferenceToSQL->getSQL(array('drop_sequence'=>array('a')));
		$this->assertEquals(array("DROP SEQUENCE a;"),$result);
	}

	public function testCreateIndex(){
		$index_command = "CREATE INDEX a ON helios_transactions_workflow USING btree (transaction_id)";
		$result = $this->postgreSQLDifferenceToSQL->getSQL(
			array(
				'create_index' =>
					array(
						'a' =>
							array(
								'tablename'=>'foo',
								"indexdef"=>$index_command)
					)
			)
		);
		$this->assertEquals(array($index_command),$result);
	}

	public function testDropIndex(){
		$result = $this->postgreSQLDifferenceToSQL->getSQL(array('drop_index'=>array('toto')));
		$this->assertEquals(array("DROP INDEX toto;"),$result);
	}

	public function testCreatePrimaryKey() {
		$result = $this->postgreSQLDifferenceToSQL->getSQL(
			array(
				'create_constraint' =>
					array(
						'foo_pkey' =>
							array(
								'contype' => 'p',
								'conname' => 'foo_pkey',
								'conrelname' => 'authorities',
								'conkey' => array('id'),
								'confrelname' => null,
								'confkey' => array()
							)
					)
			)
		);
		$this->assertEquals(array("ALTER TABLE authorities ADD CONSTRAINT foo_pkey PRIMARY KEY (id);"),$result);
	}

	public function testCreateUniqueKey() {
		$result = $this->postgreSQLDifferenceToSQL->getSQL(
			array(
				'create_constraint' =>
					array(
						'mail_annuaire_mail_adresse' =>
							array(
								'contype' => 'u',
								'conname' => 'mail_annuaire_mail_adresse',
								'conrelname' => 'mail_annuaire',
								'conkey' => array('mail_address','authority_id'),
								'confrelname' => null,
								'confkey' => array()
							)
					)
			)
		);
		$this->assertEquals(array("ALTER TABLE mail_annuaire ADD CONSTRAINT mail_annuaire_mail_adresse UNIQUE (mail_address,authority_id);"),$result);
	}

	public function testCreateForeignKey() {
		$result = $this->postgreSQLDifferenceToSQL->getSQL(
			array(
				'create_constraint' =>
					array(
						'actes_batch_files_batch_id_fk' =>
							array(
								'contype' => 'f',
								'conname' => 'actes_batch_files_batch_id_fk',
								'conrelname' => 'actes_batch_files',
								'conkey' => array('batch_id'),
								'confrelname' => 'actes_batches',
								'confkey' => array('id')
							)
					)
			)
		);
		$this->assertEquals(array("ALTER TABLE actes_batch_files ADD CONSTRAINT actes_batch_files_batch_id_fk FOREIGN KEY (batch_id) REFERENCES actes_batches (id);"),$result);
	}

	public function testCreateConstraintInRightOrder(){
		$result = $this->postgreSQLDifferenceToSQL->getSQL(
			array(
				'create_constraint' =>
					array(
						'actes_batch_files_batch_id_fk' =>
							array(
								'contype' => 'f',
								'conname' => 'actes_batch_files_batch_id_fk',
								'conrelname' => 'actes_batch_files',
								'conkey' => array('batch_id'),
								'confrelname' => 'actes_batches',
								'confkey' => array('id')
							),
						'mail_annuaire_mail_adresse' =>
							array(
								'contype' => 'u',
								'conname' => 'mail_annuaire_mail_adresse',
								'conrelname' => 'mail_annuaire',
								'conkey' => array('mail_address','authority_id'),
								'confrelname' => null,
								'confkey' => array()
							),
						'foo_pkey' =>
							array(
								'contype' => 'p',
								'conname' => 'foo_pkey',
								'conrelname' => 'authorities',
								'conkey' => array('id'),
								'confrelname' => null,
								'confkey' => array()
							)
					)
			)
		);
		$this->assertEquals('ALTER TABLE authorities ADD CONSTRAINT foo_pkey PRIMARY KEY (id);',$result[0]);
		$this->assertEquals('ALTER TABLE mail_annuaire ADD CONSTRAINT mail_annuaire_mail_adresse UNIQUE (mail_address,authority_id);',$result[1]);
		$this->assertEquals('ALTER TABLE actes_batch_files ADD CONSTRAINT actes_batch_files_batch_id_fk FOREIGN KEY (batch_id) REFERENCES actes_batches (id);',$result[2]);
	}

	public function testDropConstraint(){
		$result = $this->postgreSQLDifferenceToSQL->getSQL(
			array(
				'drop_constraint' => array(
					'actes_batch_files_batch_id_fk' =>
						array(
							'contype' => 'f',
							'conname' => 'actes_batch_files_batch_id_fk',
							'conrelname' => 'actes_batch_files',
							'conkey' => array('batch_id'),
							'confrelname' => 'actes_batches',
							'confkey' => array('id')
						)
				)
			)
		);
		$this->assertEquals("ALTER TABLE actes_batch_files DROP CONSTRAINT actes_batch_files_batch_id_fk;",$result[0]);
	}


	public function testDropConstraintInRightOrder(){
		$result = $this->postgreSQLDifferenceToSQL->getSQL(
			array(
				'drop_constraint' => array(
					'actes_batch_files_batch_id_fk' =>
						array(
							'contype' => 'f',
							'conname' => 'actes_batch_files_batch_id_fk',
							'conrelname' => 'actes_batch_files',
							'conkey' => array('batch_id'),
							'confrelname' => 'actes_batches',
							'confkey' => array('id')
						),
					'mail_annuaire_mail_adresse' =>
						array(
							'contype' => 'u',
							'conname' => 'mail_annuaire_mail_adresse',
							'conrelname' => 'mail_annuaire',
							'conkey' => array('mail_address','authority_id'),
							'confrelname' => null,
							'confkey' => array()
						),
					'authorities_pkey' =>
						array(
							'contype' => 'p',
							'conname' => 'authorities_pkey',
							'conrelname' => 'authorities',
							'conkey' => array('id'),
							'confrelname' => null,
							'confkey' => array()
						)
				)
			)
		);
		$this->assertEquals("ALTER TABLE actes_batch_files DROP CONSTRAINT actes_batch_files_batch_id_fk;",$result[0]);
		$this->assertEquals("ALTER TABLE mail_annuaire DROP CONSTRAINT mail_annuaire_mail_adresse;",$result[1]);
		$this->assertEquals("ALTER TABLE authorities DROP CONSTRAINT authorities_pkey;",$result[2]);
	}

	public function testCreateTable(){
		$result = $this->postgreSQLDifferenceToSQL->getSQL(
			array('create_table'=>array(
				"actes_batch_files"=> array(
					"id" => array(
						"data_type" =>  "character varying",
                		"character_maximum_length" => 64,
                		"is_nullable" =>  "NO",
                		"column_default"=> "nextval('actes_batch_files_id_seq'::regclass)"
					)
				)
			))
		);

		$this->assertEquals("CREATE TABLE actes_batch_files (\n    id character varying(64) DEFAULT nextval('actes_batch_files_id_seq'::regclass) NOT NULL\n);",$result[0]);
	}

	public function testDropTable(){
		$result = $this->postgreSQLDifferenceToSQL->getSQL(
			array('drop_table'=>array(
				"actes_batch_files"
			))
		);
		$this->assertEquals("DROP TABLE actes_batch_files;",$result[0]);
	}

	public function testAlterTableAddColumn(){
		$result = $this->postgreSQLDifferenceToSQL->getSQL( array(
				'alter_table' => array(
					'add_column' => array(
						"actes_batch_files"=> array(
							"id" => array(
								"data_type" =>  "character varying",
								"character_maximum_length" => 64,
								"is_nullable" =>  "NO",
								"column_default"=> "nextval('actes_batch_files_id_seq'::regclass)"
							)
						)
					)
				)
		));
		$this->assertEquals("ALTER TABLE actes_batch_files ADD COLUMN id character varying(64) DEFAULT nextval('actes_batch_files_id_seq'::regclass) NOT NULL;",$result[0]);
	}

	public function testAlterTableDropColumn(){
		$result = $this->postgreSQLDifferenceToSQL->getSQL( array(
			'alter_table' => array(
				'drop_column' => array(
					"actes_batch_files" => array("id")
				)
			)
		));
		$this->assertEquals("ALTER TABLE actes_batch_files DROP COLUMN id;",$result[0]);
	}

	public function testAlterTableAlterColumn(){
		$result = $this->postgreSQLDifferenceToSQL->getSQL( array(
			'alter_table' => array(
				'alter_column' => array(
					"actes_batch_files"=> array(
						"id" => array(
							"data_type" =>  "character varying",
							"character_maximum_length" => 64,
							"is_nullable" =>  "NO",
							"column_default"=> "nextval('actes_batch_files_id_seq'::regclass)"
						)
					)
				)
			)
		));
		$this->assertEquals("ALTER TABLE actes_batch_files ALTER COLUMN id character varying(64) DEFAULT nextval('actes_batch_files_id_seq'::regclass) NOT NULL;",$result[0]);
	}

	public function testCreateS2lowDatabase(){
		$s2low_definition = $this->getS2lowDefinition();
		$postgreSQLDifference = new PostgreSQLDifference();
		$result = $postgreSQLDifference->getDifference(array(),$s2low_definition);
		$result = $this->postgreSQLDifferenceToSQL->getSQL($result);
		$this->assertContains("CREATE SEQUENCE actes_batches_id_seq;",$result);
		$this->assertContains("CREATE INDEX atw_tid_idx ON actes_transactions_workflow USING btree (transaction_id)",$result);
		$this->assertContains("ALTER TABLE helios_retour ADD CONSTRAINT helios_retour_authority_id FOREIGN KEY (authority_id) REFERENCES authorities (id);",$result);
	}


}