<?php
// src/config/db.php

class Database {
    private $driver;
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    private $ssl;
    public $conn;

    public function __construct() {
        $localConfigFile = __DIR__ . '/../../config.local.php';
        $local = file_exists($localConfigFile) ? (include $localConfigFile) : [];
        if (!is_array($local)) {
            $local = [];
        }

        $this->driver = getenv('DB_DRIVER') ?: ($local['DB_DRIVER'] ?? 'pgsql');
        $this->host = getenv('DB_HOST') ?: ($local['DB_HOST'] ?? 'localhost');
        $this->port = getenv('DB_PORT') ?: ($local['DB_PORT'] ?? ($this->driver === 'pgsql' ? '5432' : '3306'));
        $this->db_name = getenv('DB_NAME') ?: ($local['DB_NAME'] ?? ($this->driver === 'pgsql' ? 'postgres' : 'sisco_db'));
        $this->username = getenv('DB_USER') ?: ($local['DB_USER'] ?? ($this->driver === 'pgsql' ? 'postgres' : 'root'));
        $this->password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($local['DB_PASS'] ?? '');
        $this->ssl = (bool)(getenv('DB_SSL') ?: ($local['DB_SSL'] ?? ($this->driver === 'pgsql')));
    }

    public function getConnection() {
        $this->conn = null;
        try {
            if ($this->driver === 'pgsql') {
                $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";sslmode=" . ($this->ssl ? 'require' : 'prefer');
                $opciones = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
            } else {
                $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
                $opciones = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                if ($this->ssl) {
                    $opciones[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                }
            }
            $this->conn = new PDO($dsn, $this->username, $this->password, $opciones);
        } catch (PDOException $exception) {
            error_log("Error de conexión BD ({$this->driver}): " . $exception->getMessage());
            throw new RuntimeException("Error al conectar con la base de datos.");
        }
        return $this->conn;
    }
}
?>