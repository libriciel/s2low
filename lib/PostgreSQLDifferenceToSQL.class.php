<?php

class PostgreSQLDifferenceToSQL {

	public function getSQL(array $difference){
		$difference = $this->normalizeDBDefinition($difference);
		return array_merge(
				$this->dropConstraint($difference['drop_constraint']),
				$this->dropIndex($difference['drop_index']),
				$this->createSequence($difference['create_sequence']),
				$this->createTable($difference['create_table']),
				$this->alterTable($difference['alter_table']),
				$this->dropTable($difference['drop_table']),
				$this->dropSequence($difference['drop_sequence']),
				$this->createIndex($difference['create_index']),
				$this->createConstraint($difference['create_constraint'])
			);
	}

	private function alterTable($alter_list){
		$sql = array();
		foreach($alter_list['add_column'] as $table_name => $col_def){
			foreach($col_def as $col_name => $col_info){
				$sql[] = "ALTER TABLE $table_name ADD COLUMN ".$this->getColumnDefinition($col_name,$col_info).";";
			}
		}
		foreach($alter_list['drop_column'] as $table_name => $col_def){
			foreach($col_def as $col_name => $col_info){
				$sql[] = "ALTER TABLE $table_name DROP COLUMN $col_info;";
			}
		}
		foreach($alter_list['alter_column'] as $table_name => $col_def){
			foreach($col_def as $col_name => $col_info){
				$sql[] = "ALTER TABLE $table_name ALTER COLUMN ".$this->getColumnDefinition($col_name,$col_info).";";
			}
		}
		return $sql;
	}

	private function dropTable($table_list){
		$sql = array();
		foreach($table_list as $table_name){
			$sql[] = "DROP TABLE $table_name;";
		}
		return $sql;
	}

	private function createTable($table_list){
		$result=array();
		foreach($table_list as $table_name => $table_info){
			$column = array();
			foreach($table_info as $column_id => $column_info){
				$column[] = $this->getColumnDefinition($column_id,$column_info);
			}
			$column_def = implode(",\n    ",$column);
			$result[] = "CREATE TABLE $table_name (\n    $column_def\n);" ;
		}
		return $result;
	}

	private function getColumnDefinition($column_name,$column_info){
		$sql =  "{$column_name} {$column_info['data_type']}";
		if ($column_info['character_maximum_length']){
			$sql.="({$column_info['character_maximum_length']})";
		}
		if ($column_info['column_default']){
			$sql.= " DEFAULT {$column_info['column_default']}";
		}
		if ($column_info['is_nullable'] == 'NO'){
			$sql.=" NOT NULL";
		}
		return $sql;
	}


	private function createConstraint($constraint_list){
		uasort($constraint_list, function ($a,$b){
			$contype = array('p'=>0,'u'=>1,'f'=>2);
			if ($a == $b) {
				return 0;
			}
			return ($contype[$a['contype']] < $contype[$b['contype']]) ? -1 : 1;
		});
		$result = array();
		foreach($constraint_list as $constraint_info){
			if ($constraint_info['contype'] == 'p'){
				$pk_all_col = implode(',',$constraint_info['conkey']);
				$result[] = "ALTER TABLE {$constraint_info['conrelname']} ADD CONSTRAINT {$constraint_info['conname']} PRIMARY KEY ($pk_all_col);";
			}
			if ($constraint_info['contype'] == 'u'){
				$pk_all_col = implode(',',$constraint_info['conkey']);
				$result[] = "ALTER TABLE {$constraint_info['conrelname']} ADD CONSTRAINT {$constraint_info['conname']} UNIQUE ($pk_all_col);";
			}
			if ($constraint_info['contype'] == 'f'){
				$pk_all_col = implode(',',$constraint_info['conkey']);
				$fk_all_col = implode(',',$constraint_info['confkey']);
				$result[] = "ALTER TABLE {$constraint_info['conrelname']} ADD CONSTRAINT {$constraint_info['conname']} FOREIGN KEY ($pk_all_col) REFERENCES {$constraint_info['confrelname']} ($fk_all_col);";
			}
		}
		return $result;
	}

	private function dropConstraint($constraint_list){
		uasort($constraint_list, function ($a,$b){
			$contype = array('f'=>0,'u'=>1,'p'=>2);
			if ($a == $b) {
				return 0;
			}
			return ($contype[$a['contype']] < $contype[$b['contype']]) ? -1 : 1;
		});
		$result = array();
		foreach($constraint_list as $constraint_info){
			$result[] = "ALTER TABLE {$constraint_info['conrelname']} DROP CONSTRAINT {$constraint_info['conname']};";
		}
		return $result;
	}

	private function createIndex($index_list){
		$sql = array();
		foreach($index_list as $index_properties){
			$sql[] = $index_properties['indexdef'];
		}
		return $sql;
	}

	private function dropIndex($index_list){
		$sql = array();
		foreach($index_list as $index_name){
			$sql[] = "DROP INDEX $index_name;";
		}
		return $sql;
	}

	private function createSequence($sequence_list){
		$sql = array();
		foreach($sequence_list as $sequence_name){
			$sql[] = "CREATE SEQUENCE $sequence_name;";
		}
		return $sql;
	}

	private function dropSequence($sequence_list){
		$sql = array();
		foreach($sequence_list as $sequence_name){
			$sql[] = "DROP SEQUENCE $sequence_name;";
		}
		return $sql;
	}

	private function normalizeDBDefinition(array $difference){
		foreach(
			array(
				'drop_constraint',
				'drop_index',
				'create_sequence',
				'create_table',
				'alter_table',
				'drop_table',
				'drop_sequence',
				'create_index',
				'create_constraint'
			) as $type) {
			if (!isset($difference[$type])) {
				$difference[$type] = array();
			}
		}
		foreach(array('add_column','drop_column','alter_column') as $type){
			if (!isset($difference['alter_table'][$type])) {
				$difference['alter_table'][$type] = array();
			}
		}
		return $difference;
	}

}