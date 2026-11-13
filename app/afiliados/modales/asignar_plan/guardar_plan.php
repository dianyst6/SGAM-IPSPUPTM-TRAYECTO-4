<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);

require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/config/persona_helper.php';
include 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cedula'], $_POST['id_planes_contrato'], $_POST['fecha_inicio'])) {
        $cedula = htmlspecialchars($_POST['cedula']);
        $id_plan = $_POST['id_planes_contrato'];
        $monto_total = $_POST['monto_total'];
        $fecha_inicio = $_POST['fecha_inicio'];
        $fecha_fin = $_POST['fecha_fin'];
        $frecuencia = $_POST['frecuencia_pago'];
        $dia_pago = $_POST['dia_pago_mensual'];
        $estado_contrato = $_POST['estado_contrato'];

        $conn->begin_transaction();

        try {
            // La cédula es la entrada; se resuelve el id_persona interno.
            $id_persona = buscarIdPersonaPorCedula($conn, $cedula);
            if ($id_persona === null) {
                throw new Exception("El afiliado no existe.");
            }

            // Verificar que el afiliado existe
            $stmt = $conn->prepare("SELECT id_persona FROM afiliados WHERE id_persona = ?");
            $stmt->bind_param("i", $id_persona);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception("El afiliado no existe.");
            }
            $stmt->close();

            // Si se está activando un plan, desactivar los anteriores para evitar conflictos.
            if ($estado_contrato === 'Activo') {
                $stmt_upd = $conn->prepare("UPDATE contrato_plan SET estado_contrato = 'Inactivo' WHERE id_persona = ? AND estado_contrato = 'Activo'");
                $stmt_upd->bind_param("i", $id_persona);
                $stmt_upd->execute();
                $stmt_upd->close();
            }

            // Insertar el nuevo contrato (la relación es por id_persona)
            $sql_contrato = "INSERT INTO contrato_plan (ID_planes_contrato, id_persona, fecha_inicio, fecha_fin, monto_total, frecuencia_pago, dia_pago_mensual, estado_contrato) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_c = $conn->prepare($sql_contrato);
            $stmt_c->bind_param("iissdsis", $id_plan, $id_persona, $fecha_inicio, $fecha_fin, $monto_total, $frecuencia, $dia_pago, $estado_contrato);
            $stmt_c->execute();
            $stmt_c->close();

            $conn->commit();

            $usuario_log = $_SESSION['username'] ?? 'Sistema';
            registrarEnBitacora($conn, $usuario_log, "Asignación de Plan", "Nuevo plan ($id_plan) asignado al afiliado: $cedula");

            $_SESSION['flash_msg'] = "Nuevo plan de salud asignado exitosamente.";
            $_SESSION['flash_type'] = "success";

            header("Location: /IPSPUPTM/home.php?vista=afiliados");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_msg'] = $e->getMessage();
            $_SESSION['flash_type'] = "danger";
            header("Location: /IPSPUPTM/home.php?vista=afiliados");
            exit();
        }
    } else {
        echo "Faltan datos requeridos en el formulario.";
    }
} else {
    echo "Método de solicitud no válido.";
}

$conn->close();
?>
