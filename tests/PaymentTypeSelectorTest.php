<?php

use App\Enums\LangTypes;
use App\Enums\ProductTypes;
use App\Enums\UserOsTypes;
use App\PaymentMethod;
use PHPUnit\Framework\TestCase;
use App\PaymentTypeSelector;
use App\Database;

class PaymentTypeSelectorTest extends TestCase
{
    private string $tmpDbFile;
    private Database $db;

    protected function setUp(): void
    {
        $this->tmpDbFile = __DIR__ . '/test.sqlite';

        if (file_exists($this->tmpDbFile)) {
            unlink($this->tmpDbFile);
        }

        $dumpDB = file_get_contents(__DIR__ . '/../paymentsdb.sql');
        $pdo = new PDO('sqlite:' . $this->tmpDbFile);
        $pdo->exec($dumpDB);

        $this->db = new Database($this->tmpDbFile);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tmpDbFile)) {
            unlink($this->tmpDbFile);
        }
    }

    public function testWalletIsAvailableForBook()
    {
        $selector = new PaymentTypeSelector(
            ProductTypes::BOOK->value,
            10.00,
            LangTypes::EN->value,
            'UA',
            UserOsTypes::ANDROID->value,
            $this->db
        );
        $buttons = $selector->getButtons();

        $names = array_map(fn(PaymentMethod $btn) => $btn->getName(), $buttons);
        $this->assertContains('Кошелек Booknet', $names);
    }

    public function testWalletIsNotAvailableForWalletRefill()
    {
        $selector = new PaymentTypeSelector(
            ProductTypes::WALLET_REFILL->value,
            10.00,
            LangTypes::EN->value,
            'UA',
            UserOsTypes::ANDROID->value,
            $this->db
        );
        $buttons = $selector->getButtons();

        $names = array_map(fn(PaymentMethod $btn) => $btn->getName(), $buttons);
        $this->assertNotContains('Кошелек Booknet', $names);
    }

    public function testGooglePayHiddenInIndia()
    {
        $selector = new PaymentTypeSelector(
            ProductTypes::BOOK->value,
            20.00,
            LangTypes::EN->value,
            'IN',
            UserOsTypes::ANDROID->value,
            $this->db
        );
        $buttons = $selector->getButtons();

        $names = array_map(fn(PaymentMethod $btn) => $btn->getName(), $buttons);
        $this->assertNotContains('GooglePay', $names);
    }

    public function testGooglePayVisibleOnAndroidOutsideIndia()
    {
        $selector = new PaymentTypeSelector(
            ProductTypes::BOOK->value,
            20.00,
            LangTypes::EN->value,
            'US',
            UserOsTypes::ANDROID->value,
            $this->db
        );
        $buttons = $selector->getButtons();

        $names = array_map(fn(PaymentMethod $btn) => $btn->getName(), $buttons);
        $this->assertContains('GooglePay', $names);
    }

    public function testRewardOnlyWalletIfSmallAmountInEs()
    {
        $selector = new PaymentTypeSelector(
            ProductTypes::REWARD->value,
            0.2,
            LangTypes::ES->value,
            'ES',
            UserOsTypes::ANDROID->value,
            $this->db
        );
        $buttons = $selector->getButtons();

        $this->assertCount(1, $buttons);
        $this->assertEquals('Кошелек Booknet', $buttons[0]->getName());
    }
}
