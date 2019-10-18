<?php

class PostgreSQLDifferenceTest extends PHPUnit_Framework_TestCase {

	/** @var  PostgreSQLDifference */
	private $postgreSQLDifference;

	protected function setUp() : void {
		parent::setUp();
		$this->postgreSQLDifference = new PostgreSQLDifference();
	}

	private function getS2lowDefinition(){
		return json_decode(file_get_contents(__DIR__."/../../../db/s2low.sql.json"),true);
	}

	public function testEmpty(){
		$this->assertEmpty($this->postgreSQLDifference->getDifference(array(),array()));
	}

	public function testSequence(){
		$db1 = array('sequence'=>array('a','b','c'));
		$db2 = array('sequence'=>array('a','c','d'));
		$result = $this->postgreSQLDifference->getDifference($db1,$db2);
		$this->assertEquals(array('b'),$result['drop_sequence']);
		$this->assertEquals(array('d'),$result['create_sequence']);
	}

	public function testCreateIndex(){
		$db1 = array();
		$db2 = array('index'=>array('a'=>array('tablename'=>'foo','indexdef'=>'bar')));
		$result = $this->postgreSQLDifference->getDifference($db1,$db2);
		$this->assertEquals(array('a'=>array('tablename'=>'foo','indexdef'=>'bar')),$result['create_index']);
	}

	public function testDropIndex(){
		$db1 = array('index'=>array('a'=>array('tablename'=>'foo','indexdef'=>'bar')));
		$db2 = array();
		$result = $this->postgreSQLDifference->getDifference($db1,$db2);
		$this->assertEquals(array('a'),$result['drop_index']);
	}

	public function testModifIndex(){
		$db1 = array('index'=>array('a'=>array('tablename'=>'foo','indexdef'=>'baz')));
		$db2 = array('index'=>array('a'=>array('tablename'=>'foo','indexdef'=>'bar')));
		$result = $this->postgreSQLDifference->getDifference($db1,$db2);
		$this->assertEquals(array('a'),$result['drop_index']);
		$this->assertEquals(array('a'=>array('tablename'=>'foo','indexdef'=>'bar')),$result['create_index']);
	}

	public function testCreateConstraint(){
		$constraint_list =  array('contype'=>'p','conrelname'=>'foo','conkey'=>array('id'),'confrelname'=>null,'confkey'=>array());
		$db1 = array();
		$db2 = array('constraint'=>array('table1' => array('foo' =>$constraint_list)));
		$result = $this->postgreSQLDifference->getDifference($db1,$db2);
		$this->assertEquals($constraint_list,$result['create_constraint'][0]);
	}

	public function testDeleteConstraint(){
		$constraint_list = array('contype'=>'p','conrelname'=>'foo','conkey'=>array('id'),'confrelname'=>null,'confkey'=>array());
		$db1 = array('constraint'=>array('table1' => array('foo' =>$constraint_list)));
		$db2 = array();
		$result = $this->postgreSQLDifference->getDifference($db1,$db2);
		$this->assertEquals($constraint_list,$result['drop_constraint'][0]);
	}

	public function testModifConstraint(){
		$constraint_list_1 = array('contype'=>'p','conrelname'=>'foo','conkey'=>array('id'),'confrelname'=>null,'confkey'=>array());
		$constraint_list_2 = array('contype'=>'p','conrelname'=>'foo','conkey'=>array('new_id'),'confrelname'=>null,'confkey'=>array());
		$db1 = array('constraint'=>array('table1' => array('foo' =>$constraint_list_1)));
		$db2 = array('constraint'=>array('table1' => array('foo' =>$constraint_list_2)));
		$result = $this->postgreSQLDifference->getDifference($db1,$db2);
		$this->assertEquals($constraint_list_2,$result['create_constraint'][0]);
		$this->assertEquals($constraint_list_1,$result['drop_constraint'][0]);
	}

	public function testCreateTable(){
		$table_list = array('test'=>array('id'=>array('data_type'=>'integer','character_maximum_length'=>null,'is_nullable'=>'no','column_default'=>null)));
		$db1 = array();
		$db2 = array('table'=>$table_list);
		$result = $this->postgreSQLDifference->getDifference($db1,$db2);
		$this->assertEquals($table_list,$result['create_table']);
	}

