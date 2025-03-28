<?php
class User {
    private $conn;
    private $table = 'users';

    // Constructor y login ya están implementados

    public function getTechnicians() {
        $query = 'SELECT id, full_name FROM ' . $this->table . ' WHERE role = "technician" AND active = 1';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}