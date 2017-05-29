<?php

class PsqlDiff {
	
	/**
	 * Return an array of SQL commands to eliminate differences 
	 * with two PostgreSQL schema definition
	 * The command must be run on $db1
	 *  
	 *  Limitation :
	 *  Sequence : verify and create only sequence created by CREATE SEQUENCE xxx; without min value, max value, descending sequence, specifique increment
	 *  Foreign key : on delete XYZ is not implemented
	 *  General : table and foreign key are not created in the right order
	 *  Primary key modification : some sql command "alter column" who did nothing
	 *  Index modification has no effect !
	 *  
	 * @param array $db1 the shema definition of the changing database 
	 * @param array $db2 the schema defintion of the imutable database
	 * @return array the list of sql commands
	 */
	public function diff(array $db1,array $db2){
		$db1 = $this->normalizeDBDefinition($db1);
		$db2 = $this->normalizeDBDefinition($db2);
		$sql = array();
		$sql = array_merge($sql,$this->createSequence($db1, $db2));
		
		$table_create_sql = $this->createTable($db1, $db2);

		usort($table_create_sql['constraint'],function($a,$b){

			if (preg_match("#CREATE UNIQUE INDEX#",$a)){
				return -1;
			}
			if (preg_match("#CREATE UNIQUE INDEX#",$b)){
				return 1;
			}

			if (preg_match("#ADD PRIMARY KEY#",$a)){
				return -1;
			}
			if (preg_match("#ADD PRIMARY KEY#",$b)){
				return 1;
			}

			return 0;
		});

		$sql = array_merge($sql,$table_create_sql['table']);
		$sql = array_merge($sql,$table_create_sql['index']);
		$sql = array_merge($sql,$this->dropTable($db1, $db2));

		$table_change_sql = $this->changeTable($db1, $db2);


		usort($table_change_sql['constraint'],function($a,$b){
			if (preg_match("#DROP INDEX#",$a)){
				return -1;
			}
			if (preg_match("#DROP INDEX#",$b)){
				return 1;
			}
			if (preg_match("#CREATE UNIQUE INDEX#",$a)){
				return -1;
			}
			if (preg_match("#CREATE UNIQUE INDEX#",$b)){
				return 1;
			}

			if (preg_match("#ADD PRIMARY KEY#",$a)){
				return -1;
			}
			if (preg_match("#ADD PRIMARY KEY#",$b)){
				return 1;
			}

			return 0;
		});

		$sql = array_merge($sql,$table_change_sql['column']);		
		$sql = array_merge($sql,$table_create_sql['constraint']);
		$sql = array_merge($sql,$table_change_sql['constraint']);
		$sql = array_merge($sql,$this->dropSequence($db1, $db2));
		
		return $sql;
	}
	
	private function normalizeDBDefinition(array $db){
		if (! isset($db['sequence'])){
			$db['sequence'] = array();
		}
		if (! isset($db['table'])){
			$db['table'] = array();
		}
		return $db;
	}
	
	private function createSequence(array $db1, array $db2){
		$sql = array();
		foreach($db2['sequence'] as $sequence_name => $sequence_info){
			if (empty($db1['sequence'])){
				$sql[] = "CREATE SEQUENCE $sequence_name;";
			}
		}
		return $sql;
	}
	
	private function dropSequence(array $db1, array $db2){
		$sql = array();
		foreach($db1['sequence'] as $sequence_name => $sequence_info){
			if (empty($db2['sequence'][$sequence_name])){
				$sql[] = "DROP SEQUENCE $sequence_name;";
			}
		}
		return $sql;
	}
	
