<?php
class Equipment {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAllEquipment() {
        $stmt = $this->pdo->query("SELECT * FROM equipment");
        return $stmt->fetchAll();
    }
}
?>