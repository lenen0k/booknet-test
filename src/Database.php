<?php

namespace App;

use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;

class Database {
    private PDO $pdo;

    public function __construct($dbFile) {
        if (!file_exists($dbFile)) {
            throw new InvalidArgumentException("Database file '$dbFile' not found");
        }

        try {
            $this->pdo = new PDO('sqlite:' . $dbFile);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to open database file '$dbFile': " . $e->getMessage());
        }
    }

    public function query($sql, $params = []): array {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to execute query '$sql': " . $e->getMessage());
        }
    }

    public function getEnabledMethods(): array {
        $sql = "
            SELECT pm.id, pm.name, pm.commission, pm.image_url, pm.pay_url, pm.priority, ps.name AS payment_system_name
            FROM payment_methods pm
            LEFT JOIN payment_systems ps ON pm.payment_system_id = ps.id
            WHERE pm.enabled = 1 AND ps.enabled = 1
        ";
        return $this->query($sql);
    }

    public function getEnabledMethodCountrySettings(string $countryCode): array {
        $sql = "SELECT method_id, country_code, mode 
                FROM method_country_settings
                LEFT JOIN payment_methods pm ON method_country_settings.method_id = pm.id
                LEFT JOIN payment_systems ps ON pm.payment_system_id = ps.id
                WHERE pm.enabled = 1 and ps.enabled = 1 AND country_code = ?";
        return $this->query($sql, [$countryCode]);
    }

    public function getEnabledMethodCustomSettings(): array {
        $sql = "SELECT method_id, condition 
                FROM method_settings
                LEFT JOIN payment_methods pm ON method_settings.method_id = pm.id
                LEFT JOIN payment_systems ps ON pm.payment_system_id = ps.id
                WHERE pm.enabled = 1 and ps.enabled = 1";
        return $this->query($sql);
    }
}
