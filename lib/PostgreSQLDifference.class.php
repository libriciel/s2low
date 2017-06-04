<?php

class PostgreSQLDifference {


	public function getDifference(array $base_definition,array $base_cible_definition){

		$base_definition = $this->normalizeDBDefinition($base_definition);
		$base_cible_definition = $this->normalizeDBDefinition($base_cible_definition);

		$result = array();

		$result['create_sequence'] = $this->createSequence($base_definition,$base_cible_definition);
		$result['drop_sequence'] = $this->dropSequence($base_definition,$base_cible_definition);

		$result['create_index'] = $this->createIndex($base_definition,$base_cible_definition);
		$result['drop_index'] = $this->dropIndex($base_definition,$base_cible_definition);

		$result['create_constraint'] = $this->createConstraint($base_definition,$base_cible_definition);
		$result['drop_constraint'] = $this->dropConstraint($base_definition,$base_cible_definition);

		$result['create_table'] = $this->createTable($base_definition,$base_cible_definition);
		$result['drop_table'] = $this->dropTable($base_definition,$base_cible_definition);

		$result['alter_table'] = $this->updateTable($base_definition,$base_cible_definition);

		return $this->deleteEmptyValue($result);
	}

	private function createSequence(array $base_definition,array $base_cible_definition){
		return array_values(array_diff($base_cible_definition['sequence'],$base_definition['sequence']));
	}

	private function dropSequence(array $base_definition,array $base_cible_definition){
		return array_values(array_diff($base_definition['sequence'],$base_cible_definition['sequence']));
	}

	private function createTable(array $base_definition,array $base_cible_definition){
		$result = array();
		foreach($base_cible_definition['table'] as $index_name => $index_param){
			if (empty($base_definition['table'][$index_name])){
				$result[$index_name] = $index_param;
			}
		}
		return $result;
	}

	private function dropTable(array $base_definition,array $base_cible_definition){
		$result = array();
		foreach($base_definition['table'] as $index_name => $index_param){
			if (empty($base_cible_definition['table'][$index_name])){
				$result[] = $index_name;
			}
		}
		return $result;
	}

	private function updateTable(array $base_definition,array $base_cible_definition){
		$result = array();
		foreach($base_definition['table'] as $table_name => $table_definition){

			if (empty($base_cible_definition['table'][$table_name])){
				continue;
			}
			if ($base_definition['table'][$table_name] == $base_cible_definition['table'][$table_name]){
				continue;
			}
			$result['add_column'][$table_name] = $this->addColumn($base_definition['table'][$table_name],$base_cible_definition['table'][$table_name]);
			$result['drop_column'][$table_name] = $this->dropColumn($base_definition['table'][$table_name],$base_cible_definition['table'][$table_name]);
			$result['alter_column'][$table_name] = $this->alterColumn($base_definition['table'][$table_name],$base_cible_definition['table'][$table_name]);
		}
		return $this->deleteEmptyValue($result);

	}

	private function addColumn($table_def,$table_cible_def){
		$result = array();
		foreach($table_cible_def as $col_name => $col_definition){
			if (empty($table_def[$col_name])){
				$result[$col_name] = $col_definition;
			}
		}
		return $result;
	}

	private function dropColumn($table_def,$table_cible_def){
		$result = array();
		foreach($table_def as $col_name => $col_definition){
			if (empty($table_cible_def[$col_name])){
				$result[] = $col_name;
			}
		}
		return $result;
	}

	private function alterColumn($table_def,$table_cible_def){
		$result = array();
		foreach($table_def as $col_name => $col_definition){

			if (empty($table_cible_def[$col_name])){
				continue;
			}
			if ($table_def[$col_name] == $table_cible_def[$col_name]){
				continue;
			}
			$result[$col_name] = $table_cible_def[$col_name];
		}
		return $result;
	}

	private function createConstraint(array $base_definition,array $base_cible_definition){



		$result = array();
		foreach($base_cible_definition['constraint'] as $index_name => $index_param){
			if (empty($base_definition['constraint'][$index_name])){
				$result[] = $index_param;
				continue;
			}
			if (! $this->isConstraintEqual($base_definition['constraint'][$index_name],$base_cible_definition['constraint'][$index_name])){
				$result[] = $index_param;
			}
		}
		return $result;
	}

	private function dropConstraint(array $base_definition,array $base_cible_definition){
		$result = array();
		foreach($base_definition['constraint'] as $index_name => $index_param){
			if (empty($base_cible_definition['constraint'][$index_name])){
				$result[] = $index_param;
				continue;
			}
			if (! $this->isConstraintEqual($base_definition['constraint'][$index_name],$base_cible_definition['constraint'][$index_name])){
				$result[] = $index_param;
			}
		}
		return $result;
	}

	private function isConstraintEqual($constraint1,$constraint2){
		return $constraint1 == $constraint2;
	}

	private function createIndex(array $base_definition,array $base_cible_definition){
		$result = array();
		foreach($base_cible_definition['index'] as $index_name => $index_param){
			if (empty($base_definition['index'][$index_name])){
				$result[$index_name] = $index_param;
				continue;
			}
			if (! $this->isIndexEqual($base_definition['index'][$index_name],$base_cible_definition['index'][$index_name])){
				$result[$index_name] = $index_param;
			}
		}
		return $result;
	}

	private function isIndexEqual($index1,$index2){
		return $index1 == $index2;
	}

	private function dropIndex(array $base_definition,array $base_cible_definition){
		$result = array();
		foreach($base_definition['index'] as $index_name => $index_param){
			if (empty($base_cible_definition['index'][$index_name])){
				$result[] = $index_name;
				continue;
			}
			if (! $this->isIndexEqual($base_definition['index'][$index_name],$base_cible_definition['index'][$index_name])){
				$result[] = $index_name;
			}
		}
		return $result;
	}


	private function normalizeDBDefinition(array $definition){
		foreach(array('sequence','table','constraint','index') as $type) {
			if (!isset($definition[$type])) {
				$definition[$type] = array();
			}
		}
		return $definition;
	}

	private function deleteEmptyValue(array $result){
		foreach($result as $key => $value){
			if ($value == array()){
				unset($result[$key]);
			}
		}
		return $result;
	}
}
