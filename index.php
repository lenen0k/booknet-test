<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Database;
use App\PaymentTypeSelector;

$dbPath = 'payments.db';

$db = new Database($dbPath);

$productType = $_GET['productType'] ?? 'book';
$amount = (float)($_GET['amount'] ?? 1);
$lang = $_GET['lang'] ?? 'en';
$countryCode = $_GET['countryCode'] ?? 'US';
$userOs = $_GET['userOs'] ?? 'android';

$selector = new PaymentTypeSelector($productType, $amount, $lang, $countryCode, $userOs, $db);
$paymentButtons = $selector->getButtons();

foreach ($paymentButtons as $btn) {
    echo 'Payment Method: ' . $btn->getName() . '<br>';
    echo 'Commission: ' . $btn->getCommission() . '%<br>';
    echo 'Icon: <img src="' . $btn->getImageUrl() . '" alt="' . $btn->getName() . '"><br>';
    echo 'Pay Link: <a href="' . $btn->getPayUrl() . '">Go to Payment</a><br><br>';
}

