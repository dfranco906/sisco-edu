<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Profesor.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
    exit;
}

if (!isset($_FILES['archivo_csv']) || $_FILES['archivo_csv']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Debe seleccionar un archivo CSV válido para importar."]);
    exit;
}

$tmpPath = $_FILES['archivo_csv']['tmp_name'];
$fileName = $_FILES['archivo_csv']['name'];

// Validar extensión
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
if (!in_array($ext, ['csv', 'txt'])) {
    http_response_code(422);
    echo json_encode([
        "status" => "error",
        "message" => "Formato no compatible. Por favor suba un archivo .csv (delimitado por comas o punto y coma)."
    ]);
    exit;
}

function parsearCsvProfesores(string $tmpPath): array {
    $handle = fopen($tmpPath, 'r');
    if (!$handle) return [];

    $primeraLinea = fgets($handle);
    if ($primeraLinea === false) {
        fclose($handle);
        return [];
    }

    $conteoPuntoComa = substr_count($primeraLinea, ';');
    $conteoComa = substr_count($primeraLinea, ',');
    $conteoTab = substr_count($primeraLinea, "\t");

    $delimitador = ';';
    if ($conteoComa > $conteoPuntoComa && $conteoComa > $conteoTab) {
        $delimitador = ',';
    } elseif ($conteoTab > $conteoPuntoComa && $conteoTab > $conteoComa) {
        $delimitador = "\t";
    }

    rewind($handle);

    $cabecerasRaw = fgetcsv($handle, 0, $delimitador);
    if (!$cabecerasRaw) {
        fclose($handle);
        return [];
    }

    $cabeceras = [];
    foreach ($cabecerasRaw as $c) {
        $cNorm = trim((string)$c);
        $cNorm = preg_replace('/^\xEF\xBB\xBF/', '', $cNorm); // Quitar BOM
        $cNorm = mb_strtolower($cNorm);
        $cNorm = str_replace([' ', '_', '-', '/', '.'], '', $cNorm);
        $cabeceras[] = $cNorm;
    }

    $filas = [];
    while (($datos = fgetcsv($handle, 0, $delimitador)) !== false) {
        if (count(array_filter($datos)) === 0) continue;
        $filaAsoc = [];
        foreach ($cabeceras as $i => $col) {
            $val = trim((string)($datos[$i] ?? ''));
            if (in_array($col, ['cedula', 'cedulaidentidad', 'ci', 'documento', 'ceduladeidentidad'])) {
                $filaAsoc['cedula_identidad'] = $val;
            } elseif (in_array($col, ['nombre', 'nombres'])) {
                $filaAsoc['nombre'] = $val;
            } elseif (in_array($col, ['apellido', 'apellidos'])) {
                $filaAsoc['apellido'] = $val;
            } elseif (in_array($col, ['email', 'correo', 'mail', 'correoelectronico'])) {
                $filaAsoc['email'] = $val;
            } elseif (in_array($col, ['celular', 'telefono', 'tel', 'whatsapp', 'movil'])) {
                $filaAsoc['celular'] = $val;
            } elseif (in_array($col, ['grado', 'curso', 'gradocurso'])) {
                $filaAsoc['grado'] = $val;
            } elseif (in_array($col, ['materia', 'asignatura', 'materiaasignada'])) {
                $filaAsoc['materia'] = $val;
            } else {
                $filaAsoc[$col] = $val;
            }
        }
        $filas[] = $filaAsoc;
    }

    fclose($handle);
    return $filas;
}

try {
    $filas = parsearCsvProfesores($tmpPath);

    if (empty($filas)) {
        http_response_code(422);
        echo json_encode([
            "status" => "error",
            "message" => "El archivo está vacío o no contiene filas con datos válidos."
        ]);
        exit;
    }

    $db = (new Database())->getConnection();
    $profesor = new Profesor($db);

    $idGradoDefecto = filter_input(INPUT_POST, 'id_grado_defecto', FILTER_VALIDATE_INT) ?: null;
    $idMateriaDefecto = filter_input(INPUT_POST, 'id_materia_defecto', FILTER_VALIDATE_INT) ?: null;

    $resultado = $profesor->importarBatch($filas, $idGradoDefecto, $idMateriaDefecto);

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Importación procesada: {$resultado['creados']} creados, {$resultado['actualizados']} actualizados, {$resultado['asignaciones']} asignaciones registradas.",
        "data" => $resultado
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("Error en Profesor/importar_profesores: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al procesar la importación: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
