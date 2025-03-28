<?php

require_once __DIR__ . '/../includes/Settings.php';

class Database {
    private $host = 'localhost';
    private $db_name = 'saedigro_techvisits';
    private $username = 'saedigro_onarud';
    private $password = 'jr010101';
    private $conn;

     public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $e) {
            error_log("Error de conexión: " . $e->getMessage());
        }

        return $this->conn;
    }
}

$database = new Database();
$db = $database->connect();
$settings = Settings::getInstance($db);