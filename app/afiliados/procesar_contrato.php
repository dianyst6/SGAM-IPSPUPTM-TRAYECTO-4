<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/persona_helper.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/plan_helper.php';
include 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cedula = trim((string) ($_POST['cedula_afiliado'] ?? ''));
    $idPlan = (int) ($_POST['id_plan'] ?? 0);
    $diaPago = isset($_POST['dia_pago_mensual'])
        ? (int) $_POST['dia_pago_mensual']
        : PLAN_DIA_PAGO_DEFECTO;
    $esAdmin = ((int) ($_SESSION['role_id'] ?? 0)) === 1;

    // La cédula es la entrada; el contrato se guarda por id_persona.
    $idPersona = buscarIdPersonaPorCedula($conn, $cedula);
    if ($idPersona === null) {
        echo "Error: el afiliado no existe.";
        exit;
    }

    $conn->begin_transaction();

    try {
        $resultado = contratoCrearAnual(
            $conn,
            $idPersona,
            $idPlan,
            $diaPago,
            $esAdmin,
            planHoy()
        );

        $conn->commit();

        $contrato = $resultado['contrato'];
        $resumen = $resultado['resumen'];
        $usuario_log = $_SESSION['username'] ?? 'Sistema';
        registrarEnBitacora(
            $conn,
            $usuario_log,
            "Contrato creado",
            "Contrato {$contrato['anio']} para cédula $cedula. Inicial: $"
            . number_format($resumen['monto_inicial'], 2)
        );

        echo "<script>alert('Plan contratado exitosamente'); window.location.href='gestion_afiliados.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . htmlspecialchars($e->getMessage());
    }
}
?>
