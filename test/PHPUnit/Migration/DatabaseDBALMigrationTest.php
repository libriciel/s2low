<?php

namespace S2lowLegacy\Test\Migration;

use Doctrine\DBAL\Connection;
use Exception;
use PDO;
use S2lowLegacy\Class\Database;
use S2lowLegacy\Lib\SQLQuery;
use S2lowTestCase;

class DatabaseDBALMigrationTest extends S2lowTestCase
{
    /** @var SQLQuery */
    private $legacySQLQuery;

    /** @var Database */
    private $legacyDatabase;

    /** @var Connection */
    private $dbalConnection;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup legacy components
        $this->legacySQLQuery = self::getContainer()->get(SQLQuery::class);
        $this->legacyDatabase = self::getContainer()->get(Database::class);

        // Setup new DBAL connection
        $this->dbalConnection = self::getContainer()->get(Connection::class);
    }

    public function testQueryVsFetchAllAssociative()
    {
        $sql = "SELECT * FROM actes_status ORDER BY id";

        $legacyResult = $this->legacySQLQuery->query($sql);
        $dbalResult = $this->dbalConnection->fetchAllAssociative($sql);

        $this->assertSame($legacyResult, $dbalResult);
        $this->assertNotEmpty($legacyResult);
    }

    public function testQueryWithParams()
    {
        $sql = "SELECT name FROM actes_status WHERE id = ?";

        $legacyResult = $this->legacySQLQuery->query($sql, [1]);
        $dbalResult = $this->dbalConnection->fetchAllAssociative($sql, [1]);

        $this->assertSame($legacyResult, $dbalResult);
    }

    public function testQueryOneScalarVsFetchOne()
    {
        $sql = "SELECT name FROM actes_status WHERE id = ?";

        // In legacy, queryOne returns a scalar if there is only 1 column
        $legacyResult = $this->legacySQLQuery->queryOne($sql, [1]);
        $dbalResult = $this->dbalConnection->fetchOne($sql, [1]);

        $this->assertSame($legacyResult, $dbalResult);
        $this->assertSame("Posté", $legacyResult);
    }

    public function testQueryOneArrayVsFetchAssociative()
    {
        $sql = "SELECT id, name FROM actes_status WHERE id = ?";

        // In legacy, if >1 columns, queryOne returns the first row as associative array
        $legacyResult = $this->legacySQLQuery->queryOne($sql, 1);
        $dbalResult = $this->dbalConnection->fetchAssociative($sql, [1]);

        $this->assertSame($legacyResult, $dbalResult);
        $this->assertSame(['id' => 1, 'name' => 'Posté'], $legacyResult);
    }

    public function testQueryOneEmptyResult()
    {
        $sql = "SELECT name FROM actes_status WHERE id = -999";

        $legacyResult = $this->legacySQLQuery->queryOne($sql);
        $dbalResult = $this->dbalConnection->fetchOne($sql);

        // SQLQuery returns false if no result
        $this->assertFalse($legacyResult);
        // DBAL fetchOne returns false if no result
        $this->assertFalse($dbalResult);

        // What about fetchAssociative ?
        $sqlMulti = "SELECT id, name FROM actes_status WHERE id = -999";
        $legacyResultMulti = $this->legacySQLQuery->queryOne($sqlMulti);
        $dbalResultMulti = $this->dbalConnection->fetchAssociative($sqlMulti);

        $this->assertFalse($legacyResultMulti);
        $this->assertFalse($dbalResultMulti);
    }

    public function testQueryOneColVsFetchFirstColumn()
    {
        $sql = "SELECT id FROM actes_status ORDER BY id LIMIT 3";

        $legacyResult = $this->legacySQLQuery->queryOneCol($sql);
        $dbalResult = $this->dbalConnection->fetchFirstColumn($sql);

        $this->assertSame($legacyResult, $dbalResult);
        $this->assertSame([-1, 0, 1], $legacyResult);
    }

    public function testPrepareAndExecuteIterative()
    {
        $sql = "SELECT id, name FROM actes_status ORDER BY id LIMIT 2";

        // Legacy behavior
        $this->legacySQLQuery->prepareAndExecute($sql);
        $legacyRows = [];
        while ($this->legacySQLQuery->hasMoreResult()) {
            $legacyRows[] = $this->legacySQLQuery->fetch();
        }

        // DBAL behavior
        $stmt = $this->dbalConnection->executeQuery($sql);
        $dbalRows = [];
        while ($row = $stmt->fetchAssociative()) {
            $dbalRows[] = $row;
        }

        $this->assertSame($legacyRows, $dbalRows);
        $this->assertCount(2, $legacyRows);
    }

    /**
     * @throws Exception
     */
    public function testTransactionCommit()
    {
        // DBAL Commit
        $this->dbalConnection->beginTransaction();
        $this->dbalConnection->executeStatement("INSERT INTO actes_status (id, name) VALUES (999, 'TestDBAL')");
        $this->dbalConnection->commit();

        $this->assertSame('TestDBAL', $this->dbalConnection->fetchOne("SELECT name FROM actes_status WHERE id = 999"));

        // Clean up
        $this->dbalConnection->executeStatement("DELETE FROM actes_status WHERE id = 999");
    }

    /**
     * @throws Exception
     */
    public function testTransactionRollback()
    {
        $this->dbalConnection->beginTransaction();
        $this->dbalConnection->executeStatement("INSERT INTO actes_status (id, name) VALUES (998, 'TestDBALRollback')");
        $this->dbalConnection->rollBack();

        $this->assertFalse($this->dbalConnection->fetchOne("SELECT name FROM actes_status WHERE id = 998"));
    }

    /**
     * @throws Exception
     */
    public function testNestedTransactions()
    {
        // In legacy Database, calling begin() twice returns 0 and does nothing the second time.
        // In DBAL, it increments the transaction nesting level (savepoints).
        $this->dbalConnection->beginTransaction(); // Level 1
        $this->dbalConnection->executeStatement("INSERT INTO actes_status (id, name) VALUES (997, 'Lvl1')");

        $this->dbalConnection->beginTransaction(); // Level 2
        $this->dbalConnection->executeStatement("INSERT INTO actes_status (id, name) VALUES (996, 'Lvl2')");

        $this->dbalConnection->rollBack(); // Rolls back Level 2 (via SAVEPOINT)

        // Grâce à "use_savepoints: true" dans doctrine.yaml, l'annulation
        // du niveau 2 ne casse pas la transaction globale.
        $this->dbalConnection->commit(); // Commits Level 1

        // On vérifie que Lvl1 a bien été commit, mais pas Lvl2
        $this->assertSame('Lvl1', $this->dbalConnection->fetchOne("SELECT name FROM actes_status WHERE id = 997"));
        $this->assertFalse($this->dbalConnection->fetchOne("SELECT name FROM actes_status WHERE id = 996"));

        // Clean up
        $this->dbalConnection->executeStatement("DELETE FROM actes_status WHERE id = 997");
    }


    public function testDbalLobWriteAndRead()
    {
        $dbalConnection = $this->dbalConnection;

        $longString = str_repeat("Ceci est un long flux de retour avec des accents éàç. ", 100);

        // Ensure parents exist
        $dbalConnection->executeStatement("INSERT INTO actes_envelopes (id, siren, department, user_id, submission_date) VALUES (999, '123456789', '01', 1, '2023-01-01') ON CONFLICT DO NOTHING");
        $dbalConnection->executeStatement("INSERT INTO actes_transactions (id, envelope_id, last_status_id, user_id, authority_id) VALUES (999, 999, 1, 1, 1) ON CONFLICT DO NOTHING");

        $dbalConnection->executeStatement(
            "INSERT INTO actes_transactions_workflow (transaction_id, status_id, date, message, flux_retour) VALUES (999, 1, '2023-01-01', 'Test', ?)",
            [$longString],
            [\Doctrine\DBAL\ParameterType::LARGE_OBJECT]
        );

        // Read LOB using DBAL fetchOne
        $result = $dbalConnection->fetchOne(
            "SELECT flux_retour FROM actes_transactions_workflow WHERE status_id = 1 AND transaction_id = 999"
        );

        // DBAL automatically returns a stream resource for PostgreSQL LOBs
        $this->assertIsResource($result);
        $readString = stream_get_contents($result);

        $this->assertEquals($longString, $readString);

        // Cleanup
        $dbalConnection->executeStatement("DELETE FROM actes_transactions_workflow WHERE transaction_id = 999");
    }
}
