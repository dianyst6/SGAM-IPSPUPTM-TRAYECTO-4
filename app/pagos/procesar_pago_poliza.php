<?php
/**
 * app/pagos/procesar_pago_poliza.php
 *
 * Descuenta de la póliza el costo de una cita.
 * Valida en el servidor: contrato del titular, cobertura para la fecha de la
 * cita, límites por categoría, montos y doble cobro.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/plan_helper.php';
include 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: text/html; charset=utf-8');

function polizaError(string $mensaje): void
{
    echo '<script>alert(' . json_encode($mensaje, JSON_UNESCAPED_UNICODE) . '); history.back();</script>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    polizaError('Método no permitido.');
}

$idCita       = (int) ($_POST['id_cita'] ?? 0);
$idContrato   = (int) ($_POST['id_contrato'] ?? 0);
$montoPagado  = round((float) ($_POST['monto'] ?? 0), 2);
$usuario      = $_SESSION['username'] ?? 'Sistema';
$estadoPoliza = 'Deducida de Póliza';

if ($idCita <= 0 || $idContrato <= 0) {
    polizaError('Faltan datos de la cita o del contrato.');
}
if ($montoPagado <= 0) {
    polizaError('El monto a descontar debe ser mayor que cero.');
}

$conn->begin_transaction();

try {
    // 1. Cita y control de doble cobro.
    $stmt = $conn->prepare(
        "SELECT c.id_cita, c.id_persona, c.fecha_cita, c.estado_pago, c.estado,
                e.descuento AS descuento_especialidad
         FROM citas c
         INNER JOIN especialidades e ON e.id_especialidad = c.id_especialidad
         WHERE c.id_cita = ? LIMIT 1"
    );
    $stmt->bind_param('i', $idCita);
    $stmt->execute();
    $cita = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$cita) {
        throw new Exception('La cita no existe.');
    }
    if (($cita['estado'] ?? '') === 'cancelada') {
        throw new Exception('La cita está cancelada y no puede cobrarse.');
    }
    if (($cita['estado_pago'] ?? '') === $estadoPoliza) {
        throw new Exception('Esta cita ya fue descontada de la póliza.');
    }

    // El descuento se toma de la especialidad en la base de datos.
    $porcentaje = max(0, min(100, (float) ($cita['descuento_especialidad'] ?? 0)));

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM consumo_plan WHERE id_cita = ?");
    $stmt->bind_param('i', $idCita);
    $stmt->execute();
    if ((int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0) > 0) {
        $stmt->close();
        throw new Exception('Esta cita ya tiene un consumo registrado en la póliza.');
    }
    $stmt->close();

    // 2. Exámenes y monto real de la cita.
    $stmt = $conn->prepare(
        "SELECT ce.id_examen, ce.precio_historico, ex.nombre_examen, ex.id_categoria
         FROM citas_examenes ce
         INNER JOIN examenes ex ON ex.ID_examen = ce.id_examen
         WHERE ce.id_cita = ?
         ORDER BY ce.id_cita_examen ASC"
    );
    $stmt->bind_param('i', $idCita);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $items = [];
    $totalDb = 0.0;
    while ($fila = $resultado->fetch_assoc()) {
        $precio = round((float) $fila['precio_historico'], 2);
        if ($precio < 0) {
            throw new Exception('La cita tiene un precio negativo.');
        }
        $items[] = [
            'id_examen' => (int) $fila['id_examen'],
            'nombre' => $fila['nombre_examen'],
            'id_categoria' => $fila['id_categoria'] !== null ? (int) $fila['id_categoria'] : null,
            'precio' => $precio,
        ];
        $totalDb += $precio;
    }
    $stmt->close();
    $totalDb = round($totalDb, 2);

    if (empty($items) || $totalDb <= 0) {
        throw new Exception('La cita no tiene exámenes con precio registrado.');
    }

    $esperado = round($totalDb * (1 - ($porcentaje / 100)), 2);
    if (abs($montoPagado - $esperado) > 0.05) {
        throw new Exception(
            'El monto a descontar no coincide con el cálculo del servidor ($'
            . number_format($esperado, 2) . ').'
        );
    }
    if ($montoPagado > $totalDb + 0.009) {
        throw new Exception('El monto descontado no puede superar el costo de la cita.');
    }

    // 3. Cobertura real para la fecha de la cita.
    $fechaCitaDia = substr((string) $cita['fecha_cita'], 0, 10);
    $cobertura = contratoValidarCobertura($conn, (int) $cita['id_persona'], $fechaCitaDia);
    if (!$cobertura['ok']) {
        throw new Exception('No se puede descontar de la póliza: ' . $cobertura['motivo']);
    }

    $contrato = $cobertura['contrato'];
    $resumen = $cobertura['resumen'];
    if ((int) $contrato['ID_contrato'] !== $idContrato) {
        throw new Exception('El contrato indicado no corresponde al paciente de la cita.');
    }
    if ($montoPagado > (float) $resumen['disponible'] + 0.009) {
        throw new Exception(
            'La cobertura disponible es $' . number_format((float) $resumen['disponible'], 2)
            . ' y la cita requiere $' . number_format($montoPagado, 2) . '.'
        );
    }

    // 4. Límites por categoría y cantidad.
    $idPlan = (int) $contrato['ID_planes_contrato'];
    $factor = $totalDb > 0 ? $montoPagado / $totalDb : 0.0;

    $porCategoriaCantidad = [];
    $porExamenCantidad = [];
    $componentes = [];

    foreach ($items as $item) {
        $idEx = $item['id_examen'];
        $idCat = $item['id_categoria'];
        $descuento = round($item['precio'] * $factor, 2);

        $porExamenCantidad[$idEx] = ($porExamenCantidad[$idEx] ?? 0) + 1;
        if ($idCat !== null) {
            $porCategoriaCantidad[$idCat] = ($porCategoriaCantidad[$idCat] ?? 0) + 1;
        }

        $componente = planComponenteCobertura($conn, $idPlan, $idEx, $idCat);
        if ($componente === null) {
            throw new Exception('El examen "' . $item['nombre'] . '" no está incluido en el plan.');
        }
        $componentes[$idEx] = [
            'componente' => $componente,
            'id_categoria' => $idCat,
            'nombre' => $item['nombre'],
        ];
    }

    foreach ($componentes as $idEx => $info) {
        $componente = $info['componente'];
        $idCat = $info['id_categoria'];
        $nombre = $info['nombre'];

        if ($componente['cantidad_maxima'] !== null) {
            $maximo = (int) $componente['cantidad_maxima'];
            if ($componente['ID_examen_componentes'] !== null) {
                $usados = planConsumoExamen($conn, $idContrato, $idEx);
                $solicitados = $porExamenCantidad[$idEx] ?? 1;
            } else {
                $usados = $idCat !== null
                    ? planConsumoCategoria($conn, $idContrato, $idCat, 'interno')['cantidad']
                    : 0;
                $solicitados = $idCat !== null
                    ? ($porCategoriaCantidad[$idCat] ?? 1)
                    : ($porExamenCantidad[$idEx] ?? 1);
            }

            if ($usados + $solicitados > $maximo) {
                throw new Exception(
                    'El examen "' . $nombre . '" supera el cupo disponible del plan ('
                    . $maximo . ' permitido(s)).'
                );
            }
        }

        // El tope en dinero por categoría aplica solo a consumos externos.
    }

    // 5. Registrar el consumo, ajustando el último centavo al total cobrado.
    $stmtIns = $conn->prepare(
        "INSERT INTO consumo_plan
            (ID_contrato_plan, id_persona, ID_examen_plan, monto_descontado, id_cita, fecha_consumo)
         VALUES (?, ?, ?, ?, ?, NOW())"
    );

    $idPersona = (int) $cita['id_persona'];
    $acumulado = 0.0;
    $ultimo = count($items) - 1;

    foreach ($items as $indice => $item) {
        $descuento = round($item['precio'] * $factor, 2);
        if ($indice === $ultimo) {
            $descuento = round($montoPagado - $acumulado, 2);
        }
        if ($descuento < 0) {
            $descuento = 0.0;
        }
        $acumulado = round($acumulado + $descuento, 2);

        $idEx = $item['id_examen'];
        $stmtIns->bind_param('iiidi', $idContrato, $idPersona, $idEx, $descuento, $idCita);
        $stmtIns->execute();
    }
    $stmtIns->close();

    // 6. Marcar la cita y registrar bitácora.
    $stmtUpd = $conn->prepare(
        "UPDATE citas SET estado_pago = ? WHERE id_cita = ? AND estado_pago <> ?"
    );
    $stmtUpd->bind_param('sis', $estadoPoliza, $idCita, $estadoPoliza);
    $stmtUpd->execute();
    if ($stmtUpd->affected_rows !== 1) {
        $stmtUpd->close();
        throw new Exception('No se pudo actualizar la cita; puede haber sido cobrada por otra sesión.');
    }
    $stmtUpd->close();

    $accion = 'Pago Cita con Póliza';
    $desc = "Cita #$idCita pagada mediante descuento de póliza. "
        . 'Monto original: $' . number_format($totalDb, 2)
        . ', Monto descontado: $' . number_format($montoPagado, 2)
        . ', Descuento: ' . number_format($porcentaje, 2) . '%.';
    registrarenBitacora($conn, $usuario, $accion, $desc);

    $conn->commit();

    echo "<script>alert('Pago procesado correctamente con saldo de póliza.'); "
        . "window.location.href='/IPSPUPTM/home.php?vista=gestionpagoscitas';</script>";
} catch (Throwable $e) {
    $conn->rollback();
    polizaError($e->getMessage());
}
