<?php

define("TESTING_ENVIRONNEMENT", "true");
define("TRACE_FILE_PATH", "/tmp/s2low-phpunit.log");
define('HELIOS_FILES_UPLOAD_ROOT', "vfs://test/helios/");
define("ANTIVIRUS_COMMAND", "ls");

require_once(__DIR__ . "/../../init/init.php");

/** @deprecated  */
class PHPUnit_Framework_TestCase extends \PHPUnit\Framework\TestCase
{
    public function setExpectedException($e, string $message)
    {
        $this->expectException($e);
        $this->expectExceptionMessage($message);
    }
    public function noAssertion()
    {
        $this->assertTrue(true);
    }
}

// Database Initialization for Testing
echo "=== Database Initialization BEGIN ===\n";
$kernel = new S2low\Kernel('test', false);
$kernel->boot();
$container = $kernel->getContainer();
$sqlQuery = $container->get(S2lowLegacy\Lib\SQLQuery::class);

echo "Dropping/recreating public schema...\n";
$sqlQuery->exec("DROP SCHEMA IF EXISTS public CASCADE");
$sqlQuery->exec("CREATE SCHEMA public");
$sqlQuery->exec("GRANT ALL ON SCHEMA public TO public");

echo "Loading db/s2low.sql...\n";
$sqlQuery->exec(file_get_contents(SITEROOT . '/db/s2low.sql'));

echo "Running doctrine migrations...\n";
$application = new Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
$command = $application->find('doctrine:migrations:migrate');
$commandTester = new Symfony\Component\Console\Tester\CommandTester($command);
$commandTester->execute(['--no-interaction' => true]);

echo "Loading fixtures s2low-test.sql...\n";
$sqlQuery->exec(file_get_contents(SITEROOT . '/test/PHPUnit/s2low-test.sql'));

echo "Resetting sequences...\n";
$sqlQuery->exec("SELECT SETVAL('users_id_seq', (SELECT MAX(id)+1 FROM users))");
$sqlQuery->exec("SELECT SETVAL('authority_siret_id_seq', (SELECT MAX(id)+1 FROM authority_siret))");
$sqlQuery->exec("SELECT SETVAL('nounce_id_seq', (SELECT MAX(id)+1 FROM nounce))");
$sqlQuery->exec("SELECT SETVAL('authorities_id_seq', (SELECT MAX(id)+1 FROM authorities))");
$sqlQuery->exec("SELECT SETVAL('helios_transactions_id_seq', (SELECT MAX(id)+1 FROM helios_transactions))");
$sqlQuery->exec("SELECT SETVAL('authority_groups_id_seq', (SELECT MAX(id)+1 FROM authority_groups))");

$kernel->shutdown();
echo "=== Database Initialization END ===\n";
