<?php
class PostgreSQLSchemaInfo extends SQL {

	public function getDatabaseDefinition() {
		$result['sequence'] = $this->getSequence();
		$result['table'] = $this->getTable();
		$result['constraint'] = $this->getConstraints($result['table']);
		$result['index'] = $this->getIndex($result['constraint']);
		return $result;
	}

	private function getSequence(){
		$sql = "SELECT sequence_name FROM information_schema.sequences ORDER BY sequence_name";
		return $this->queryOneCol($sql);
	}

	private function getTable(){
		$result = array();
		$sql = "SELECT 
					table_name, 
					column_name,
					data_type,
					character_maximum_length,
					is_nullable,
					column_default 
				FROM information_schema.columns
				WHERE table_schema='public' 
				ORDER BY table_name,ordinal_position";
		foreach($this->query($sql) as $line){
			$table_name = $line['table_name'];
			$column_name = $line['column_name'];
			unset($line['table_name']);
			unset($line['column_name']);
			$result[$table_name][$column_name] = $line;
 		};
		return $result;
	}

	private function getIndex(array $constraint){
		$sql = "SELECT tablename,indexname,indexdef FROM pg_indexes WHERE schemaname='public'";
		$result = array();
		foreach($this->query($sql) as $line){
			if ( isset($constraint[$line['tablename']][$line['indexname']])){
				continue;
			}
			$indexname = $line['indexname'];
			unset($line['indexname']);
			$result[$indexname] = $line;
		}
		return $result;
	}

	private function getConstraints(array $table){
		$sql = "SELECT 
						pg_constraint.conname,
						pg_constraint.contype,
						pg_class_2.relname as conrelname, 
						array_to_json(pg_constraint.conkey) as conkey,
						pg_class.relname as confrelname, 
						array_to_json(pg_constraint.confkey) 
						as confkey  
				FROM pg_constraint 
				LEFT JOIN pg_class ON pg_constraint.confrelid=pg_class.oid
				LEFT JOIN pg_class as pg_class_2 ON pg_constraint.conrelid=pg_class_2.oid 
				JOIN pg_namespace ON pg_constraint.connamespace=pg_namespace.oid 
				WHERE pg_namespace.nspname='public'";

		$result = array();
		foreach($this->query($sql) as $line){
			$line['conkey'] = $this->convertConkeyToConkeyname($line['conkey'],$table, $line['conrelname']);
			$line['confkey'] = $this->convertConkeyToConkeyname($line['confkey'],$table,$line['confrelname']);
			$result[$line['conrelname']][$line['conname']] = $line;
		}
		return $result;
	}

	private function convertConkeyToConkeyname($json_encoded_conkey,array $table, $table_name){
		$conkeyname = array();
		$conkey_array = json_decode($json_encoded_conkey ?? '[]');  //Quickfix passage en 8.0
		if (! is_array($conkey_array)){
			return array();
		}
		foreach($conkey_array as $conkey){
			$col_name = array_keys($table[$table_name])[$conkey - 1];
			$conkeyname[] = $col_name;
		}
		return $conkeyname;
	}
}
