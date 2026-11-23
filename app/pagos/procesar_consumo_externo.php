<?php
/**
 * app/pagos/procesar_consumo_externo.php
 *
 * Registra consumos extra-institucionales contra la cobertura real del
 * contrato del titular. Valida en el servidor persona, contrato, cobertura
 * y límites por categoría.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/plan_helper.php';
include 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$idContrato = (int) ($_POST['id_contrato'] ?? 0);
$idPersona  = (int) ($_POST['id_persona'] ?? 0);
$items = json_decode((string) ($_POST['items'] ?? ''), true);

if ($idContrato <= 0 || $idPersona <= 0 || !is_array($items) || empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$usuario = $_SESSION['username'] ?? 'Sistema';

$conn->begin_transaction();

try {
    // 1. La persona debe pertenecer al grupo familiar del titular.
    $idTitular = planPersonaTitular($conn, $idPersona);
    if ($idTitular === null) {
        throw new Exception('La persona no está registrada como afiliado ni como beneficiario.');
    }

    // 2. Cobertura real del contrato para hoy.
    $cobertura = contratoValidarCobertura($conn, $idPersona, planHoy());
    if (!$cobertura['ok']) {
        throw new Exception('No se puede registrar el consumo: ' . $cobertura['motivo']);
    }

    $contrato = $cobertura['contrato'];
    $resumen = $cobertura['resumen'];
    if ((int) $contrato['ID_contrato'] !== $idContrato) {
        throw new Exception('El contrato indicado no corresponde al paciente.');
    }
    if ((int) $contrato['id_persona'] !== $idTitular) {
        throw new Exception('El contrato indicado no pertenece al titular del grupo familiar.');
    }

    $idPlan = (int) $contrato['ID_planes_contrato'];

    // 3. Normalizar y validar los ítems.
    $itemsValidos = [];
    $porCategoria = [];
    $totalSolicitado = 0.0;

    foreach (array_values($items) as $indice => $item) {
        if (!is_array($item)) {
            throw new Exception('El ítem #' . ($indice + 1) . ' no es válido.');
        }

        $nombre = trim((string) ($item['nombre'] ?? ''));
        $idCategoria = (int) ($item['id_categoria'] ?? 0);
        $costo = round((float) ($item['costo'] ?? 0), 2);

        if ($nombre === '') {
            throw new Exception('Debe indicar el nombre del estudio o consulta del ítem #' . ($indice + 1) . '.');
        }
        if ($idCategoria <= 0) {
            throw new Exception('Debe seleccionar una categoría para el ítem #' . ($indice + 1) . '.');
        }
        if ($costo <= 0) {
            throw new Exception('El costo del ítem #' . ($indice + 1) . ' debe ser mayor que cero.');
        }

        $componente = planComponenteCobertura($conn, $idPlan, null, $idCategoria);
        if ($componente === null) {
            throw new Exception('La categoría del ítem #' . ($indice + 1) . ' no está incluida en el plan.');
        }

        $itemsValidos[] = [
            'nombre' => $nombre,
            'id_categoria' => $idCategoria,
            'costo' => $costo,
        ];

        if (!isset($porCategoria[$idCategoria])) {
            $porCategoria[$idCategoria] = ['monto' => 0.0, 'cantidad' => 0];
        }
        $porCategoria[$idCategoria]['monto'] += $costo;
        $porCategoria[$idCategoria]['cantidad']++;
        $totalSolicitado += $costo;
    }

    $totalSolicitado = round($totalSolicitado, 2);
    if ($totalSolicitado > (float) $resumen['disponible'] + 0.009) {
        throw new Exception(
            'El consumo supera la cobertura disponible del contrato ($'
            . number_format((float) $resumen['disponible'], 2) . ').'
        );
    }

    // 4. Validar límites acumulados por categoría.
    foreach ($porCategoria as $idCategoria => $solicitado) {
        $componente = planComponenteCobertura($conn, $idPlan, null, $idCategoria);
        if ($componente === null) {
            throw new Exception('La categoría seleccionada no está incluida en el plan.');
        }

        $maximoMonto = round((float) $componente['monto_maximo'], 2);
        if ($maximoMonto <= 0) {
            throw new Exception('La categoría seleccionada no tiene cobertura disponible en este plan.');
        }

        $consumo = planConsumoCategoria($conn, $idContrato, (int) $idCategoria, 'externo');
        if ($consumo['monto'] + $solicitado['monto'] > $maximoMonto + 0.009) {
            throw new Exception(
                'La categoría supera su límite de $' . number_format($maximoMonto, 2)
                . '. Consumido: $' . number_format($consumo['monto'], 2)
                . '; solicitado: $' . number_format($solicitado['monto'], 2) . '.'
            );
        }

        // Fuera de la institución manda el dinero; el cupo de cantidad es interno.
    }

    // 5. Registrar los consumos.
    $stmt = $conn->prepare(
        "INSERT INTO consumo_plan
            (ID_contrato_plan, id_cita, id_persona, ID_examen_plan,
             nombre_estudio_externo, id_categoria_externa, monto_descontado, fecha_consumo)
         VALUES (?, NULL, ?, NULL, ?, ?, ?, NOW())"
    );

    foreach ($itemsValidos as $item) {
        $idCategoria = $item['id_categoria'];
        $stmt->bind_param(
            'iisid',
            $idContrato,
            $idPersona,
            $item['nombre'],
            $idCategoria,
            $item['costo']
        );
        $stmt->execute();
    }
    $stmt->close();

    registrarenBitacora(
        $conn,
        $usuario,
        'Consumo Externo',
        'Consumo externo por $' . number_format($totalSolicitado, 2)
        . ' registrado para la persona #' . $idPersona
        . ' en el contrato #' . $idContrato . '.'
    );

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Consumo externo registrado y deducido con éxito.',
        'total' => $totalSolicitado,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
