<?php
// src/config/db.php

/*
 * Toda la aplicacion trabaja con la hora civil del colegio.  Esto evita que
 * endpoints CLI/HTTP dependan del valor instalado en php.ini.
 */
if (!defined('SISCO_TIMEZONE')) define('SISCO_TIMEZONE', 'America/Asuncion');
if (!defined('VENTANA_ASISTENCIA_ALUMNOS_MINUTOS')) define('VENTANA_ASISTENCIA_ALUMNOS_MINUTOS', 10);
date_default_timezone_set(SISCO_TIMEZONE);

class Database {
    private $host = "localhost";
    private $db_name = "sisco_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
            // MySQL/SO es la fuente del instante de recepcion. No derivar su
            // offset desde la tzdb embebida en PHP, que puede quedar obsoleta.
            $this->conn->exec("SET time_zone = 'SYSTEM'");
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>
