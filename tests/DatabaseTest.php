<?php

use PHPUnit\Framework\TestCase;
use App\Database;

class DatabaseTest extends TestCase
{
    private string $dbFile;

    protected function setUp(): void
    {
        $this->dbFile = tempnam(sys_get_temp_dir(), 'testdb_');
        $pdo = new PDO('sqlite:' . $this->dbFile);
        $pdo->exec("CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT);");
        $pdo->exec("INSERT INTO test (name) VALUES ('User1'), ('User2');");
    }

    protected function tearDown(): void
    {
        if (file_exists($this->dbFile)) {
            unlink($this->dbFile);
        }
    }

    public function testConnectionSuccess()
    {
        $db = new Database($this->dbFile);
        $this->assertInstanceOf(Database::class, $db);
    }

    public function testQueryReturnsData()
    {
        $db = new Database($this->dbFile);
        $results = $db->query("SELECT * FROM test WHERE name = ?", ['User1']);

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('User1', $results[0]['name']);
    }

    public function testThrowsExceptionIfFileNotFound()
    {
        $this->expectException(InvalidArgumentException::class);
        new Database('/nonexistent.sqlite');
    }

    public function testQueryThrowsExceptionOnInvalidSQL()
    {
        $this->expectException(RuntimeException::class);

        $db = new Database($this->dbFile);
        $db->query("SELECT * FROM nonexistent_table");
    }
}
