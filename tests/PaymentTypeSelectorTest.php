<?php

use App\PaymentMethod;
use PHPUnit\Framework\TestCase;
use App\PaymentTypeSelector;
use App\Database;

class PaymentTypeSelectorTest extends TestCase
{
    private string $tempDbFile;
    private Database $db;

    protected function setUp(): void
    {
        $this->tempDbFile = tempnam(sys_get_temp_dir(), 'testdb_');

        $dumpDB = file_get_contents(__DIR__ . '/../paymentsdb.sql');
        $pdo = new PDO('sqlite:' . $this->tempDbFile);
        $pdo->exec($dumpDB);

        $this->db = new Database($this->tempDbFile);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempDbFile)) {
            unlink($this->tempDbFile);
        }
    }

    public function testWalletIsAvailableForBook()
    {
        $selector = new PaymentTypeSelector('book', 10.00, 'en', 'UA', 'android', $this->db);
        $buttons = $selector->getButtons();

        $names = array_map(fn(PaymentMethod $btn) => $btn->getName(), $buttons);
        $this->assertContains('Кошелек Booknet', $names);
    }

    public function testWalletIsNotAvailableForWalletRefill()
    {
        $selector = new PaymentTypeSelector('walletRefill', 10.00, 'en', 'UA', 'android', $this->db);
        $buttons = $selector->getButtons();

        $names = array_map(fn(PaymentMethod $btn) => $btn->getName(), $buttons);
        $this->assertNotContains('Кошелек Booknet', $names);
    }

    public function testGooglePayHiddenInIndia()
    {
        $selector = new PaymentTypeSelector('book', 20.00, 'en', 'IN', 'android', $this->db);
        $buttons = $selector->getButtons();

        $names = array_map(fn(PaymentMethod $btn) => $btn->getName(), $buttons);
        $this->assertNotContains('GooglePay', $names);
    }

    public function testGooglePayVisibleOnAndroidOutsideIndia()
    {
        $selector = new PaymentTypeSelector('book', 20.00, 'en', 'US', 'android', $this->db);
        $buttons = $selector->getButtons();

        $names = array_map(fn(PaymentMethod $btn) => $btn->getName(), $buttons);
        $this->assertContains('GooglePay', $names);
    }

    public function testRewardOnlyWalletIfSmallAmountInEs()
    {
        $selector = new PaymentTypeSelector('reward', 0.2, 'es', 'ES', 'android', $this->db);
        $buttons = $selector->getButtons();

        $this->assertCount(1, $buttons);
        $this->assertEquals('Кошелек Booknet', $buttons[0]->getName());
    }
}