	public function testDropTable(){
		$table_list = array('test'=>array('id'=>array('data_type'=>'integer','character_maximum_length'=>null,'is_nullable'=>'no','column_default'=>null)));
		$db1 = array('table'=>$table_list);
		$db2 = array();
		$result = $this->postgreSQLDifference->getDifference($db1,$db2);
		$this->assertEquals(array('test'),$result['drop_table']);
	}

	public function testUpdateTableAddColumn(){
		$table_list = array('test'=>array('id'=>array('data_type'=>'integer','character_maximum_length'=>null,'is_nullable'=>'no','column_default'=>null)));
		$db1 = array('table'=>$table_list);
		$col1_def = array('data_type'=>'text','character_maximum_length'=>null,'is_nullable'=>'no','column_defautl'=>null);
		$table_list['test']['col1'] = $col1_def;
		$db2 = array('table'=>$table_list);
		$result = $this->postgreSQLDifference->getDifference($db1,$db2);
		$this->assertEquals($col1_def,$result['alter_table']['add_column']['test']['col1']);
	}

	public function testUpdateTableDropColumn(){
		$table_list = array('test'=>array('id'=>array('data_type'=>'integer','character_maximum_length'=>null,'is_nullable'=>'no','column_default'=>null)));
		$db2 = array('table'=>$table_list);
		$col1_def = array('data_type'=>'text','character_maximum_length'=>null,'is_nullable'=>'no','column_defautl'=>null);
		$table_list['test']['col1'] = $col1_def;
		$db1 = array('table'=>$table_list);
		$result = $this->postgreSQLDifference->getDifference($db1,$db2);
		$this->assertEquals(array('col1'),$result['alter_table']['drop_column']['test']);
	}

	public function testAlterTableAlterColumn(){
		$table_list = array('test'=>array('id'=>array('data_type'=>'integer','character_maximum_length'=>null,'is_nullable'=>'no','column_default'=>null)));
		$db1 = array('table'=>$table_list);
		$table_list['test']['id']['column_default'] = 42;
		$db2 = array('table'=>$table_list);
		$result = $this->postgreSQLDifference->getDifference($db1,$db2);
		$this->assertEquals($table_list['test']['id'],$result['alter_table']['alter_column']['test']['id']);
	}

	public function testSameDefinition(){
		$db_def = $this->getS2lowDefinition();
		$this->assertEmpty($this->postgreSQLDifference->getDifference($db_def,$db_def));
	}

	public function testCreateDatabase(){
		$db_def = $this->getS2lowDefinition();
		$result = $this->postgreSQLDifference->getDifference(array(),$db_def);
		$this->assertArrayHasKey('create_sequence',$result);
		$this->assertArrayHasKey('create_index',$result);
		$this->assertArrayHasKey('create_constraint',$result);
		$this->assertArrayHasKey('create_table',$result);
		$this->assertArrayNotHasKey('drop_sequence',$result);
		$this->assertArrayNotHasKey('drop_index',$result);
		$this->assertArrayNotHasKey('drop_constraint',$result);
		$this->assertArrayNotHasKey('drop_table',$result);
		$this->assertArrayNotHasKey('alter_table',$result);
	}

	public function testDropDatabase(){
		$db_def = $this->getS2lowDefinition();
		$result = $this->postgreSQLDifference->getDifference($db_def,array());
		$this->assertArrayNotHasKey('create_sequence',$result);
		$this->assertArrayNotHasKey('create_index',$result);
		$this->assertArrayNotHasKey('create_constraint',$result);
		$this->assertArrayNotHasKey('create_table',$result);
		$this->assertArrayHasKey('drop_sequence',$result);
		$this->assertArrayHasKey('drop_index',$result);
		$this->assertArrayHasKey('drop_constraint',$result);
		$this->assertArrayHasKey('drop_table',$result);
		$this->assertArrayNotHasKey('alter_table',$result);
	}


	public function testWithPublicNamespace(){
		$db_def_cible = $this->getS2lowDefinition();
		$db_def_actual = $db_def_cible;
		$db_def_actual['index']['actes_transactions_workflow_date_idx']['indexdef'] = 'CREATE INDEX actes_transactions_workflow_date_idx ON public.actes_transactions_workflow USING btree (date)';
		$diff = $this->postgreSQLDifference->getDifference($db_def_actual,$db_def_cible);
		$this->assertEmpty($diff);
	}


}