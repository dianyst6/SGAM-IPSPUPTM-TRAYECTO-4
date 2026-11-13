<?php
// 1.3 Guard central: sesión válida + rol permitido
require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
auth_guard([1, 2]);

require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';

header('Content-Type: application/json');

if (isset($_POST['cedula'])) {
    $cedula = htmlspecialchars($_POST['cedula']);

    try {
        $conn->begin_transaction();

        $eliminado_afiliado = false;
        $eliminado_persona = false;

        // 1. Eliminar citas relacionadas al afiliado (la cita guarda id_persona)
        $sql_eliminar_citas = "DELETE FROM citas
                               WHERE id_persona = (SELECT id_persona FROM afiliados WHERE cedula = ?)";
        $stmt_eliminar_citas = $conn->prepare($sql_eliminar_citas);

        if ($stmt_eliminar_citas) {
            $stmt_eliminar_citas->bind_param("s", $cedula);
            if (!$stmt_eliminar_citas->execute()) {
                throw new Exception("Error al eliminar las citas relacionadas: " . $stmt_eliminar_citas->error);
            }
            $stmt_eliminar_citas->close();
        } else {
            throw new Exception("Error al preparar la consulta para eliminar citas: " . $conn->error);
        }

        // 3. Obtener información del afiliado para la bitácora
        $sql_info_afiliado = "SELECT p.nombre, p.apellido
                                FROM afiliados a
                                JOIN persona p ON a.cedula = p.cedula
                                WHERE a.cedula = ?";
        $stmt_info_afiliado = $conn->prepare($sql_info_afiliado);

        if ($stmt_info_afiliado) {
            $stmt_info_afiliado->bind_param("s", $cedula);
            $stmt_info_afiliado->execute();
            $result_info_afiliado = $stmt_info_afiliado->get_result();

            if ($result_info_afiliado->num_rows > 0) {
                $info_afiliado = $result_info_afiliado->fetch_assoc();
            } else {
                throw new Exception("El afiliado no existe o ya fue eliminado.");
            }
            $stmt_info_afiliado->close();
        } else {
            throw new Exception("Error al preparar la consulta para obtener información del afiliado: " . $conn->error);
        }

        // 4. Eliminar de afiliados
        $sql_afiliados = "DELETE FROM afiliados WHERE cedula = ?";
        $stmt_afiliados = $conn->prepare($sql_afiliados);

        if ($stmt_afiliados) {
            $stmt_afiliados->bind_param("s", $cedula);
            $eliminado_afiliado = $stmt_afiliados->execute();
            $stmt_afiliados->close();
        } else {
            throw new Exception("Error al preparar la consulta para eliminar afiliados: " . $conn->error);
        }

        // 5. Verificar si quedan relaciones en afiliados
        $sql_check_afiliados = "SELECT cedula FROM afiliados WHERE cedula = ?";
        $stmt_check = $conn->prepare($sql_check_afiliados);

        if ($stmt_check) {
            $stmt_check->bind_param("s", $cedula);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows === 0) {
                // 6. No hay más relaciones en afiliados, elimina de persona
                $sql_persona = "DELETE FROM persona WHERE cedula = ?";
                $stmt_persona = $conn->prepare($sql_persona);

                if ($stmt_persona) {
                    $stmt_persona->bind_param("s", $cedula);
                    $eliminado_persona = $stmt_persona->execute();
                    $stmt_persona->close();
                } else {
                    throw new Exception("Error al preparar la consulta para eliminar persona: " . $conn->error);
                }
            }
            $stmt_check->close();
        } else {
            throw new Exception("Error al preparar la consulta para verificar afiliados: " . $conn->error);
        }

        // 7. Registrar en la bitácora
        $usuario = $_SESSION['username'] ?? 'Sistema';
        $accion = "Eliminación de Afiliado y Citas";
        $descripcion = "Se eliminó al afiliado: " . $info_afiliado['nombre'] . " " . $info_afiliado['apellido'] . " (Cédula: $cedula) y todas las citas relacionadas.";
        registrarEnBitacora($conn, $usuario, $accion, $descripcion);

        // 8. Confirma la transacción
        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Afiliado y citas relacionadas eliminadas correctamente.']);

    } catch (Exception $e) {
        // 9. Cancela la transacción si algo falla
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Ocurrió un error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Cédula no proporcionada.']);
}

$conn->close();
?>
