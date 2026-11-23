<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/plan_helper.php';

header('Content-Type: application/json; charset=utf-8');

$idContrato = isset($_POST['id_contrato']) ? (int) $_POST['id_contrato'] : 0;
if ($idContrato <= 0) {
    echo json_encode(['success' => false, 'error' => 'Contrato no especificado.']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT cp.*, per.nombre, per.apellido, per.cedula, pl.nombre_plan
     FROM contrato_plan cp
     INNER JOIN persona per ON per.id_persona = cp.id_persona
     INNER JOIN planes pl ON pl.ID_planes = cp.ID_planes_contrato
     WHERE cp.ID_contrato = ? LIMIT 1"
);
$stmt->bind_param('i', $idContrato);
$stmt->execute();
$contrato = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$contrato) {
    echo json_encode(['success' => false, 'error' => 'El contrato no existe.']);
    exit;
}

$resumen = contratoResumen($conn, $contrato);
$pendientes = contratoCuotasPendientes($conn, $idContrato);

$cuotasPendientes = [];
$saldoCuotas = 0.0;
foreach ($pendientes as $cuota) {
    $saldo = round((float) $cuota['monto'] - (float) $cuota['monto_pagado'], 2);
    $saldoCuotas += $saldo;
    $cuotasPendientes[] = [
        'id_cuota' => (int) $cuota['id_cuota'],
        'numero' => (int) $cuota['numero_cuota'],
        'fecha_vencimiento' => $cuota['fecha_vencimiento'],
        'monto' => round((float) $cuota['monto'], 2),
        'saldo' => $saldo,
        'vencida' => $cuota['fecha_vencimiento'] < planHoy(),
    ];
}
$saldoCuotas = round($saldoCuotas, 2);

$saldoTotal = round($resumen['saldo_inicial'] + $saldoCuotas, 2);
$tasaRespaldo = isset($_POST['tasa_bcv']) ? (float) $_POST['tasa_bcv'] : 0.0;
$tasaBcv = planObtenerTasaBCV($tasaRespaldo);

echo json_encode([
    'success' => true,
    'contrato' => [
        'id' => (int) $contrato['ID_contrato'],
        'anio' => (int) ($contrato['anio'] ?? 0),
        'plan' => $contrato['nombre_plan'],
        'estado' => $contrato['estado_contrato'],
        'fecha_inicio' => $contrato['fecha_inicio'],
        'fecha_fin' => $contrato['fecha_fin'],
        'titular' => trim($contrato['nombre'] . ' ' . $contrato['apellido']),
        'cedula' => $contrato['cedula'],
    ],
    'monto_total' => $resumen['monto_total'],
    'saldo' => $saldoTotal,
    'saldo_cuotas' => $saldoCuotas,
    'inicial_requerido' => $resumen['monto_inicial'],
    'inicial_pagado' => $resumen['inicial_pagado'],
    'inicial_pendiente' => $resumen['saldo_inicial'],
    'inicial_completo' => $resumen['inicial_completo'],
    'cuotas_pendientes' => $cuotasPendientes,
    'cuotas_pagadas' => $resumen['cuotas_pagadas'],
    'cuotas_total' => $resumen['cuotas_total'],
    'cuotas_vencidas' => $resumen['cuotas_vencidas'],
    'deuda_vencida' => $resumen['deuda_vencida'],
    'en_mora' => $resumen['en_mora'],
    'cobertura_activa' => $resumen['cobertura_activa'],
    'motivo' => $resumen['motivo'],
    'cobertura' => $resumen['cobertura'],
    'consumido' => $resumen['consumido'],
    'disponible' => $resumen['disponible'],
    'tasa_bcv' => $tasaBcv,
]);
