
<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';

$cedula = isset($_GET['cedula']) ? mysqli_real_escape_string($conn, $_GET['cedula']) : '';

if (empty($cedula)) {
    echo json_encode(['success' => false, 'message' => 'Cédula no proporcionada']);
    exit;
}

// 1. Buscar si la cédula pertenece a un afiliado o beneficiario
$sql_persona = "SELECT p.id_persona, p.cedula, p.nombre, p.apellido, 'Afiliado' as tipo, a.ID as id_vinculo 
                FROM persona p 
                JOIN afiliados a ON p.id_persona = a.id_persona 
                WHERE p.cedula = '$cedula'
                UNION
                SELECT p.id_persona, p.cedula, p.nombre, p.apellido, 'Beneficiario' as tipo, b.id_beneficiario as id_vinculo 
                FROM persona p 
                JOIN beneficiarios b ON p.id_persona = b.id_persona 
                WHERE p.cedula = '$cedula'";

$res_persona = mysqli_query($conn, $sql_persona);
$persona = mysqli_fetch_assoc($res_persona);

if (!$persona) {
    echo json_encode(['success' => false, 'message' => 'Persona no encontrada o no es un afiliado/beneficiario']);
    exit;
}

$id_persona = (int) $persona['id_persona'];
$id_vinculo = $persona['id_vinculo'];
$tipo = $persona['tipo'];

// 2. Obtener el contrato activo (Si es beneficiario, buscar contrato del titular)
if ($tipo == 'Beneficiario') {
    $sql_titular = "SELECT id_afiliado FROM beneficiarios WHERE id_beneficiario = '$id_vinculo'";
    $res_titular = mysqli_query($conn, $sql_titular);
    $id_titular_vinculo = mysqli_fetch_assoc($res_titular)['id_afiliado'];
    
    $sql_contrato = "SELECT cp.ID_contrato, cp.fecha_inicio, cp.monto_total, p.nombre_plan, p.ID_planes, p.monto_cobertura 
                     FROM contrato_plan cp 
                     JOIN planes p ON cp.ID_planes_contrato = p.ID_planes 
                     JOIN afiliados a ON cp.id_persona = a.id_persona
                     WHERE a.ID = '$id_titular_vinculo' AND cp.estado_contrato = 'Activo'";
} else {
    $sql_contrato = "SELECT cp.ID_contrato, cp.fecha_inicio, cp.monto_total, p.nombre_plan, p.ID_planes, p.monto_cobertura 
                     FROM contrato_plan cp 
                     JOIN planes p ON cp.ID_planes_contrato = p.ID_planes 
                     WHERE cp.id_persona = '$id_persona' AND cp.estado_contrato = 'Activo'";
}

$res_contrato = mysqli_query($conn, $sql_contrato);
$contrato = mysqli_fetch_assoc($res_contrato);

if (!$contrato) {
    echo json_encode(['success' => false, 'message' => 'No tiene un contrato de plan de salud activo']);
    exit;
}

$id_contrato = $contrato['ID_contrato'];
$id_plan = $contrato['ID_planes'];
$fecha_inicio = $contrato['fecha_inicio'];

// 2.1 Verificar Pago Inicial y Solvencia

// Verificar si el plan está totalmente pagado
$sql_pagos_totales = "SELECT SUM(monto_cuota) as total_pagado FROM pagos_contrato WHERE ID_contrato = '$id_contrato'";
$res_pagos_totales = mysqli_query($conn, $sql_pagos_totales);
$total_pagado = mysqli_fetch_assoc($res_pagos_totales)['total_pagado'] ?? 0;
$monto_total_contrato = $contrato['monto_total'] ?? 0;

$plan_solventado = ($monto_total_contrato > 0 && floatval($total_pagado) >= floatval($monto_total_contrato));

