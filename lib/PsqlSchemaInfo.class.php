<?php
class PsqlSchemaInfo extends SQL {

	public function getDatabaseDefinition() {
		$result = array();

		foreach($this->getSequence() as $sequence_info){
			$result['sequence'][$sequence_info['sequence_name']] = $sequence_info;
		}

		foreach ($this->getTableList() as $table_name) {
			$result['table'][$table_name] = array (
					'column' => $this->getColumn($table_name),
					'index' =>  $this->getIndex($table_name),
					'constraints' => $this->getConstraints($table_name),
			);
		}

		return $result;
	}
	
	public function getTableList(){
		$sql = "SELECT table_name " .
				" FROM information_schema.tables " .
				" WHERE table_type = 'BASE TABLE' " .
				" AND table_schema NOT IN ('pg_catalog', 'information_schema')";
		return $this->queryOneCol($sql);
	}
	
	public function getSequence(){
		$sql = "SELECT * FROM information_schema.sequences";
		return $this->query($sql);
	}
	
	public function getColumn($tableName){
		$r = array();
		$result = $this->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME= ?;",$tableName);
		foreach($result as $line){
			$r[$line['column_name']] = $line;
		}
		return $r;
	}
	
	public function getIndex($tableName){
		$result = array();
		$indexDefinition = array();
		$r = $this->query("SELECT * FROM pg_indexes WHERE schemaname='public' AND tablename=?",$tableName);
		
		foreach ($r as $line){
			$result[$line['indexname']] = $line['indexdef'];
		}
		return $result;
	}
	
	public function getConstraints($tableName){
		$result = array();
		$sql = "SELECT constraint_column_usage.*,table_constraints.constraint_type, check_clause, key_column_usage.column_name as constraint_column " .
				" FROM information_schema.table_constraints " .
				" JOIN information_schema.constraint_column_usage " .
				" ON table_constraints.constraint_name = constraint_column_usage.constraint_name ".
				" LEFT JOIN information_schema.check_constraints " .
				" ON table_constraints.constraint_name= check_constraints.constraint_name ".
				" LEFT JOIN information_schema.key_column_usage " .
				" ON key_column_usage.constraint_name=table_constraints.constraint_name". 
				" WHERE table_constraints.table_name=?";
		$r = $this->query($sql,$tableName);
		foreach($r as $line){
			$result[$line['constraint_name']][] = $line;
		}
		return $result;
	}
	
	
	
}

/*



Les contraintes (unique) crée des index automatiquement

Il faut donc d'abord créer les contraintes AVANT Les index !

# http://solaimurugan.blogspot.fr/2010/10/list-out-all-forien-key-constraints.html
SELECT tc.constraint_name,
tc.constraint_type,
tc.table_name,
kcu.column_name,
tc.is_deferrable,
tc.initially_deferred,
rc.match_option AS match_type,

rc.update_rule AS on_update,
rc.delete_rule AS on_delete,
ccu.table_name AS references_table,
ccu.column_name AS references_field
FROM information_schema.table_constraints tc

LEFT JOIN information_schema.key_column_usage kcu
ON tc.constraint_catalog = kcu.constraint_catalog
AND tc.constraint_schema = kcu.constraint_schema
AND tc.constraint_name = kcu.constraint_name

LEFT JOIN information_schema.referential_constraints rc
ON tc.constraint_catalog = rc.constraint_catalog
AND tc.constraint_schema = rc.constraint_schema
AND tc.constraint_name = rc.constraint_name

LEFT JOIN information_schema.constraint_column_usage ccu
ON rc.unique_constraint_catalog = ccu.constraint_catalog
AND rc.unique_constraint_schema = ccu.constraint_schema
AND rc.unique_constraint_name = ccu.constraint_name

WHERE lower(tc.constraint_type) in ('primary key','unique','foreign key')
 *
 */