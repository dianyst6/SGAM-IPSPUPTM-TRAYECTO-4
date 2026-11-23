<?php
/**
 * app/pagos/get_afiliado_plan_limits.php
 *
 * Cobertura real de un afiliado o beneficiario para una fecha determinada.
 * La cédula se resuelve a id_persona; el contrato se busca por el titular
 * del grupo familiar y la vigencia se valida por fechas.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/plan_helper.php';

header('Content-Type: application/json; charset=utf-8');

$responder = static function (array $datos, int $codigo = 200): void {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
};

$cedula = trim((string) ($_GET['cedula'] ?? ''));
if ($cedula === '') {
    $responder(['success' => false, 'message' => 'Cédula no proporcionada.'], 400);
}

$fecha = (string) ($_GET['fecha'] ?? planHoy());
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !checkdate(
    (int) substr($fecha, 5, 2),
    (int) substr($fecha, 8, 2),
    (int) substr($fecha, 0, 4)
)) {
    $fecha = planHoy();
}

$stmt = $conn->prepare(
    "SELECT p.id_persona, p.cedula, p.nombre, p.apellido,
            CASE
                WHEN a.ID IS NOT NULL THEN 'Afiliado'
                WHEN b.id_beneficiario IS NOT NULL THEN 'Beneficiario'
                ELSE NULL
            END AS tipo
     FROM persona p
     LEFT JOIN afiliados a     ON a.id_persona = p.id_persona
     LEFT JOIN beneficiarios b ON b.id_persona = p.id_persona
     WHERE p.cedula = ?
     LIMIT 1"
);
if (!$stmt) {
    $responder(['success' => false, 'message' => 'No se pudo consultar la persona.'], 500);
}
$stmt->bind_param('s', $cedula);
$stmt->execute();
$persona = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$persona || empty($persona['tipo'])) {
    $responder([
        'success' => false,
        'message' => 'Persona no encontrada o no es un afiliado/beneficiario.',
    ], 404);
}

$idPersona = (int) $persona['id_persona'];
$cobertura = contratoValidarCobertura($conn, $idPersona, $fecha);

if ($cobertura['contrato'] === null || $cobertura['resumen'] === null) {
    $responder([
        'success' => false,
        'message' => $cobertura['motivo'],
        'fecha_consulta' => $fecha,
    ], 404);
}

$contrato = $cobertura['contrato'];
$resumen  = $cobertura['resumen'];
$plan     = $cobertura['plan'];
$idContrato = (int) $contrato['ID_contrato'];
$idPlan     = (int) $contrato['ID_planes_contrato'];

// Límites por categoría y consumo real del contrato.
$categorias = [];
$stmt = $conn->prepare(
    "SELECT comp.id_categoria_componente AS id_categoria,
            cat.nombre_categoria,
            comp.cantidad_maxima,
            comp.monto_maximo
     FROM componentes_planes comp
     INNER JOIN categorias_examenes cat
             ON cat.id_categoria = comp.id_categoria_componente
     WHERE comp.ID_planes_componentes = ?
       AND comp.id_categoria_componente IS NOT NULL
     ORDER BY cat.nombre_categoria ASC"
);
if ($stmt) {
    $stmt->bind_param('i', $idPlan);
    $stmt->execute();
    $resultado = $stmt->get_result();
    while ($fila = $resultado->fetch_assoc()) {
        $idCategoria = (int) $fila['id_categoria'];
        // El modal externo controla dinero; la cantidad es un cupo interno.
        $consumoInterno = planConsumoCategoria($conn, $idContrato, $idCategoria, 'interno');
        $consumoExterno = planConsumoCategoria($conn, $idContrato, $idCategoria, 'externo');
        $limiteMonto = round((float) $fila['monto_maximo'], 2);
        $limiteCantidad = $fila['cantidad_maxima'] !== null
            ? (int) $fila['cantidad_maxima']
            : null;

        $categorias[] = [
            'id_categoria' => $idCategoria,
            'nombre_categoria' => $fila['nombre_categoria'],
            'monto_maximo' => $limiteMonto,
            'cantidad_maxima' => $limiteCantidad,
            'cantidad_consumida' => $consumoInterno['cantidad'],
            'cantidad_disponible' => $limiteCantidad !== null
                ? max(0, $limiteCantidad - $consumoInterno['cantidad'])
                : null,
            'consumido' => round($consumoExterno['monto'], 2),
            'disponible' => max(0, round($limiteMonto - $consumoExterno['monto'], 2)),
        ];
    }
    $stmt->close();
}

$responder([
    'success' => true,
    'afiliado' => [
        'id_persona' => $idPersona,
        'cedula' => $persona['cedula'],
        'nombre' => trim($persona['nombre'] . ' ' . $persona['apellido']),
        'tipo' => $persona['tipo'],
        'id_contrato' => $idContrato,
        'plan' => $plan['nombre_plan'] ?? '',
        'anio' => (int) ($contrato['anio'] ?? 0),
        'fecha_inicio' => $contrato['fecha_inicio'],
        'fecha_fin' => $contrato['fecha_fin'],
        'cobertura_total' => $resumen['cobertura'],
        'total_gastado' => $resumen['consumido'],
        'saldo_disponible' => $resumen['disponible'],
        'pago_inicial_ok' => $resumen['inicial_completo'],
        'inicial_pagado' => $resumen['inicial_pagado'],
        'inicial_requerido' => $resumen['monto_inicial'],
        'inicial_pendiente' => $resumen['saldo_inicial'],
        'solvente' => !$resumen['en_mora'],
        'meses_deuda' => $resumen['cuotas_vencidas'],
        'cuotas_vencidas' => $resumen['cuotas_vencidas'],
        'deuda_vencida' => $resumen['deuda_vencida'],
        'cobertura_activa' => $cobertura['cobertura_activa'],
        'vigente' => $cobertura['vigente'],
        'motivo' => $cobertura['motivo'],
        'fecha_consulta' => $fecha,
        'fecha_mora' => $cobertura['fecha_mora'],
    ],
    'categorias' => $categorias,
]);
