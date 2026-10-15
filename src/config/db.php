<?php
// src/config/db.php

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $localConfigFile = __DIR__ . '/../../config.local.php';
        $local = file_exists($localConfigFile) ? (include $localConfigFile) : [];
        if (!is_array($local)) {
            $local = [];
        }

        $this->host = getenv('DB_HOST') ?: ($local['DB_HOST'] ?? 'localhost');
        $this->db_name = getenv('DB_NAME') ?: ($local['DB_NAME'] ?? 'sisco_db');
        $this->username = getenv('DB_USER') ?: ($local['DB_USER'] ?? 'root');
        $this->password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($local['DB_PASS'] ?? '');
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $opciones = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $opciones);
        } catch (PDOException $exception) {
            error_log("Error de conexión BD: " . $exception->getMessage());
            throw new RuntimeException("Error al conectar con la base de datos.");
        }
        return $this->conn;
    }
}
?>