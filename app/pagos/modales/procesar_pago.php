<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/plan_helper.php';
include 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

$idContrato = isset($_POST['id_contrato']) ? (int) $_POST['id_contrato'] : 0;
$tipoRecibido = (string) ($_POST['tipo_pago'] ?? 'Cuota');
$tipoPago = $tipoRecibido === 'Pago Inicial' ? 'Pago Inicial' : 'Cuota';
$monto = round((float) str_replace(',', '.', (string) ($_POST['monto_cuota'] ?? 0)), 2);
$fechaPago = trim((string) ($_POST['fecha_pago'] ?? planHoy()));
$metodoPago = trim((string) ($_POST['metodo_pago'] ?? ''));
$cantidadCuotas = isset($_POST['cantidad_cuotas']) ? (int) $_POST['cantidad_cuotas'] : 1;

$metodosValidos = ['Transferencia', 'Efectivo', 'Punto de Venta', 'Pago Móvil'];

$redirigir = static function (string $mensaje, string $tipo): void {
    $_SESSION['flash_msg'] = $mensaje;
    $_SESSION['flash_type'] = $tipo;
    header("Location: /IPSPUPTM/home.php?vista=gestionpagoscontrato");
    exit();
};

if ($idContrato <= 0) {
    $redirigir('Debe seleccionar un contrato.', 'danger');
}

if (!in_array($metodoPago, $metodosValidos, true)) {
    $redirigir('El método de pago no es válido.', 'danger');
}

$fechaObj = DateTime::createFromFormat('Y-m-d', $fechaPago);
if (!$fechaObj || $fechaObj->format('Y-m-d') !== $fechaPago) {
    $redirigir('La fecha de pago no es válida.', 'danger');
}
if ($fechaPago > planHoy()) {
    $redirigir('La fecha de pago no puede ser futura.', 'danger');
}

$tasaBcv = planObtenerTasaBCV(isset($_POST['tasa_bcv']) ? (float) $_POST['tasa_bcv'] : 0.0);
if ($tasaBcv <= 0) {
    $redirigir('No se pudo obtener la tasa BCV. Intente de nuevo.', 'danger');
}

$conn->begin_transaction();

try {
    $resultado = contratoRegistrarPago(
        $conn,
        $idContrato,
        $tipoPago,
        $monto,
        $fechaPago,
        $metodoPago,
        $tasaBcv,
        $tipoPago === 'Cuota' ? $cantidadCuotas : null
    );

    $conn->commit();

    $usuarioLog = $_SESSION['username'] ?? 'Sistema';
    $detalle = $tipoPago === 'Pago Inicial'
        ? 'Abono al pago inicial'
        : $resultado['cuotas'] . ' cuota(s) completa(s)';

    registrarEnBitacora(
        $conn,
        $usuarioLog,
        "Pago de contrato",
        "Contrato $idContrato: $detalle por $" . number_format($resultado['monto'], 2)
        . ' (Bs ' . number_format($resultado['monto_bs'], 2)
        . ' a tasa ' . number_format($tasaBcv, 2) . ')'
    );

    if ($tipoPago === 'Pago Inicial') {
        if ($resultado['inicial_completo']) {
            $mensaje = 'Pago inicial completado. $' . number_format($resultado['monto'], 2)
                . ' (Bs ' . number_format($resultado['monto_bs'], 2) . ').';
            if ($resultado['estado'] !== 'Activo') {
                $mensaje .= ' El contrato sigue Pendiente: regularice la deuda de años anteriores.';
            }
        } else {
            $mensaje = 'Abono al pago inicial registrado: $' . number_format($resultado['monto'], 2)
                . ' (Bs ' . number_format($resultado['monto_bs'], 2) . ').'
                . ' Pendiente: $' . number_format($resultado['saldo_inicial'], 2) . '.';
        }
    } else {
        $mensaje = $resultado['cuotas'] . ' cuota(s) pagada(s) por $'
            . number_format($resultado['monto'], 2)
            . ' (Bs ' . number_format($resultado['monto_bs'], 2) . '). Estado: '
            . $resultado['estado'] . '.';
    }

    $redirigir($mensaje, 'success');

} catch (Exception $e) {
    $conn->rollback();
    $redirigir($e->getMessage(), 'danger');
}
?>