	private function createTable(array $db1,array $db2){
		$sql = array('table'=>array(),'constraint'=>array(),'index'=>array());
		foreach($db2['table'] as $table_name => $table_info){
			if (empty($db1['table'][$table_name])){
				
				$column = array();
				foreach($table_info['column'] as $column_id => $column_info){
					$column[] = $this->getColumnDefinition($column_info);
				}
				$column_def = implode(",\n    ",$column);
				$sql['table'][] = "CREATE TABLE $table_name (\n    $column_def\n);" ;
				
				$sql['constraint'] = array_merge($sql['constraint'],$this->getConstraints($table_name,array(),$table_info['constraints']));
				
				//Prevent double indexing of constraints !
				foreach($table_info['constraints'] as $constraint_name => $constraint_info){
					unset($table_info['index'][$constraint_name]);
				}
				$sql['index']  = array_merge($sql['index'],$this->getIndex($table_name, array(), $table_info['index']));
				
			}
		}
		return $sql;
	}
	
	private function getColumnDefinition($column_info){
		$sql =  "{$column_info['column_name']} {$column_info['data_type']}";
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
	
	private function dropTable(array $db1,array $db2){
		$sql = array();
		foreach($db1['table'] as $table_name => $table_info){
			if (empty($db2['table'][$table_name])){
				$sql[] = "DROP TABLE $table_name;";
			}
		}
		return $sql;
	}
	
	private function changeTable(array $db1,array $db2){
		$sql = array('column'=>array(),'constraint'=>array(),'index'=>array());
		foreach($db1['table'] as $table_name => $table_info){			
			if (empty($db2['table'][$table_name])){
				continue;
			}
			$table1 = $db1['table'][$table_name];
			$table2 = $db2['table'][$table_name];
			$sql['column'] = array_merge($sql['column'],$this->addColumn($table_name,$table1,$table2));
			$sql['column'] = array_merge($sql['column'],$this->dropColumn($table_name,$table1,$table2));
			$sql['column'] = array_merge($sql['column'],$this->editColumn($table_name,$table1,$table2));

			$sql['constraint'] = array_merge($sql['constraint'],$this->getConstraints($table_name, $table1['constraints'], $table2['constraints']));


			$sql['constraint'] = array_merge($sql['constraint'],$this->getIndex($table_name, $table1['index'], $table2['index']));

		}
		return $sql;
	}

	private function addColumn($table_name,array $table1,array $table2){
		$sql = array();
		foreach($table2['column'] as $column_name => $column_info){
			if (empty($table1['column'][$column_name])){
				$sql[] = "ALTER TABLE $table_name ADD COLUMN ".$this->getColumnDefinition($column_info).";";
			}
		}
		return $sql;
	}
	
	private function dropColumn($table_name,array $table1, array $table2){
		$sql = array();
		foreach($table1['column'] as $column_name => $column_info){
			if (empty($table2['column'][$column_name])){
				$sql[] = "ALTER TABLE $table_name DROP COLUMN $column_name;";
			}
		}
		return $sql;
	}
	
	private function getConstraints($table_name,$constraint1,$constraint2){

		$sql = array();
			
		foreach($constraint2 as $constraint_name => $constraint_info){
			if(empty($constraint1[$constraint_name])){
				$sql[] = $this->addConstraint($table_name, $constraint_info);
			}
		}		
		foreach($constraint1 as $constraint_name => $constraint_info){
			if (empty($constraint2[$constraint_name])){
				$sql[] = $this->dropConstraint($table_name, $constraint_name);
			}
		}
		foreach($constraint1 as $constraint_name => $constraint_info){
			if (empty($constraint2[$constraint_name])){
				continue;
			}
			if ($this->constraintIsDifferent($constraint1[$constraint_name],$constraint2[$constraint_name])){
				$sql[] = $this->dropConstraint($table_name, $constraint_name);
				$sql[] = $this->addConstraint($table_name, $constraint2[$constraint_name]);
			}
		}



		return $sql;
	}
	
	private function addConstraint($table_name,$constraint_info){
		if ($constraint_info[0]['constraint_type'] == 'CHECK'){
			$sql = "ALTER TABLE $table_name ADD CONSTRAINT {$constraint_info[0]['constraint_name']} {$constraint_info[0]['constraint_type']} {$constraint_info[0]['check_clause']};";
		} else if ($constraint_info[0]['constraint_type'] == 'PRIMARY KEY'){
			foreach($constraint_info as $i => $info){			
				$pk_col[] = $info['column_name'];
			}
			$pk_all_col = implode(',',$pk_col);
			$sql = "ALTER TABLE $table_name ADD PRIMARY KEY ($pk_all_col);";
		} else if ($constraint_info[0]['constraint_type'] == 'FOREIGN KEY'){
			$sql = "ALTER TABLE $table_name ADD CONSTRAINT {$constraint_info[0]['constraint_name']} FOREIGN KEY ({$constraint_info[0]['constraint_column']}) REFERENCES {$constraint_info[0]['table_name']}({$constraint_info[0]['column_name']});";
		} else if ($constraint_info[0]['constraint_type'] == 'UNIQUE'){
			foreach($constraint_info as $i => $info){
				$pk_col[] = $info['constraint_column'];
			}
			
			$pk_result =array();
			$pk_col_result = array();
			foreach($pk_col as $pk_one_col){
				if (! isset($pk_result[$pk_one_col])){
					$pk_result[$pk_one_col] = true;
					$pk_col_result[] = $pk_one_col;
				}
			}
			$pk_all_col = implode(',',$pk_col_result);
			$sql = "ALTER TABLE $table_name ADD CONSTRAINT {$constraint_info[0]['constraint_name']} UNIQUE ($pk_all_col);";
		} 
		return $sql;
	}
	
	private function dropConstraint($table_name,$constraint_name){
		return "ALTER TABLE $table_name DROP CONSTRAINT $constraint_name;";
	}
	
	private function constraintIsDifferent($constraint1,$constraint2){
		foreach($constraint1 as $num => $constrainte_info){
			if (empty($constraint2[$num])){
				return true;
			}
			foreach($constrainte_info as $key => $value){
				if (in_array($key,array('constraint_catalog','table_catalog'))){
					continue;
				}
				if ($constraint2[$num][$key] != $value){
					return true;
				}
			}
		}
		foreach($constraint2 as $num => $constraint_info){
			if (empty($constraint1[$num])){
				return true;
			}
		}
		return false;
	}
	
	private function editColumn($table_name,$table1,$table2){
		$sql = array();
		foreach($table1['column'] as $column_name => $column_info){
			if (empty($table2['column'][$column_name])){
				continue;
			}
			$column_info_1 = $table1['column'][$column_name];
			$column_info_2 = $table2['column'][$column_name];
			if ($this->columnIsDifferent($column_info_1,$column_info_2)){
				$sql[] = "ALTER TABLE $table_name ALTER COLUMN ".$this->getColumnDefinition($column_info_2).";";
			}
		}
		return $sql;
	}
	
	private function columnIsDifferent($column_info_1,$column_info_2){
		foreach(array('data_type','character_maximum_length','column_default','is_nullable') as $info){
			if ($column_info_1[$info] != $column_info_2[$info]){
				return true;
			}
		}
		return false;
	}
	
	private function getIndex($table_name, $index1, $index2){
		$sql = array();
		foreach($index2 as $index_name => $index_definition){
			if (empty($index1[$index_name])){
				$sql[] = $index_definition.";";
			}
		}
		foreach($index1 as $index_name => $index_definition){
			if (empty($index2[$index_name])){
				$sql[] = "DROP INDEX $index_name;";
			}
		}
		foreach($index1 as $index_name => $index_definition){
			if (empty($index2[$index_name])){
				continue;
			}
			if ($index1[$index_name] != $index2[$index_name]){
				//Y a un bug :
				//CREATE UNIQUE INDEX mail_user_groupe_unique ON mail_user_groupe USING btree (id_groupe, id_user)
				//CREATE UNIQUE INDEX mail_user_groupe_unique ON mail_user_groupe USING btree (id_user, id_groupe)
				// sont considéré comme différent...
				// et du coup, on peut pas supprimer les index unique qui sont par ailleur référencé par une clé étrangère...
				//$sql[] = "DROP INDEX $index_name;";
				//$sql[] = $index2[$index_name].";";
			}
		}

		return $sql;
	}
	
	
}