<?php
namespace App;

class PaymentTypeSelector {
    public function __construct(
        private string $productType,
        private float $amount,
        private string $lang,
        private string $countryCode,
        private string $userOs,
        private Database $db,
    ) {}

    public function getButtons(): array {
        $enabledMethods = $this->fetchEnabledMethods();
        $filteredMethods = $this->filterMethods($enabledMethods);
        $sortedMethods = $this->sortMethods($filteredMethods);

        return $sortedMethods;
    }

    private function fetchEnabledMethods(): array {
        $sql = "
            SELECT pm.id, pm.name, pm.commission, pm.image_url, pm.pay_url, pm.priority, ps.name AS payment_system_name
            FROM payment_methods pm
            LEFT JOIN payment_systems ps ON pm.payment_system_id = ps.id
            WHERE pm.enabled = 1 AND ps.enabled = 1
        ";
        return $this->db->query($sql);
    }

    private function filterMethods(array $methods): array {
        $filteredMethods = [];

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

            if ($this->isAvailableInCountry($paymentMethod) && $this->passCustomSettings($paymentMethod)) {
                $filteredMethods[] = $paymentMethod;
            }
        }

        $filteredMethods = $this->addConstantFilteredConditions($filteredMethods);

        return $filteredMethods;
    }

    private function isAvailableInCountry(PaymentMethod $method): bool {
        $rules = $this->db->query("
            SELECT * FROM method_country_settings
            WHERE method_id = ? AND country_code = ?
        ", [$method->getId(), $this->countryCode]);

        if (!$rules) return true;

        foreach ($rules as $rule) {
            if ($rule['mode'] === 'deny') return false;
            if ($rule['mode'] === 'allow') return true;
        }

        return false;
    }

    private function passCustomSettings(PaymentMethod $method): bool {
        $conditionsData = $this->db->query("SELECT condition FROM method_settings WHERE method_id = ?", [$method->getId()]);
        $conditions = array_column($conditionsData, 'condition');

        foreach ($conditions as $cond) {
            switch ($cond) {
                case 'only_android':
                    if ($this->userOs !== 'android') return false;
                    break;
                case 'only_ios':
                    if ($this->userOs !== 'ios') return false;
                    break;
                case 'no_wallet_topup':
                    if ($this->productType === 'walletRefill') return false;
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
        if ($this->lang !== 'es' || $this->amount >= 0.3) return $filteredMethods;

        if ($this->productType === 'reward') {
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
