<?php
require_once __DIR__ . '/ConfiguracionSistema.php';

class Aula {
    private $conn;
    private $table_name = "aulas";

    public $id_aula;
    public $nombre;
    public $codigo;
    public $bloque;
    public $piso;
    public $ubicacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear(): bool {
        $query = "INSERT INTO " . $this->table_name . " (nombre, codigo, bloque, piso, ubicacion)
                  VALUES (:nombre, :codigo, :bloque, :piso, :ubicacion)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":codigo", $this->codigo);
        $stmt->bindParam(":bloque", $this->bloque);
        $stmt->bindParam(":piso", $this->piso);
        $stmt->bindParam(":ubicacion", $this->ubicacion);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT id_aula, nombre, COALESCE(codigo, codigo_sala) AS codigo, 
                         COALESCE(bloque, 'Bloque A') AS bloque, piso, ubicacion, activo
                  FROM " . $this->table_name . "
                  WHERE activo = 1
                  ORDER BY bloque ASC, piso ASC, nombre ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function leerDesactivadas() {
        $query = "SELECT id_aula, nombre, COALESCE(codigo, codigo_sala) AS codigo, 
                         COALESCE(bloque, 'Bloque A') AS bloque, piso, ubicacion, activo
                  FROM " . $this->table_name . "
                  WHERE activo = 0
                  ORDER BY bloque ASC, piso ASC, nombre ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function actualizar(): bool {
        $query = "UPDATE " . $this->table_name . "
                  SET nombre = :nombre,
                      codigo = :codigo,
                      bloque = :bloque,
                      piso = :piso,
                      ubicacion = :ubicacion
                  WHERE id_aula = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":codigo", $this->codigo);
        $stmt->bindParam(":bloque", $this->bloque);
        $stmt->bindParam(":piso", $this->piso);
        $stmt->bindParam(":ubicacion", $this->ubicacion);
        $stmt->bindParam(":id", $this->id_aula);

        return $stmt->execute();
    }

    public function desactivar(): bool {
        $query = "UPDATE " . $this->table_name . "
                  SET activo = 0
                  WHERE id_aula = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_aula);

