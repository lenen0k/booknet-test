<?php
namespace App;

use App\Enums\LangTypes;
use App\Enums\PaymentMethodCountryMode;
use App\Enums\ProductTypes;
use App\Enums\UserOsTypes;

class PaymentTypeSelector {
    const ES_MIN_AMOUNT = 0.3;
    public function __construct(
        private string $productType,
        private float $amount,
        private string $lang,
        private string $countryCode,
        private string $userOs,
        private Database $db,
    ) {}

    public function getButtons(): array {
        $enabledMethods = $this->db->getEnabledMethods();
        $filteredMethods = $this->filterMethods($enabledMethods);
        $sortedMethods = $this->sortMethods($filteredMethods);

        return $sortedMethods;
    }

    private function filterMethods(array $methods): array {
        $filteredMethods = [];

        $countrySettings = $this->db->getEnabledMethodCountrySettings($this->countryCode);
        $customSettings = $this->db->getEnabledMethodCustomSettings();

        foreach ($methods as $method) {
            $paymentMethod = new PaymentMethod(
                $method['id'],
                $method['name'],
                $method['commission'],
                $method['image_url'],
                $method['pay_url'],
                $method['priority'],
                $method['payment_system_name']
            );

            if ($this->isAvailableInCountry($paymentMethod, $countrySettings) && $this->passCustomSettings($paymentMethod, $customSettings)) {
                $filteredMethods[] = $paymentMethod;
            }
        }

        $filteredMethods = $this->addConstantFilteredConditions($filteredMethods);

        return $filteredMethods;
    }

    private function isAvailableInCountry(PaymentMethod $method, array $countrySettings): bool
    {
        $rules = array_filter($countrySettings, fn($s) => $s['method_id'] == $method->getId());

        if (!$rules) return true;

        foreach ($rules as $rule) {
            if ($rule['mode'] === PaymentMethodCountryMode::DENY->value) return false;
            if ($rule['mode'] === PaymentMethodCountryMode::ALLOW->value) return true;
        }

        return false;
    }

    private function passCustomSettings(PaymentMethod $method, array $customSettings): bool {
        $conditions = array_filter($customSettings, fn($s) => $s['method_id'] == $method->getId());
        $conditions = array_column($conditions, 'condition');

        foreach ($conditions as $cond) {
            switch ($cond) {
                case 'only_android':
                    if ($this->userOs !== UserOsTypes::ANDROID->value) return false;
                    break;
                case 'only_ios':
                    if ($this->userOs !== UserOsTypes::IOS->value) return false;
                    break;
                case 'no_wallet_topup':
                    if ($this->productType === ProductTypes::WALLET_REFILL->value) return false;
                    break;
            }
        }

        return true;
    }

    private function addConstantFilteredConditions(array $filteredMethods): array
    {
        $filteredMethods = $this->duplicatePrivatBankForUkraine($filteredMethods);
        $filteredMethods = $this->addSpainConditions($filteredMethods);

        return $filteredMethods;
    }

    private function duplicatePrivatBankForUkraine(array $filteredMethods): array
    {
        if ($this->countryCode !== 'UA') return $filteredMethods;

        foreach ($filteredMethods as $method) {
            if (str_contains($method->getPaymentSystemName(), 'CardPay')) {
                $duplicate = clone $method;
                $duplicate->setName('Cards PrivatBank');
                $filteredMethods[] = $duplicate;
            }
        }

        return $filteredMethods;
    }

    private function addSpainConditions(array $filteredMethods): array
    {
        if ($this->lang !== LangTypes::ES->value || $this->amount >= self::ES_MIN_AMOUNT) return $filteredMethods;

        if ($this->productType === ProductTypes::REWARD->value) {
            $filteredMethods = array_filter($filteredMethods, fn($m) => str_contains(strtolower($m->getPaymentSystemName()), 'booknet'));
        } else {
            $filteredMethods = array_filter($filteredMethods, fn($m) => !str_contains(strtolower($m->getName()), 'paypal'));
        }

        return $filteredMethods;
    }

    private function sortMethods(array $methods): array
    {
        usort($methods, fn($a, $b) => $a->getPriority() <=> $b->getPriority());

        return $methods;
    }
}