if ($plan_solventado) {
    $pago_inicial_ok = true;
    $solvente = true;
    $meses_deuda = 0;
} else {
    // A. Pago Inicial (30%)
    $sql_pago_inicial = "SELECT COUNT(*) as cuenta FROM pagos_contrato WHERE ID_contrato = '$id_contrato' AND tipo_pago = 'Pago Inicial'";
    $res_pago_inicial = mysqli_query($conn, $sql_pago_inicial);
    $pago_inicial_ok = (mysqli_fetch_assoc($res_pago_inicial)['cuenta'] > 0);

    // B. Solvencia (Meses de deuda)
    // El conteo de cuotas empieza 3 meses después de la fecha de inicio del contrato
    $fecha_inicio_cuotas = new DateTime($fecha_inicio);
    $fecha_inicio_cuotas->modify('+3 months');
    $hoy = new DateTime();

    // Si todavía no han pasado 3 meses desde el inicio, no hay cuotas pendientes
    if ($hoy <= $fecha_inicio_cuotas) {
        $meses_transcurridos = 0;
    } else {
        $interval = $fecha_inicio_cuotas->diff($hoy);
        $meses_transcurridos = ($interval->y * 12) + $interval->m + 1; // +1 porque el mes actual cuenta
    }

    // Contamos cuántas cuotas ha pagado
    $sql_cuotas = "SELECT COUNT(*) as cuenta FROM pagos_contrato WHERE ID_contrato = '$id_contrato' AND tipo_pago = 'Cuota'";
    $res_cuotas = mysqli_query($conn, $sql_cuotas);
    $cuotas_pagadas = mysqli_fetch_assoc($res_cuotas)['cuenta'];

    $meses_deuda = $meses_transcurridos - $cuotas_pagadas;
    if ($meses_deuda < 0) $meses_deuda = 0;

    // Si ya venció el periodo de gracia (meses_transcurridos > 0) pero no ha pagado NINGUNA cuota,
    // se le bloquea de inmediato (cero tolerancia si nunca ha empezado a pagar).
    // Si ya tiene al menos una cuota pagada, se le permite la tolerancia normal de hasta 2 meses de deuda.
    if ($meses_transcurridos > 0 && $cuotas_pagadas == 0) {
        $solvente = false;
    } else {
        $solvente = ($meses_deuda <= 2); // Máximo 2 meses de deuda permitidos
    }
}

// 3. Obtener límites de categorías y consumo actual
$sql_categorias = "SELECT c.id_categoria, c.nombre_categoria, cp_comp.monto_maximo 
                   FROM componentes_planes cp_comp 
                   JOIN categorias_examenes c ON cp_comp.id_categoria_componente = c.id_categoria 
                   WHERE cp_comp.ID_planes_componentes = '$id_plan' AND cp_comp.id_categoria_componente IS NOT NULL";

$res_cats = mysqli_query($conn, $sql_categorias);
$categorias = [];

while ($cat = mysqli_fetch_assoc($res_cats)) {
    $id_cat = $cat['id_categoria'];
    
    // Calcular consumo de esta categoría (Exámenes en el catálogo + Estudios externos)
    $sql_consumo = "SELECT SUM(monto_descontado) as consumido 
                    FROM consumo_plan 
                    WHERE ID_contrato_plan = '$id_contrato' 
                    AND (
                        ID_examen_plan IN (SELECT ID_examen FROM examenes WHERE id_categoria = '$id_cat')
                        OR id_categoria_externa = '$id_cat'
                    )";
    $res_consumo = mysqli_query($conn, $sql_consumo);
    $consumido = mysqli_fetch_assoc($res_consumo)['consumido'] ?? 0;
    
    $cat['consumido'] = floatval($consumido);
    $cat['disponible'] = floatval($cat['monto_maximo']) - floatval($consumido);
    $categorias[] = $cat;
}

$sql_consumo_total = "SELECT SUM(monto_descontado) as total FROM consumo_plan WHERE ID_contrato_plan = '$id_contrato'";
$res_total = mysqli_query($conn, $sql_consumo_total);
$total_gastado = mysqli_fetch_assoc($res_total)['total'] ?? 0;
$saldo_disponible_global = floatval($contrato['monto_cobertura']) - floatval($total_gastado);

echo json_encode([
    'success' => true,
    'afiliado' => [
        'id_persona' => $id_persona,
        'nombre' => $persona['nombre'] . ' ' . $persona['apellido'],
        'tipo' => $tipo,
        'id_contrato' => $id_contrato,
        'plan' => $contrato['nombre_plan'],
        'cobertura_total' => floatval($contrato['monto_cobertura']),
        'total_gastado' => floatval($total_gastado),
        'saldo_disponible' => floatval($saldo_disponible_global),
        'pago_inicial_ok' => $pago_inicial_ok,
        'solvente' => $solvente,
        'meses_deuda' => $meses_deuda
    ],
    'categorias' => $categorias
]);
