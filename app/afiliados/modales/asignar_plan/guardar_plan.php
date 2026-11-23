<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);

require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/persona_helper.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/plan_helper.php';
include 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['cedula'], $_POST['id_planes_contrato'])) {
        echo "Faltan datos requeridos en el formulario.";
        exit();
    }

    $cedula = trim((string) $_POST['cedula']);
    $idPlan = (int) $_POST['id_planes_contrato'];
    $diaPago = isset($_POST['dia_pago_mensual']) ? (int) $_POST['dia_pago_mensual'] : PLAN_DIA_PAGO_DEFECTO;
    $esAdmin = ((int) ($_SESSION['role_id'] ?? 0)) === 1;
    $fechaInscripcion = planHoy();
    $fueraDePeriodo = !planPeriodoInscripcionAbierto($fechaInscripcion);

    $conn->begin_transaction();

    try {
        if (!preg_match('/^[0-9]{1,8}$/', $cedula)) {
            throw new Exception('La cédula debe contener solo números (máximo 8).');
        }

        if ($fueraDePeriodo && $esAdmin && empty($_POST['autorizar_excepcion'])) {
            throw new Exception(
                'La inscripción está fuera del periodo (' . planPeriodoInscripcionTexto()
                . '). Debe confirmar la autorización excepcional.'
            );
        }

        // La cédula es la entrada; se resuelve el id_persona interno.
        $idPersona = buscarIdPersonaPorCedula($conn, $cedula);
        if ($idPersona === null) {
            throw new Exception('El afiliado no existe.');
        }

        $stmt = $conn->prepare("SELECT id_persona FROM afiliados WHERE id_persona = ?");
        $stmt->bind_param("i", $idPersona);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $stmt->close();
            throw new Exception('El afiliado no existe.');
        }
        $stmt->close();

        $resultado = contratoCrearAnual(
            $conn,
            $idPersona,
            $idPlan,
            $diaPago,
            $esAdmin,
            $fechaInscripcion
        );

        $conn->commit();

        $contrato = $resultado['contrato'];
        $resumen = $resultado['resumen'];
        $cuotas = $resumen['cuotas'];
        $montoCuota = $cuotas ? (float) $cuotas[0]['monto'] : 0.0;

        $usuario_log = $_SESSION['username'] ?? 'Sistema';
        $detalleExcepcion = $fueraDePeriodo ? ' (inscripción excepcional autorizada)' : '';
        registrarEnBitacora(
            $conn,
            $usuario_log,
            "Asignación de Plan",
            "Contrato {$contrato['anio']} creado para cédula $cedula. Inicial: $"
            . number_format($resumen['monto_inicial'], 2)
            . ", cuota: $" . number_format($montoCuota, 2)
            . " x " . count($cuotas) . $detalleExcepcion
        );

        $_SESSION['flash_msg'] = "Contrato {$contrato['anio']} creado. Pago inicial del 30 %: $"
            . number_format($resumen['monto_inicial'], 2)
            . ". Cuota mensual: $" . number_format($montoCuota, 2)
            . " (" . count($cuotas) . " cuotas). El afiliado queda como "
            . "\"Pago inicial pendiente\" hasta que registre ese monto.";
        $_SESSION['flash_type'] = "success";

        header("Location: /IPSPUPTM/home.php?vista=afiliados&filtro=todos");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['flash_msg'] = $e->getMessage();
        $_SESSION['flash_type'] = "danger";
        header("Location: /IPSPUPTM/home.php?vista=afiliados&filtro=todos");
        exit();
    }
} else {
    echo "Método de solicitud no válido.";
}

$conn->close();
?>
