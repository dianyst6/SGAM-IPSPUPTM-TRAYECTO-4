<?php
/**
 * app/citas/get_examenes.php
 *
 * Devuelve los exámenes de una especialidad con la cobertura real del
 * contrato del titular. El consumo se calcula desde consumo_plan, no
 * desde el estado de pago de las citas.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/plan_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['id_especialidad'])) {
    echo json_encode([]);
    exit;
}

$idEsp    = (int) $_GET['id_especialidad'];
$tipoPac  = (string) ($_GET['tipo_pac'] ?? 'externo');
$idPac    = (int) ($_GET['id_paciente'] ?? 0);

$fecha = (string) ($_GET['fecha'] ?? planHoy());
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = planHoy();
}

$stmt = $conn->prepare(
    "SELECT ID_examen, nombre_examen, precio, id_categoria
     FROM examenes
     WHERE ID_especialidad_examenes = ? AND estado = 'activo'
     ORDER BY nombre_examen ASC"
);
if (!$stmt) {
    echo json_encode([]);
    exit;
}
$stmt->bind_param('i', $idEsp);
$stmt->execute();
$resultado = $stmt->get_result();

$filas = [];
while ($fila = $resultado->fetch_assoc()) {
    $filas[] = $fila;
}
$stmt->close();

$cobertura = null;
if ($tipoPac === 'interno' && $idPac > 0) {
    $cobertura = contratoValidarCobertura($conn, $idPac, $fecha);
}

$examenes = [];

foreach ($filas as $fila) {
    $idExamen   = (int) $fila['ID_examen'];
    $idCategoria = $fila['id_categoria'] !== null ? (int) $fila['id_categoria'] : null;
    $precio     = round((float) $fila['precio'], 2);

    $salida = [
        'ID_examen' => $idExamen,
        'nombre_examen' => $fila['nombre_examen'],
        'precio' => $precio,
        'id_categoria' => $idCategoria,
        'disponibles' => 'ilimitado',
        'is_disabled' => false,
    ];

    if ($tipoPac === 'interno' && $idPac > 0) {
        if ($cobertura === null || !$cobertura['ok']) {
            $salida['disponibles'] = 0;
            $salida['is_disabled'] = true;
            $salida['etiqueta_bloqueo'] = 'Sin cobertura';
            $salida['motivo_bloqueo'] = $cobertura['motivo'] ?? 'Sin cobertura activa.';
            $examenes[] = $salida;
            continue;
        }

        $contrato   = $cobertura['contrato'];
        $resumen    = $cobertura['resumen'];
        $idPlan     = (int) $contrato['ID_planes_contrato'];
        $idContrato = (int) $contrato['ID_contrato'];

        $componente = planComponenteCobertura($conn, $idPlan, $idExamen, $idCategoria);

        if ($componente === null) {
            $salida['disponibles'] = 0;
            $salida['is_disabled'] = true;
            $salida['etiqueta_bloqueo'] = 'No incluido';
            $salida['motivo_bloqueo'] = 'Este examen no está incluido en el plan.';
            $examenes[] = $salida;
            continue;
        }

        $consumoCategoria = $idCategoria !== null
            ? planConsumoCategoria($conn, $idContrato, $idCategoria, 'interno')
            : ['cantidad' => 0, 'monto' => 0.0];

        $maxCantidad = $componente['cantidad_maxima'] !== null
            ? (int) $componente['cantidad_maxima']
            : null;
        $maxMonto = $componente['monto_maximo'] !== null
            ? round((float) $componente['monto_maximo'], 2)
            : null;

        $cantidadUsada = $componente['ID_examen_componentes'] !== null
            ? planConsumoExamen($conn, $idContrato, $idExamen)
            : $consumoCategoria['cantidad'];

        $restan = $maxCantidad !== null ? $maxCantidad - $cantidadUsada : null;
        $disponibleGlobal = (float) $resumen['disponible'];

        $motivos = [];
        $etiqueta = '';

        if ($restan !== null && $restan <= 0) {
            $motivos[] = 'Sin cupo en la categoría (máximo ' . $maxCantidad . ').';
            $etiqueta = 'Sin cupo';
        }

        // Dentro de la institución la cobertura se controla por cantidad.
        // El tope en dinero de la categoría aplica solo a consumos externos.

        if ($disponibleGlobal < $precio) {
            $motivos[] = 'Cobertura global insuficiente ($'
                . number_format($disponibleGlobal, 2) . ' disponibles).';
            if ($etiqueta === '') {
                $etiqueta = 'Cobertura insuficiente';
            }
        }

        $salida['disponibles'] = $restan !== null ? max(0, $restan) : 'ilimitado';
        $salida['is_disabled'] = !empty($motivos);
        $salida['etiqueta_bloqueo'] = $etiqueta;
        $salida['cantidad_maxima'] = $maxCantidad;
        $salida['monto_maximo'] = $maxMonto; // tope referencial para consumos externos
        $salida['cantidad_consumida'] = $cantidadUsada;
        if (!empty($motivos)) {
            $salida['motivo_bloqueo'] = implode(' ', $motivos);
        }
    }

    $examenes[] = $salida;
}

echo json_encode($examenes, JSON_UNESCAPED_UNICODE);
