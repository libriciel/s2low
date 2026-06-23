<?php

use S2lowLegacy\Lib\SQLQuery;

class SQLQueryTest extends S2lowTestCase
{
    /**
     * @var SQLQuery
     */
    private $sqlQuery;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sqlQuery = self::getContainer()->get(SQLQuery::class);
    }

    public function testGetPdo()
    {
        $this->assertInstanceOf("PDO", $this->sqlQuery->getPdo());
    }

    public function testQuery()
    {
        $sql = "SELECT 42 as response";
        $result = $this->sqlQuery->query($sql);
        $this->assertEquals(42, $result[0]['response']);
    }

    public function testBadQuery()
    {
        $sql = "NOT_SQL_WORD";
        $this->expectExceptionMessage("NOT_SQL_WORD");
        $this->sqlQuery->query($sql);
    }

    public function testDisconnect()
    {
        $this->sqlQuery->disconnect();
        self::expectNotToPerformAssertions();
    }

    public function testSleep()
    {
        $this->sqlQuery->sleep(0);
        self::assertTrue(true);
    }

    public function testQueryOne()
    {
        $sql = "SELECT id FROM users ORDER BY id LIMIT 1";
        $result = $this->sqlQuery->queryOne($sql);
        $this->assertEquals(1, $result);
    }

    public function testQueryOneCol()
    {
        $sql = "SELECT id FROM users ORDER BY id LIMIT 2";
        $result = $this->sqlQuery->queryOneCol($sql);
        $this->assertEquals([1, 2], $result);
    }

    public function testQueryOneEmptyResult()
    {
        $sql = "SELECT id FROM users WHERE givenname=?";
        $result = $this->sqlQuery->queryOne($sql, "not existing givenname");
        $this->assertFalse($result);
    }

    public function testQueryOneColEmptyResult()
    {
        $sql = "SELECT id FROM users WHERE givenname=?";
        $result = $this->sqlQuery->queryOneCol($sql, "not existing givenname");
        $this->assertEmpty($result);
    }

    public function testQueryOneManyResult()
    {
        $sql = "SELECT id FROM users ORDER BY id ";
        $result = $this->sqlQuery->queryOne($sql);
        $this->assertEquals(1, $result);
    }

    public function testSlowQuery()
    {
        $this->sqlQuery->setSlowQuery(0);
        $sql = "SELECT id FROM users ORDER BY id ";

        set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$capturedError) {
            $capturedError = [
                'errno' => $errno,
                'message' => $errstr,
                'file' => $errfile,
                'line' => $errline,
            ];
            return true;
        }, E_USER_WARNING);
        $this->sqlQuery->queryOne($sql);
        restore_error_handler();

        $this->assertNotNull($capturedError);
        $this->assertEquals(E_USER_WARNING, $capturedError['errno']);
        $this->assertStringContainsString('Requete lente', $capturedError['message']);
    }

    public function testQueryOneColManyResult()
    {
        $sql = "SELECT * FROM users ORDER BY id ";
        $result = $this->sqlQuery->queryOne($sql);
        $this->assertSame(1, $result['id']);
    }

    public function testTransactionQueriesDelegatedToDbal()
    {
        $pdoMock = $this->createMock(\PDO::class);
        $connectionMock = $this->createMock(\Doctrine\DBAL\Connection::class);

        // On s'attend à ce que beginTransaction soit appelé sur la connexion pour 'BEGIN'
        $connectionMock->expects($this->once())
            ->method('beginTransaction');

        // On s'attend à ce que commit soit appelé sur la connexion pour 'COMMIT' lorsque la transaction est active
        $connectionMock->expects($this->once())
            ->method('commit');

        // On s'attend à ce que rollBack soit appelé sur la connexion pour 'ROLLBACK' lorsque la transaction est active
        $connectionMock->expects($this->once())
            ->method('rollBack');

        // Simuler isTransactionActive() pour retourner true lorsqu'il est appelé
        $connectionMock->expects($this->exactly(2))
            ->method('isTransactionActive')
            ->willReturn(true);

        // Instancier SQLQuery avec la connexion simulée
        $sqlQuery = new SQLQuery($pdoMock, $connectionMock);

        // 1. Test de 'BEGIN'
        $result = $sqlQuery->query('BEGIN');
        $this->assertSame(
            [],
            $result,
            "L'appel à 'BEGIN' via query() doit être intercepté et retourner un tableau vide car délégué à DBAL."
        );

        // 2. Test de 'COMMIT'
        $result = $sqlQuery->query('COMMIT');
        $this->assertSame(
            [],
            $result,
            "L'appel à 'COMMIT' via query() doit être intercepté et retourner un tableau vide car délégué à DBAL."
        );

        // 3. Test de 'ROLLBACK'
        $result = $sqlQuery->query('ROLLBACK');
        $this->assertSame(
            [],
            $result,
            "L'appel à 'ROLLBACK' via query() doit être intercepté et retourner un tableau vide car délégué à DBAL."
        );
    }

    public function testTransactionQueriesIgnoredWhenNotActive()
    {
        $pdoMock = $this->createMock(\PDO::class);
        $connectionMock = $this->createMock(\Doctrine\DBAL\Connection::class);

        // On s'attend à ce que commit et rollBack ne soient JAMAIS appelés
        $connectionMock->expects($this->never())->method('commit');
        $connectionMock->expects($this->never())->method('rollBack');

        $connectionMock->expects($this->exactly(2))
            ->method('isTransactionActive')
            ->willReturn(false);

        $sqlQuery = new SQLQuery($pdoMock, $connectionMock);

        // Test de 'COMMIT' lorsque la transaction n'est pas active
        $result = $sqlQuery->query('COMMIT');
        $this->assertSame(
            [],
            $result,
            "L'appel à 'COMMIT' lorsque la transaction n'est pas active doit être intercepté et retourner un tableau vide sans appeler commit()."
        );

        // Test de 'ROLLBACK' lorsque la transaction n'est pas active
        $result = $sqlQuery->query('ROLLBACK');
        $this->assertSame(
            [],
            $result,
            "L'appel à 'ROLLBACK' lorsque la transaction n'est pas active doit être intercepté et retourner un tableau vide sans appeler rollBack()."
        );
    }

    public function testTransactionQueriesVariousFormats()
    {
        $pdoMock = $this->createMock(\PDO::class);
        $connectionMock = $this->createMock(\Doctrine\DBAL\Connection::class);

        // On s'attend à ce que beginTransaction, commit et rollBack soient appelés
        $connectionMock->expects($this->once())->method('beginTransaction');
        $connectionMock->expects($this->once())->method('commit');
        $connectionMock->expects($this->once())->method('rollBack');
        $connectionMock->expects($this->exactly(2))->method('isTransactionActive')->willReturn(true);

        $sqlQuery = new SQLQuery($pdoMock, $connectionMock);

        // Minuscules, espaces et point-virgule
        $sqlQuery->query("  begin;  \n");
        $sqlQuery->query("COMMIT;\r");
        $sqlQuery->query("rollback");
    }

    public function testTransactionQueriesViaExec()
    {
        $pdoMock = $this->createMock(\PDO::class);
        $connectionMock = $this->createMock(\Doctrine\DBAL\Connection::class);

        // On s'attend à ce que beginTransaction, commit et rollBack soient appelés sur DBAL via exec()
        $connectionMock->expects($this->once())->method('beginTransaction');
        $connectionMock->expects($this->once())->method('commit');
        $connectionMock->expects($this->once())->method('rollBack');
        $connectionMock->expects($this->exactly(2))->method('isTransactionActive')->willReturn(true);

        $sqlQuery = new SQLQuery($pdoMock, $connectionMock);

        $sqlQuery->exec('BEGIN');
        $sqlQuery->exec('COMMIT');
        $sqlQuery->exec('ROLLBACK');
    }

    public function testTransactionQueriesFallbackToPdo()
    {
        $pdoMock = $this->createMock(\PDO::class);

        // On s'attend à ce que les méthodes PDO soient appelées directement
        $pdoMock->expects($this->exactly(2))->method('beginTransaction');
        $pdoMock->expects($this->once())->method('commit');
        $pdoMock->expects($this->once())->method('rollBack');

        // On s'attend à ce que inTransaction() soit appelé deux fois et retourne true
        $pdoMock->expects($this->exactly(2))
            ->method('inTransaction')
            ->willReturn(true);

        // Instancier SQLQuery sans la connexion DBAL
        $sqlQuery = new SQLQuery($pdoMock, null);

        $sqlQuery->query('BEGIN');
        $sqlQuery->query('COMMIT');

        $sqlQuery->query('BEGIN');
        $sqlQuery->query('ROLLBACK');
    }

    public function testTransactionQueriesFallbackToPdoIgnoredWhenNotActive()
    {
        $pdoMock = $this->createMock(\PDO::class);

        // On s'attend à ce que commit et rollBack ne soient JAMAIS appelés sur PDO
        $pdoMock->expects($this->never())->method('commit');
        $pdoMock->expects($this->never())->method('rollBack');

        // On s'attend à ce que inTransaction() soit appelé deux fois et retourne false
        $pdoMock->expects($this->exactly(2))
            ->method('inTransaction')
            ->willReturn(false);

        $sqlQuery = new SQLQuery($pdoMock, null);

        $sqlQuery->query('COMMIT');
        $sqlQuery->query('ROLLBACK');
    }
}