        return $stmt->execute();
    }

    public function restaurar(): bool {
        $query = "UPDATE " . $this->table_name . "
                  SET activo = 1
                  WHERE id_aula = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_aula);

        return $stmt->execute();
    }

    public function eliminar(): bool {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE id_aula = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_aula);

        return $stmt->execute();
    }

    /**
     * Algoritmo determinista para generar el código estándar de aula.
     * Estructura: {PREFIJO_BLOQUE}-{PREFIJO_PISO}-{CORRELATIVO_2_DIGITOS}
     * Ejemplos: A-PB-01, A-P1-02, B-P2-01.
     * Longitud óptima para pantallas OLED y transceptor LoRa.
     */
    public function generarSiguienteCodigo(string $bloque, string $piso, ?int $excluirId = null): string {
        $prefijoBloque = $this->extraerPrefijoBloque($bloque);
        $prefijoPiso = $this->extraerPrefijoPiso($piso);
        $patronPrefijo = "{$prefijoBloque}-{$prefijoPiso}";

        $sql = "SELECT COALESCE(codigo, codigo_sala) AS cod 
                FROM {$this->table_name} 
                WHERE (codigo LIKE :patron OR codigo_sala LIKE :patron)" .
                ($excluirId ? " AND id_aula != :excluir" : "");

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':patron', "{$patronPrefijo}-%", PDO::PARAM_STR);
        if ($excluirId) {
            $stmt->bindValue(':excluir', $excluirId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $codigosExistentes = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $maxNumero = 0;
        foreach ($codigosExistentes as $cod) {
            if (preg_match('/' . preg_quote($patronPrefijo, '/') . '-(\d+)/i', (string)$cod, $matches)) {
                $num = (int)$matches[1];
                if ($num > $maxNumero) {
                    $maxNumero = $num;
                }
            }
        }

        // Si no hay códigos con este patrón, contar aulas existentes en ese bloque y piso para sugerir
        if ($maxNumero === 0) {
            $sqlCount = "SELECT COUNT(*) FROM {$this->table_name} 
                         WHERE bloque = :bloque AND piso = :piso" .
                         ($excluirId ? " AND id_aula != :excluir" : "");
            $stmtCount = $this->conn->prepare($sqlCount);
            $stmtCount->bindValue(':bloque', $bloque, PDO::PARAM_STR);
            $stmtCount->bindValue(':piso', $piso, PDO::PARAM_STR);
            if ($excluirId) {
                $stmtCount->bindValue(':excluir', $excluirId, PDO::PARAM_INT);
            }
            $stmtCount->execute();
            $maxNumero = (int)$stmtCount->fetchColumn();
        }

        $siguienteNumero = $maxNumero + 1;
        $codigoCandidato = sprintf('%s-%02d', $patronPrefijo, $siguienteNumero);

        // Validar unicidad absoluta en la tabla
        $seguridadLoop = 0;
        while ($this->existeCodigo($codigoCandidato, $excluirId) && $seguridadLoop < 50) {
            $siguienteNumero++;
            $codigoCandidato = sprintf('%s-%02d', $patronPrefijo, $siguienteNumero);
            $seguridadLoop++;
        }

        return strtoupper($codigoCandidato);
    }

    /**
     * Verifica si un código de sala ya existe en la base de datos.
     */
    public function existeCodigo(string $codigo, ?int $excluirId = null): bool {
        $sql = "SELECT COUNT(*) FROM {$this->table_name} 
                WHERE (LOWER(codigo) = LOWER(:codigo) OR LOWER(codigo_sala) = LOWER(:codigo))" .
                ($excluirId ? " AND id_aula != :excluir" : "");
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':codigo', trim($codigo), PDO::PARAM_STR);
        if ($excluirId) {
            $stmt->bindValue(':excluir', $excluirId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Extrae un prefijo conciso (1 a 4 caracteres) a partir del nombre o identificador del bloque.
     */
    private function extraerPrefijoBloque(string $bloque): string {
        $b = trim($bloque);
        if (preg_match('/(?:bloque|edificio|campus)\s+([a-z0-9]+)/i', $b, $m)) {
            return strtoupper($m[1]);
        }
        $limpio = preg_replace('/[^A-Za-z0-9]/', '', $b);
        if (strlen($limpio) <= 4) {
            return strtoupper($limpio) ?: 'A';
        }
        return strtoupper(substr($limpio, 0, 3));
    }

    /**
     * Extrae un prefijo estándar de piso (PB, SS, P1, P2, P3, etc.).
     */
    private function extraerPrefijoPiso(string $piso): string {
        $p = trim($piso);
        if (stripos($p, 'baja') !== false || strcasecmp($p, 'pb') === 0) {
            return 'PB';
        }
        if (stripos($p, 'subsuelo') !== false || stripos($p, 'sotano') !== false || strcasecmp($p, 'ss') === 0) {
            return 'SS';
        }
        if (preg_match('/(\d+)/', $p, $m)) {
            return 'P' . $m[1];
        }
        $limpio = preg_replace('/[^A-Za-z0-9]/', '', $p);
        return strtoupper(substr($limpio, 0, 3)) ?: 'P1';
    }

    /**
     * Obtiene la estructura configurada de bloques y pisos institucionales.
     */
    public function obtenerEstructuraCampus(): array {
        $config = new ConfiguracionSistema($this->conn);
        $bloquesRaw = $config->obtener('campus_bloques');
        $pisosRaw = $config->obtener('campus_pisos');

        $bloques = $bloquesRaw ? json_decode($bloquesRaw, true) : null;
        $pisos = $pisosRaw ? json_decode($pisosRaw, true) : null;

        if (!is_array($bloques) || empty($bloques)) {
            $bloques = [
                ['id' => 'A', 'nombre' => 'Bloque A'],
                ['id' => 'B', 'nombre' => 'Bloque B']
            ];
        }

        if (!is_array($pisos) || empty($pisos)) {
            $pisos = [
                ['id' => 'PB', 'nombre' => 'Planta Baja', 'numero' => 0],
                ['id' => 'P1', 'nombre' => '1° Piso', 'numero' => 1],
                ['id' => 'P2', 'nombre' => '2° Piso', 'numero' => 2],
                ['id' => 'P3', 'nombre' => '3° Piso', 'numero' => 3]
            ];
        }

        return [
            'bloques' => $bloques,
            'pisos' => $pisos
        ];
    }

    /**
     * Guarda la estructura de bloques y pisos institucionales.
     */
    public function guardarEstructuraCampus(array $bloques, array $pisos): bool {
        $bloquesFiltrados = [];
        foreach ($bloques as $b) {
            $nombre = trim($b['nombre'] ?? '');
            $id = trim($b['id'] ?? '');
            if ($nombre !== '') {
                $bloquesFiltrados[] = [
                    'id' => $id ?: strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $nombre), 0, 3)),
                    'nombre' => $nombre
                ];
            }
        }

        $pisosFiltrados = [];
        foreach ($pisos as $idx => $p) {
            $nombre = trim($p['nombre'] ?? '');
            $id = trim($p['id'] ?? '');
            if ($nombre !== '') {
                $pisosFiltrados[] = [
                    'id' => $id ?: $this->extraerPrefijoPiso($nombre),
                    'nombre' => $nombre,
                    'numero' => isset($p['numero']) ? (int)$p['numero'] : $idx
                ];
            }
        }

        if (empty($bloquesFiltrados)) {
            $bloquesFiltrados = [['id' => 'A', 'nombre' => 'Bloque A']];
        }
        if (empty($pisosFiltrados)) {
            $pisosFiltrados = [['id' => 'PB', 'nombre' => 'Planta Baja', 'numero' => 0]];
        }

        $jsonBloques = json_encode($bloquesFiltrados, JSON_UNESCAPED_UNICODE);
        $jsonPisos = json_encode($pisosFiltrados, JSON_UNESCAPED_UNICODE);

        $sql = "INSERT INTO configuraciones_sistema (clave, valor, categoria, tipo, descripcion, fecha_actualizacion)
                VALUES ('campus_bloques', :val_bloques, 'campus', 'json', 'Lista de bloques o edificios', CURRENT_TIMESTAMP)
                ON CONFLICT (clave) DO UPDATE 
                SET valor = EXCLUDED.valor, fecha_actualizacion = CURRENT_TIMESTAMP";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':val_bloques' => $jsonBloques]);

        $sql = "INSERT INTO configuraciones_sistema (clave, valor, categoria, tipo, descripcion, fecha_actualizacion)
                VALUES ('campus_pisos', :val_pisos, 'campus', 'json', 'Lista de pisos o niveles', CURRENT_TIMESTAMP)
                ON CONFLICT (clave) DO UPDATE 
                SET valor = EXCLUDED.valor, fecha_actualizacion = CURRENT_TIMESTAMP";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':val_pisos' => $jsonPisos]);

        return true;
    }
}
