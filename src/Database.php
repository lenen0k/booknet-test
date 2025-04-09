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
}
