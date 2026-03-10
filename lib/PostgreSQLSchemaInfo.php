<?php

namespace S2lowLegacy\Lib;

class PostgreSQLSchemaInfo extends SQL
{
    public function getDatabaseDefinition()
    {
        $result['sequence'] = $this->getSequence();
        $result['table'] = $this->getTable();
        $result['constraint'] = $this->getConstraints();
        $result['index'] = $this->getIndex($result['constraint']);
        return $result;
    }

    private function getSequence()
    {
        $sql = "SELECT sequence_name FROM information_schema.sequences ORDER BY sequence_name";
        return $this->queryOneCol($sql);
    }

    private function getTable(): array
    {
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
        foreach ($this->query($sql) as $line) {
            $table_name = $line['table_name'];
            $column_name = $line['column_name'];
            unset($line['table_name']);
            unset($line['column_name']);
            $result[$table_name][$column_name] = $line;
        };
        return $result;
    }

    private function getIndex(array $constraint): array
    {
        $sql = "SELECT tablename,indexname,indexdef FROM pg_indexes WHERE schemaname='public'";
        $result = array();
        foreach ($this->query($sql) as $line) {
            if (isset($constraint[$line['tablename']][$line['indexname']])) {
                continue;
            }
            $indexname = $line['indexname'];
            unset($line['indexname']);
            $result[$indexname] = $line;
        }
        return $result;
    }

    private function getTableColumnsByAttnum(string $tableName): array
    {
        $sql = "
        SELECT attnum, attname
        FROM pg_attribute
        JOIN pg_class ON pg_class.oid = pg_attribute.attrelid
        JOIN pg_namespace ON pg_namespace.oid = pg_class.relnamespace
        WHERE pg_namespace.nspname = 'public'
          AND pg_class.relname = :table
          AND attnum > 0
          AND NOT attisdropped
        ORDER BY attnum
    ";

        $columns = [];
        foreach ($this->query($sql, ['table' => $tableName]) as $row) {
            $columns[(int)$row['attnum']] = $row['attname'];
        }

        return $columns;
    }

    private function getConstraints(): array
    {

        /**
         * pg_constraint fields:
         * conname     = constraint name
         * contype     = type (p=PK, u=UNIQUE, f=FK, c=CHECK, x=EXCLUDE)
         * conrelname  = table owning the constraint
         * conkey      = constrained columns (attnum[])
         * confrelname = referenced table (FK only)
         * confkey     = referenced columns (attnum[])
         */

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
        foreach ($this->query($sql) as $line) {
            // conkey is always defined
            $line['conkey'] = $this->convertConkeyToConkeyname($line['conkey'], $line['conrelname']);

            // confkey only exists for foreign keys
            if ($line['contype'] === 'f') {
                $line['confkey'] = $this->convertConkeyToConkeyname(
                    $line['confkey'],
                    $line['confrelname']
                );
            } else {
                $line['confkey'] = [];
            }

            $result[$line['conrelname']][$line['conname']] = $line;
        }
        return $result;
    }

    private function convertConkeyToConkeyname($json_encoded_conkey, string $table_name): array
    {
        $conkey_array = json_decode($json_encoded_conkey ?? '[]', true);
        if (! is_array($conkey_array)) {
            return array();
        }
        $columnsByAttnum = $this->getTableColumnsByAttnum($table_name);

        $conkeyname = [];
        foreach ($conkey_array as $attnum) {
            if (isset($columnsByAttnum[$attnum])) {
                $conkeyname[] = $columnsByAttnum[$attnum];
            }
        }

        return $conkeyname;
    }
}
