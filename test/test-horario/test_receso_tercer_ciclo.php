<?php
require_once __DIR__ . '/../../src/classes/Horario.php';

$horario = new Horario(null);
$recesoPermitido = !$horario->interfiereRecesoTercerCiclo(
    ['grado' => '7°', 'materia' => 'RECESO'],
    '09:40',
    '10:10'
);
$materiaNormalBloqueada = $horario->interfiereRecesoTercerCiclo(
    ['grado' => '7°', 'materia' => 'MATEMATICA'],
    '09:40',
    '10:10'
);

if (!$recesoPermitido || !$materiaNormalBloqueada) {
    fwrite(STDERR, "FALLA: la excepción de receso no está limitada correctamente.\n");
    exit(1);
}

echo "OK: RECESO permitido y materia normal bloqueada.\n";
