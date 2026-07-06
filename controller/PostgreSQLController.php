<?php

namespace S2lowLegacy\Controller;

use Closure;
use Exception;
use S2low\Exceptions\BadEnvironmentException;
use S2lowLegacy\Class\Database;
use S2lowLegacy\Lib\PostgreSQLDifference;
use S2lowLegacy\Lib\PostgreSQLDifferenceToSQL;
use S2lowLegacy\Lib\PostgreSQLSchemaInfo;
use S2lowLegacy\Lib\SQLQuery;

class PostgreSQLController
{
    private $postgreSQLSchemaInfo;
    private $postgreSQLDifference;
    private $postgreSQLDifferenceToSQL;
    private $database_json_definition_filepath;
    private $database_sql_definition_filepath;
    private $sqlQuery;
    private string $projectDirectory;
    private string $environment;

    public function __construct(
        string $database_json_definition_filepath,
        string $database_sql_definition_filepath,
        string $projectDirectory,
        string $environment,
        SQLQuery $sqlQuery,
        PostgreSQLSchemaInfo $postgreSQLSchemaInfo,
        PostgreSQLDifference $postgreSQLDifference,
        PostgreSQLDifferenceToSQL $postgreSQLDifferenceToSQL,
    ) {
        $this->postgreSQLSchemaInfo = $postgreSQLSchemaInfo;
        $this->postgreSQLDifference = $postgreSQLDifference;
        $this->projectDirectory = $projectDirectory;
        $this->postgreSQLDifferenceToSQL = $postgreSQLDifferenceToSQL;
        $this->database_json_definition_filepath = $database_json_definition_filepath;
        $this->database_sql_definition_filepath = $database_sql_definition_filepath;
        $this->sqlQuery = $sqlQuery;
        $this->environment = $environment;
    }


    public function getAlterDatabaseCommand()
    {
        $database_definition = $this->postgreSQLSchemaInfo->getDatabaseDefinition();
        $db_definition = file_get_contents($this->database_json_definition_filepath);
        $file_defintion = json_decode($db_definition, true);
        $difference = $this->postgreSQLDifference->getDifference($database_definition, $file_defintion);

        return $this->postgreSQLDifferenceToSQL->getSQL($difference);
    }

    public function alterDatabase(Closure $log_function)
    {

        $sql_command = $this->getAlterDatabaseCommand() ;
        if (! $sql_command) {
            $log_function("La base de données est déjà à jour");
            return;
        }
        $this->sqlQuery->getConnection()->beginTransaction();
        $log_function("Début de la transaction");
        try {
            foreach ($sql_command as $sql) {
                $log_function("$sql");
                $this->sqlQuery->query($sql);
            }
            $this->sqlQuery->getConnection()->commit();
            $log_function("Base de données modifié avec succès");
        } catch (Exception $e) {
            $log_function($e->getMessage());
            $this->sqlQuery->getConnection()->rollBack();
            $log_function("Erreur : La base de données N'A PAS été modifié");
        }
    }

    public function saveDatabaseToFile()
    {
        $database_definition = $this->postgreSQLSchemaInfo->getDatabaseDefinition();

        file_put_contents(
            $this->database_json_definition_filepath,
            json_encode($database_definition, JSON_PRETTY_PRINT)
        );

        $difference = $this->postgreSQLDifference->getDifference(array(), $database_definition);
        $sql_command = $this->postgreSQLDifferenceToSQL->getSQL($difference);

        $sql_content = implode("\n", $sql_command) . "\n";

        file_put_contents($this->database_sql_definition_filepath, $sql_content);
    }

    public function populateDbTest(): void
    {
        if ($this->environment === 'test') {
            $this->alterDatabase(function ($message) {
            });

            $data = file_get_contents($this->projectDirectory . '/test/PHPUnit/s2low-test.sql');
            $this->sqlQuery->exec($data);
        } else {
            throw new BadEnvironmentException();
        }
    }
}
