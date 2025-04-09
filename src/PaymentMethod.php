<?php

namespace App;

class PaymentMethod {
    public function __construct(
        private int $id,
        private string $name,
        private float $commission,
        private string $imageUrl,
        private string $payUrl,
        private int $priority = 0,
        private string $paymentSystemName
    ) {}

    public function getId(): int {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function getCommission(): float {
        return $this->commission;
    }

    public function getImageUrl(): string {
        return $this->imageUrl;
    }

    public function getPayUrl(): string {
        return $this->payUrl;
    }

    public function getPriority(): int {
        return $this->priority;
    }

    public function getPaymentSystemName(): string {
        return $this->paymentSystemName;
    }
}
