<?php

use PHPUnit\Framework\TestCase;
use App\Database;

class DatabaseTest extends TestCase
{
    private string $tmpDbFile;

    protected function setUp(): void
    {
        $this->tmpDbFile = __DIR__ . '/test.sqlite';

        if (file_exists($this->tmpDbFile)) {
            unlink($this->tmpDbFile);
        }

        $pdo = new PDO('sqlite:' . $this->tmpDbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("
            CREATE TABLE payment_systems (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                enabled INTEGER
            );

            CREATE TABLE payment_methods (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                payment_system_id INTEGER,
                name TEXT,
                commission REAL,
                image_url TEXT,
                pay_url TEXT,
                priority INTEGER,
                enabled INTEGER,
                FOREIGN KEY (payment_system_id) REFERENCES payment_systems(id)
            );

            CREATE TABLE method_country_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                method_id INTEGER,
                mode TEXT CHECK (mode IN ('allow', 'deny')),
                country_code TEXT,
                FOREIGN KEY (method_id) REFERENCES payment_methods(id)
            );

            CREATE TABLE method_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                method_id INTEGER,
                condition TEXT,
                FOREIGN KEY (method_id) REFERENCES payment_methods(id)
            );
        ");

        $pdo->exec("
            INSERT INTO payment_systems (id, name, enabled) VALUES (1, 'PayTest', 1);
            INSERT INTO payment_methods (id, name, commission, image_url, pay_url, priority, enabled, payment_system_id)
            VALUES (1, 'TestCard', 2.5, 'card.jpg', '/pay/card', 1, 1, 1);

            INSERT INTO method_country_settings (method_id, country_code, mode)
            VALUES (1, 'US', 'allow');

            INSERT INTO method_settings (method_id, condition)
            VALUES (1, 'only_android');
        ");
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tmpDbFile)) {
            unlink($this->tmpDbFile);
        }
    }

    public function testConnectionSuccess()
    {
        $db = new Database($this->tmpDbFile);
        $this->assertInstanceOf(Database::class, $db);
    }

    public function testThrowsExceptionIfFileNotFound()
    {
        $this->expectException(InvalidArgumentException::class);
        new Database('/nonexistent.sqlite');
    }

    public function testQueryThrowsExceptionOnInvalidSQL()
    {
        $this->expectException(RuntimeException::class);

        $db = new Database($this->tmpDbFile);
        $db->query("SELECT * FROM nonexistent_table");
    }

    public function testQueryReturnsData()
    {
        $db = new Database($this->tmpDbFile);
        $results = $db->query("SELECT name FROM payment_methods WHERE id = ?", [1]);
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('TestCard', $results[0]['name']);
    }

    public function testGetEnabledMethods()
    {
        $db = new Database($this->tmpDbFile);
        $methods = $db->getEnabledMethods();

        $this->assertCount(1, $methods);
        $this->assertEquals('TestCard', $methods[0]['name']);
        $this->assertEquals('PayTest', $methods[0]['payment_system_name']);
    }

    public function testGetEnabledMethodCountrySettings()
    {
        $db = new Database($this->tmpDbFile);
        $settings = $db->getEnabledMethodCountrySettings('US');

        $this->assertCount(1, $settings);
        $this->assertEquals('US', $settings[0]['country_code']);
        $this->assertEquals('allow', $settings[0]['mode']);
    }

    public function testGetEnabledMethodCustomSettings()
    {
        $db = new Database($this->tmpDbFile);
        $settings = $db->getEnabledMethodCustomSettings();

        $this->assertCount(1, $settings);
        $this->assertEquals('only_android', $settings[0]['condition']);
    }
}
